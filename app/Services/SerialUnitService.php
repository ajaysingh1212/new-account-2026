<?php

namespace App\Services;

use App\Models\DeliveryChallanItem;
use App\Models\Item;
use App\Models\ProductionBatch;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\PurchaseReturn;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\StockMovement;
use App\Models\StockOutChallanItem;

class SerialUnitService
{
    public function activeSoldKeys(int $companyId, ?int $excludeInvoiceId = null): array
    {
        return collect($this->activeSoldScopedKeys($companyId, $excludeInvoiceId))
            ->map(fn($key) => preg_replace('/^\d+:/', '', (string) $key))
            ->unique()
            ->values()
            ->all();
    }

    public function activeSoldScopedKeys(int $companyId, ?int $excludeInvoiceId = null): array
    {
        $soldCounts = SalesInvoiceItem::whereHas('salesInvoice', function ($query) use ($companyId, $excludeInvoiceId) {
                $query->where('company_id', $companyId);
                if ($excludeInvoiceId) {
                    $query->where('id', '<>', $excludeInvoiceId);
                }
            })
            ->get()
            ->flatMap(function ($line) {
                return collect($line->selected_units ?? [])->map(fn($unit) => $this->scopeUnitKey((int) ($line->item_id ?? 0), $unit))->filter();
            })
            ->countBy();

        $returnedCounts = SalesReturnItem::whereHas(
                'salesReturn',
                fn($query) => $query->where('company_id', $companyId)
            )
            ->when(
                $excludeInvoiceId,
                fn($query) => $query->whereHas(
                    'salesReturn',
                    fn($returnQuery) => $returnQuery->where('sales_invoice_id', '<>', $excludeInvoiceId)
                )
            )
            ->get()
            ->flatMap(function ($line) {
                return collect($line->selected_units ?? [])->map(fn($unit) => $this->scopeUnitKey((int) ($line->item_id ?? 0), $unit))->filter();
            })
            ->countBy();

        $salesKeys = $soldCounts
            ->filter(fn($count, $key) => $count > (int) $returnedCounts->get($key, 0))
            ->keys()
            ->values()
            ->all();

        return array_values(array_unique(array_merge(
            $salesKeys,
            $this->challanKeys($companyId),
            $this->stockOutKeys($companyId)
        )));
    }

    public function allocatedKeys(int $companyId, ?string $excludeType = null, ?int $excludeId = null): array
    {
        $salesKeys = $this->activeSoldKeysWithoutOtherDocuments($companyId);
        $challanKeys = $this->challanKeys($companyId, $excludeType === 'delivery_challan' ? $excludeId : null);
        $stockOutKeys = $this->stockOutKeys($companyId, $excludeType === 'stock_out_challan' ? $excludeId : null);

        return array_values(array_unique(array_merge($salesKeys, $challanKeys, $stockOutKeys)));
    }

    public function scopeUnitKey(int $itemId, array|string|null $unit): ?string
    {
        $rawKey = is_array($unit)
            ? ($unit['key'] ?? $unit['serial_no'] ?? $unit['vts_sim'] ?? $unit['buyer_code'] ?? $unit['sku'] ?? $unit['batch_no'] ?? $unit['production_batch_no'] ?? null)
            : $unit;

        if ($rawKey === null || $rawKey === '') {
            return null;
        }

        $rawKey = (string) $rawKey;
        $scopeKey = (string) $itemId . ':' . $rawKey;

        return str_starts_with($rawKey, (string) $itemId . ':') ? $rawKey : $scopeKey;
    }

    public function unitPool(int $companyId, ?string $excludeType = null, ?int $excludeId = null): array
    {
        $usedKeys = $this->allocatedKeys($companyId, $excludeType, $excludeId);

        $currentUnits = collect($this->currentStockUnitsByItem($companyId));
        if ($currentUnits->isEmpty()) {
            $currentUnits = $this->postedFinishedGoodsUnits($companyId);
        }

        return $currentUnits
            ->map(fn(array $rows, int $itemId) => collect($rows)
                ->map(function (array $unit) use ($itemId, $usedKeys) {
                    $scopeKey = $this->scopeUnitKey($itemId, $unit);

                    return array_merge($unit, [
                        'scope_key' => $scopeKey,
                        'sold' => in_array($scopeKey, $usedKeys, true),
                    ]);
                })
                ->values()
                ->all())
            ->all();
    }

