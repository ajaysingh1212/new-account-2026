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
        $items = $visibility->scopeForUser(
            Item::with('productType')->orderBy('name'),
            Item::class
        )->get();
        return view('admin.stocks.index', compact('items'));
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
