<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\CostCenter;
use App\Models\DeliveryChallan;
use App\Models\DeliveryChallanItem;
use App\Models\Item;
use App\Models\Party;
use App\Models\PendingOrder;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SubCostCenter;
use App\Services\EntryVisibilityService;
use App\Services\AccountingService;
use App\Services\SerialUnitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryChallanController extends Controller
{
    public function index(EntryVisibilityService $visibility)
    {
        $challans = $visibility->scopeForUser(
            DeliveryChallan::with(['party','creator','convertedInvoice','items'])->latest(),
            DeliveryChallan::class
        )->get();

        return view('admin.delivery-challans.index', compact('challans'));
    }

    public function create()
    {
        return view('admin.delivery-challans.create', $this->formData());
    }

    public function store(Request $request, EntryVisibilityService $visibility, AccountingService $accounting)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($request, $data, $visibility, $accounting) {
            $attachment = $request->hasFile('attachment')
                ? $request->file('attachment')->store('challan-attachments', 'public')
                : null;

            $challan = DeliveryChallan::create(array_merge($data, [
                'company_id' => auth()->user()->current_company_id,
                'challan_no' => $data['challan_no'] ?: $this->nextNo(),
                'attachment' => $attachment,
                'status' => 'issued',
                'created_by' => auth()->id(),
            ]));

            $challan->update($this->storeLines($request, $challan, $accounting));
            $visibility->syncFromRequest($request, $challan);
        });

        return redirect()->route('admin.delivery-challans.index')->with('success', 'Delivery challan created.');
    }

    public function show(DeliveryChallan $deliveryChallan, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($deliveryChallan);
        $deliveryChallan->load(['party','items.item','convertedInvoice']);

        return view('admin.delivery-challans.show', compact('deliveryChallan'));
    }

    public function edit(DeliveryChallan $deliveryChallan, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($deliveryChallan);
        abort_if($deliveryChallan->status === 'cancelled', 422, 'Cancelled challan cannot be edited.');
        abort_if($deliveryChallan->converted_sales_invoice_id, 422, 'Converted challan cannot be edited.');
        $deliveryChallan->load('items');

        return view('admin.delivery-challans.edit', array_merge($this->formData($deliveryChallan), compact('deliveryChallan')));
    }

    public function update(Request $request, DeliveryChallan $deliveryChallan, EntryVisibilityService $visibility, AccountingService $accounting)
    {
        $visibility->authorizeManage($deliveryChallan);
        abort_if($deliveryChallan->status === 'cancelled', 422, 'Cancelled challan cannot be edited.');
        abort_if($deliveryChallan->converted_sales_invoice_id, 422, 'Converted challan cannot be edited.');
        $data = $this->validated($request);

        DB::transaction(function () use ($request, $deliveryChallan, $data, $visibility, $accounting) {
            $attachment = $request->hasFile('attachment')
                ? $request->file('attachment')->store('challan-attachments', 'public')
                : $deliveryChallan->attachment;

            $deliveryChallan->load('items.item');
            $this->reverseStock($deliveryChallan, $accounting, 'delivery_challan_update_reversal');
            $deliveryChallan->update(array_merge($data, ['attachment' => $attachment]));
            $deliveryChallan->items()->delete();
            $deliveryChallan->update($this->storeLines($request, $deliveryChallan, $accounting));
            $visibility->syncFromRequest($request, $deliveryChallan);
        });

        return redirect()->route('admin.delivery-challans.show', $deliveryChallan)->with('success', 'Delivery challan updated.');
    }

    public function print(DeliveryChallan $deliveryChallan, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($deliveryChallan);
        $deliveryChallan->load(['party','items.item']);

        return view('admin.delivery-challans.print', compact('deliveryChallan'));
    }

    public function convertForm(DeliveryChallan $deliveryChallan, EntryVisibilityService $visibility, SerialUnitService $serialUnits)
    {
        $visibility->authorizeManage($deliveryChallan);
        abort_if($deliveryChallan->status === 'cancelled', 422, 'Cancelled challan cannot be converted.');
        if ($deliveryChallan->converted_sales_invoice_id) {
            return redirect()->route('admin.sales.show', $deliveryChallan->converted_sales_invoice_id)
                ->with('info', 'Delivery challan already converted to this sale.');
        }

        $deliveryChallan->load(['party', 'items.item']);
        $companyId = $deliveryChallan->company_id;
        $items = $deliveryChallan->items->map(function (DeliveryChallanItem $line) {
            $item = $line->item;

            return [
                'item_id' => $line->item_id,
                'name' => $item?->name ?: 'Item',
                'code' => $item?->item_code ?: '-',
                'unit' => $line->unit ?: $item?->unit,
                'quantity' => (float) $line->quantity,
                'unit_price' => (float) $line->unit_price,
                'discount_type' => $line->discount_type,
                'discount_value' => (float) $line->discount_value,
                'tax_percent' => (float) $line->tax_percent,
                'description' => $line->description,
                'selected_units' => $line->selected_units ?? [],
                'weight' => (float) ($item?->per_quantity_weight ?? 0),
                'current_stock' => (float) ($item?->current_stock ?? 0),
            ];
        })->values();

        $activeItems = Item::where('company_id', $companyId)
            ->where('status', 'active')
            ->whereHas('productType', fn($q) => $q->where('nature', 'finished_goods'))
            ->orderBy('name')
            ->get();

        return view('admin.sales.convert', [
            'sourceType' => 'delivery_challan',
            'sourceLabel' => 'Delivery Challan',
            'source' => $deliveryChallan,
            'parties' => Party::where('company_id', $companyId)->where('status', 'active')->orderBy('display_name')->get(),
            'items' => $activeItems,
            'lineData' => $items,
            'unitPool' => $serialUnits->unitPool($companyId, 'delivery_challan', $deliveryChallan->id),
            'itemMeta' => $activeItems->mapWithKeys(fn(Item $item) => [
                $item->id => [
                    'requires_gps' => $serialUnits->isGpsItem($item),
                    'weight' => (float) ($item->per_quantity_weight ?? 0),
                    'current_stock' => (float) ($item->current_stock ?? 0),
                ],
            ])->all(),
            'actionRoute' => route('admin.delivery-challans.convert', $deliveryChallan),
            'backRoute' => route('admin.delivery-challans.show', $deliveryChallan),
            'saleNo' => $this->nextSaleNo(),
        ]);
    }

    public function convert(Request $request, DeliveryChallan $deliveryChallan, EntryVisibilityService $visibility, AccountingService $accounting, SerialUnitService $serialUnits)
    {
        $visibility->authorizeManage($deliveryChallan);
        abort_if($deliveryChallan->status === 'cancelled', 422, 'Cancelled challan cannot be converted.');
        abort_if($deliveryChallan->converted_sales_invoice_id, 422, 'Delivery challan already converted to sale.');

        $data = $request->validate([
            'party_id' => ['nullable','exists:parties,id'],
            'sale_type' => ['required','in:credit,cash'],
            'invoice_no' => ['nullable','max:20'],
            'billing_date' => ['required','date'],
            'reference_no' => ['nullable','max:255'],
            'phone' => ['nullable','max:255'],
            'billing_address' => ['nullable','string'],
            'shipping_address' => ['nullable','string'],
            'discount_amount' => ['nullable','numeric','min:0'],
            'notes' => ['nullable','string'],
            'terms' => ['nullable','string'],
            'selected_units.*' => ['nullable','string'],
        ]);

        DB::transaction(function () use ($deliveryChallan, $accounting, $data) {
            $deliveryChallan->load(['items.item.bomMaterials.rawItem', 'party']);

            $invoice = SalesInvoice::create([
                'company_id' => $deliveryChallan->company_id,
                'party_id' => $data['party_id'] ?: $deliveryChallan->party_id,
                'cost_center_id' => $deliveryChallan->cost_center_id,
                'sub_cost_center_id' => $deliveryChallan->sub_cost_center_id,
                'sale_type' => $data['sale_type'],
                'invoice_no' => $data['invoice_no'] ?: $this->nextSaleNo(),
                'billing_date' => $data['billing_date'],
                'reference_no' => $data['reference_no'] ?: $deliveryChallan->challan_no,
                'phone' => $data['phone'] ?? $deliveryChallan->phone,
                'billing_address' => $data['billing_address'] ?? $deliveryChallan->billing_address,
                'shipping_address' => $data['shipping_address'] ?? $deliveryChallan->shipping_address,
                'subtotal' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'grand_total' => 0,
                'notes' => trim((string) ($data['notes'] ?? $deliveryChallan->notes) . "\nConverted from delivery challan {$deliveryChallan->challan_no}."),
                'terms' => $data['terms'] ?? $deliveryChallan->terms,
                'status' => 'posted',
                'created_by' => auth()->id(),
                'source_delivery_challan_id' => $deliveryChallan->id,
            ]);

            $subtotal = $tax = $lineDiscountTotal = $selectedGrossTotal = $challanGrossTotal = 0;
            $invoiceHasLines = false;
            foreach ($deliveryChallan->items->values() as $i => $line) {
                $item = $line->item;
                if (!$item) {
                    continue;
                }

                $selectedUnits = collect($line->selected_units ?? [])->filter(fn($unit) => is_array($unit))->values()->all();
                $selectedQty = count($selectedUnits);
                abort_if($selectedQty > (int) $line->quantity, 422, "{$item->name} ke selected units challan quantity se zyada hain.");
                $challanGrossTotal += (float) $line->line_total;
                $pendingQty = max(0, (float) $line->quantity - $selectedQty);
                if ($pendingQty > 0) {
                    $this->createPendingOrder($deliveryChallan, $line, $pendingQty);
                }
                if ($selectedQty <= 0) {
                    continue;
                }

                $lineDiscount = (float) ($line->discount_amount ?? 0);
                $lineTax = (float) ($line->tax_amount ?? 0);
                $gross = (float) $line->line_total;
                if ($selectedQty < (float) $line->quantity) {
                    $ratio = $selectedQty / (float) $line->quantity;
                    $lineDiscount = round($lineDiscount * $ratio, 2);
                    $lineTax = round($lineTax * $ratio, 2);
                    $gross = round($gross * $ratio, 2);
                }
                $net = max(0, $gross - $lineTax);
                $selectedGrossTotal += $gross;

                SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,
                    'item_id' => $line->item_id,
                    'description' => $line->description,
                    'quantity' => $selectedQty,
                    'unit' => $line->unit,
                    'unit_price' => $line->unit_price,
                    'discount_type' => $line->discount_type,
                    'discount_value' => $line->discount_value,
                    'discount_amount' => $lineDiscount,
                    'tax_percent' => $line->tax_percent,
                    'tax_amount' => $lineTax,
                    'line_total' => $gross,
                    'selected_units' => $selectedUnits,
                ]);
                $invoiceHasLines = true;
                $subtotal += $net;
                $tax += $lineTax;
                $lineDiscountTotal += $lineDiscount;
            }

            if (!$invoiceHasLines) {
                $invoice->delete();
            } else {
                $overallDiscount = (float) ($data['discount_amount'] ?? $deliveryChallan->discount_amount ?? 0);
                $overallDiscount = $challanGrossTotal > 0 ? round($overallDiscount * ($selectedGrossTotal / $challanGrossTotal), 2) : 0;
                $invoice->update([
                'subtotal' => $subtotal,
                'discount_amount' => $lineDiscountTotal + $overallDiscount,
                'tax_amount' => $tax,
                'grand_total' => max(0, $subtotal + $tax - $overallDiscount),
                ]);
            }

            if ($invoiceHasLines && $invoice->party_id) {
                $accounting->postPartyLedger($invoice->party, [
                    'entry_date' => $invoice->billing_date,
                    'entry_type' => 'sale',
                    'reference_type' => SalesInvoice::class,
                    'reference_id' => $invoice->id,
                    'reference_no' => $invoice->invoice_no,
                    'debit' => $invoice->grand_total,
                    'credit' => 0,
                    'description' => "Sales invoice converted from delivery challan {$deliveryChallan->challan_no}.",
                ]);
            }

            $deliveryChallan->update([
                'status' => 'converted',
                'converted_sales_invoice_id' => $invoiceHasLines ? $invoice->id : null,
                'converted_at' => now(),
            ]);
        });

        return redirect()->route('admin.delivery-challans.show', $deliveryChallan)->with('success', 'Delivery challan converted to sale.');
    }

    public function cancel(DeliveryChallan $deliveryChallan, EntryVisibilityService $visibility, AccountingService $accounting)
    {
        $visibility->authorizeManage($deliveryChallan);
        abort_if($deliveryChallan->status === 'cancelled', 422, 'Already cancelled.');
        abort_if($deliveryChallan->converted_sales_invoice_id, 422, 'Converted challan cannot be cancelled.');
        DB::transaction(function () use ($deliveryChallan, $accounting) {
            $deliveryChallan->load('items.item');
            $this->reverseStock($deliveryChallan, $accounting, 'delivery_challan_cancel_reversal');
            $deliveryChallan->update(['status' => 'cancelled']);
        });

        return back()->with('success', 'Delivery challan cancelled.');
    }

    public function destroy(DeliveryChallan $deliveryChallan, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($deliveryChallan);
        abort_if($deliveryChallan->status !== 'cancelled', 422, 'Cancel the challan before deleting it so stock is restored.');
        abort_if($deliveryChallan->converted_sales_invoice_id, 422, 'Converted challan cannot be deleted.');
        $deliveryChallan->delete();

        return redirect()->route('admin.delivery-challans.index')->with('success', 'Delivery challan deleted.');
    }

    private function formData(?DeliveryChallan $deliveryChallan = null): array
    {
        $companyId = auth()->user()->current_company_id;

        return [
            'parties' => Party::where('company_id', $companyId)->where('status', 'active')->orderBy('display_name')->get(),
            'items' => Item::where('company_id', $companyId)->where('status', 'active')
                ->where(fn($q) => $q->whereDoesntHave('productType')->orWhereHas('productType', fn($type) => $type->where('nature', '<>', 'raw_material')))->orderBy('name')->get(),
            'costCenters' => CostCenter::where('company_id', $companyId)->where('status', 'active')->get(),
            'subCostCenters' => SubCostCenter::where('company_id', $companyId)->where('status', 'active')->get(),
            'challanNo' => $this->nextNo(),
            'unitPool' => app(SerialUnitService::class)->unitPool($companyId, 'delivery_challan', $deliveryChallan?->id),
            'itemMeta' => Item::where('company_id', $companyId)->get()->mapWithKeys(fn($item) => [$item->id => ['requires_gps' => app(SerialUnitService::class)->isGpsItem($item)]])->all(),
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'party_id' => ['nullable','exists:parties,id'],
            'cost_center_id' => ['nullable','exists:cost_centers,id'],
            'sub_cost_center_id' => ['nullable','exists:sub_cost_centers,id'],
            'challan_no' => ['nullable','max:30'],
            'challan_date' => ['required','date'],
            'reference_no' => ['nullable','max:255'],
            'dispatch_through' => ['nullable','max:255'],
            'vehicle_no' => ['nullable','max:255'],
            'driver_name' => ['nullable','max:255'],
            'driver_phone' => ['nullable','max:30'],
            'lr_no' => ['nullable','max:255'],
            'lr_date' => ['nullable','date'],
            'phone' => ['nullable','max:255'],
            'billing_address' => ['nullable','string'],
            'shipping_address' => ['nullable','string'],
            'discount_amount' => ['nullable','numeric','min:0'],
            'notes' => ['nullable','string'],
            'terms' => ['nullable','string'],
            'attachment' => ['nullable','file','max:4096'],
            'item_id' => ['required','array'],
            'item_id.*' => ['required','exists:items,id'],
            'quantity.*' => ['required','numeric','min:0.001'],
            'unit_price.*' => ['nullable','numeric','min:0'],
            'tax_percent.*' => ['nullable','numeric','min:0'],
            'discount_value.*' => ['nullable','numeric','min:0'],
            'selected_units.*' => ['nullable','string'],
        ]);
    }

    private function storeLines(Request $request, DeliveryChallan $challan, AccountingService $accounting): array
    {
        $subtotal = $tax = $lineDiscount = 0;
        $reservedKeys = [];
        foreach ($request->item_id as $i => $itemId) {
            $item = Item::with('productType')->lockForUpdate()->findOrFail($itemId);
            $qty = (float) $request->quantity[$i];
            abort_unless((int)$item->company_id === (int)$challan->company_id, 422, 'Selected item does not belong to this company.');
            abort_if($item->productType?->nature === 'raw_material', 422, 'Raw materials cannot be dispatched from Delivery Challan.');
            abort_if($item->track_stock && (int)$qty != $qty, 422, "Quantity must be a whole number for {$item->name}.");
            $serials = app(SerialUnitService::class);
            $pool = collect($serials->unitPool($challan->company_id, 'delivery_challan', $challan->id)[$item->id] ?? [])
                ->map(function($unit) use ($reservedKeys) { if (in_array($unit['key'] ?? null,$reservedKeys,true)) $unit['sold']=true; return $unit; })->all();
            $requested = json_decode($request->selected_units[$i] ?? '[]', true) ?: [];
            $availableCount = collect($pool)
                ->where('sold', false)
                ->when($serials->isGpsItem($item), fn($units) => $units->filter(fn($unit) => !empty($unit['vts_sim'])))
                ->count();
            $selectedUnits = $item->track_stock ? $serials->reconcile($requested, $pool, min((int)$qty, count($requested)), $serials->isGpsItem($item)) : [];
            abort_if($item->track_stock && (float)$item->current_stock > 0 && count($requested) === 0, 422, "{$item->name} stock me hai, Serial / VTS select karna jaruri hai.");
            abort_if($item->track_stock && $availableCount > 0 && count($selectedUnits) < min((int)$qty, $availableCount), 422, "{$item->name} ke available " . min((int)$qty, $availableCount) . " serial/VTS units select karna jaruri hai.");
            abort_if($item->track_stock && count($selectedUnits) > (int)$qty, 422, "{$item->name} ke selected units quantity se zyada hain.");
            abort_if($serials->isGpsItem($item) && collect($selectedUnits)->contains(fn($u) => empty($u['vts_sim'])), 422, "GPS item {$item->name} ke liye SIM/VTS number wala unit select karein.");
            $reservedKeys = array_merge($reservedKeys, collect($selectedUnits)->pluck('key')->all());
            $price = (float) ($request->unit_price[$i] ?? 0);
            $base = $qty * $price;
            $discount = (($request->discount_type[$i] ?? 'percent') === 'flat') ? (float) ($request->discount_value[$i] ?? 0) : $base * (float) ($request->discount_value[$i] ?? 0) / 100;
            $gross = max(0, $base - $discount);
            $taxPercent = (float) ($request->tax_percent[$i] ?? 18);
            $taxAmount = $taxPercent > 0 ? $gross * $taxPercent / (100 + $taxPercent) : 0;
            $taxable = $gross - $taxAmount;
            $total = $gross;

            DeliveryChallanItem::create([
                'delivery_challan_id' => $challan->id,
                'item_id' => $item->id,
                'description' => $request->description[$i] ?? $item->description,
                'quantity' => $qty,
                'unit' => $request->unit[$i] ?? $item->unit,
                'unit_price' => $price,
                'discount_type' => $request->discount_type[$i] ?? 'percent',
                'discount_value' => $request->discount_value[$i] ?? 0,
                'discount_amount' => $discount,
                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,
                'line_total' => $total,
                'selected_units' => $selectedUnits,
            ]);

            if (count($selectedUnits) > 0) {
                $accounting->moveStock($item, [
                'party_id' => $challan->party_id, 'movement_date' => $challan->challan_date,
                'movement_type' => 'delivery_challan', 'direction' => 'out', 'quantity' => count($selectedUnits),
                'unit_price' => $item->purchase_price, 'total_value' => count($selectedUnits) * (float)$item->purchase_price,
                'reference_type' => DeliveryChallan::class, 'reference_id' => $challan->id,
                'reference_no' => $challan->challan_no, 'description' => 'Delivery challan stock dispatch.',
                'movement_units' => $selectedUnits,
                ]);
            }

            $subtotal += $taxable;
            $tax += $taxAmount;
            $lineDiscount += $discount;
        }

        $overallDiscount = (float) ($request->discount_amount ?? 0);

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $lineDiscount + $overallDiscount,
            'tax_amount' => $tax,
            'grand_total' => max(0, $subtotal + $tax - $overallDiscount),
        ];
    }

    private function reverseStock(DeliveryChallan $challan, AccountingService $accounting, string $type): void
    {
        foreach ($challan->items as $line) if ($line->item) {
            $selectedQty = count($line->selected_units ?? []);
            if ($selectedQty <= 0) {
                continue;
            }
            $accounting->moveStock($line->item, [
                'party_id' => $challan->party_id, 'movement_date' => now()->toDateString(),
                'movement_type' => $type, 'direction' => 'in', 'quantity' => $selectedQty,
                'unit_price' => $line->item->purchase_price,
                'total_value' => $selectedQty * (float)$line->item->purchase_price,
                'reference_type' => DeliveryChallan::class, 'reference_id' => $challan->id,
                'reference_no' => $challan->challan_no, 'description' => 'Delivery challan stock reversal.',
                'movement_units' => $line->selected_units ?? [],
            ]);
        }
    }

    private function createPendingOrder(DeliveryChallan $challan, DeliveryChallanItem $line, float $qty): void
    {
        $ratio = (float) $line->quantity > 0 ? $qty / (float) $line->quantity : 0;
        $cost = round($qty * (float) ($line->item?->purchase_price ?? 0), 2);
        $sale = round((float) $line->line_total * $ratio, 2);
        $profit = $sale - $cost;

        PendingOrder::create([
            'company_id' => $challan->company_id,
            'party_id' => $challan->party_id,
            'delivery_challan_id' => $challan->id,
            'delivery_challan_item_id' => $line->id,
            'item_id' => $line->item_id,
            'pending_date' => $challan->challan_date?->toDateString() ?? now()->toDateString(),
            'quantity' => $qty,
            'unit' => $line->unit,
            'unit_price' => $line->unit_price,
            'discount_type' => $line->discount_type,
            'discount_value' => $line->discount_value,
            'discount_amount' => round((float) $line->discount_amount * $ratio, 2),
            'tax_percent' => $line->tax_percent,
            'tax_amount' => round((float) $line->tax_amount * $ratio, 2),
            'line_total' => $sale,
            'cost_amount' => $cost,
            'profit_amount' => $profit,
            'profit_percent' => $cost > 0 ? round($profit / $cost * 100, 2) : 0,
            'raw_materials' => $line->item?->bomMaterials?->map(function ($bom) use ($qty) {
                $unitPrice = (float) ($bom->unit_price ?? $bom->rawItem?->purchase_price ?? 0);
                $rawQty = $qty * (float) $bom->qty_per_unit;
                return ['name' => $bom->rawItem?->name ?: 'Raw material', 'qty' => $rawQty, 'unit' => $bom->rawItem?->unit, 'unit_price' => $unitPrice, 'amount' => round($rawQty * $unitPrice, 2)];
            })->values(),
        ]);
    }

    private function nextNo(): string
    {
        $next = DeliveryChallan::where('company_id', auth()->user()->current_company_id)->withTrashed()->count() + 1;
        return 'DC-' . now()->format('Y') . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    private function nextSaleNo(): string
    {
        return str_pad((string) (SalesInvoice::where('company_id', auth()->user()->current_company_id)->withTrashed()->count() + 1), 8, '0', STR_PAD_LEFT);
    }

    private function authorizeCompany(DeliveryChallan $deliveryChallan): void
    {
        abort_unless($deliveryChallan->company_id === auth()->user()->current_company_id || auth()->user()->isSuperAdmin(), 403);
    }
}