    public function reconcile(array $requested, array $pool, int $quantity, bool $requiresGps): array
    {
        $available = collect($pool)->where('sold', false)
            ->when($requiresGps, fn($rows) => $rows->filter(fn($unit) => !empty($unit['vts_sim'])))
            ->values();
        $requestedKeys = collect($requested)
            ->map(fn($unit) => $unit['scope_key'] ?? $this->scopeUnitKey((int) ($unit['item_id'] ?? 0), $unit))
            ->filter()
            ->all();
        $selected = $available->filter(fn($unit) => in_array($unit['scope_key'] ?? $this->scopeUnitKey((int) ($unit['item_id'] ?? 0), $unit), $requestedKeys, true))->take($quantity);
        if ($selected->count() < $quantity) {
            $selectedKeys = $selected->pluck('scope_key')->map(fn($k) => $k ?? $this->scopeUnitKey((int) ($selected->first()['item_id'] ?? 0), $selected->first()))->all();
            $selected = $selected->concat($available->reject(fn($unit) => in_array($unit['scope_key'] ?? $this->scopeUnitKey((int) ($unit['item_id'] ?? 0), $unit), $selectedKeys, true))->take($quantity - $selected->count()));
        }
        return $selected->take($quantity)->values()->all();
    }

    public function isGpsItem(Item $item): bool
    {
        return str_contains(strtolower(implode(' ', array_filter([
            $item->name, $item->item_code, $item->sku, $item->brand, $item->model, $item->description,
        ]))), 'gps');
    }

    public function currentStockUnitsByItem(int $companyId, ?int $itemId = null): array
    {
        $balances = [];

        StockMovement::with(['item.productType', 'party'])
            ->where('company_id', $companyId)
            ->when($itemId, fn($query) => $query->where('item_id', $itemId))
            ->whereHas('item.productType', fn($query) => $query->where('nature', 'finished_goods'))
            ->orderBy('movement_date')
            ->orderBy('id')
            ->get()
            ->each(function (StockMovement $movement) use (&$balances) {
                $units = $this->movementUnits($movement);
                if (empty($units)) {
                    return;
                }

                $delta = $movement->direction === 'in' ? 1 : -1;
                $currentItemId = (int) $movement->item_id;
                foreach ($units as $unit) {
                    if (!is_array($unit)) {
                        continue;
                    }

                    $unit['item_id'] = $currentItemId;
                    $identity = $this->unitIdentity($unit, $currentItemId);
                    if (!$identity) {
                        continue;
                    }

                    $balances[$currentItemId][$identity] ??= [
                        'balance' => 0,
                        'last_direction' => null,
                        'last_movement_at' => null,
                        'last_movement_id' => null,
                        'unit' => $unit,
                        'last_movement' => null,
                    ];
                    $balances[$currentItemId][$identity]['balance'] += $delta;
                    $balances[$currentItemId][$identity]['last_direction'] = $movement->direction;
                    $balances[$currentItemId][$identity]['last_movement_at'] = $movement->movement_date?->format('Y-m-d');
                    $balances[$currentItemId][$identity]['last_movement_id'] = $movement->id;
                    $balances[$currentItemId][$identity]['unit'] = array_merge($unit, [
                        'item_id' => $currentItemId,
                        'item_name' => $movement->item?->name,
                        'last_movement_type' => $movement->movement_type,
                        'last_movement_date' => $movement->movement_date?->format('Y-m-d'),
                        'last_reference_no' => $movement->reference_no,
                        'last_party' => $movement->party?->display_name,
                    ]);
                    $balances[$currentItemId][$identity]['last_movement'] = $movement;
                }
            });

        return collect($balances)
            ->map(fn($rows) => collect($rows)
                ->filter(fn($row) => (int) $row['balance'] > 0 && ($row['last_direction'] ?? null) === 'in')
                ->map(fn($row) => $row['unit'])
                ->values()
                ->all())
            ->filter(fn($rows) => !empty($rows))
            ->all();
    }

