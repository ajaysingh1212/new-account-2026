<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Party;
use App\Models\PartyLedger;
use App\Models\StockMovement;

class AccountingService
{
    public function moveStock(Item $item, array $data): ?StockMovement
    {
        if (!$item->track_stock) return null;

        $qty = (float) $data['quantity'];
        $value = (float) ($data['total_value'] ?? ($qty * (float) ($data['unit_price'] ?? 0)));
        $newStock = $data['direction'] === 'in'
            ? (float) $item->current_stock + $qty
            : (float) $item->current_stock - $qty;
        $newValue = $data['direction'] === 'in'
            ? (float) $item->stock_value + $value
            : max(0, (float) $item->stock_value - $value);

        $item->update(['current_stock' => $newStock, 'stock_value' => $newValue]);

        return StockMovement::create(array_merge($data, [
            'company_id' => $item->company_id,
            'item_id' => $item->id,
            'stock_after' => $newStock,
            'value_after' => $newValue,
            'created_by' => auth()->id(),
        ]));
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
