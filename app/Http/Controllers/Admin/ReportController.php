<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Company;
use App\Models\Expense;
use App\Models\Item;
use App\Models\Party;
use App\Models\PartyPaymentAllocation;
use App\Models\PurchaseBill;
use App\Models\ProductionBatch;
use App\Models\SalesInvoice;
use App\Models\StockMovement;
use App\Services\EntryVisibilityService;
use App\Services\AgeingSlabService;
use App\Services\PartyOutstandingService;
use App\Services\SerialUnitService;
use App\Services\SalesProfitService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function gst1(Request $request, EntryVisibilityService $visibility)
    {
        $data = $this->gstReport($request, $visibility, 'sales');
        if ($request->query('export') === 'excel') {
            return $this->downloadCsv('gst-1-report.csv', $data['invoiceRows']);
        }
        if ($request->query('export') === 'pdf') {
            return view('admin.reports.print', ['title' => 'GST-1 Report', 'rows' => $data['invoiceRows'], 'totals' => $data['totals'], 'filters' => $data['filters']]);
        }

        return view('admin.reports.gst', $data + ['reportTitle' => 'GST-1 Report', 'reportSubTitle' => 'Sales GST Report', 'routeName' => 'admin.reports.gst1']);
    }

    public function gst2(Request $request, EntryVisibilityService $visibility)
    {
        $data = $this->gstReport($request, $visibility, 'purchase');
        if ($request->query('export') === 'excel') {
            return $this->downloadCsv('gst-2-report.csv', $data['invoiceRows']);
        }
        if ($request->query('export') === 'pdf') {
            return view('admin.reports.print', ['title' => 'GST-2 Report', 'rows' => $data['invoiceRows'], 'totals' => $data['totals'], 'filters' => $data['filters']]);
        }

        return view('admin.reports.gst', $data + ['reportTitle' => 'GST-2 Report', 'reportSubTitle' => 'Purchase GST Report', 'routeName' => 'admin.reports.gst2']);
    }

    public function gst3(Request $request, EntryVisibilityService $visibility)
    {
        $sales = $this->gstReport($request, $visibility, 'sales');
        $purchase = $this->gstReport($request, $visibility, 'purchase');
        $output = (float) $sales['totals']['gst'];
        $input = (float) $purchase['totals']['gst'];

        return view('admin.reports.gst3', [
            'parties' => $sales['parties'],
            'filters' => $sales['filters'],
            'salesTotals' => $sales['totals'],
            'purchaseTotals' => $purchase['totals'],
            'netGst' => $output - $input,
            'salesRows' => $sales['invoiceRows'],
            'purchaseRows' => $purchase['invoiceRows'],
        ]);
    }

    public function partyStatement(Request $request, EntryVisibilityService $visibility, PartyOutstandingService $outstanding)
    {
        $companyScoped = fn(Builder $q, string $model) => $visibility->scopeForUser($q, $model);
        $parties = $companyScoped(Party::orderBy('display_name'), Party::class)->get();
        $partyId = $request->integer('party_id') ?: null;
        $from = $request->date('from_date')?->toDateString() ?? now()->startOfMonth()->toDateString();
        $to = $request->date('to_date')?->toDateString() ?? now()->toDateString();
        $ledgers = $outstanding->statementRows($visibility, $partyId, $to, $from);
        $summary = $outstanding->statementSummary($visibility, $partyId, $to, $from);

        return view('admin.reports.party-statement', compact('parties','partyId','from','to','ledgers','summary'));
    }

    public function partyProfitLoss(Request $request, EntryVisibilityService $visibility)
    {
        return $this->salePurchaseByParty($request, $visibility, 'profit');
    }

    public function allParties(EntryVisibilityService $visibility)
    {
        $parties = $visibility->scopeForUser(Party::with('creator')->orderBy('display_name'), Party::class)->get();
        return view('admin.reports.all-parties', compact('parties'));
    }

    public function partyByItem(Request $request, EntryVisibilityService $visibility)
    {
        $filters = $this->filters($request);
        $parties = $visibility->scopeForUser(Party::orderBy('display_name'), Party::class)->get();
        $items = $visibility->scopeForUser(Item::orderBy('name'), Item::class)->get();
        $sales = $visibility->scopeForUser(SalesInvoice::with(['party','items.item'])->whereBetween('billing_date', [$filters['from'], $filters['to']]), SalesInvoice::class)
            ->when($filters['partyId'], fn($q) => $q->where('party_id', $filters['partyId']))
            ->get()
            ->flatMap(fn($bill) => $bill->items->map(fn($line) => [
                'date' => $bill->billing_date,
                'party' => $bill->party?->display_name,
                'item' => $line->item?->name,
                'type' => 'Sale',
                'qty' => (float) $line->quantity,
                'amount' => (float) $line->line_total,
            ]));

        return view('admin.reports.party-by-item', compact('parties','items','filters','sales'));
    }

    public function salePurchaseByParty(Request $request, EntryVisibilityService $visibility, string $mode = 'normal')
    {
        $filters = $this->filters($request);
        $parties = $visibility->scopeForUser(Party::orderBy('display_name'), Party::class)->get();
        $sales = $visibility->scopeForUser(SalesInvoice::with('party')->whereBetween('billing_date', [$filters['from'], $filters['to']]), SalesInvoice::class)
            ->when($filters['partyId'], fn($q) => $q->where('party_id', $filters['partyId']))
            ->get();
        $purchases = $visibility->scopeForUser(PurchaseBill::with('party')->whereBetween('billing_date', [$filters['from'], $filters['to']]), PurchaseBill::class)
            ->when($filters['partyId'], fn($q) => $q->where('party_id', $filters['partyId']))
            ->get();

        $rows = $parties->map(function (Party $party) use ($sales, $purchases) {
            $sale = (float) $sales->where('party_id', $party->id)->sum('grand_total');
            $purchase = (float) $purchases->where('party_id', $party->id)->sum('grand_total');
            return ['party' => $party, 'sale' => $sale, 'purchase' => $purchase, 'net' => $sale - $purchase];
        })->filter(fn($row) => $row['sale'] || $row['purchase'])->values();

        return view('admin.reports.sale-purchase-by-party', compact('parties','filters','rows','mode'));
    }

    public function salesReport(Request $request, EntryVisibilityService $visibility)
    {
        return $this->billReport($request, $visibility, SalesInvoice::class, 'sales', 'Sales Report');
    }

    public function purchaseReport(Request $request, EntryVisibilityService $visibility)
    {
        return $this->billReport($request, $visibility, PurchaseBill::class, 'purchases', 'Purchase Report');
    }

    public function dayBook(Request $request, EntryVisibilityService $visibility)
    {
        $filters = $this->filters($request);
        $sales = $visibility->scopeForUser(SalesInvoice::whereBetween('billing_date', [$filters['from'], $filters['to']]), SalesInvoice::class)->get();
        $purchases = $visibility->scopeForUser(PurchaseBill::whereBetween('billing_date', [$filters['from'], $filters['to']]), PurchaseBill::class)->get();
        $bank = $visibility->scopeForUser(BankTransaction::with('bankAccount')->whereBetween('transaction_date', [$filters['from'], $filters['to']]), BankTransaction::class)->get();
        return view('admin.reports.day-book', compact('filters','sales','purchases','bank'));
    }

    public function allTransactions(Request $request, EntryVisibilityService $visibility)
    {
        return $this->dayBook($request, $visibility);
    }

    public function profitLoss(Request $request, EntryVisibilityService $visibility, SalesProfitService $profits)
    {
        $filters = $this->filters($request);
        $salesBills = $visibility->scopeForUser(SalesInvoice::with(['items.item'])->whereBetween('billing_date', [$filters['from'], $filters['to']]), SalesInvoice::class)->get();
        $sales = (float) $salesBills->sum('grand_total');
        $salesCost = (float) $salesBills->sum(fn(SalesInvoice $bill) => $profits->invoiceCost($bill));
        $grossProfit = $sales - $salesCost;
        $purchases = (float) $visibility->scopeForUser(PurchaseBill::whereBetween('billing_date', [$filters['from'], $filters['to']]), PurchaseBill::class)->sum('grand_total');
        $expenses = (float) $visibility->scopeForUser(Expense::whereBetween('expense_date', [$filters['from'], $filters['to']])->where('status', 'approved'), Expense::class)->sum('total_amount');
        return view('admin.reports.profit-loss', compact('filters','sales','salesCost','grossProfit','purchases','expenses'));
    }

    public function billWiseProfit(Request $request, EntryVisibilityService $visibility, SalesProfitService $profits)
    {
        $filters = $this->filters($request);
        $parties = $visibility->scopeForUser(Party::orderBy('display_name'), Party::class)->get();
        $bills = $visibility->scopeForUser(SalesInvoice::with(['party','items.item'])->whereBetween('billing_date', [$filters['from'], $filters['to']]), SalesInvoice::class)
            ->when($filters['partyId'], fn($q) => $q->where('party_id', $filters['partyId']))
            ->latest('billing_date')
            ->get()
            ->map(function (SalesInvoice $bill) use ($profits) {
                $cost = $profits->invoiceCost($bill);
                $sale = $profits->invoiceSale($bill);
                return [
                    'bill' => $bill,
                    'cost' => $cost,
                    'sale' => $sale,
                    'profit' => $sale - $cost,
                    'profit_percent' => $profits->profitPercentage($sale - $cost, $cost),
                    'detail' => $profits->invoiceDetail($bill),
                ];
            });

        return view('admin.reports.bill-wise-profit', compact('filters','parties','bills'));
    }

    public function ageing(Request $request, EntryVisibilityService $visibility, AgeingSlabService $ageingSlabs, PartyOutstandingService $outstanding)
    {
        $companyParties = $visibility->scopeForUser(Party::orderBy('display_name'), Party::class)->get();
        $partyId = $request->integer('party_id') ?: null;
        $kind = $request->input('kind', 'both');
        abort_unless(in_array($kind, ['receivable', 'payable', 'both'], true), 422, 'Invalid ageing type.');
        $to = $request->date('to_date')?->toDateString() ?? now()->toDateString();
        $billRows = $outstanding->billRows($visibility, $partyId, $to, $kind);
        $rows = $ageingSlabs->matrix($billRows);
        $slabs = AgeingSlabService::SLABS;

        return view('admin.reports.ageing', compact('rows', 'billRows', 'partyId', 'kind', 'to', 'slabs') + ['parties' => $companyParties]);
    }

    public function ageingBillPrint(string $kind, string $bill, EntryVisibilityService $visibility, PartyOutstandingService $outstanding)
    {
        $modelClass = $kind === 'payable' ? PurchaseBill::class : SalesInvoice::class;
        $record = $visibility->scopeForUser(
            $modelClass::query()->with(['party','items.item','company']),
            $modelClass
        )->where(function ($query) use ($bill) {
            $query->where('id', $bill)->orWhere('invoice_no', $bill);
        })->firstOrFail();

        $payments = PartyPaymentAllocation::with('payment.bankAccount')
            ->where('bill_model', $modelClass)
            ->where('bill_id', $record->id)
            ->orderBy('created_at')
            ->get();
        $billPayload = $outstanding->billPayload($visibility, $modelClass, $record->id);
        $company = $record->company ?? Company::find(auth()->user()->current_company_id);
        $balance = (float) ($billPayload['due'] ?? 0);
        $status = $balance > 0 ? 'Outstanding' : 'Settled';

        return view('admin.reports.ageing-bill-pdf', [
            'kind' => $kind,
            'bill' => $record,
            'company' => $company,
            'party' => $record->party,
            'payments' => $payments,
            'totals' => [
                'grand_total' => (float) $record->grand_total,
                'returned' => (float) ($billPayload['returned'] ?? 0),
                'effective_total' => (float) ($billPayload['effective_total'] ?? $record->grand_total),
                'paid' => (float) ($billPayload['paid'] ?? $payments->sum('amount')),
                'balance' => $balance,
            ],
            'status' => $status,
        ]);
    }

    public function ageingBillDiagnosis(string $kind, string $bill, EntryVisibilityService $visibility, PartyOutstandingService $outstanding)
    {
        $modelClass = $kind === 'payable' ? PurchaseBill::class : SalesInvoice::class;
        $record = $visibility->scopeForUser(
            $modelClass::query()->with(['party','items.item','company']),
            $modelClass
        )->where(function ($query) use ($bill) {
            $query->where('id', $bill)->orWhere('invoice_no', $bill);
        })->firstOrFail();

        $payments = PartyPaymentAllocation::with('payment.bankAccount')
            ->where('bill_model', $modelClass)
            ->where('bill_id', $record->id)
            ->orderBy('created_at')
            ->get();
        $company = $record->company ?? Company::find(auth()->user()->current_company_id);
        $asOf = now()->endOfDay();
        $days = $record->billing_date ? (int) floor($record->billing_date->startOfDay()->diffInDays($asOf)) : 0;
        $billPayload = $outstanding->billPayload($visibility, $modelClass, $record->id);
        $balance = (float) ($billPayload['due'] ?? 0);

        return view('admin.reports.ageing-bill-diagnosis', [
            'kind' => $kind,
            'bill' => $record,
            'company' => $company,
            'party' => $record->party,
            'payments' => $payments,
            'days' => $days,
            'balance' => $balance,
            'summary' => [
                'total' => (float) $record->grand_total,
                'returned' => (float) ($billPayload['returned'] ?? 0),
                'effective_total' => (float) ($billPayload['effective_total'] ?? $record->grand_total),
                'paid' => (float) ($billPayload['paid'] ?? $payments->sum('amount')),
                'pending' => $balance,
                'payment_count' => $payments->count(),
            ],
        ]);
    }

    public function ageingPartyPrint(string $party, Request $request, EntryVisibilityService $visibility, AgeingSlabService $ageingSlabs, SalesProfitService $profits, PartyOutstandingService $outstanding)
    {
        return view('admin.reports.ageing-party-pdf', $this->ageingPartyPayload($party, $request, $visibility, $ageingSlabs, $profits, $outstanding));
    }

    public function ageingPartyDiagnosis(string $party, Request $request, EntryVisibilityService $visibility, AgeingSlabService $ageingSlabs, SalesProfitService $profits, PartyOutstandingService $outstanding)
    {
        return view('admin.reports.ageing-party-diagnosis', $this->ageingPartyPayload($party, $request, $visibility, $ageingSlabs, $profits, $outstanding));
    }

    public function balanceSheet(Request $request, EntryVisibilityService $visibility)
    {
        $filters = $this->filters($request);
        $parties = $visibility->scopeForUser(Party::query(), Party::class)->get();
        $banks = $visibility->scopeForUser(\App\Models\BankAccount::query(), \App\Models\BankAccount::class)->get();
        return view('admin.reports.balance-sheet', compact('filters','parties','banks'));
    }

    public function itemTrace(Request $request, EntryVisibilityService $visibility, SerialUnitService $serialUnits)
    {
        $type = $request->input('type', 'serial');
        $term = trim((string) $request->input('q', ''));
        $result = null;

        if ($term !== '') {
            if ($type === 'invoice') {
                $result = [
                    'sales' => $visibility->scopeForUser(
                        SalesInvoice::with(['party','items.item'])->where('invoice_no', $term),
                        SalesInvoice::class
                    )->first(),
                ];
            } elseif ($type === 'production') {
                $result = [
                    'production' => $visibility->scopeForUser(
                        ProductionBatch::with('finishedItem.bomMaterials.rawItem')->where('batch_no', $term),
                        ProductionBatch::class
                    )->first(),
                ];
            } else {
                $result = $this->serialTrace($term, $visibility, $serialUnits);
            }
        }

        return view('admin.reports.item-trace', compact('type', 'term', 'result'));
    }

    private function serialTrace(string $term, EntryVisibilityService $visibility, SerialUnitService $serialUnits): array
    {
        $needle = mb_strtolower($term);
        $matchesTerm = function (array $unit) use ($needle): bool {
            return collect(['key', 'serial_no', 'vts_sim', 'sku', 'batch_no', 'production_batch_no', 'buyer_code'])
                ->contains(fn($field) => isset($unit[$field]) && mb_strtolower((string) $unit[$field]) === $needle);
        };

        $productionMatches = $visibility->scopeForUser(
            ProductionBatch::with('finishedItem.bomMaterials.rawItem'),
            ProductionBatch::class
        )->get()->flatMap(function (ProductionBatch $batch) use ($matchesTerm) {
            return collect($batch->units_data ?? [])
                ->filter(fn($unit) => is_array($unit) && $matchesTerm($unit))
                ->map(fn($unit, $index) => ['batch' => $batch, 'unit' => $unit, 'key' => $batch->id . '-' . $index]);
        })->values();

        $movements = $visibility->scopeForUser(
            StockMovement::with(['item', 'party', 'creator'])->orderBy('movement_date')->orderBy('id'),
            StockMovement::class
        )->get()->map(function (StockMovement $movement) use ($serialUnits) {
            $movement->setRelation('trace_units', collect($serialUnits->movementUnits($movement)));
            return $movement;
        })->filter(function (StockMovement $movement) use ($matchesTerm) {
            return $movement->trace_units->contains(fn($unit) => is_array($unit) && $matchesTerm($unit));
        })->values();

        $companies = Company::whereIn('id', $movements->pluck('company_id')->merge($productionMatches->pluck('batch.company_id'))->filter()->unique())
            ->pluck('name', 'id');

        $timeline = collect();
        foreach ($productionMatches as $match) {
            $timeline->push([
                'date' => $match['batch']->production_date,
                'company' => $companies[$match['batch']->company_id] ?? '-',
                'item' => $match['batch']->finishedItem?->name,
                'type' => 'CRM / Production',
                'direction' => 'in',
                'qty' => 1,
                'party' => '-',
                'reference' => $match['batch']->batch_no,
                'description' => 'Serial generated in CRM production.',
                'unit' => array_merge($match['unit'], ['key' => $match['key']]),
            ]);
        }

        foreach ($movements as $movement) {
            foreach ($movement->trace_units->filter(fn($unit) => is_array($unit) && $matchesTerm($unit)) as $unit) {
                $timeline->push([
                    'date' => $movement->movement_date,
                    'company' => $companies[$movement->company_id] ?? '-',
                    'company_id' => $movement->company_id,
                    'item' => $movement->item?->name,
                    'item_id' => $movement->item_id,
                    'type' => str_replace('_', ' ', $movement->movement_type),
                    'direction' => $movement->direction,
                    'qty' => 1,
                    'party' => $movement->party?->display_name ?: '-',
                    'reference' => $movement->reference_no,
                    'description' => $movement->description,
                    'unit' => $unit,
                ]);
            }
        }

        $locations = $timeline
            ->whereNotNull('company_id')
            ->groupBy(fn($row) => $row['company_id'] . ':' . $row['item_id'])
            ->map(function ($rows) {
                $net = $rows->sum(fn($row) => $row['direction'] === 'in' ? 1 : -1);
                $last = $rows->sortBy([['date', 'asc']])->last();

                return [
                    'company' => $last['company'] ?? '-',
                    'item' => $last['item'] ?? '-',
                    'net' => $net,
                    'last_type' => $last['type'] ?? '-',
                    'last_date' => $last['date'] ?? null,
                    'last_party' => $last['party'] ?? '-',
                    'reference' => $last['reference'] ?? '-',
                ];
            })
            ->filter(fn($row) => $row['net'] > 0)
            ->values();

        $lastEvent = $timeline->sortBy([['date', 'asc']])->last();

        return [
            'unitMatches' => $productionMatches,
            'timeline' => $timeline->sortBy([['date', 'asc']])->values(),
            'locations' => $locations,
            'lastEvent' => $lastEvent,
        ];
    }

    private function gstReport(Request $request, EntryVisibilityService $visibility, string $type): array
    {
        $filters = $this->filters($request);
        $model = $type === 'sales' ? SalesInvoice::class : PurchaseBill::class;
        $dateColumn = 'billing_date';
        $parties = $visibility->scopeForUser(Party::orderBy('display_name'), Party::class)->get();
        $bills = $visibility->scopeForUser($model::with(['party','items.item'])->whereBetween($dateColumn, [$filters['from'], $filters['to']]), $model)
            ->when($filters['partyId'], fn($q) => $q->where('party_id', $filters['partyId']))
            ->get();

        $gstBills = $bills->filter(fn($bill) => (float) $bill->tax_amount > 0);
        $withoutGst = $bills->filter(fn($bill) => (float) $bill->tax_amount <= 0);
        $summary = $gstBills->groupBy('party_id')->map(function (Collection $rows) {
            $party = $rows->first()->party;
            return [
                'party' => $party?->display_name ?: 'Cash / Walk-in',
                'gstin' => $party?->gstin ?: '-',
                'state' => $party?->state ?: '-',
                'taxable' => (float) $rows->sum(fn($bill) => max(0, (float) $bill->grand_total - (float) $bill->tax_amount)),
                'gst' => (float) $rows->sum('tax_amount'),
                'total' => (float) $rows->sum('grand_total'),
            ];
        })->values();

        $invoiceRows = $gstBills->map(fn($bill) => [
            'date' => $bill->billing_date?->format('d-m-Y'),
            'invoice' => $bill->invoice_no,
            'party' => $bill->party?->display_name ?: 'Cash / Walk-in',
            'gstin' => $bill->party?->gstin ?: '-',
            'taxable' => max(0, (float) $bill->grand_total - (float) $bill->tax_amount),
            'gst' => (float) $bill->tax_amount,
            'total' => (float) $bill->grand_total,
        ])->values();

        $totals = [
            'taxable' => (float) $invoiceRows->sum('taxable'),
            'gst' => (float) $invoiceRows->sum('gst'),
            'total' => (float) $invoiceRows->sum('total'),
        ];

        return compact('filters','parties','summary','invoiceRows','withoutGst','totals','type');
    }

    private function billReport(Request $request, EntryVisibilityService $visibility, string $model, string $viewKey, string $title)
    {
        $filters = $this->filters($request);
        $parties = $visibility->scopeForUser(Party::orderBy('display_name'), Party::class)->get();
        $bills = $visibility->scopeForUser($model::with('party')->whereBetween('billing_date', [$filters['from'], $filters['to']]), $model)
            ->when($filters['partyId'], fn($q) => $q->where('party_id', $filters['partyId']))
            ->latest('billing_date')
            ->get();
        return view('admin.reports.bill-report', compact('filters','parties','bills','title','viewKey'));
    }

    private function filters(Request $request): array
    {
        if ($request->filled('period') && $request->input('period') !== 'month') {
            [$from, $to] = $this->periodRange($request->input('period'));
            return [
                'month' => now()->format('Y-m'),
                'period' => $request->input('period'),
                'from' => $request->input('from_date', $from),
                'to' => $request->input('to_date', $to),
                'partyId' => $request->integer('party_id') ?: null,
                'withoutGst' => $request->boolean('without_gst'),
            ];
        }

        $month = $request->input('month', now()->format('Y-m'));
        $date = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        return [
            'month' => $month,
            'period' => 'month',
            'from' => $request->input('from_date', $date->toDateString()),
            'to' => $request->input('to_date', $date->copy()->endOfMonth()->toDateString()),
            'partyId' => $request->integer('party_id') ?: null,
            'withoutGst' => $request->boolean('without_gst'),
        ];
    }

    private function periodRange(string $period): array
    {
        $today = now();

        return match ($period) {
            'today' => [$today->toDateString(), $today->toDateString()],
            'yesterday' => [$today->copy()->subDay()->toDateString(), $today->copy()->subDay()->toDateString()],
            'week' => [$today->copy()->startOfWeek()->toDateString(), $today->copy()->endOfWeek()->toDateString()],
            'three_months' => [$today->copy()->subMonths(3)->startOfDay()->toDateString(), $today->toDateString()],
            'six_months' => [$today->copy()->subMonths(6)->startOfDay()->toDateString(), $today->toDateString()],
            'nine_months' => [$today->copy()->subMonths(9)->startOfDay()->toDateString(), $today->toDateString()],
            'year' => [$today->copy()->subYear()->startOfDay()->toDateString(), $today->toDateString()],
            'all' => ['1970-01-01', $today->toDateString()],
            default => [$today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()],
        };
    }

    private function ageingPartyPayload(string $party, Request $request, EntryVisibilityService $visibility, AgeingSlabService $ageingSlabs, SalesProfitService $profits, PartyOutstandingService $outstanding): array
    {
        $kind = $request->input('kind', 'both');
        abort_unless(in_array($kind, ['receivable', 'payable', 'both'], true), 422, 'Invalid ageing type.');

        $to = $request->date('to_date')?->toDateString() ?? now()->toDateString();
        $asOf = Carbon::parse($to)->endOfDay();
        $partyId = $party === 'cash' ? null : (int) $party;

        $bills = $outstanding->billRows($visibility, $partyId, $to, $kind)
            ->reject(fn(array $row) => $row['model'] === Party::class)
            ->map(function (array $row) use ($visibility, $profits, $asOf) {
                $modelClass = $row['model'];
                $record = $visibility->scopeForUser(
                    $modelClass::query()->with($modelClass === SalesInvoice::class
                        ? ['party','items.item.bomMaterials.rawItem','company']
                        : ['party','items.item','company']),
                    $modelClass
                )->find($row['bill_id']);

                if (!$record) {
                    return null;
                }

                return $this->ageingBillPayload($record, $modelClass, $row['kind'], $asOf, $profits, $row);
            })
            ->filter()
            ->sortByDesc('date')
            ->values();

        abort_if($bills->isEmpty(), 404, 'No ageing bills found for this party.');

        $partyModel = $partyId ? Party::find($partyId) : null;
        $company = $bills->first()['record']->company ?? Company::find(auth()->user()->current_company_id);
        $slabRows = $ageingSlabs->matrix($bills->map(fn($row) => [
            'kind' => $row['kind'],
            'party_id' => $partyId,
            'party' => $partyModel?->display_name ?: 'Cash / Walk-in',
            'age' => $row['age'],
            'due' => $row['due'],
            'bill_id' => $row['record']->id,
        ]));

        return [
            'kind' => $kind,
            'to' => $to,
            'asOf' => $asOf,
            'partyKey' => $party,
            'party' => $partyModel,
            'company' => $company,
            'bankAccount' => BankAccount::where('company_id', auth()->user()->current_company_id)->first(),
            'bills' => $bills,
            'slabs' => AgeingSlabService::SLABS,
            'slabRow' => $slabRows->first(),
            'slabBills' => collect(AgeingSlabService::SLABS)->mapWithKeys(function ($label, $key) use ($bills, $ageingSlabs) {
                $rows = $bills->filter(fn($bill) => $ageingSlabs->slabKey((int) $bill['age']) === $key)->values();

                return [$key => [
                    'label' => $label,
                    'bills' => $rows,
                    'count' => $rows->count(),
                    'due' => (float) $rows->sum('due'),
                    'receivable' => (float) $rows->where('kind', 'receivable')->sum('due'),
                    'payable' => (float) $rows->where('kind', 'payable')->sum('due'),
                ]];
            }),
            'totals' => [
                'receivable' => (float) $bills->where('kind', 'receivable')->sum('due'),
                'payable' => (float) $bills->where('kind', 'payable')->sum('due'),
                'subtotal' => (float) $bills->sum('subtotal'),
                'discount' => (float) $bills->sum('discount'),
                'tax' => (float) $bills->sum('tax'),
                'grand_total' => (float) $bills->sum('total'),
                'returned' => (float) $bills->sum('returned'),
                'effective_total' => (float) $bills->sum('effective_total'),
                'paid' => (float) $bills->sum('paid'),
                'due' => (float) $bills->sum('due'),
                'sale' => (float) $bills->where('kind', 'receivable')->sum('total'),
                'cost' => (float) $bills->sum('cost'),
                'profit' => (float) $bills->sum('profit'),
                'payment_count' => (int) $bills->sum(fn($row) => $row['payments']->count()),
            ],
        ];
    }

    private function ageingBillPayload($bill, string $modelClass, string $kind, Carbon $asOf, SalesProfitService $profits, ?array $outstandingRow = null): array
    {
        $payments = PartyPaymentAllocation::with('payment.bankAccount')
            ->where('bill_model', $modelClass)
            ->where('bill_id', $bill->id)
            ->orderBy('created_at')
            ->get();
        $paid = (float) ($outstandingRow['paid'] ?? $payments->sum('amount'));
        $returned = (float) ($outstandingRow['returned'] ?? 0);
        $effectiveTotal = (float) ($outstandingRow['effective_total'] ?? max(0, (float) $bill->grand_total - $returned));
        $cost = $modelClass === SalesInvoice::class ? $profits->invoiceCost($bill) : (float) $bill->grand_total;
        $profit = $modelClass === SalesInvoice::class ? ($effectiveTotal - $cost) : 0.0;

        return [
            'kind' => $kind,
            'record' => $bill,
            'date' => $bill->billing_date,
            'age' => $bill->billing_date ? (int) floor($bill->billing_date->startOfDay()->diffInDays($asOf)) : 0,
            'subtotal' => (float) $bill->subtotal,
            'discount' => (float) $bill->discount_amount,
            'tax' => (float) $bill->tax_amount,
            'total' => (float) $bill->grand_total,
            'returned' => $returned,
            'effective_total' => $effectiveTotal,
            'paid' => $paid,
            'due' => max(0, $effectiveTotal - $paid),
            'payments' => $payments,
            'cost' => $cost,
            'profit' => $profit,
            'profit_percent' => $modelClass === SalesInvoice::class ? $profits->profitPercentage($profit, $cost) : 0.0,
            'items' => $bill->items->map(function ($line) use ($modelClass, $profits) {
                $lineCost = $modelClass === SalesInvoice::class ? $profits->lineCost($line) : (float) $line->line_total;
                $lineProfit = $modelClass === SalesInvoice::class ? ((float) $line->line_total - $lineCost) : 0.0;

                return [
                    'name' => $line->item?->name ?: 'Item',
                    'description' => $line->description ?: '-',
                    'hsn' => $line->item?->hsn_code ?: '-',
                    'qty' => (float) $line->quantity,
                    'unit' => $line->unit ?: $line->item?->unit,
                    'rate' => (float) $line->unit_price,
                    'discount' => (float) ($line->discount_amount ?? 0),
                    'tax_percent' => (float) ($line->tax_percent ?? 0),
                    'tax' => (float) ($line->tax_amount ?? 0),
                    'amount' => (float) $line->line_total,
                    'cost' => $lineCost,
                    'profit' => $lineProfit,
                    'profit_percent' => $modelClass === SalesInvoice::class ? $profits->profitPercentage($lineProfit, $lineCost) : 0.0,
                    'units' => collect($line->selected_units ?? [])->filter(fn($unit) => is_array($unit))->values(),
                ];
            })->values(),
        ];
    }

    private function downloadCsv(string $filename, Collection $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Invoice', 'Party', 'GSTIN', 'Taxable Amount', 'GST Amount', 'Total']);
            foreach ($rows as $row) {
                fputcsv($handle, [$row['date'], $row['invoice'], $row['party'], $row['gstin'], $row['taxable'], $row['gst'], $row['total']]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
