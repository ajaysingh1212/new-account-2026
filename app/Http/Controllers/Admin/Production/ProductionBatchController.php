<?php

namespace App\Http\Controllers\Admin\Production;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Party;
use App\Models\ProductionBatch;
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
            'batchNo'       => $this->nextNo(),
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
                    'serial_no'  => $request->input("unit_serial.{$i}"),
                    'batch_no'   => $request->input("unit_batch.{$i}"),
                    'sale_price' => $request->input("unit_sale_price.{$i}"),
                    'gst'        => $request->input("unit_gst.{$i}"),
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

    private function nextNo(): string
    {
        $count = ProductionBatch::where('company_id', auth()->user()->current_company_id)->count() + 1;
        return 'PB-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }
}
