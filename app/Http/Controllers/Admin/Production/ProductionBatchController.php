<?php

namespace App\Http\Controllers\Admin\Production;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Buyer;
use App\Models\Item;
use App\Models\Party;
use App\Models\ProductionBatch;
use App\Models\SalesInvoiceItem;
use App\Services\AccountingService;
use App\Services\EntryVisibilityService;
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
            'bom'              => $item->bomMaterials->map(fn($bom) => [
                'raw_item_id'      => $bom->raw_item_id,
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
            'unit_sale_price.*'=> ['nullable','numeric','min:0'],
            'unit_gst.*'       => ['nullable','numeric','min:0'],
            'unit_warehouse.*' => ['nullable','string','max:255'],
            'unit_notes.*'     => ['nullable','string','max:500'],
        ]);

        DB::transaction(function () use ($request, $data, $accounting, $visibility) {
            $finished = Item::with('bomMaterials.rawItem')->lockForUpdate()->findOrFail($data['finished_item_id']);
            $qty      = (float) $data['quantity'];
            $rawCost  = 0;

            // 1. Consume raw materials & validate stock
            foreach ($finished->bomMaterials as $bom) {
                $raw  = Item::lockForUpdate()->findOrFail($bom->raw_item_id);
                $need = (float) $bom->qty_per_unit * $qty;

                abort_if(
                    (float) $raw->current_stock < $need,
                    422,
                    "Insufficient stock for raw material: {$raw->name} (need {$need}, have {$raw->current_stock})"
                );

                $value    = $need * (float) $raw->purchase_price;
                $rawCost += $value;

                $accounting->moveStock($raw, [
                    'movement_date'  => $data['production_date'],
                    'movement_type'  => 'production_consumption',
                    'direction'      => 'out',
                    'quantity'       => $need,
                    'unit_price'     => $raw->purchase_price,
                    'total_value'    => $value,
                    'reference_no'   => $data['batch_no'] ?: $this->nextNo(),
                    'description'    => "Consumed for production of {$finished->name}",
                ]);
            }

            // 2. Build per-unit data from request arrays
            $unitsData = [];
            $unitCount = (int) $qty;
            for ($i = 0; $i < $unitCount; $i++) {
                $unitsData[] = [
                    'buyer_id'   => $request->input("unit_buyer_id.{$i}"),
                    'buyer_code' => $request->input("unit_buyer_code.{$i}") ?: 'BC-AUTO-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                    'serial_no'  => $request->input("unit_serial.{$i}"),
                    'batch_no'   => $request->input("unit_batch.{$i}"),
                    'sale_price' => $request->input("unit_sale_price.{$i}"),
                    'gst'        => $request->input("unit_gst.{$i}"),
                    'sale_mode'  => $request->input("unit_sale_mode.{$i}", 'exclusive'),
                    'warehouse'  => $request->input("unit_warehouse.{$i}"),
                    'notes'      => $request->input("unit_notes.{$i}"),
                ];
            }

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
                'description'    => "Finished goods produced — {$batch->batch_no}",
            ]);
        });

        return redirect()
            ->route('admin.production-batches.index')
            ->with('success', 'Production batch saved. Raw materials consumed and finished goods added to stock.');
    }

    public function update(Request $request, ProductionBatch $productionBatch, AccountingService $accounting, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($productionBatch);

        $data = $request->validate([
            'batch_no' => ['required','max:30'],
            'production_date' => ['required','date'],
            'quantity' => ['required','numeric','min:0.001'],
            'notes' => ['nullable','string'],
            'unit_buyer_code.*' => ['nullable','string','max:100'],
            'unit_buyer_id.*' => ['nullable','exists:buyers,id'],
            'unit_serial.*' => ['nullable','string','max:100'],
            'unit_batch.*' => ['nullable','string','max:100'],
            'unit_sale_price.*' => ['nullable','numeric','min:0'],
            'unit_gst.*' => ['nullable','numeric','min:0'],
            'unit_sale_mode.*' => ['nullable','in:exclusive,inclusive'],
            'unit_warehouse.*' => ['nullable','string','max:255'],
            'unit_notes.*' => ['nullable','string','max:500'],
        ]);

        DB::transaction(function () use ($request, $data, $productionBatch, $accounting, $visibility) {
            $productionBatch->load('finishedItem.bomMaterials.rawItem');
            $oldValues = $productionBatch->toArray();
            $soldKeys = collect($this->soldUnitKeys($productionBatch->company_id))
                ->filter(fn($key) => str_starts_with($key, $productionBatch->id . '-'))
                ->values();

            abort_if($soldKeys->count() > (int) $data['quantity'], 422, 'Quantity cannot be less than already sold units.');

            $this->reverseProductionPosting($productionBatch, $accounting);

            $finished = Item::with('bomMaterials.rawItem')->lockForUpdate()->findOrFail($productionBatch->finished_item_id);
            $qty = (float) $data['quantity'];
            $rawCost = 0;

            foreach ($finished->bomMaterials as $bom) {
                $raw = Item::lockForUpdate()->findOrFail($bom->raw_item_id);
                $need = (float) $bom->qty_per_unit * $qty;
                abort_if((float) $raw->current_stock < $need, 422, "Insufficient stock for raw material: {$raw->name} (need {$need}, have {$raw->current_stock})");
                $value = $need * (float) $raw->purchase_price;
                $rawCost += $value;
                $accounting->moveStock($raw, [
                    'movement_date' => $data['production_date'],
                    'movement_type' => 'production_consumption',
                    'direction' => 'out',
                    'quantity' => $need,
                    'unit_price' => $raw->purchase_price,
                    'total_value' => $value,
                    'reference_type' => ProductionBatch::class,
                    'reference_id' => $productionBatch->id,
                    'reference_no' => $data['batch_no'],
                    'description' => "Consumed for updated production of {$finished->name}",
                ]);
            }

            $unitsData = [];
            for ($i = 0; $i < (int) $qty; $i++) {
                $unitsData[] = [
                    'buyer_code' => $request->input("unit_buyer_code.{$i}") ?: 'BC-AUTO-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                    'buyer_id' => $request->input("unit_buyer_id.{$i}"),
                    'serial_no' => $request->input("unit_serial.{$i}"),
                    'batch_no' => $request->input("unit_batch.{$i}"),
                    'sale_price' => $request->input("unit_sale_price.{$i}"),
                    'gst' => $request->input("unit_gst.{$i}"),
                    'sale_mode' => $request->input("unit_sale_mode.{$i}", 'exclusive'),
                    'warehouse' => $request->input("unit_warehouse.{$i}"),
                    'notes' => $request->input("unit_notes.{$i}"),
                ];
            }

            $productionBatch->update([
                'batch_no' => $data['batch_no'],
                'production_date' => $data['production_date'],
                'quantity' => $qty,
                'raw_material_cost' => $rawCost,
                'cost_per_unit' => $qty > 0 ? $rawCost / $qty : 0,
                'notes' => $data['notes'] ?? null,
                'units_data' => $unitsData,
            ]);

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
                'description' => "Finished goods updated - {$productionBatch->batch_no}",
            ]);

            $visibility->syncFromRequest($request, $productionBatch);
            $this->logUpdate($productionBatch, $oldValues, $productionBatch->fresh()->toArray());
        });

        return redirect()->route('admin.production-batches.show', $productionBatch)->with('success', 'Production batch updated with stock reposted.');
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
                'description' => 'Production output reversal before update.',
            ]);
        }

        foreach ($finished?->bomMaterials ?? [] as $bom) {
            $raw = $bom->rawItem;
            if (!$raw) {
                continue;
            }
            $qty = (float) $bom->qty_per_unit * (float) $batch->quantity;
            $accounting->moveStock($raw, [
                'movement_date' => now()->toDateString(),
                'movement_type' => 'production_consumption_reversal',
                'direction' => 'in',
                'quantity' => $qty,
                'unit_price' => $raw->purchase_price,
                'total_value' => $qty * (float) $raw->purchase_price,
                'reference_type' => ProductionBatch::class,
                'reference_id' => $batch->id,
                'reference_no' => $batch->batch_no,
                'description' => 'Production raw material reversal before update.',
            ]);
        }
    }

    private function soldUnitKeys(int $companyId): array
    {
        return SalesInvoiceItem::whereHas('salesInvoice', fn($q) => $q->where('company_id', $companyId))
            ->get()
            ->flatMap(fn($line) => collect($line->selected_units ?? [])->pluck('key'))
            ->filter()
            ->values()
            ->all();
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
}
