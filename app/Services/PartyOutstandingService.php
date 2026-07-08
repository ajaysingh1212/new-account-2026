<?php

namespace App\Services;

use App\Models\PartyPaymentAllocation;
use App\Models\PurchaseBill;
use App\Models\SalesInvoice;
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

            $rows = $rows->merge($this->rowsForBills($sales, SalesInvoice::class, 'receivable', $asOf));
        }

        if ($kind === 'both' || $kind === 'payable') {
            $purchases = $visibility->scopeForUser(PurchaseBill::with('party')->where('purchase_type', 'credit'), PurchaseBill::class)
                ->whereDate('billing_date', '<=', $toDate)
                ->when($partyId, fn($query) => $query->where('party_id', $partyId))
                ->get();

            $rows = $rows->merge($this->rowsForBills($purchases, PurchaseBill::class, 'payable', $asOf));
        }

        return $rows
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

    public function statementRows(EntryVisibilityService $visibility, ?int $partyId = null, ?string $to = null): Collection
    {
        $balance = 0.0;

        return $this->billRows($visibility, $partyId, $to)
            ->sortBy('date')
            ->values()
            ->map(function (array $row) use (&$balance) {
                $debit = $row['kind'] === 'receivable' ? $row['due'] : 0.0;
                $credit = $row['kind'] === 'payable' ? $row['due'] : 0.0;
                $balance += $credit - $debit;

                return (object) [
                    'entry_date' => $row['date'],
                    'party' => (object) ['display_name' => $row['party']],
                    'entry_type' => $row['kind'] === 'receivable' ? 'sale_due' : 'purchase_due',
                    'reference_no' => $row['invoice'],
                    'description' => $row['kind'] === 'receivable' ? 'Sales invoice outstanding.' : 'Purchase bill outstanding.',
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance_after' => $balance,
                ];
            })
            ->sortByDesc('entry_date')
            ->values();
    }

    private function rowsForBills(Collection $bills, string $model, string $kind, Carbon $asOf): Collection
    {
        $payments = PartyPaymentAllocation::where('bill_model', $model)
            ->whereIn('bill_id', $bills->pluck('id'))
            ->selectRaw('bill_id, SUM(amount) as paid')
            ->groupBy('bill_id')
            ->pluck('paid', 'bill_id');

        return $bills->map(function ($bill) use ($model, $kind, $asOf, $payments) {
            $paid = (float) ($payments[$bill->id] ?? 0);

            return [
                'kind' => $kind,
                'party_id' => $bill->party_id,
                'party' => $bill->party?->display_name ?: 'Cash / Walk-in',
                'invoice' => $bill->invoice_no,
                'date' => $bill->billing_date,
                'age' => $bill->billing_date ? (int) floor($bill->billing_date->startOfDay()->diffInDays($asOf)) : 0,
                'total' => (float) $bill->grand_total,
                'paid' => $paid,
                'due' => max(0, (float) $bill->grand_total - $paid),
                'bill_id' => $bill->id,
                'model' => $model,
            ];
        });
    }
}
