<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\StockMovement;
use App\Services\EntryVisibilityService;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(EntryVisibilityService $visibility)
    {
        $month = request('month', now()->format('Y-m'));
        $nature = request('nature');

        $items = $visibility->scopeForUser(
            Item::with('productType')
                ->when($nature, fn($q) => $q->whereHas('productType', fn($type) => $type->where('nature', $nature)))
                ->orderBy('name'),
            Item::class
        )->get();

        $overallValue = $items->sum(fn($item) => (float) $item->stock_value);
        $overallQty = $items->sum(fn($item) => (float) $item->current_stock);

        $monthStart = \Carbon\Carbon::parse($month . '-01')->startOfMonth();
        $monthEnd = (clone $monthStart)->endOfMonth();
        $monthMovements = $visibility->scopeForUser(
            StockMovement::whereBetween('movement_date', [$monthStart->toDateString(), $monthEnd->toDateString()]),
            StockMovement::class
        )->get();
        $monthIn = $monthMovements->where('direction', 'in')->sum(fn($m) => (float) $m->total_value);
        $monthOut = $monthMovements->where('direction', 'out')->sum(fn($m) => (float) $m->total_value);

        return view('admin.stocks.index', compact('items', 'overallValue', 'overallQty', 'month', 'nature', 'monthIn', 'monthOut'));
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
}
