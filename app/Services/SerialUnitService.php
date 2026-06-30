<?php

namespace App\Services;

use App\Models\DeliveryChallanItem;
use App\Models\Item;
use App\Models\ProductionBatch;
use App\Models\PurchaseBillItem;
use App\Models\SalesInvoiceItem;
use App\Models\SalesReturnItem;
use App\Models\StockOutChallanItem;

class SerialUnitService
{
    public function activeSoldKeys(int $companyId, ?int $excludeInvoiceId = null): array
    {
        $soldCounts = SalesInvoiceItem::whereHas('salesInvoice', function ($query) use ($companyId, $excludeInvoiceId) {
                $query->where('company_id', $companyId);
                if ($excludeInvoiceId) {
                    $query->where('id', '<>', $excludeInvoiceId);
                }
            })
            ->get()
            ->flatMap(fn($line) => collect($line->selected_units ?? [])->pluck('key'))
            ->filter()
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
            ->flatMap(fn($line) => collect($line->selected_units ?? [])->pluck('key'))
            ->filter()
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

    public function unitPool(int $companyId, ?string $excludeType = null, ?int $excludeId = null): array
    {
        $usedKeys = $this->allocatedKeys($companyId, $excludeType, $excludeId);

        $produced = ProductionBatch::with('finishedItem')
            ->where('company_id', $companyId)->where('status', 'posted')->get()
            ->flatMap(fn(ProductionBatch $batch) => collect($batch->units_data ?? [])->map(function ($unit, $index) use ($batch, $usedKeys) {
                if (!is_array($unit) || !empty($unit['reverted_at'])) return null;
                $key = $batch->id.'-'.$index;
                return array_merge($unit, [
                    'key' => $key, 'item_id' => $batch->finished_item_id,
                    'item_name' => $batch->finishedItem?->name,
                    'production_batch_no' => $batch->batch_no,
                    'production_date' => $batch->production_date?->format('Y-m-d'),
                    'sold' => in_array($key, $usedKeys, true),
                ]);
            }))->filter()->groupBy('item_id')->map(fn($rows) => $rows->values()->all())->all();

        PurchaseBillItem::with(['purchaseBill','item.productType'])
            ->whereHas('purchaseBill', fn($q) => $q->where('company_id', $companyId))
            ->whereHas('item.productType', fn($q) => $q->where('nature', '<>', 'raw_material'))
            ->get()->each(function (PurchaseBillItem $line) use (&$produced, $usedKeys) {
                foreach (($line->selected_units ?? []) as $index => $unit) {
                    $key = 'PBI-'.$line->id.'-'.$index;
                    $produced[$line->item_id][] = array_merge($unit, [
                        'key' => $key, 'item_id' => $line->item_id, 'item_name' => $line->item?->name,
                        'production_batch_no' => $unit['production_batch_no'] ?? $line->purchaseBill?->invoice_no,
                        'production_date' => $line->purchaseBill?->billing_date?->format('Y-m-d'),
                        'sold' => in_array($key, $usedKeys, true),
                    ]);
                }
            });

        return $produced;
    }

    public function reconcile(array $requested, array $pool, int $quantity, bool $requiresGps): array
    {
        $available = collect($pool)->where('sold', false)
            ->when($requiresGps, fn($rows) => $rows->filter(fn($unit) => !empty($unit['vts_sim'])))
            ->values();
        $requestedKeys = collect($requested)->pluck('key')->filter()->all();
        $selected = $available->filter(fn($unit) => in_array($unit['key'] ?? null, $requestedKeys, true))->take($quantity);
        if ($selected->count() < $quantity) {
            $selected = $selected->concat($available->whereNotIn('key', $selected->pluck('key'))->take($quantity - $selected->count()));
        }
        return $selected->take($quantity)->values()->all();
    }

    public function isGpsItem(Item $item): bool
    {
        return str_contains(strtolower(implode(' ', array_filter([
            $item->name, $item->item_code, $item->sku, $item->brand, $item->model, $item->description,
        ]))), 'gps');
    }

    private function activeSoldKeysWithoutOtherDocuments(int $companyId): array
    {
        $sold = SalesInvoiceItem::whereHas('salesInvoice', fn($q) => $q->where('company_id', $companyId))
            ->get()->flatMap(fn($line) => collect($line->selected_units ?? [])->pluck('key'))->filter()->countBy();
        $returned = SalesReturnItem::whereHas('salesReturn', fn($q) => $q->where('company_id', $companyId))
            ->get()->flatMap(fn($line) => collect($line->selected_units ?? [])->pluck('key'))->filter()->countBy();
        return $sold->filter(fn($count, $key) => $count > (int) $returned->get($key, 0))->keys()->values()->all();
    }

    private function challanKeys(int $companyId, ?int $excludeId = null): array
    {
        return DeliveryChallanItem::whereHas('deliveryChallan', fn($q) => $q->where('company_id', $companyId)->where('status', 'issued')->when($excludeId, fn($x) => $x->whereKeyNot($excludeId)))
            ->get()->flatMap(fn($line) => collect($line->selected_units ?? [])->pluck('key'))->filter()->values()->all();
    }

    private function stockOutKeys(int $companyId, ?int $excludeId = null): array
    {
        return StockOutChallanItem::whereHas('stockOutChallan', fn($q) => $q->where('company_id', $companyId)->where('status', 'issued')->when($excludeId, fn($x) => $x->whereKeyNot($excludeId)))
            ->get()->flatMap(fn($line) => collect($line->selected_units ?? [])->pluck('key'))->filter()->values()->all();
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
