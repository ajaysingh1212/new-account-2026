<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankTransaction;
use App\Models\Expense;
use App\Models\Item;
use App\Models\Party;
use App\Models\PartyLedger;
use App\Models\PartyPaymentAllocation;
use App\Models\PurchaseBill;
use App\Models\ProductionBatch;
use App\Models\SalesInvoice;
use App\Services\EntryVisibilityService;
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

    public function partyStatement(Request $request, EntryVisibilityService $visibility)
    {
        $companyScoped = fn(Builder $q, string $model) => $visibility->scopeForUser($q, $model);
        $parties = $companyScoped(Party::orderBy('display_name'), Party::class)->get();
        $partyId = $request->integer('party_id') ?: null;
        $from = $request->date('from_date')?->toDateString() ?? now()->startOfMonth()->toDateString();
        $to = $request->date('to_date')?->toDateString() ?? now()->toDateString();
        $ledgers = PartyLedger::with('party')
            ->whereBetween('entry_date', [$from, $to])
            ->when($partyId, fn($q) => $q->where('party_id', $partyId))
            ->whereIn('party_id', $parties->pluck('id'))
            ->latest('entry_date')
            ->latest()
            ->get();

        return view('admin.reports.party-statement', compact('parties','partyId','from','to','ledgers'));
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

    public function profitLoss(Request $request, EntryVisibilityService $visibility)
    {
        $filters = $this->filters($request);
        $sales = (float) $visibility->scopeForUser(SalesInvoice::whereBetween('billing_date', [$filters['from'], $filters['to']]), SalesInvoice::class)->sum('grand_total');
        $purchases = (float) $visibility->scopeForUser(PurchaseBill::whereBetween('billing_date', [$filters['from'], $filters['to']]), PurchaseBill::class)->sum('grand_total');
        $expenses = (float) $visibility->scopeForUser(Expense::whereBetween('expense_date', [$filters['from'], $filters['to']])->where('status', 'approved'), Expense::class)->sum('total_amount');
        return view('admin.reports.profit-loss', compact('filters','sales','purchases','expenses'));
    }

    public function billWiseProfit(Request $request, EntryVisibilityService $visibility)
    {
        $filters = $this->filters($request);
        $parties = $visibility->scopeForUser(Party::orderBy('display_name'), Party::class)->get();
        $bills = $visibility->scopeForUser(SalesInvoice::with(['party','items.item'])->whereBetween('billing_date', [$filters['from'], $filters['to']]), SalesInvoice::class)
            ->when($filters['partyId'], fn($q) => $q->where('party_id', $filters['partyId']))
            ->latest('billing_date')
            ->get()
            ->map(function (SalesInvoice $bill) {
                $cost = $bill->items->sum(function ($line) {
                    $unitCost = collect($line->selected_units ?? [])->avg('cost_per_unit');
                    $unitCost = $unitCost ?: (float) ($line->item?->purchase_price ?? 0);
                    return $unitCost * (float) $line->quantity;
                });
                $sale = (float) $bill->grand_total;
                return [
                    'bill' => $bill,
                    'cost' => $cost,
                    'sale' => $sale,
                    'profit' => $sale - $cost,
                    'detail' => $this->salesInvoiceDetail($bill, $cost, $sale),
                ];
            });

        return view('admin.reports.bill-wise-profit', compact('filters','parties','bills'));
    }

    public function ageing(Request $request, EntryVisibilityService $visibility)
    {
        $companyParties = $visibility->scopeForUser(Party::orderBy('display_name'), Party::class)->get();
        $partyId = $request->integer('party_id') ?: null;
        $bucket = $request->input('bucket', '30');
        $from = $request->input('from_date');
        $to = $request->input('to_date', now()->toDateString());
        $days = $bucket === 'custom' ? null : (int) $bucket;
        $startDate = $days ? now()->subDays($days)->toDateString() : ($from ?: now()->subDays(30)->toDateString());

        $sales = $visibility->scopeForUser(SalesInvoice::with('party')->where('sale_type', 'credit'), SalesInvoice::class)
            ->whereBetween('billing_date', [$startDate, $to])
            ->when($partyId, fn($q) => $q->where('party_id', $partyId))
            ->get()
            ->map(fn($bill) => $this->ageingRow($bill, SalesInvoice::class, 'receivable'));
        $purchases = $visibility->scopeForUser(PurchaseBill::with('party')->where('purchase_type', 'credit'), PurchaseBill::class)
            ->whereBetween('billing_date', [$startDate, $to])
            ->when($partyId, fn($q) => $q->where('party_id', $partyId))
            ->get()
            ->map(fn($bill) => $this->ageingRow($bill, PurchaseBill::class, 'payable'));
        $rows = $sales->merge($purchases)->filter(fn($row) => $row['due'] > 0)->sortByDesc('date')->values();

        return view('admin.reports.ageing', ['parties' => $companyParties, 'rows' => $rows, 'partyId' => $partyId, 'bucket' => $bucket, 'from' => $startDate, 'to' => $to]);
    }

    public function balanceSheet(Request $request, EntryVisibilityService $visibility)
    {
        $filters = $this->filters($request);
        $parties = $visibility->scopeForUser(Party::query(), Party::class)->get();
        $banks = $visibility->scopeForUser(\App\Models\BankAccount::query(), \App\Models\BankAccount::class)->get();
        return view('admin.reports.balance-sheet', compact('filters','parties','banks'));
    }

    public function itemTrace(Request $request, EntryVisibilityService $visibility)
    {
        $type = $request->input('type', 'invoice');
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
                $batches = $visibility->scopeForUser(
                    ProductionBatch::with('finishedItem.bomMaterials.rawItem'),
                    ProductionBatch::class
                )->get();
                $matches = $batches->flatMap(function (ProductionBatch $batch) use ($term) {
                    return collect($batch->units_data ?? [])
                        ->filter(fn($unit) => in_array($term, [$unit['buyer_code'] ?? null, $unit['serial_no'] ?? null, $unit['batch_no'] ?? null], true))
                        ->map(fn($unit, $index) => ['batch' => $batch, 'unit' => $unit, 'key' => $batch->id.'-'.$index]);
                })->values();

                $sales = SalesInvoice::with(['party','items.item'])
                    ->whereHas('items', fn($q) => $q->where('selected_units', 'like', '%' . $term . '%'))
                    ->where('company_id', auth()->user()->current_company_id)
                    ->get();

                $result = ['unitMatches' => $matches, 'sales' => $sales];
            }
        }

        return view('admin.reports.item-trace', compact('type', 'term', 'result'));
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
        if ($request->filled('period')) {
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

    private function salesInvoiceDetail(SalesInvoice $bill, float $cost, float $sale): array
    {
        return [
            'invoice' => $bill->invoice_no,
            'date' => $bill->billing_date?->format('d M Y'),
            'sale_type' => ucfirst((string) $bill->sale_type),
            'reference' => $bill->reference_no ?: '-',
            'phone' => $bill->phone ?: ($bill->party?->phone ?: '-'),
            'billing_address' => $bill->billing_address ?: ($bill->party?->billing_address ?: '-'),
            'shipping_address' => $bill->shipping_address ?: ($bill->party?->shipping_address ?: '-'),
            'party' => [
                'name' => $bill->party?->display_name ?: 'Cash / Walk-in',
                'legal_name' => $bill->party?->legal_name ?: '-',
                'phone' => $bill->party?->phone ?: '-',
                'email' => $bill->party?->email ?: '-',
                'gstin' => $bill->party?->gstin ?: '-',
                'city' => trim(collect([$bill->party?->city, $bill->party?->state, $bill->party?->pincode])->filter()->implode(', ')) ?: '-',
            ],
            'amounts' => [
                'subtotal' => (float) $bill->subtotal,
                'discount' => (float) $bill->discount_amount,
                'tax' => (float) $bill->tax_amount,
                'total' => (float) $bill->grand_total,
                'cost' => $cost,
                'profit' => $sale - $cost,
            ],
            'items' => $bill->items->map(function ($line) {
                $unitCost = collect($line->selected_units ?? [])->avg('cost_per_unit');
                $unitCost = $unitCost ?: (float) ($line->item?->purchase_price ?? 0);
                return [
                    'name' => $line->item?->name ?: 'Item',
                    'description' => $line->description ?: '-',
                    'hsn' => $line->item?->hsn_code ?: '-',
                    'qty' => (float) $line->quantity,
                    'unit' => $line->unit ?: $line->item?->unit,
                    'rate' => (float) $line->unit_price,
                    'tax' => (float) $line->tax_amount,
                    'amount' => (float) $line->line_total,
                    'cost' => $unitCost * (float) $line->quantity,
                ];
            })->values(),
        ];
    }

    private function ageingRow($bill, string $model, string $kind): array
    {
        $paid = (float) PartyPaymentAllocation::where('bill_model', $model)->where('bill_id', $bill->id)->sum('amount');
        return [
            'kind' => $kind,
            'party' => $bill->party?->display_name ?: 'Cash / Walk-in',
            'invoice' => $bill->invoice_no,
            'date' => $bill->billing_date,
            'age' => $bill->billing_date ? $bill->billing_date->diffInDays(now()) : 0,
            'total' => (float) $bill->grand_total,
            'paid' => $paid,
            'due' => max(0, (float) $bill->grand_total - $paid),
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
