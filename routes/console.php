<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\Item;
use App\Models\PurchaseBillItem;
use App\Models\StockMovement;
use App\Services\SerialUnitService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('inventory:audit-serials {--company_id= : Limit audit to one company}', function (SerialUnitService $serialUnits) {
    $companyId = $this->option('company_id') ? (int) $this->option('company_id') : null;
    $currentUnits = $serialUnits->currentStockUnitsByItem($companyId ?: (int) (auth()->user()?->current_company_id ?? 0));

    if (!$companyId) {
        $currentUnits = [];
        foreach (Item::query()->distinct()->pluck('company_id')->filter() as $id) {
            foreach ($serialUnits->currentStockUnitsByItem((int) $id) as $itemId => $units) {
                $currentUnits[$itemId] = $units;
            }
        }
    }

    $serialItemIds = PurchaseBillItem::query()
        ->whereNotNull('selected_units')
        ->whereHas('purchaseBill', fn($query) => $query->when($companyId, fn($q) => $q->where('company_id', $companyId)))
        ->get()
        ->filter(fn($line) => collect($line->selected_units ?? [])->filter(fn($unit) => is_array($unit) && !empty($unit['key']))->isNotEmpty())
        ->pluck('item_id')
        ->merge(
            StockMovement::query()
                ->whereNotNull('movement_units')
                ->when($companyId, fn($query) => $query->where('company_id', $companyId))
                ->get()
                ->filter(fn($movement) => collect($movement->movement_units ?? [])->filter(fn($unit) => is_array($unit) && !empty($unit['key']))->isNotEmpty())
                ->pluck('item_id')
        )
        ->unique()
        ->values();

    $rows = Item::with('productType')
        ->whereIn('id', $serialItemIds)
        ->orderBy('company_id')
        ->orderBy('name')
        ->get()
        ->map(function (Item $item) use ($currentUnits) {
            $stored = (float) $item->current_stock;
            $movementStock = (float) StockMovement::where('company_id', $item->company_id)
                ->where('item_id', $item->id)
                ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END), 0) as net")
                ->value('net');
            $availableSerials = count($currentUnits[$item->id] ?? []);

            return [
                'company_id' => $item->company_id,
                'item_id' => $item->id,
                'item' => $item->name,
                'stored_stock' => number_format($stored, 3, '.', ''),
                'movement_stock' => number_format($movementStock, 3, '.', ''),
                'available_serials' => $availableSerials,
                'status' => abs($stored - $movementStock) < 0.0005 && (int) round($stored) === $availableSerials ? 'OK' : 'MISMATCH',
            ];
        })
        ->filter(fn($row) => $row['status'] !== 'OK')
        ->values()
        ->all();

    if (empty($rows)) {
        $this->info('No serial inventory mismatches found.');
        return self::SUCCESS;
    }

    $this->table(['Company', 'Item ID', 'Item', 'Stored', 'Movement', 'Available Serials', 'Status'], $rows);
    $this->warn('Report only: no data was modified.');

    return self::FAILURE;
})->purpose('Report serialised inventory mismatches without modifying data');
