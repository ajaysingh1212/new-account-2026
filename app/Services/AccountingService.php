<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Party;
use App\Models\PartyLedger;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    public function moveStock(Item $item, array $data): ?StockMovement
    {
        return DB::transaction(function () use ($item, $data) {
            $lockedItem = Item::whereKey($item->id)->lockForUpdate()->firstOrFail();
            if (!$lockedItem->track_stock && empty($data['force'])) {
                return null;
            }

            $qty = (float) $data['quantity'];
            $value = (float) ($data['total_value'] ?? ($qty * (float) ($data['unit_price'] ?? 0)));
            $direction = $data['direction'] === 'out' ? 'out' : 'in';
            $baseStock = max(0, (float) $lockedItem->current_stock);
            $newStock = $direction === 'in'
                ? $baseStock + $qty
                : max(0, $baseStock - $qty);
            $newValue = $direction === 'in'
                ? (float) $lockedItem->stock_value + $value
                : max(0, (float) $lockedItem->stock_value - $value);

            $lockedItem->forceFill([
                'current_stock' => $newStock,
                'stock_value' => $newValue,
            ])->save();

            $item->forceFill([
                'current_stock' => $newStock,
                'stock_value' => $newValue,
            ]);

            return StockMovement::create(array_merge($data, [
                'direction' => $direction,
                'company_id' => $lockedItem->company_id,
                'item_id' => $lockedItem->id,
                'stock_after' => $newStock,
                'value_after' => $newValue,
                'created_by' => auth()->id(),
            ]));
        });
    }

    public function postPartyLedger(Party $party, array $data): void
    {
        $balance = (float) $party->current_balance + (float) ($data['credit'] ?? 0) - (float) ($data['debit'] ?? 0);
        $party->update(['current_balance' => $balance]);

        PartyLedger::create(array_merge($data, [
            'company_id' => $party->company_id,
            'party_id' => $party->id,
            'balance_after' => $balance,
            'created_by' => auth()->id(),
        ]));
    }
}
