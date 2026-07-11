<?php

namespace App\Http\Controllers\Admin\Production;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Buyer;
use App\Models\Item;
use App\Models\Party;
use App\Models\ProductionBatch;
use App\Models\SalesInvoiceItem;
use App\Models\StockMovement;
use App\Services\AccountingService;
use App\Services\CrmIdentifierPropagationService;
use App\Services\EntryVisibilityService;
use App\Services\SerialUnitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionBatchController extends Controller
{
    public function index(EntryVisibilityService $visibility)
    {
        $batches = $visibility->scopeForUser(
            ProductionBatch::with(['finishedItem','creator'])->latest(),
            ProductionBatch::class
        )->get();
        return view('admin.production.index', compact('batches'));
    }

    public function create()
    {
        $companyId = auth()->user()->current_company_id;

        $finishedItems = Item::with([
                'productType',
                'bomMaterials.rawItem'
            ])
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->whereHas('productType', function ($q) {
                $q->where('nature', 'finished_goods');
            })
            ->orderBy('name')
            ->get();

       

        // Build a rich JSON structure for the frontend wizard
        $itemsData = $finishedItems->keyBy('id')->map(fn($item) => [
            'id'               => $item->id,
            'name'             => $item->name,
            'item_code'        => $item->item_code,
            'hsn_code'         => $item->hsn_code,
            'sale_price'       => (float) $item->sale_price,
            'sale_gst_percent' => (float) $item->sale_gst_percent,
            'requires_gps'      => $this->isGpsItem($item),
            'bom'              => $item->bomMaterials->map(fn($bom) => [
                'raw_item_id'      => $bom->raw_item_id,
                'line_type'        => $bom->line_type ?? 'raw_material',
                'name'             => $bom->rawItem?->name ?? 'Unknown',
                'unit'             => $bom->rawItem?->unit ?? 'PCS',
                'qty_per_unit'     => (float) $bom->qty_per_unit,
                'purchase_price'   => (float) ($bom->rawItem?->purchase_price ?? 0),
                'purchase_gst'     => (float) ($bom->rawItem?->purchase_gst_percent ?? 0),
                'current_stock'    => (float) ($bom->rawItem?->current_stock ?? 0),
                'low_stock_qty'    => (float) ($bom->rawItem?->low_stock_qty ?? 0),
            ])->values(),
        ]);

        // Optional parties list for buyer field
        $parties = [];
        if (class_exists(Party::class)) {
            $parties = Party::where('company_id', $companyId)
                ->where('status', 'active')
                ->orderBy('display_name')
                ->get(['id','display_name']);
        }

        return view('admin.production.create', [
            'finishedItems' => $finishedItems,
            'itemsData'     => $itemsData,
            'parties'       => $parties,
            'buyers'        => Buyer::where('company_id', $companyId)->where('status', 'active')->orderBy('name')->get(),
            'batchNo'       => $this->nextNo(),
        ]);
    }

    public function show(ProductionBatch $productionBatch, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($productionBatch);
        $productionBatch->load(['finishedItem', 'creator']);

        $auditLogs = AuditLog::with(['user','company'])
            ->where('model', ProductionBatch::class)
            ->where('model_id', $productionBatch->id)
            ->latest('created_at')
            ->get();

        return view('admin.production.show', ['batch' => $productionBatch, 'auditLogs' => $auditLogs]);
    }

    public function edit(ProductionBatch $productionBatch, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($productionBatch);
        abort_if($productionBatch->status === 'reverted', 422, 'Reverted production batch cannot be edited.');
        $productionBatch->load(['finishedItem', 'creator']);

        return view('admin.production.edit', [
            'batch' => $productionBatch,
            'soldUnitKeys' => $this->soldUnitKeys($productionBatch->company_id),
        ]);
    }

    public function store(Request $request, AccountingService $accounting, EntryVisibilityService $visibility)
    {
        $data = $request->validate([
            'finished_item_id' => ['required','exists:items,id'],
            'batch_no'         => ['nullable','max:30'],
            'production_date'  => ['required','date'],
            'quantity'         => ['required','numeric','min:0.001'],
            'notes'            => ['nullable','string'],
            // Per-unit fields (arrays)
            'unit_serial.*'    => ['nullable','string','max:100'],
            'unit_buyer_id.*'  => ['nullable','exists:buyers,id'],
            'unit_buyer_code.*'=> ['nullable','string','max:100'],
            'unit_batch.*'     => ['nullable','string','max:100'],
            'unit_vts_sim.*'   => ['nullable','string','max:100'],
            'unit_sale_price.*'=> ['nullable','numeric','min:0'],
            'unit_gst.*'       => ['nullable','numeric','min:0'],
            'unit_warehouse.*' => ['nullable','string','max:255'],
            'unit_notes.*'     => ['nullable','string','max:500'],
        ]);

        DB::transaction(function () use ($request, $data, $accounting, $visibility) {
            $finished = Item::with('bomMaterials.rawItem')->lockForUpdate()->findOrFail($data['finished_item_id']);
            $qty      = (float) $data['quantity'];
            $rawCost  = 0;
            $requiresGps = $this->isGpsItem($finished);

            // 1. Consume raw materials & validate stock
            foreach ($finished->bomMaterials as $bom) {
                $raw  = Item::lockForUpdate()->findOrFail($bom->raw_item_id);
                $need = (float) $bom->qty_per_unit * $qty;
                $unitCost = $this->bomUnitCost($bom, $raw);
                $value = $need * $unitCost;
                $rawCost += $value;

                if (($bom->line_type ?? 'raw_material') === 'service' || $raw->item_type === 'service') {
                    continue;
                }

                abort_if(
                    (float) $raw->current_stock < $need,
                    422,
                    "Insufficient stock for raw material: {$raw->name} (need {$need}, have {$raw->current_stock})"
                );

                $accounting->moveStock($raw, [
                    'movement_date'  => $data['production_date'],
                    'movement_type'  => 'production_consumption',
                    'direction'      => 'out',
                    'quantity'       => $need,
                    'unit_price'     => $unitCost,
                    'total_value'    => $value,
                    'reference_no'   => $data['batch_no'] ?: $this->nextNo(),
                    'description'    => "Consumed for production of {$finished->name}",
                ]);
            }

            // 2. Build per-unit data from request arrays
            $unitsData = [];
            $unitCount = (int) $qty;
            for ($i = 0; $i < $unitCount; $i++) {
                $vtsSim = trim((string) $request->input("unit_vts_sim.{$i}", ''));
                abort_if($requiresGps && $vtsSim === '', 422, 'VTS/SIM number is required for every GPS finished goods unit.');
                $unitsData[] = [
                    'buyer_id'   => $request->input("unit_buyer_id.{$i}"),
                    'buyer_code' => $request->input("unit_buyer_code.{$i}") ?: 'BC-AUTO-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                    'serial_no'  => $request->input("unit_serial.{$i}"),
                    'batch_no'   => $request->input("unit_batch.{$i}"),
                    'vts_sim'    => $vtsSim ?: null,
                    'sale_price' => $request->input("unit_sale_price.{$i}"),
                    'gst'        => $request->input("unit_gst.{$i}"),
                    'sale_mode'  => $request->input("unit_sale_mode.{$i}", 'exclusive'),
                    'warehouse'  => $request->input("unit_warehouse.{$i}"),
                    'notes'      => $request->input("unit_notes.{$i}"),
                ];
            }

            $this->assertUnitIdentifiersAvailable($unitsData, auth()->user()->current_company_id);

            // 3. Create production batch record
            $batch = ProductionBatch::create([
                'company_id'        => auth()->user()->current_company_id,
                'finished_item_id'  => $finished->id,
                'batch_no'          => $data['batch_no'] ?: $this->nextNo(),
                'production_date'   => $data['production_date'],
                'quantity'          => $qty,
                'raw_material_cost' => $rawCost,
                'cost_per_unit'     => $qty > 0 ? $rawCost / $qty : 0,
                'notes'             => $data['notes'] ?? null,
                'units_data'        => $unitsData ?: null,
                'status'            => 'posted',
                'created_by'        => auth()->id(),
            ]);

            $visibility->syncFromRequest($request, $batch);

            // 4. Add finished goods to stock (only here, not on item creation)
            $accounting->moveStock($finished, [
                'movement_date'  => $batch->production_date,
                'movement_type'  => 'production_output',
                'direction'      => 'in',
                'quantity'       => $qty,
                'unit_price'     => $batch->cost_per_unit,
                'total_value'    => $rawCost,
                'reference_type' => ProductionBatch::class,
                'reference_id'   => $batch->id,
                'reference_no'   => $batch->batch_no,
                'movement_units'  => $this->productionMovementUnits($batch, $unitsData),
                'description'    => "Finished goods produced — {$batch->batch_no}",
            ]);
        });

        return redirect()
            ->route('admin.production-batches.index')
            ->with('success', 'Production batch saved. Raw materials consumed and finished goods added to stock.');
    }

    public function update(Request $request, ProductionBatch $productionBatch, AccountingService $accounting, EntryVisibilityService $visibility, CrmIdentifierPropagationService $propagation)
    {
        $visibility->authorizeView($productionBatch);
        abort_if($productionBatch->status === 'reverted', 422, 'Reverted production batch cannot be edited.');

        $data = $request->validate([
            'batch_no' => ['required','max:30'],
            'production_date' => ['required','date'],
            'quantity' => ['required','numeric','min:0.001'],
            'notes' => ['nullable','string'],
            'finished_item_sku' => ['nullable','string','max:255'],
            'propagation_targets' => ['nullable','array'],
            'propagation_targets.*' => ['string','max:100'],
            'unit_buyer_code.*' => ['nullable','string','max:100'],
            'unit_buyer_id.*' => ['nullable','exists:buyers,id'],
            'unit_serial.*' => ['nullable','string','max:100'],
            'unit_batch.*' => ['nullable','string','max:100'],
            'unit_vts_sim.*' => ['nullable','string','max:100'],
            'unit_sale_price.*' => ['nullable','numeric','min:0'],
            'unit_gst.*' => ['nullable','numeric','min:0'],
            'unit_sale_mode.*' => ['nullable','in:exclusive,inclusive'],
            'unit_warehouse.*' => ['nullable','string','max:255'],
            'unit_notes.*' => ['nullable','string','max:500'],
        ]);

        DB::transaction(function () use ($request, $data, $productionBatch, $accounting, $visibility, $propagation) {
            $productionBatch->load('finishedItem.bomMaterials.rawItem');
            $oldValues = $productionBatch->toArray();
            $oldUnits = $productionBatch->units_data ?? [];
            $oldSku = $productionBatch->finishedItem?->sku;
            $soldKeys = collect($this->soldUnitKeys($productionBatch->company_id))
                ->filter(fn($key) => str_starts_with($key, $productionBatch->id . '-'))
                ->values();

            abort_if($soldKeys->count() > (int) $data['quantity'], 422, 'Quantity cannot be less than already sold units.');

            $this->reverseProductionPosting($productionBatch, $accounting);

            $finished = Item::with('bomMaterials.rawItem')->lockForUpdate()->findOrFail($productionBatch->finished_item_id);
            $qty = (float) $data['quantity'];
            $rawCost = 0;
            $requiresGps = $this->isGpsItem($finished);

            foreach ($finished->bomMaterials as $bom) {
                $raw = Item::lockForUpdate()->findOrFail($bom->raw_item_id);
                $need = (float) $bom->qty_per_unit * $qty;
                $unitCost = $this->bomUnitCost($bom, $raw);
                $value = $need * $unitCost;
                $rawCost += $value;

                if (($bom->line_type ?? 'raw_material') === 'service' || $raw->item_type === 'service') {
                    continue;
                }

                abort_if((float) $raw->current_stock < $need, 422, "Insufficient stock for raw material: {$raw->name} (need {$need}, have {$raw->current_stock})");
                $accounting->moveStock($raw, [
                    'movement_date' => $data['production_date'],
                    'movement_type' => 'production_consumption',
                    'direction' => 'out',
                    'quantity' => $need,
                    'unit_price' => $unitCost,
                    'total_value' => $value,
                    'reference_type' => ProductionBatch::class,
                    'reference_id' => $productionBatch->id,
                    'reference_no' => $data['batch_no'],
                    'description' => "Consumed for updated production of {$finished->name}",
                ]);
            }

            $unitsData = $this->unitsFromRequest($request, (int) $qty, $requiresGps);

            $this->assertUnitIdentifiersAvailable($unitsData, $productionBatch->company_id, $productionBatch->id);

            $productionBatch->update([
                'batch_no' => $data['batch_no'],
                'production_date' => $data['production_date'],
                'quantity' => $qty,
                'raw_material_cost' => $rawCost,
                'cost_per_unit' => $qty > 0 ? $rawCost / $qty : 0,
                'notes' => $data['notes'] ?? null,
                'units_data' => $unitsData,
            ]);
            $newSku = $data['finished_item_sku'] ?? null;
            if ((string) $finished->sku !== (string) $newSku) {
                $finished->update(['sku' => $newSku]);
            }

            $accounting->moveStock($finished, [
                'movement_date' => $productionBatch->production_date,
                'movement_type' => 'production_output',
                'direction' => 'in',
                'quantity' => $qty,
                'unit_price' => $productionBatch->cost_per_unit,
                'total_value' => $rawCost,
                'reference_type' => ProductionBatch::class,
                'reference_id' => $productionBatch->id,
                'reference_no' => $productionBatch->batch_no,
                'movement_units' => $this->productionMovementUnits($productionBatch, $unitsData),
                'description' => "Finished goods updated - {$productionBatch->batch_no}",
            ]);

            $propagation->propagate(
                $productionBatch,
                $oldUnits,
                $unitsData,
                $oldSku,
                $newSku,
                $data['propagation_targets'] ?? []
            );

            $visibility->syncFromRequest($request, $productionBatch);
            $this->logUpdate($productionBatch, $oldValues, $productionBatch->fresh()->toArray());
        });

        return redirect()->route('admin.production-batches.show', $productionBatch)->with('success', 'Production batch updated with stock reposted.');
    }

    public function identifierImpact(Request $request, ProductionBatch $productionBatch, EntryVisibilityService $visibility, CrmIdentifierPropagationService $propagation)
    {
        $visibility->authorizeView($productionBatch);
        abort_if($productionBatch->status === 'reverted', 422, 'Reverted production batch cannot be edited.');
        $data = $request->validate([
            'quantity' => ['required','integer','min:1'],
            'finished_item_sku' => ['nullable','string','max:255'],
            'unit_serial.*' => ['nullable','string','max:100'],
            'unit_vts_sim.*' => ['nullable','string','max:100'],
        ]);

        $productionBatch->load('finishedItem');
        $units = $this->unitsFromRequest($request, (int) $data['quantity'], false);

        return response()->json([
            'changed' => $this->identifiersChanged($productionBatch, $units, $data['finished_item_sku'] ?? null),
            'targets' => $propagation->preview($productionBatch, $units, $data['finished_item_sku'] ?? null),
        ]);
    }

    public function revert(ProductionBatch $productionBatch, AccountingService $accounting, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($productionBatch);

        DB::transaction(function () use ($productionBatch, $accounting) {
            $productionBatch->refresh()->load('finishedItem');

            abort_if($productionBatch->status === 'reverted', 422, 'Production batch is already reverted.');

            $soldKeys = collect($this->soldUnitKeys($productionBatch->company_id))
                ->filter(fn($key) => str_starts_with($key, $productionBatch->id . '-'))
                ->values();

            abort_if($soldKeys->isNotEmpty(), 422, 'This batch has sold finished-goods units. Reverse the related sale first.');

            $netMovements = $this->netProductionMovementsForBatch($productionBatch);

            $finishedNet = $netMovements->first(fn($row) => (int) $row['item_id'] === (int) $productionBatch->finished_item_id);
            if ($finishedNet && $finishedNet['quantity'] > 0) {
                $finished = Item::lockForUpdate()->findOrFail($finishedNet['item_id']);
                $accounting->moveStock($finished, [
                    'movement_date' => now()->toDateString(),
                    'movement_type' => 'production_batch_revert_output',
                    'direction' => 'out',
                    'quantity' => $finishedNet['quantity'],
                    'unit_price' => $finishedNet['quantity'] > 0 ? $finishedNet['value'] / $finishedNet['quantity'] : 0,
                    'total_value' => $finishedNet['value'],
                    'reference_type' => ProductionBatch::class,
                    'reference_id' => $productionBatch->id,
                    'reference_no' => $productionBatch->batch_no,
                    'movement_units' => $this->productionMovementUnits($productionBatch, $productionBatch->units_data ?? []),
                    'description' => 'Production batch reverted - finished goods removed.',
                ]);
            }

            foreach ($netMovements as $row) {
                if ((int) $row['item_id'] === (int) $productionBatch->finished_item_id || $row['quantity'] <= 0) {
                    continue;
                }

                $raw = Item::lockForUpdate()->findOrFail($row['item_id']);
                $accounting->moveStock($raw, [
                    'movement_date' => now()->toDateString(),
                    'movement_type' => 'production_batch_revert_raw',
                    'direction' => 'in',
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['quantity'] > 0 ? $row['value'] / $row['quantity'] : 0,
                    'total_value' => $row['value'],
                    'reference_type' => ProductionBatch::class,
                    'reference_id' => $productionBatch->id,
                    'reference_no' => $productionBatch->batch_no,
                    'description' => 'Production batch reverted - raw material restored.',
                ]);
            }

            $oldValues = $productionBatch->toArray();
            $productionBatch->update(['status' => 'reverted']);
            $this->logUpdate($productionBatch, $oldValues, $productionBatch->fresh()->toArray());
        });

        return redirect()
            ->route('admin.production-batches.show', $productionBatch)
            ->with('success', 'Production batch reverted. Finished goods removed and raw material stock restored.');
    }

    public function revertTool(Request $request, EntryVisibilityService $visibility)
    {
        $mode = $request->input('mode', 'batch');
        $term = trim((string) $request->input('q', ''));
        $result = null;

        if ($term !== '') {
            if ($mode === 'serial') {
                $result = $this->findUnitBySerial($term, $visibility);
            } else {
                $batch = $visibility->scopeForUser(
                    ProductionBatch::with('finishedItem.bomMaterials.rawItem')->where('batch_no', $term),
                    ProductionBatch::class
                )->first();
                $result = $batch ? ['batch' => $batch, 'raw' => $this->rawMaterialRows($batch, (float) $batch->quantity)] : null;
            }
        }

        return view('admin.production.revert-tool', compact('mode', 'term', 'result'));
    }

    public function revertSelected(Request $request, AccountingService $accounting, EntryVisibilityService $visibility)
    {
        $data = $request->validate([
            'mode' => ['required','in:batch,serial'],
            'q' => ['required','string'],
        ]);

        if ($data['mode'] === 'batch') {
            $batch = $visibility->scopeForUser(
                ProductionBatch::where('batch_no', $data['q']),
                ProductionBatch::class
            )->firstOrFail();

            return $this->revert($batch, $accounting, $visibility);
        }

        $match = $this->findUnitBySerial($data['q'], $visibility);
        abort_if(!$match, 404, 'Serial number not found.');

        DB::transaction(function () use ($match, $accounting) {
            /** @var ProductionBatch $batch */
            $batch = $match['batch']->fresh('finishedItem.bomMaterials.rawItem');
            $unitIndex = (int) $match['index'];
            $units = $batch->units_data ?? [];
            abort_if(!empty($units[$unitIndex]['reverted_at']), 422, 'This serial is already reverted.');
            abort_if(in_array($batch->id . '-' . $unitIndex, $this->soldUnitKeys($batch->company_id), true), 422, 'This serial is already sold. Reverse sale first.');

            $finished = Item::lockForUpdate()->findOrFail($batch->finished_item_id);

            $accounting->moveStock($finished, [
                'movement_date' => now()->toDateString(),
                'movement_type' => 'production_serial_revert_output',
                'direction' => 'out',
                'quantity' => 1,
                'unit_price' => $batch->cost_per_unit,
                'total_value' => $batch->cost_per_unit,
                'reference_type' => ProductionBatch::class,
                'reference_id' => $batch->id,
                'reference_no' => $batch->batch_no,
                'movement_units' => $this->productionMovementUnits($batch, [$unitIndex => $units[$unitIndex]]),
                'description' => 'Production serial reverted - finished goods removed: ' . ($units[$unitIndex]['serial_no'] ?? $unitIndex),
            ]);

            foreach ($batch->finishedItem?->bomMaterials ?? [] as $bom) {
                if (!$bom->rawItem) {
                    continue;
                }
                if (($bom->line_type ?? 'raw_material') === 'service' || $bom->rawItem->item_type === 'service') {
                    continue;
                }
                $raw = Item::lockForUpdate()->findOrFail($bom->raw_item_id);
                $qty = (float) $bom->qty_per_unit;
                $unitCost = $this->bomUnitCost($bom, $raw);
                $accounting->moveStock($raw, [
                    'movement_date' => now()->toDateString(),
                    'movement_type' => 'production_serial_revert_raw',
                    'direction' => 'in',
                    'quantity' => $qty,
                    'unit_price' => $unitCost,
                    'total_value' => $qty * $unitCost,
                    'reference_type' => ProductionBatch::class,
                    'reference_id' => $batch->id,
                    'reference_no' => $batch->batch_no,
                    'description' => 'Production serial reverted - raw material restored.',
                ]);
            }

            $oldValues = $batch->toArray();
            $units[$unitIndex]['reverted_at'] = now()->toDateTimeString();
            $units[$unitIndex]['reverted_by'] = auth()->id();
            $batch->update(['units_data' => $units]);
            $this->logUpdate($batch, $oldValues, $batch->fresh()->toArray());
        });

        return redirect()->route('admin.production-reverts.index', ['mode' => 'serial', 'q' => $data['q']])
            ->with('success', 'Selected serial reverted. Raw materials restored and finished goods stock adjusted.');
    }

    private function reverseProductionPosting(ProductionBatch $batch, AccountingService $accounting): void
    {
        $finished = $batch->finishedItem;
        if ($finished) {
            $accounting->moveStock($finished, [
                'movement_date' => now()->toDateString(),
                'movement_type' => 'production_output_reversal',
                'direction' => 'out',
                'quantity' => (float) $batch->quantity,
                'unit_price' => $batch->cost_per_unit,
                'total_value' => $batch->raw_material_cost,
                'reference_type' => ProductionBatch::class,
                'reference_id' => $batch->id,
                'reference_no' => $batch->batch_no,
                'movement_units' => $this->productionMovementUnits($batch, $batch->units_data ?? []),
                'description' => 'Production output reversal before update.',
            ]);
        }

        foreach ($finished?->bomMaterials ?? [] as $bom) {
            $raw = $bom->rawItem;
            if (!$raw) {
                continue;
            }
            if (($bom->line_type ?? 'raw_material') === 'service' || $raw->item_type === 'service') {
                continue;
            }
            $qty = (float) $bom->qty_per_unit * (float) $batch->quantity;
            $unitCost = $this->bomUnitCost($bom, $raw);
            $accounting->moveStock($raw, [
                'movement_date' => now()->toDateString(),
                'movement_type' => 'production_consumption_reversal',
                'direction' => 'in',
                'quantity' => $qty,
                'unit_price' => $unitCost,
                'total_value' => $qty * $unitCost,
                'reference_type' => ProductionBatch::class,
                'reference_id' => $batch->id,
                'reference_no' => $batch->batch_no,
                'description' => 'Production raw material reversal before update.',
            ]);
        }
    }

    private function netProductionMovementsForBatch(ProductionBatch $batch)
    {
        return StockMovement::query()
            ->where('company_id', $batch->company_id)
            ->where('reference_no', $batch->batch_no)
            ->whereIn('movement_type', [
                'production_consumption',
                'production_consumption_reversal',
                'production_output',
                'production_output_reversal',
            ])
            ->get()
            ->groupBy('item_id')
            ->map(function ($movements, $itemId) {
                $quantity = $movements->sum(function (StockMovement $movement) {
                    $sign = match ($movement->movement_type) {
                        'production_consumption' => 1,
                        'production_consumption_reversal' => -1,
                        'production_output' => 1,
                        'production_output_reversal' => -1,
                        default => 0,
                    };

                    return $sign * (float) $movement->quantity;
                });

                $value = $movements->sum(function (StockMovement $movement) {
                    $sign = match ($movement->movement_type) {
                        'production_consumption' => 1,
                        'production_consumption_reversal' => -1,
                        'production_output' => 1,
                        'production_output_reversal' => -1,
                        default => 0,
                    };

                    return $sign * (float) $movement->total_value;
                });

                return [
                    'item_id' => (int) $itemId,
                    'quantity' => max(0, round($quantity, 3)),
                    'value' => max(0, round($value, 2)),
                ];
            })
            ->filter(fn($row) => $row['quantity'] > 0)
            ->values();
    }

    private function productionMovementUnits(ProductionBatch $batch, array $units): array
    {
        return collect($units)
            ->filter(fn($unit) => is_array($unit))
            ->map(function (array $unit, $index) use ($batch) {
                return array_merge($unit, [
                    'key' => $batch->id . '-' . $index,
                    'item_id' => $batch->finished_item_id,
                    'item_name' => $batch->finishedItem?->name,
                    'production_batch_no' => $batch->batch_no,
                    'production_date' => $batch->production_date?->format('Y-m-d'),
                    'cost_per_unit' => (float) $batch->cost_per_unit,
                ]);
            })
            ->values()
            ->all();
    }

    private function soldUnitKeys(int $companyId): array
    {
        return app(SerialUnitService::class)->activeSoldKeys($companyId);
    }

    private function findUnitBySerial(string $term, EntryVisibilityService $visibility): ?array
    {
        $batches = $visibility->scopeForUser(
            ProductionBatch::with('finishedItem.bomMaterials.rawItem')->where('status', 'posted'),
            ProductionBatch::class
        )->get();

        foreach ($batches as $batch) {
            foreach (($batch->units_data ?? []) as $index => $unit) {
                if (!empty($unit['reverted_at'])) {
                    continue;
                }
                if (in_array($term, array_filter([
                    $unit['serial_no'] ?? null,
                    $unit['buyer_code'] ?? null,
                    $unit['batch_no'] ?? null,
                    $unit['vts_sim'] ?? null,
                ]), true)) {
                    return [
                        'batch' => $batch,
                        'index' => $index,
                        'unit' => $unit,
                        'key' => $batch->id . '-' . $index,
                        'raw' => $this->rawMaterialRows($batch, 1),
                    ];
                }
            }
        }

        return null;
    }

    private function assertUnitIdentifiersAvailable(array $units, int $companyId, ?int $excludeBatchId = null): void
    {
        $fields = ['serial_no' => 'Serial number', 'vts_sim' => 'VTS/SIM number'];

        foreach ($fields as $field => $label) {
            $submitted = collect($units)
                ->pluck($field)
                ->map(fn($value) => trim((string) $value))
                ->filter()
                ->map(fn($value) => mb_strtolower($value));

            abort_if($submitted->duplicates()->isNotEmpty(), 422, "Duplicate {$label} is not allowed in the same production batch.");

            if ($submitted->isEmpty()) {
                continue;
            }

            $alreadyUsed = ProductionBatch::query()
                ->where('company_id', $companyId)
                ->where('status', 'posted')
                ->when($excludeBatchId, fn($query) => $query->whereKeyNot($excludeBatchId))
                ->get(['id', 'batch_no', 'units_data'])
                ->flatMap(fn(ProductionBatch $batch) => collect($batch->units_data ?? [])
                    ->filter(fn($unit) => is_array($unit) && empty($unit['reverted_at']) && trim((string) ($unit[$field] ?? '')) !== '')
                    ->map(fn($unit) => [
                        'value' => mb_strtolower(trim((string) $unit[$field])),
                        'display' => trim((string) $unit[$field]),
                        'batch' => $batch->batch_no,
                    ]));

            $conflicts = $alreadyUsed
                ->whereIn('value', $submitted->all())
                ->values();

            abort_if(
                $conflicts->isNotEmpty(),
                422,
                "{$label} is already used in an active Production / CRM Assembly. Conflicts: " .
                    $conflicts->map(fn($row) => "{$row['display']} in batch {$row['batch']}")->join(', ') .
                    ". Use a unique {$label} or update the original batch first."
            );
        }
    }

    private function unitsFromRequest(Request $request, int $quantity, bool $requiresGps): array
    {
        $units = [];
        for ($i = 0; $i < $quantity; $i++) {
            $vtsSim = trim((string) $request->input("unit_vts_sim.{$i}", ''));
            abort_if($requiresGps && $vtsSim === '', 422, 'VTS/SIM number is required for every GPS finished goods unit.');
            $units[] = [
                'buyer_code' => $request->input("unit_buyer_code.{$i}") ?: 'BC-AUTO-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'buyer_id' => $request->input("unit_buyer_id.{$i}"),
                'serial_no' => $request->input("unit_serial.{$i}"),
                'batch_no' => $request->input("unit_batch.{$i}"),
                'vts_sim' => $vtsSim ?: null,
                'sale_price' => $request->input("unit_sale_price.{$i}"),
                'gst' => $request->input("unit_gst.{$i}"),
                'sale_mode' => $request->input("unit_sale_mode.{$i}", 'exclusive'),
                'warehouse' => $request->input("unit_warehouse.{$i}"),
                'notes' => $request->input("unit_notes.{$i}"),
            ];
        }

        return $units;
    }

    private function identifiersChanged(ProductionBatch $batch, array $newUnits, ?string $newSku): bool
    {
        if ((string) $batch->finishedItem?->sku !== (string) $newSku) {
            return true;
        }

        return collect($batch->units_data ?? [])->contains(function ($unit, $index) use ($newUnits) {
            $new = $newUnits[$index] ?? [];
            return (string) ($unit['serial_no'] ?? '') !== (string) ($new['serial_no'] ?? '')
                || (string) ($unit['vts_sim'] ?? '') !== (string) ($new['vts_sim'] ?? '');
        });
    }

    private function rawMaterialRows(ProductionBatch $batch, float $qty): array
    {
        return collect($batch->finishedItem?->bomMaterials ?? [])->map(fn($bom) => [
            'name' => $bom->rawItem?->name ?: 'Raw material',
            'qty' => (float) $bom->qty_per_unit * $qty,
            'unit' => $bom->rawItem?->unit,
            'line_type' => $bom->line_type ?? 'raw_material',
            'unit_price' => $this->bomUnitCost($bom, $bom->rawItem),
        ])->values()->all();
    }

    private function bomUnitCost($bom, ?Item $raw = null): float
    {
        if (($bom->line_type ?? 'raw_material') === 'service') {
            return (float) ($bom->unit_price ?? $raw?->purchase_price ?? 0);
        }

        return (float) ($raw?->purchase_price ?? 0);
    }

    private function logUpdate(ProductionBatch $batch, array $oldValues, array $newValues): void
    {
        $user = auth()->user();
        AuditLog::log('updated', [
            'model' => ProductionBatch::class,
            'model_id' => $batch->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => sprintf(
                'Production batch %s updated by %s (%s) for company %s.',
                $batch->batch_no,
                $user?->name ?? 'System',
                $user?->rolesForCompany($batch->company_id)->pluck('name')->join(', ') ?: 'No role',
                $user?->currentCompany?->name ?? 'Unknown company'
            ),
        ]);
    }

    private function nextNo(): string
    {
        $count = ProductionBatch::where('company_id', auth()->user()->current_company_id)->count() + 1;
        return 'PB-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }

    private function isGpsItem(Item $item): bool
    {
        return str_contains(strtolower(implode(' ', array_filter([
            $item->name,
            $item->item_code,
            $item->sku,
            $item->brand,
            $item->model,
            $item->description,
        ]))), 'gps');
    }
}
