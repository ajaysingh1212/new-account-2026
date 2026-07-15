<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\CostCenter;
use App\Models\DeliveryChallan;
use App\Models\DeliveryChallanItem;
use App\Models\Item;
use App\Models\Party;
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
            DeliveryChallan::with(['party','creator','convertedInvoice'])->latest(),
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

    public function convert(DeliveryChallan $deliveryChallan, EntryVisibilityService $visibility, AccountingService $accounting)
    {
        $visibility->authorizeManage($deliveryChallan);
        abort_if($deliveryChallan->status === 'cancelled', 422, 'Cancelled challan cannot be converted.');
        abort_if($deliveryChallan->converted_sales_invoice_id, 422, 'Delivery challan already converted to sale.');

        DB::transaction(function () use ($deliveryChallan, $accounting) {
            $deliveryChallan->load(['items.item', 'party']);

            $invoice = SalesInvoice::create([
                'company_id' => $deliveryChallan->company_id,
                'party_id' => $deliveryChallan->party_id,
                'cost_center_id' => $deliveryChallan->cost_center_id,
                'sub_cost_center_id' => $deliveryChallan->sub_cost_center_id,
                'sale_type' => $deliveryChallan->party_id ? 'credit' : 'cash',
                'invoice_no' => $this->nextSaleNo(),
                'billing_date' => $deliveryChallan->challan_date?->toDateString() ?? now()->toDateString(),
                'reference_no' => $deliveryChallan->challan_no,
                'phone' => $deliveryChallan->phone,
                'billing_address' => $deliveryChallan->billing_address,
                'shipping_address' => $deliveryChallan->shipping_address,
                'subtotal' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'grand_total' => 0,
                'notes' => trim((string) $deliveryChallan->notes . "\nConverted from delivery challan {$deliveryChallan->challan_no}."),
                'terms' => $deliveryChallan->terms,
                'status' => 'posted',
                'created_by' => auth()->id(),
            ]);

            $subtotal = $tax = $lineDiscountTotal = 0;
            foreach ($deliveryChallan->items as $line) {
                $item = $line->item;
                if (!$item) {
                    continue;
                }

                $lineDiscount = (float) ($line->discount_amount ?? 0);
                $lineTax = (float) ($line->tax_amount ?? 0);
                $gross = (float) $line->line_total;
                $net = max(0, $gross - $lineTax);

                SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,
                    'item_id' => $line->item_id,
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'unit' => $line->unit,
                    'unit_price' => $line->unit_price,
                    'discount_type' => $line->discount_type,
                    'discount_value' => $line->discount_value,
                    'discount_amount' => $lineDiscount,
                    'tax_percent' => $line->tax_percent,
                    'tax_amount' => $lineTax,
                    'line_total' => $gross,
                    'selected_units' => $line->selected_units ?? [],
                ]);

                $subtotal += $net;
                $tax += $lineTax;
                $lineDiscountTotal += $lineDiscount;
            }

            $invoice->update([
                'subtotal' => $subtotal,
                'discount_amount' => $lineDiscountTotal + (float) $deliveryChallan->discount_amount,
                'tax_amount' => $tax,
                'grand_total' => max(0, $subtotal + $tax - (float) $deliveryChallan->discount_amount),
            ]);

            if ($invoice->party_id) {
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
                'converted_sales_invoice_id' => $invoice->id,
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
            abort_if($item->track_stock && (float)$item->current_stock < $qty, 422, "Insufficient stock for {$item->name}.");
            $serials = app(SerialUnitService::class);
            $pool = collect($serials->unitPool($challan->company_id, 'delivery_challan', $challan->id)[$item->id] ?? [])
                ->map(function($unit) use ($reservedKeys) { if (in_array($unit['key'] ?? null,$reservedKeys,true)) $unit['sold']=true; return $unit; })->all();
            $requested = json_decode($request->selected_units[$i] ?? '[]', true) ?: [];
            $selectedUnits = $item->track_stock ? $serials->reconcile($requested, $pool, (int)$qty, $serials->isGpsItem($item)) : [];
            abort_if($item->track_stock && count($selectedUnits) !== (int)$qty, 422, "{$item->name} ke liye {$qty} available serial/VTS units required hain.");
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

            $accounting->moveStock($item, [
                'party_id' => $challan->party_id, 'movement_date' => $challan->challan_date,
                'movement_type' => 'delivery_challan', 'direction' => 'out', 'quantity' => $qty,
                'unit_price' => $item->purchase_price, 'total_value' => $qty * (float)$item->purchase_price,
                'reference_type' => DeliveryChallan::class, 'reference_id' => $challan->id,
                'reference_no' => $challan->challan_no, 'description' => 'Delivery challan stock dispatch.',
                'movement_units' => $selectedUnits,
            ]);

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
            $accounting->moveStock($line->item, [
                'party_id' => $challan->party_id, 'movement_date' => now()->toDateString(),
                'movement_type' => $type, 'direction' => 'in', 'quantity' => (float)$line->quantity,
                'unit_price' => $line->item->purchase_price,
                'total_value' => (float)$line->quantity * (float)$line->item->purchase_price,
                'reference_type' => DeliveryChallan::class, 'reference_id' => $challan->id,
                'reference_no' => $challan->challan_no, 'description' => 'Delivery challan stock reversal.',
                'movement_units' => $line->selected_units ?? [],
            ]);
        }
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
