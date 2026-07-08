<?php

namespace App\Services;

use App\Models\Party;
use App\Models\PartyLedger;
use App\Models\PartyPaymentAllocation;
use App\Models\PurchaseBill;
use App\Models\PurchaseReturn;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PartyOutstandingService
{
    public function billRows(EntryVisibilityService $visibility, ?int $partyId = null, ?string $to = null, string $kind = 'both'): Collection
    {
        $toDate = $to ?: now()->toDateString();
        $asOf = Carbon::parse($toDate)->endOfDay();
        $rows = collect();

        if ($kind === 'both' || $kind === 'receivable') {
            $sales = $visibility->scopeForUser(SalesInvoice::with('party')->where('sale_type', 'credit'), SalesInvoice::class)
                ->whereDate('billing_date', '<=', $toDate)
                ->when($partyId, fn($query) => $query->where('party_id', $partyId))
                ->get();

            $rows = $rows->merge($this->rowsForBills($sales, SalesInvoice::class, 'receivable', $asOf, $toDate));
        }

        if ($kind === 'both' || $kind === 'payable') {
            $purchases = $visibility->scopeForUser(PurchaseBill::with('party')->where('purchase_type', 'credit'), PurchaseBill::class)
                ->whereDate('billing_date', '<=', $toDate)
                ->when($partyId, fn($query) => $query->where('party_id', $partyId))
                ->get();

            $rows = $rows->merge($this->rowsForBills($purchases, PurchaseBill::class, 'payable', $asOf, $toDate));
        }

        return $this->openingRows($visibility, $partyId, $toDate, $kind, $asOf)
            ->merge($rows)
            ->filter(fn(array $row) => $row['due'] > 0)
            ->sortByDesc('date')
            ->values();
    }

    public function balancesByParty(EntryVisibilityService $visibility, ?string $to = null): Collection
    {
        return $this->billRows($visibility, null, $to)
            ->groupBy('party_id')
            ->map(fn(Collection $rows) => [
                'receivable' => (float) $rows->where('kind', 'receivable')->sum('due'),
                'payable' => (float) $rows->where('kind', 'payable')->sum('due'),
                'net' => (float) $rows->where('kind', 'payable')->sum('due') - (float) $rows->where('kind', 'receivable')->sum('due'),
                'bill_count' => $rows->count(),
            ]);
    }

    public function balanceForParty(EntryVisibilityService $visibility, int $partyId, ?string $to = null): array
    {
        return $this->balancesByParty($visibility, $to)->get($partyId, [
            'receivable' => 0.0,
            'payable' => 0.0,
            'net' => 0.0,
            'bill_count' => 0,
        ]);
    }

    public function statementRows(EntryVisibilityService $visibility, ?int $partyId = null, ?string $to = null, ?string $from = null): Collection
    {
        $toDate = $to ?: now()->toDateString();
        $ledgers = PartyLedger::with('party')
            ->where('company_id', auth()->user()->current_company_id)
            ->whereDate('entry_date', '<=', $toDate)
            ->when($partyId, fn($query) => $query->where('party_id', $partyId))
            ->whereIn('entry_type', [
                'opening_balance',
                'sale',
                'purchase',
                'sales_return',
                'purchase_return',
                'payment_in',
                'payment_out',
            ])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get()
            ->groupBy(fn(PartyLedger $ledger) => implode('|', [
                $ledger->party_id,
                $ledger->entry_type,
                $ledger->reference_type,
                $ledger->reference_id,
            ]))
            ->map(fn(Collection $rows) => $rows->last())
            ->sortBy([['party_id', 'asc'], ['entry_date', 'asc'], ['id', 'asc']])
            ->values();

        $balances = [];

        $rows = $ledgers
            ->map(function (PartyLedger $ledger) use (&$balances) {
                $partyId = (int) $ledger->party_id;
                $balances[$partyId] = ($balances[$partyId] ?? 0.0) + (float) $ledger->credit - (float) $ledger->debit;
                $ledger->balance_after = $balances[$partyId];

                return $ledger;
            });

        if ($from) {
            $rows = $rows->filter(fn(PartyLedger $ledger) => $ledger->entry_date?->toDateString() >= $from);
        }

        return $rows
            ->sortByDesc('entry_date')
            ->values();
    }

    public function statementSummary(EntryVisibilityService $visibility, ?int $partyId = null, ?string $to = null, ?string $from = null): array
    {
        $rows = $this->statementRows($visibility, $partyId, $to, $from);
        $asOnRows = $from ? $this->statementRows($visibility, $partyId, $to) : $rows;

        return [
            'sale' => (float) $rows->where('entry_type', 'sale')->sum('debit'),
            'purchase' => (float) $rows->where('entry_type', 'purchase')->sum('credit'),
            'sales_return' => (float) $rows->where('entry_type', 'sales_return')->sum('credit'),
            'purchase_return' => (float) $rows->where('entry_type', 'purchase_return')->sum('debit'),
            'payment_in' => (float) $rows->where('entry_type', 'payment_in')->sum('credit'),
            'payment_out' => (float) $rows->where('entry_type', 'payment_out')->sum('debit'),
            'debit' => (float) $rows->sum('debit'),
            'credit' => (float) $rows->sum('credit'),
            'final_balance' => (float) ($asOnRows->sortBy([['entry_date', 'asc'], ['id', 'asc']])->last()?->balance_after ?? 0),
        ];
    }

    public function openBillsForPayment(int $partyId, string $paymentType, ?string $to = null): Collection
    {
        $kind = $paymentType === 'payment_in' ? 'receivable' : 'payable';

        return $this->billRows(app(EntryVisibilityService::class), $partyId, $to, $kind)
            ->reject(fn(array $row) => $row['model'] === Party::class)
            ->map(fn(array $row) => [
                'id' => $row['bill_id'],
                'type' => $paymentType === 'payment_in' ? 'sales' : 'purchase',
                'invoice_no' => $row['invoice'],
                'billing_date' => $row['date']?->format('Y-m-d'),
                'grand_total' => round((float) $row['total'], 2),
                'returned' => round((float) $row['returned'], 2),
                'paid' => round((float) $row['paid'], 2),
                'due' => round((float) $row['due'], 2),
                'history' => $row['history'],
            ])
            ->values();
    }

    public function billPayload(EntryVisibilityService $visibility, string $modelClass, int $billId, ?string $to = null): ?array
    {
        $kind = $modelClass === PurchaseBill::class ? 'payable' : 'receivable';
        $bill = $visibility->scopeForUser($modelClass::query(), $modelClass)->find($billId);

        if (!$bill) {
            return null;
        }

        return $this->billRows($visibility, $bill->party_id, $to, $kind)
            ->first(fn(array $row) => $row['model'] === $modelClass && (int) $row['bill_id'] === $billId);
    }

    private function rowsForBills(Collection $bills, string $model, string $kind, Carbon $asOf, string $toDate): Collection
    {
        $payments = PartyPaymentAllocation::where('bill_model', $model)
            ->whereIn('bill_id', $bills->pluck('id'))
            ->whereHas('payment', fn($query) => $query->whereDate('payment_date', '<=', $toDate))
            ->selectRaw('bill_id, SUM(amount) as paid')
            ->groupBy('bill_id')
            ->pluck('paid', 'bill_id');

        $returns = $this->returnsByBill($model, $bills->pluck('id'), $toDate);
        $histories = PartyPaymentAllocation::with('payment')
            ->where('bill_model', $model)
            ->whereIn('bill_id', $bills->pluck('id'))
            ->whereHas('payment', fn($query) => $query->whereDate('payment_date', '<=', $toDate))
            ->latest()
            ->get()
            ->groupBy('bill_id');

        return $bills->map(function ($bill) use ($model, $kind, $asOf, $payments, $returns, $histories) {
            $paid = (float) ($payments[$bill->id] ?? 0);
            $returned = (float) ($returns[$bill->id] ?? 0);
            $effectiveTotal = max(0, (float) $bill->grand_total - $returned);

            return [
                'kind' => $kind,
                'party_id' => $bill->party_id,
                'party' => $bill->party?->display_name ?: 'Cash / Walk-in',
                'invoice' => $bill->invoice_no,
                'date' => $bill->billing_date,
                'age' => $bill->billing_date ? (int) floor($bill->billing_date->startOfDay()->diffInDays($asOf)) : 0,
                'total' => (float) $bill->grand_total,
                'returned' => $returned,
                'effective_total' => $effectiveTotal,
                'paid' => $paid,
                'due' => max(0, $effectiveTotal - $paid),
                'bill_id' => $bill->id,
                'model' => $model,
                'history' => ($histories[$bill->id] ?? collect())->map(fn($allocation) => [
                    'date' => $allocation->payment?->payment_date?->format('d M Y'),
                    'reference_no' => $allocation->payment?->reference_no ?: '-',
                    'amount' => round((float) $allocation->amount, 2),
                    'mode' => $allocation->payment?->payment_mode ?: '-',
                ])->values(),
            ];
        });
    }

    private function returnsByBill(string $model, Collection $billIds, string $toDate): Collection
    {
        if ($billIds->isEmpty()) {
            return collect();
        }

        if ($model === SalesInvoice::class) {
            return SalesReturn::whereIn('sales_invoice_id', $billIds)
                ->whereDate('return_date', '<=', $toDate)
                ->selectRaw('sales_invoice_id as bill_id, SUM(grand_total) as returned')
                ->groupBy('sales_invoice_id')
                ->pluck('returned', 'bill_id');
        }

        return PurchaseReturn::whereIn('purchase_bill_id', $billIds)
            ->whereDate('return_date', '<=', $toDate)
            ->selectRaw('purchase_bill_id as bill_id, SUM(grand_total) as returned')
            ->groupBy('purchase_bill_id')
            ->pluck('returned', 'bill_id');
    }

    private function openingRows(EntryVisibilityService $visibility, ?int $partyId, string $toDate, string $kind, Carbon $asOf): Collection
    {
        return $visibility->scopeForUser(Party::query(), Party::class)
            ->when($partyId, fn($query) => $query->where('id', $partyId))
            ->whereDate('opening_balance_date', '<=', $toDate)
            ->where('opening_balance', '>', 0)
            ->get()
            ->map(function (Party $party) use ($kind, $toDate, $asOf) {
                $side = $party->opening_balance_type === 'receivable' ? 'receivable' : 'payable';
                if ($kind !== 'both' && $kind !== $side) {
                    return null;
                }

                $paid = (float) PartyPaymentAllocation::where('company_id', $party->company_id)
                    ->where('party_id', $party->id)
                    ->where('bill_type', 'opening_balance')
                    ->whereHas('payment', function ($query) use ($side, $toDate) {
                        $query->where('payment_type', $side === 'receivable' ? 'payment_in' : 'payment_out')
                            ->whereDate('payment_date', '<=', $toDate);
                    })
                    ->sum('amount');
                $due = max(0, (float) $party->opening_balance - $paid);
                $date = $party->opening_balance_date ?: now();

                return [
                    'kind' => $side,
                    'party_id' => $party->id,
                    'party' => $party->display_name,
                    'invoice' => 'Opening Balance',
                    'date' => $date,
                    'age' => (int) floor($date->copy()->startOfDay()->diffInDays($asOf)),
                    'total' => (float) $party->opening_balance,
                    'returned' => 0.0,
                    'effective_total' => (float) $party->opening_balance,
                    'paid' => $paid,
                    'due' => $due,
                    'bill_id' => null,
                    'model' => Party::class,
                    'history' => collect(),
                ];
            })
            ->filter()
            ->values()
            ->toBase();
    }
}
