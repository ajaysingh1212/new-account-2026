<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ProductionBatch;
use App\Models\ProductType;
use App\Models\PurchaseBillItem;
use App\Models\StockMovement;
use App\Models\StockOutChallan;
use App\Services\EntryVisibilityService;
use App\Services\SerialUnitService;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(EntryVisibilityService $visibility)
    {
        $month = request('month', now()->format('Y-m'));
        $nature = request('nature');
        $productTypeId = request('product_type_id');
        $serialSearch = trim((string) request('q', ''));

        $items = $visibility->scopeForUser(
            Item::with('productType')
                ->when($nature, fn($q) => $q->whereHas('productType', fn($type) => $type->where('nature', $nature)))
                ->when($productTypeId, fn($q) => $q->where('product_type_id', $productTypeId))
                ->orderBy('name'),
            Item::class
        )->get();

        $serialsByItem = $this->currentSerialsByItem();
        if ($serialSearch !== '') {
            $term = mb_strtolower($serialSearch);
            $items = $items->filter(function (Item $item) use ($serialsByItem, $term) {
                $haystack = mb_strtolower(implode(' ', array_filter([
                    $item->name,
                    $item->item_code,
                    $item->sku,
                    collect($serialsByItem[$item->id] ?? [])->flatMap(fn($unit) => [
                        $unit['serial_no'] ?? null,
                        $unit['vts_sim'] ?? null,
                        $unit['sku'] ?? null,
                        $unit['batch_no'] ?? null,
                    ])->filter()->join(' '),
                ])));

                return str_contains($haystack, $term);
            })->values();
        }

        $items->each(function ($item) {
            $item->calculated_stock_value = (float) $item->current_stock * (float) $item->purchase_price;
            $item->calculated_avg_rate = (float) $item->purchase_price;
        });

        $overallValue = $items->sum(fn($item) => (float) $item->calculated_stock_value);
        $overallQty = $items->sum(fn($item) => (float) $item->current_stock);
        $productTypes = $visibility->scopeForUser(ProductType::orderBy('name'), ProductType::class)->get();

        $monthStart = \Carbon\Carbon::parse($month . '-01')->startOfMonth();
        $monthEnd = (clone $monthStart)->endOfMonth();
        $monthMovements = $visibility->scopeForUser(
            StockMovement::with('item.productType')
                ->whereBetween('movement_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->when($nature, fn($q) => $q->whereHas('item.productType', fn($type) => $type->where('nature', $nature)))
                ->when($productTypeId, fn($q) => $q->whereHas('item', fn($item) => $item->where('product_type_id', $productTypeId))),
            StockMovement::class
        )->get();
        $monthIn = $monthMovements->where('direction', 'in')->sum(fn($m) => (float) $m->total_value);
        $monthOut = $monthMovements->where('direction', 'out')->sum(fn($m) => (float) $m->total_value);

        return view('admin.stocks.index', compact('items', 'overallValue', 'overallQty', 'month', 'nature', 'productTypeId', 'productTypes', 'monthIn', 'monthOut', 'serialsByItem', 'serialSearch'));
    }

    public function history(Request $request, EntryVisibilityService $visibility)
    {
        $serialSearch = trim((string) $request->input('q', ''));
        $movements = $visibility->scopeForUser(
            StockMovement::with(['item','party','creator'])
                ->when($request->filled('item_id'), fn($q) => $q->where('item_id', $request->item_id))
                ->latest('movement_date')
                ->latest(),
            StockMovement::class
        )->get();

        if ($serialSearch !== '') {
            $term = mb_strtolower($serialSearch);
            $movements = $movements->filter(function (StockMovement $movement) use ($term) {
                $haystack = mb_strtolower(implode(' ', array_filter([
                    $movement->reference_no,
                    $movement->item?->name,
                    $movement->item?->item_code,
                    $movement->item?->sku,
                    collect($movement->movement_units ?? [])->flatMap(fn($unit) => [
                        $unit['serial_no'] ?? null,
                        $unit['vts_sim'] ?? null,
                        $unit['sku'] ?? null,
                        $unit['batch_no'] ?? null,
                        $unit['production_batch_no'] ?? null,
                        $unit['key'] ?? null,
                    ])->filter()->join(' '),
                ])));

                return str_contains($haystack, $term);
            })->values();
        }

        $items = $visibility->scopeForUser(Item::orderBy('name'), Item::class)->get();
        return view('admin.stocks.history', compact('movements', 'items', 'serialSearch'));
    }

    public function specialStockOut(EntryVisibilityService $visibility)
    {
        $challans = $visibility->scopeForUser(
            StockOutChallan::with(['party','creator','items.item'])
                ->where('status', 'issued')
                ->latest(),
            StockOutChallan::class
        )->get();

        $rows = $challans->flatMap(function (StockOutChallan $challan) {
            return $challan->items->map(fn($line) => [
                'challan' => $challan,
                'item' => $line->item,
                'quantity' => (float) $line->quantity,
                'unit' => $line->unit,
                'value' => (float) $line->line_total,
            ]);
        });

        return view('admin.stocks.special-stock-out', compact('rows', 'challans'));
    }

    private function currentSerialsByItem(): array
    {
        $balances = [];
        $companyId = auth()->user()->current_company_id;
        $soldKeys = app(SerialUnitService::class)->activeSoldKeys($companyId);

        ProductionBatch::with('finishedItem')
            ->where('company_id', $companyId)
            ->where('status', 'posted')
            ->get()
            ->each(function (ProductionBatch $batch) use (&$balances, $soldKeys) {
                foreach ($batch->units_data ?? [] as $index => $unit) {
                    if (!is_array($unit) || !empty($unit['reverted_at'])) {
                        continue;
                    }

                    $unit = array_merge($unit, [
                        'key' => $batch->id . '-' . $index,
                        'item_id' => $batch->finished_item_id,
                        'item_name' => $batch->finishedItem?->name,
                        'production_batch_no' => $batch->batch_no,
                        'production_date' => $batch->production_date?->format('Y-m-d'),
                        'cost_per_unit' => (float) $batch->cost_per_unit,
                    ]);

                    if (in_array($unit['key'], $soldKeys, true)) {
                        continue;
                    }

                    $identity = $this->unitIdentity($unit);
                    if ($identity) {
                        $balances[$batch->finished_item_id][$identity] = $unit;
                    }
                }
            });

        PurchaseBillItem::with(['purchaseBill', 'item'])
            ->whereHas('purchaseBill', fn($q) => $q->where('company_id', $companyId))
            ->get()
            ->each(function (PurchaseBillItem $line) use (&$balances, $soldKeys) {
                foreach ($line->selected_units ?? [] as $index => $unit) {
                    if (!is_array($unit)) {
                        continue;
                    }

                    $unit = array_merge($unit, [
                        'key' => 'PBI-' . $line->id . '-' . $index,
                        'item_id' => $line->item_id,
                        'item_name' => $line->item?->name,
                        'production_batch_no' => $unit['production_batch_no'] ?? $line->purchaseBill?->invoice_no,
                        'production_date' => $line->purchaseBill?->billing_date?->format('Y-m-d'),
                        'cost_per_unit' => (float) $line->unit_price,
                    ]);

                    if (in_array($unit['key'], $soldKeys, true)) {
                        continue;
                    }

                    $identity = $this->unitIdentity($unit);
                    if ($identity) {
                        $balances[$line->item_id][$identity] = $unit;
                    }
                }
            });

        return collect($balances)
            ->map(fn($units) => array_values($units))
            ->all();
    }

    private function unitIdentity(array $unit): ?string
    {
        foreach (['serial_no', 'vts_sim', 'sku', 'key'] as $field) {
            if (!empty($unit[$field])) {
                return $field . ':' . (string) $unit[$field];
            }
        }

        return null;
    }
}
