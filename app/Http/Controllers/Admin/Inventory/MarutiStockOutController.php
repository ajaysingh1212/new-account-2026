<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Services\MarutiStockOutService;
use Illuminate\Http\Request;

class MarutiStockOutController extends Controller
{
    public function available(MarutiStockOutService $service)
    {
        return response()->json(['rows' => $service->available($service->authorize())])->header('Cache-Control', 'no-store, private');
    }

    public function store(Request $request, MarutiStockOutService $service)
    {
        $companyId = $service->authorize();
        $request->validate(['selected_json' => ['required', 'json']]);
        $request->merge(['selected' => json_decode($request->input('selected_json'), true)]);
        $data = $request->validate([
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['required', 'string', 'size:64', 'distinct'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $batch = $service->store($companyId, $data['selected'], $data['notes'] ?? null);

        return redirect()->route('admin.stocks.special-stock-out')->with('success', "Selected stock saved in Special Stock Out ({$batch}). You can restore individual units here.");
    }

    public function restore(int $id, MarutiStockOutService $service)
    {
        $service->restore($service->authorize(), $id);

        return back()->with('success', 'Stock restored. It is available for sales, estimates and delivery challans.');
    }
}
