<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Item;
use App\Models\MarutiStockOut;
use App\Models\PurchaseBillItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MarutiStockOutService
{
    public function enabled(int $companyId): bool
    {
        $name = Company::find($companyId)?->name ?? '';

        return in_array(strtoupper(preg_replace('/[^a-z0-9]/i', '', $name)), ['MARUTISUZUKI', 'MARUTISUZUKIVENTURES'], true);
    }

    public function authorize(): int
    {
        $user = auth()->user();
        $companyId = (int) $user?->current_company_id;
        abort_unless($user && ($user->isSuperAdmin() || $user->isAdmin()) && $this->enabled($companyId), 403);

        return $companyId;
    }

    public function available(int $companyId): array
    {
        $serials = app(SerialUnitService::class);
        $unitsByItem = $serials->currentStockUnitsByItem($companyId);
        // An empty serialized balance must never fall back to a stale quantity.
        $serializedIds = StockMovement::where('company_id', $companyId)->whereHas('item.productType', fn ($q) => $q->whereIn('nature', Item::SALEABLE_PRODUCT_NATURES))
            ->get(['item_id', 'movement_units'])->filter(fn ($m) => ! empty($m->movement_units))->pluck('item_id')
            ->merge(PurchaseBillItem::whereHas('purchaseBill', fn ($q) => $q->where('company_id', $companyId))
                ->whereHas('item.productType', fn ($q) => $q->whereIn('nature', Item::SALEABLE_PRODUCT_NATURES))
                ->get(['item_id', 'selected_units'])->filter(fn ($line) => ! empty($line->selected_units))->pluck('item_id'))
            ->flip();
        $rows = [];
        foreach (Item::where('company_id', $companyId)->orderBy('name')->get() as $item) {
            $units = $unitsByItem[$item->id] ?? [];
            $serialized = $units !== [] || $serializedIds->has($item->id);
            if (! $serialized && (float) $item->current_stock <= 0) {
                continue;
            }
            foreach ($serialized ? $units : [null] as $unit) {
                $quantity = $unit === null ? (float) $item->current_stock : 1;
                $row = [
                    'item_id' => $item->id, 'item_name' => $item->name, 'item_code' => $item->item_code,
                    'unit' => $item->unit, 'quantity' => $quantity, 'sku' => $unit['sku'] ?? $item->sku,
                    'unit_data' => $unit, 'unit_cost' => (float) $item->purchase_price,
                ];
                // Includes ledger metadata, so a changed unit requires a fresh review.
                $row['selection_key'] = hash('sha256', json_encode([$companyId, $row]));
                $rows[] = $row;
            }
        }

        return $rows;
    }

    public function store(int $companyId, array $keys, ?string $notes): string
    {
        return DB::transaction(function () use ($companyId, $keys, $notes) {
            Company::whereKey($companyId)->lockForUpdate()->firstOrFail();
            Item::where('company_id', $companyId)->orderBy('id')->lockForUpdate()->get();
            $available = collect($this->available($companyId))->keyBy('selection_key');
            if (collect($keys)->contains(fn ($key) => ! $available->has($key))) {
                throw ValidationException::withMessages(['selected' => 'Stock changed since the popup was opened. Reopen it and review the available stock.']);
            }
            $batch = 'MSO-'.now()->format('Ymd-His').'-'.Str::upper(Str::random(6));
            foreach (collect($keys)->map(fn ($key) => $available[$key])->groupBy('item_id') as $itemId => $rows) {
                $count = $available->where('item_id', $itemId)->sum('quantity');
                foreach ($rows as $row) {
                    $entry = MarutiStockOut::create(collect($row)->except(['selection_key'])->all() + [
                        'company_id' => $companyId, 'batch_no' => $batch, 'notes' => $notes, 'created_by' => auth()->id(),
                    ]);
                    $this->move($entry, 'out', (int) $count);
                    $count -= $row['quantity'];
                }
            }

            return $batch;
        });
    }

    public function restore(int $companyId, int $id): void
    {
        DB::transaction(function () use ($companyId, $id) {
            Company::whereKey($companyId)->lockForUpdate()->firstOrFail();
            $entry = MarutiStockOut::where('company_id', $companyId)->whereKey($id)->lockForUpdate()->firstOrFail();
            if ($entry->restored_at) {
                throw ValidationException::withMessages(['restore' => 'This stock has already been restored.']);
            }
            $this->move($entry, 'in');
            $entry->update(['restored_at' => now(), 'restored_by' => auth()->id()]);
        });
    }

    private function move(MarutiStockOut $entry, string $direction, ?int $availableCount = null): void
    {
        $item = Item::where('company_id', $entry->company_id)->whereKey($entry->item_id)->lockForUpdate()->firstOrFail();
        // Reconcile serialized item counters to the same availability shown in
        // Stocks, including legacy counters that differ from physical units.
        if ($entry->unit_data !== null) {
            $count = $availableCount ?? count(app(SerialUnitService::class)->currentStockUnitsByItem($entry->company_id, $item->id)[$item->id] ?? []);
            $item->update(['current_stock' => $count, 'stock_value' => $count * (float) $item->purchase_price]);
        }
        $date = max(now()->toDateString(), (string) StockMovement::where('company_id', $entry->company_id)->where('item_id', $item->id)->max('movement_date'));
        app(AccountingService::class)->moveStock($item, [
            'force' => true, 'movement_date' => $date,
            'movement_type' => $direction === 'out' ? 'maruti_special_stock_out' : 'maruti_special_stock_restore',
            'direction' => $direction, 'quantity' => (float) $entry->quantity,
            'unit_price' => (float) $entry->unit_cost,
            'total_value' => (float) $entry->quantity * (float) $entry->unit_cost,
            'reference_type' => MarutiStockOut::class, 'reference_id' => $entry->id, 'reference_no' => $entry->batch_no,
            'description' => $direction === 'out' ? 'Maruti stock moved to Special Stock Out.' : 'Maruti Special Stock Out restored to available stock.',
            'movement_units' => $entry->unit_data === null ? [] : [$entry->unit_data],
        ]);
    }
}
