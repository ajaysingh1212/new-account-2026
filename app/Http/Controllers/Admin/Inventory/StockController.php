<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ProductType;
use App\Models\PurchaseEstimateItem;
use App\Models\Replacement;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\StockOutChallan;
use App\Services\EntryVisibilityService;
use App\Services\SerialUnitService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(EntryVisibilityService $visibility)
    {
        $month = request('month', now()->format('Y-m'));
        $nature = request('nature');
        $productTypeId = request('product_type_id');
        $serialSearch = trim((string) request('q', ''));
        $companyAdmin = auth()->user()?->isSuperAdmin() || auth()->user()?->isAdmin();

        $companyId = auth()->user()->current_company_id;
        $incomingByItem = PurchaseEstimateItem::whereHas('purchaseEstimate', fn($q) => $q->where('company_id',$companyId)->where('status','transit'))
            ->selectRaw('item_id, SUM(quantity) as incoming_qty')->groupBy('item_id')->pluck('incoming_qty','item_id');
        $items = $visibility->scopeForUser(
            Item::with('productType')
                ->where(function ($q) use ($incomingByItem) {
                    $q->where('current_stock', '>', 0)
                        ->orWhereIn('id', $incomingByItem->keys())
                        ->orWhereHas('productType', fn($type) => $type->where('nature', 'raw_material'));
                })
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
        $replacementReceived = Replacement::with(['item','party','invoice','invoiceItem'])
            ->where('company_id', $companyId)
            ->whereIn('status', ['approved', 'completed'])
            ->latest('request_date')
            ->get()
            ->groupBy('item_id')
            ->map(fn($rows) => [
                'item' => $rows->first()->item,
                'quantity' => $rows->count(),
                'rows' => $rows->values(),
            ])
            ->values();

        return view('admin.stocks.index', compact('items', 'overallValue', 'overallQty', 'month', 'nature', 'productTypeId', 'productTypes', 'monthIn', 'monthOut', 'serialsByItem', 'serialSearch','incomingByItem', 'replacementReceived', 'companyAdmin'));
    }

    public function history(Request $request, EntryVisibilityService $visibility)
    {
        $serialSearch = trim((string) $request->input('q', ''));
        [$period, $from, $to] = $this->movementDateRange($request);
        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate = Carbon::parse($to)->endOfDay();

        $movements = $visibility->scopeForUser(
            StockMovement::with(['item','party','creator'])
                ->when($request->filled('item_id'), fn($q) => $q->where('item_id', $request->item_id))
                ->whereBetween('movement_date', [$fromDate->toDateString(), $toDate->toDateString()])
                ->latest(),
            StockMovement::class
        )->get();

        $adjustments = $visibility->scopeForUser(
            StockAdjustment::with(['item','adjustedBy'])
                ->when($request->filled('item_id'), fn($q) => $q->where('item_id', $request->item_id))
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->latest(),
            StockAdjustment::class
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

            $adjustments = $adjustments->filter(function (StockAdjustment $adjustment) use ($term) {
                $haystack = mb_strtolower(implode(' ', array_filter([
                    $adjustment->item?->name,
                    $adjustment->item?->item_code,
                    $adjustment->item?->sku,
                    $adjustment->note,
                    $adjustment->user_role,
                ])));

                return str_contains($haystack, $term);
            })->values();
        }

        $items = $visibility->scopeForUser(Item::orderBy('name'), Item::class)->get();
        $historyRows = $this->mergeHistoryRows($movements, $adjustments);

        return view('admin.stocks.history', compact('historyRows', 'items', 'serialSearch', 'period', 'from', 'to'));
    }

    public function adjustRawMaterial(Request $request, Item $item, EntryVisibilityService $visibility)
    {
        $user = auth()->user();
        abort_unless($user && ($user->isSuperAdmin() || $user->isAdmin()), 403, 'Admin access required.');

        $visibility->authorizeManage($item);
        abort_unless(optional($item->productType)->nature === 'raw_material', 422, 'Only raw material stock can be adjusted manually.');

        $data = $request->validate([
            'target_stock' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $adjustment = null;

        DB::transaction(function () use ($item, $data, $user, &$adjustment) {
            $lockedItem = Item::whereKey($item->id)->lockForUpdate()->firstOrFail();
            $previousStock = (float) $lockedItem->current_stock;
            $targetStock = round((float) $data['target_stock'], 3);
            $delta = round($targetStock - $previousStock, 3);

            if (abs($delta) < 0.0005) {
                return;
            }

            $unitRate = (float) $lockedItem->purchase_price;
            $previousValue = round($previousStock * $unitRate, 2);
            $newValue = round($targetStock * $unitRate, 2);

            $lockedItem->update([
                'current_stock' => $targetStock,
                'stock_value' => $newValue,
            ]);

            $roles = $user->rolesForCompany($lockedItem->company_id)->pluck('name')->filter()->join(', ');

            $adjustment = StockAdjustment::create([
                'company_id' => $lockedItem->company_id,
                'item_id' => $lockedItem->id,
                'previous_stock' => $previousStock,
                'new_stock' => $targetStock,
                'stock_change' => $delta,
                'unit_rate' => $unitRate,
                'previous_stock_value' => $previousValue,
                'new_stock_value' => $newValue,
                'user_role' => $roles ?: $user->user_type,
                'note' => $data['note'] ?? null,
                'adjusted_by' => $user->id,
            ]);
        });

        if (!$adjustment) {
            return back()->with('success', 'No stock change was needed.');
        }

        return back()->with('success', 'Raw material stock updated and history saved.');
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

    private function mergeHistoryRows($movements, $adjustments)
    {
        $movementRows = $movements->map(function (StockMovement $movement) {
            $previousStock = $movement->direction === 'in'
                ? (float) $movement->stock_after - (float) $movement->quantity
                : (float) $movement->stock_after + (float) $movement->quantity;

            return [
                'kind' => 'movement',
                'event_at' => $movement->created_at,
                'item' => $movement->item,
                'title' => str_replace('_', ' ', ucfirst($movement->movement_type)),
                'previous_stock' => $previousStock,
                'change' => $movement->direction === 'in' ? (float) $movement->quantity : -(float) $movement->quantity,
                'new_stock' => (float) $movement->stock_after,
                'party' => $movement->party?->display_name,
                'reference' => $movement->reference_no,
                'note' => $movement->description,
                'actor' => $movement->creator?->name ?? 'System',
                'role' => $movement->creator?->rolesForCompany($movement->company_id)->pluck('name')->filter()->join(', ') ?: 'No role',
            ];
        });

        $adjustmentRows = $adjustments->map(function (StockAdjustment $adjustment) {
            return [
                'kind' => 'adjustment',
                'event_at' => $adjustment->created_at,
                'item' => $adjustment->item,
                'title' => 'Manual Stock Adjustment',
                'previous_stock' => (float) $adjustment->previous_stock,
                'change' => (float) $adjustment->stock_change,
                'new_stock' => (float) $adjustment->new_stock,
                'party' => null,
                'reference' => 'Manual stock maintenance',
                'note' => $adjustment->note,
                'actor' => $adjustment->adjustedBy?->name ?? 'System',
                'role' => $adjustment->user_role ?: ($adjustment->adjustedBy?->user_type ?? 'Unknown'),
            ];
        });

        return $movementRows
            ->concat($adjustmentRows)
            ->sortByDesc('event_at')
            ->values();
    }
}
