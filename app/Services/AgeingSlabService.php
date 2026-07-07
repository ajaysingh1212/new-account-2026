<?php

namespace App\Services;

use Illuminate\Support\Collection;

class AgeingSlabService
{
    public const SLABS = [
        '1_30' => '1-30 Days',
        '31_60' => '31-60 Days',
        '61_90' => '61-90 Days',
        '91_120' => '91-120 Days',
        '121_150' => '121-150 Days',
        '150_plus' => '150+ Days',
    ];

    public function matrix(Collection $bills): Collection
    {
        return $bills->groupBy(fn(array $row) => $row['party_id'] ?: 'cash:' . $row['party'])
            ->map(function (Collection $partyBills) {
                $cells = collect(array_keys(self::SLABS))->mapWithKeys(function (string $key) use ($partyBills) {
                    $rows = $partyBills->filter(fn(array $row) => $this->slabKey((int) $row['age']) === $key);
                    return [$key => [
                        'bills' => $rows->count(),
                        'due' => (float) $rows->sum('due'),
                        'receivable' => (float) $rows->where('kind', 'receivable')->sum('due'),
                        'payable' => (float) $rows->where('kind', 'payable')->sum('due'),
                        'invoices' => $rows->pluck('bill_id')->filter()->implode(','),
                    ]];
                })->all();

                return [
                    'party_id' => $partyBills->first()['party_id'],
                    'party' => $partyBills->first()['party'],
                    'receivable' => (float) $partyBills->where('kind', 'receivable')->sum('due'),
                    'payable' => (float) $partyBills->where('kind', 'payable')->sum('due'),
                    'total_due' => (float) $partyBills->sum('due'),
                    'bill_count' => $partyBills->count(),
                    'slabs' => $cells,
                ];
            })
            ->sortByDesc('total_due')
            ->values();
    }

    public function slabKey(int $age): string
    {
        return match (true) {
            $age <= 30 => '1_30',
            $age <= 60 => '31_60',
            $age <= 90 => '61_90',
            $age <= 120 => '91_120',
            $age <= 150 => '121_150',
            default => '150_plus',
        };
    }
}