    public function movementUnits(StockMovement $movement): array
    {
        $units = collect($movement->movement_units ?? [])->filter(fn($unit) => is_array($unit))->values();
        if ($units->isNotEmpty()) {
            return $units->all();
        }

        if ($movement->reference_type === SalesReturn::class && $movement->reference_id) {
            $return = SalesReturn::with('items')->find($movement->reference_id);

            return collect($return?->items ?? [])
                ->flatMap(fn($line) => (int) $line->item_id === (int) $movement->item_id ? ($line->selected_units ?? []) : [])
                ->filter(fn($unit) => is_array($unit))
                ->values()
                ->all();
        }

        if ($movement->reference_type === PurchaseReturn::class && $movement->reference_id) {
            $return = PurchaseReturn::with('items')->find($movement->reference_id);

            return collect($return?->items ?? [])
                ->flatMap(fn($line) => (int) $line->item_id === (int) $movement->item_id ? ($line->selected_units ?? []) : [])
                ->filter(fn($unit) => is_array($unit))
                ->values()
                ->all();
        }

        if ($movement->reference_type === PurchaseBill::class && $movement->reference_id) {
            $purchase = PurchaseBill::with('items')->find($movement->reference_id);

            return collect($purchase?->items ?? [])
                ->flatMap(fn($line) => (int) $line->item_id === (int) $movement->item_id ? ($line->selected_units ?? []) : [])
                ->filter(fn($unit) => is_array($unit))
                ->values()
                ->all();
        }

        if ($movement->reference_type === SalesInvoice::class && $movement->reference_id) {
            $invoice = SalesInvoice::with('items')->find($movement->reference_id);

            return collect($invoice?->items ?? [])
                ->flatMap(fn($line) => (int) $line->item_id === (int) $movement->item_id ? ($line->selected_units ?? []) : [])
                ->filter(fn($unit) => is_array($unit))
                ->values()
                ->all();
        }

        if ($movement->reference_type === ProductionBatch::class && $movement->reference_id) {
            $batch = ProductionBatch::find($movement->reference_id);

            return collect($batch?->units_data ?? [])
                ->filter(fn($unit) => is_array($unit) && empty($unit['reverted_at']))
                ->map(fn($unit, $index) => array_merge($unit, [
                    'key' => $unit['key'] ?? $batch->id . '-' . $index,
                    'item_id' => $batch->finished_item_id,
                    'production_batch_no' => $unit['production_batch_no'] ?? $batch->batch_no,
                    'production_date' => $batch->production_date?->format('Y-m-d'),
                ]))
                ->values()
                ->all();
        }

        return [];
    }

    public function unitIdentity(array $unit, ?int $itemId = null): ?string
    {
        $itemId = $itemId ?? (!empty($unit['item_id']) ? (int) $unit['item_id'] : null);

        foreach (['key', 'serial_no', 'vts_sim', 'buyer_code', 'sku'] as $field) {
            if (!empty($unit[$field])) {
                $scope = $itemId !== null ? (string) $itemId : 'global';
                return 'item:' . $scope . ':' . $field . ':' . (string) $unit[$field];
            }
        }

        return null;
    }

    private function activeSoldKeysWithoutOtherDocuments(int $companyId): array
    {
        $sold = SalesInvoiceItem::whereHas('salesInvoice', fn($q) => $q->where('company_id', $companyId))
            ->get()
            ->flatMap(fn($line) => collect($line->selected_units ?? [])->map(fn($unit) => $this->scopeUnitKey((int) ($line->item_id ?? 0), $unit)))
            ->filter()
            ->countBy();
        $returned = SalesReturnItem::whereHas('salesReturn', fn($q) => $q->where('company_id', $companyId))
            ->get()
            ->flatMap(fn($line) => collect($line->selected_units ?? [])->map(fn($unit) => $this->scopeUnitKey((int) ($line->item_id ?? 0), $unit)))
            ->filter()
            ->countBy();
        return $sold->filter(fn($count, $key) => $count > (int) $returned->get($key, 0))->keys()->values()->all();
    }

