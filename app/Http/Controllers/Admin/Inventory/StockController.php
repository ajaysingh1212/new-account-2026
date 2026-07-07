<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ProductType;
use App\Models\PurchaseEstimateItem;
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

        $companyId = auth()->user()->current_company_id;
        $incomingByItem = PurchaseEstimateItem::whereHas('purchaseEstimate', fn($q) => $q->where('company_id',$companyId)->where('status','transit'))
            ->selectRaw('item_id, SUM(quantity) as incoming_qty')->groupBy('item_id')->pluck('incoming_qty','item_id');
        $items = $visibility->scopeForUser(
            Item::with('productType')
                ->where(fn($q) => $q->where('current_stock', '>', 0)->orWhereIn('id',$incomingByItem->keys()))
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

        return view('admin.stocks.index', compact('items', 'overallValue', 'overallQty', 'month', 'nature', 'productTypeId', 'productTypes', 'monthIn', 'monthOut', 'serialsByItem', 'serialSearch','incomingByItem'));
    }

    public function history(Request $request, EntryVisibilityService $visibility)
    {
        $serialSearch = trim((string) $request->input('q', ''));
        [$period, $from, $to] = $this->movementDateRange($request);
        $movements = $visibility->scopeForUser(
            StockMovement::with(['item','party','creator'])
                ->when($request->filled('item_id'), fn($q) => $q->where('item_id', $request->item_id))
                ->whereBetween('movement_date', [$from, $to])
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
        return view('admin.stocks.history', compact('movements', 'items', 'serialSearch', 'period', 'from', 'to'));
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
        return app(SerialUnitService::class)->currentStockUnitsByItem(auth()->user()->current_company_id);
    }

    private function movementDateRange(Request $request): array
    {
        $period = $request->input('period', 'month');
        $today = now();

        [$from, $to] = match ($period) {
            'today' => [$today->toDateString(), $today->toDateString()],
            'week' => [$today->copy()->startOfWeek()->toDateString(), $today->copy()->endOfWeek()->toDateString()],
            'year' => [$today->copy()->startOfYear()->toDateString(), $today->copy()->endOfYear()->toDateString()],
            'custom' => [
                $request->date('from_date')?->toDateString() ?? $today->copy()->startOfMonth()->toDateString(),
                $request->date('to_date')?->toDateString() ?? $today->toDateString(),
            ],
            default => [$today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()],
        };

        return [$period, $from, $to];
    }
}
