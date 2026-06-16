<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ProductType;
use App\Models\StockMovement;
use App\Models\StockOutChallan;
use App\Services\EntryVisibilityService;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(EntryVisibilityService $visibility)
    {
        $month = request('month', now()->format('Y-m'));
        $nature = request('nature');
        $productTypeId = request('product_type_id');

        $items = $visibility->scopeForUser(
            Item::with('productType')
                ->when($nature, fn($q) => $q->whereHas('productType', fn($type) => $type->where('nature', $nature)))
                ->when($productTypeId, fn($q) => $q->where('product_type_id', $productTypeId))
                ->orderBy('name'),
            Item::class
        )->get();

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

        return view('admin.stocks.index', compact('items', 'overallValue', 'overallQty', 'month', 'nature', 'productTypeId', 'productTypes', 'monthIn', 'monthOut'));
    }

    public function history(Request $request, EntryVisibilityService $visibility)
    {
        $movements = $visibility->scopeForUser(
            StockMovement::with(['item','party','creator'])
                ->when($request->filled('item_id'), fn($q) => $q->where('item_id', $request->item_id))
                ->latest('movement_date')
                ->latest(),
            StockMovement::class
        )->get();
        $items = $visibility->scopeForUser(Item::orderBy('name'), Item::class)->get();
        return view('admin.stocks.history', compact('movements', 'items'));
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
}