    private function postedFinishedGoodsUnits(int $companyId): \Illuminate\Support\Collection
    {
        $produced = ProductionBatch::with('finishedItem')
            ->where('company_id', $companyId)
            ->where('status', 'posted')
            ->get()
            ->flatMap(fn(ProductionBatch $batch) => collect($batch->units_data ?? [])
                ->map(function ($unit, $index) use ($batch) {
                    if (!is_array($unit) || !empty($unit['reverted_at'])) {
                        return null;
                    }

                    return array_merge($unit, [
                        'key' => $unit['key'] ?? $batch->id . '-' . $index,
                        'item_id' => $batch->finished_item_id,
                        'item_name' => $batch->finishedItem?->name,
                        'production_batch_no' => $batch->batch_no,
                        'production_date' => $batch->production_date?->format('Y-m-d'),
                    ]);
                }))
            ->filter();

        $purchased = PurchaseBillItem::with(['purchaseBill','item.productType'])
            ->whereHas('purchaseBill', fn($q) => $q->where('company_id', $companyId))
            ->whereHas('item.productType', fn($q) => $q->where('nature', 'finished_goods'))
            ->get()
            ->flatMap(fn(PurchaseBillItem $line) => collect($line->selected_units ?? [])
                ->map(function ($unit, $index) use ($line) {
                    if (!is_array($unit)) {
                        return null;
                    }

                    return array_merge($unit, [
                        'key' => $unit['key'] ?? 'PBI-' . $line->id . '-' . $index,
                        'item_id' => $line->item_id,
                        'item_name' => $line->item?->name,
                        'production_batch_no' => $unit['production_batch_no'] ?? $line->purchaseBill?->invoice_no,
                        'production_date' => $line->purchaseBill?->billing_date?->format('Y-m-d'),
                    ]);
                }))
            ->filter();

        return $produced
            ->concat($purchased)
            ->groupBy('item_id')
            ->map(fn($rows) => $rows->values()->all());
    }

    private function challanKeys(int $companyId, ?int $excludeId = null): array
    {
        return DeliveryChallanItem::whereHas('deliveryChallan', fn($q) => $q->where('company_id', $companyId)->where('status', 'issued')->when($excludeId, fn($x) => $x->whereKeyNot($excludeId)))
            ->get()
            ->flatMap(fn($line) => collect($line->selected_units ?? [])->map(fn($unit) => $this->scopeUnitKey((int) ($line->item_id ?? 0), $unit)))
            ->filter()
            ->values()
            ->all();
    }

    private function stockOutKeys(int $companyId, ?int $excludeId = null): array
    {
        return StockOutChallanItem::whereHas('stockOutChallan', fn($q) => $q->where('company_id', $companyId)->where('status', 'issued')->when($excludeId, fn($x) => $x->whereKeyNot($excludeId)))
            ->get()
            ->flatMap(fn($line) => collect($line->selected_units ?? [])->map(fn($unit) => $this->scopeUnitKey((int) ($line->item_id ?? 0), $unit)))
            ->filter()
            ->values()
            ->all();
    }

    public function returnedKeysForInvoiceLine(int $invoiceLineId, ?int $excludeReturnId = null): array
    {
        return SalesReturnItem::where('sales_invoice_item_id', $invoiceLineId)
            ->when($excludeReturnId, fn($query) => $query->where('sales_return_id', '<>', $excludeReturnId))
            ->get()
            ->flatMap(fn($line) => collect($line->selected_units ?? [])->pluck('key'))
            ->filter()
            ->values()
            ->all();
    }
}
