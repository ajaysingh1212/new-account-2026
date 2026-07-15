<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\CostCenter;
use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\Item;
use App\Models\Party;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SubCostCenter;
use App\Services\AccountingService;
use App\Services\EntryVisibilityService;
use App\Services\SerialUnitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstimateController extends Controller
{
    public function index(EntryVisibilityService $visibility)
    {
        $estimates = $visibility->scopeForUser(
            Estimate::with(['party','convertedInvoice','creator'])->latest(),
            Estimate::class
        )->get();

        return view('admin.estimates.index', compact('estimates'));
    }

    public function create()
    {
        return view('admin.estimates.create', $this->formData());
    }

    public function store(Request $request, EntryVisibilityService $visibility)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($request, $data, $visibility) {
            $attachment = $request->hasFile('attachment')
                ? $request->file('attachment')->store('estimate-attachments', 'public')
                : null;

            $estimate = Estimate::create(array_merge($data, [
                'company_id' => auth()->user()->current_company_id,
                'estimate_no' => $data['estimate_no'] ?: $this->nextNo(),
                'attachment' => $attachment,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]));

            $estimate->update($this->storeLines($request, $estimate));
            $visibility->syncFromRequest($request, $estimate);
        });

        return redirect()->route('admin.estimates.index')->with('success', 'Estimate created successfully.');
    }

    public function show(Estimate $estimate, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($estimate);
        $estimate->load(['party','items.item','convertedInvoice']);

        return view('admin.estimates.show', compact('estimate'));
    }

    public function convertForm(Estimate $estimate, EntryVisibilityService $visibility, SerialUnitService $serialUnits)
    {
        $visibility->authorizeManage($estimate);
        abort_if($estimate->status === 'cancelled', 422, 'Cancelled estimate cannot be converted.');
        abort_if($estimate->status === 'converted', 422, 'Estimate already converted.');

        $estimate->load(['party', 'items.item']);
        $companyId = $estimate->company_id;
        $items = $estimate->items->map(function (EstimateItem $line) {
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
                'weight' => (float) ($item?->per_quantity_weight ?? 0),
                'current_stock' => (float) ($item?->current_stock ?? 0),
            ];
        })->values();

        return view('admin.sales.convert', [
            'sourceType' => 'estimate',
            'sourceLabel' => 'Estimate / Quotation',
            'source' => $estimate,
            'parties' => Party::where('company_id', $companyId)->where('status', 'active')->orderBy('display_name')->get(),
            'items' => Item::where('company_id', $companyId)->where('status', 'active')->whereHas('productType', fn($q) => $q->where('nature', 'finished_goods'))->orderBy('name')->get(),
            'lineData' => $items,
            'unitPool' => $serialUnits->unitPool($companyId, 'estimate_conversion', $estimate->converted_sales_invoice_id),
            'itemMeta' => Item::where('company_id', $companyId)->where('status', 'active')->get()->mapWithKeys(fn(Item $item) => [
                $item->id => [
                    'requires_gps' => $serialUnits->isGpsItem($item),
                    'weight' => (float) ($item->per_quantity_weight ?? 0),
                    'current_stock' => (float) ($item->current_stock ?? 0),
                ],
            ])->all(),
            'actionRoute' => route('admin.estimates.convert', $estimate),
            'backRoute' => route('admin.estimates.show', $estimate),
            'saleNo' => $this->nextSaleNo(),
        ]);
    }

    public function edit(Estimate $estimate, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($estimate);
        abort_if($estimate->status === 'converted', 422, 'Converted estimate cannot be edited.');
        $estimate->load('items');

        return view('admin.estimates.edit', array_merge($this->formData(), compact('estimate')));
    }

    public function update(Request $request, Estimate $estimate, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($estimate);
        abort_if($estimate->status === 'converted', 422, 'Converted estimate cannot be edited.');
        $data = $this->validated($request);

        DB::transaction(function () use ($request, $estimate, $data, $visibility) {
            $attachment = $request->hasFile('attachment')
                ? $request->file('attachment')->store('estimate-attachments', 'public')
                : $estimate->attachment;

            $estimate->update(array_merge($data, ['attachment' => $attachment]));
            $estimate->items()->delete();
            $estimate->update($this->storeLines($request, $estimate));
            $visibility->syncFromRequest($request, $estimate);
        });

        return redirect()->route('admin.estimates.show', $estimate)->with('success', 'Estimate updated successfully.');
    }

    public function print(Estimate $estimate, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($estimate);
        $estimate->load(['party','items.item']);

        return view('admin.estimates.print', compact('estimate'));
    }

    public function cancel(Estimate $estimate, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($estimate);
        abort_if($estimate->status === 'converted', 422, 'Converted estimate cannot be cancelled.');
        $estimate->update(['status' => 'cancelled']);

        return back()->with('success', 'Estimate cancelled.');
    }

    public function destroy(Estimate $estimate, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($estimate);
        abort_if($estimate->status === 'converted', 422, 'Converted estimate cannot be deleted.');
        $estimate->delete();

        return redirect()->route('admin.estimates.index')->with('success', 'Estimate deleted.');
    }

    public function convert(Request $request, Estimate $estimate, AccountingService $accounting, EntryVisibilityService $visibility, SerialUnitService $serialUnits)
    {
        $visibility->authorizeManage($estimate);
        abort_if($estimate->status === 'cancelled', 422, 'Cancelled estimate cannot be converted.');
        abort_if($estimate->status === 'converted', 422, 'Estimate already converted.');

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
            'item_id' => ['required','array'],
            'item_id.*' => ['required','exists:items,id'],
            'quantity.*' => ['required','numeric','min:0.001'],
            'unit_price.*' => ['required','numeric','min:0'],
            'unit.*' => ['nullable','string'],
            'description.*' => ['nullable','string'],
            'discount_type.*' => ['nullable','in:percent,flat'],
            'discount_value.*' => ['nullable','numeric','min:0'],
            'tax_mode.*' => ['nullable','in:with_gst,without_gst'],
            'tax_percent.*' => ['nullable','numeric','min:0'],
            'selected_units.*' => ['nullable','string'],
        ]);

        DB::transaction(function () use ($request, $estimate, $data, $accounting, $serialUnits) {
            $estimate->load(['items.item','party']);
            $unitPool = $serialUnits->unitPool($estimate->company_id, 'estimate_conversion', $estimate->converted_sales_invoice_id);

            $invoice = SalesInvoice::create([
                'company_id' => $estimate->company_id,
                'party_id' => $data['party_id'] ?: $estimate->party_id,
                'cost_center_id' => $estimate->cost_center_id,
                'sub_cost_center_id' => $estimate->sub_cost_center_id,
                'sale_type' => $data['sale_type'],
                'invoice_no' => $data['invoice_no'] ?: $this->nextSaleNo(),
                'billing_date' => $data['billing_date'],
                'reference_no' => $data['reference_no'] ?: $estimate->estimate_no,
                'phone' => $data['phone'] ?? $estimate->phone,
                'billing_address' => $data['billing_address'] ?? $estimate->billing_address,
                'shipping_address' => $data['shipping_address'] ?? $estimate->shipping_address,
                'subtotal' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'grand_total' => 0,
                'notes' => $data['notes'] ?? $estimate->notes,
                'terms' => $data['terms'] ?? $estimate->terms,
                'status' => 'posted',
                'created_by' => auth()->id(),
            ]);

            $subtotal = $tax = $lineDiscount = $totalWeight = 0;
            foreach ($request->item_id as $i => $itemId) {
                $item = Item::with('productType')->lockForUpdate()->findOrFail($itemId);
                $qty = (float) $request->quantity[$i];
                abort_if((float) ((int) $qty) !== $qty && $item->track_stock, 422, "Quantity must be a whole number for {$item->name}.");
                abort_if($item->track_stock && $item->productType?->nature !== 'finished_goods', 422, 'Only finished goods can be sold from Estimate conversion.');
                abort_if($item->track_stock && (float) $item->current_stock < $qty, 422, "Insufficient stock for {$item->name}");
                $selectedUnits = $item->track_stock
                    ? $serialUnits->reconcile(
                        json_decode($request->selected_units[$i] ?? '[]', true) ?: [],
                        $unitPool[$item->id] ?? [],
                        (int) $qty,
                        $serialUnits->isGpsItem($item)
                    )
                    : [];
                abort_if($item->track_stock && count($selectedUnits) !== (int) $qty, 422, "Select exactly {$qty} available unit(s) for {$item->name}.");
                abort_if($serialUnits->isGpsItem($item) && collect($selectedUnits)->contains(fn($unit) => empty($unit['vts_sim'])), 422, "VTS/SIM number is required for selected GPS units of {$item->name}.");

                $price = (float) $request->unit_price[$i];
                $base = $qty * $price;
                $discount = (($request->discount_type[$i] ?? 'percent') === 'flat') ? (float) ($request->discount_value[$i] ?? 0) : $base * (float) ($request->discount_value[$i] ?? 0) / 100;
                $taxMode = $request->tax_mode[$i] ?? 'with_gst';
                $taxPercent = $taxMode === 'with_gst' ? (float) ($request->tax_percent[$i] ?? 18) : 0;
                $grossAfterDiscount = max(0, $base - $discount);
                $taxAmount = $taxPercent > 0 ? $grossAfterDiscount * $taxPercent / (100 + $taxPercent) : 0;
                $lineTotal = $grossAfterDiscount;
                $lineWeight = $qty * (float) ($item->per_quantity_weight ?? 0);

                SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,
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
                    'line_total' => $lineTotal,
                    'line_weight' => $lineWeight,
                    'selected_units' => $selectedUnits,
                ]);

                $accounting->moveStock($item, [
                    'party_id' => $invoice->party_id,
                    'movement_date' => $invoice->billing_date,
                    'movement_type' => 'sale',
                    'direction' => 'out',
                    'quantity' => $qty,
                    'unit_price' => $item->purchase_price,
                    'total_value' => $qty * (float) $item->purchase_price,
                    'reference_type' => SalesInvoice::class,
                    'reference_id' => $invoice->id,
                    'reference_no' => $invoice->invoice_no,
                    'description' => 'Sales stock out from estimate conversion.',
                    'movement_units' => $selectedUnits,
                ]);

                $subtotal += max(0, $grossAfterDiscount - $taxAmount);
                $tax += $taxAmount;
                $lineDiscount += $discount;
                $totalWeight += $lineWeight;
            }

            $overallDiscount = (float) ($request->discount_amount ?? 0);
            $invoice->update([
                'subtotal' => $subtotal,
                'discount_amount' => $lineDiscount + $overallDiscount,
                'tax_amount' => $tax,
                'grand_total' => max(0, $subtotal + $tax - $overallDiscount),
                'total_weight' => $totalWeight,
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
                    'description' => "Sales invoice converted from estimate {$estimate->estimate_no}.",
                ]);
            }

            $estimate->update([
                'status' => 'converted',
                'converted_sales_invoice_id' => $invoice->id,
                'converted_at' => now(),
            ]);
        });

        return redirect()->route('admin.estimates.show', $estimate)->with('success', 'Estimate converted to sale.');
    }

    private function formData(): array
    {
        $companyId = auth()->user()->current_company_id;

        return [
            'parties' => Party::where('company_id', $companyId)->where('status', 'active')->orderBy('display_name')->get(),
            'items' => Item::where('company_id', $companyId)->where('status', 'active')->orderBy('name')->get(),
            'costCenters' => CostCenter::where('company_id', $companyId)->where('status', 'active')->get(),
            'subCostCenters' => SubCostCenter::where('company_id', $companyId)->where('status', 'active')->get(),
            'estimateNo' => $this->nextNo(),
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'party_id' => ['nullable','exists:parties,id'],
            'cost_center_id' => ['nullable','exists:cost_centers,id'],
            'sub_cost_center_id' => ['nullable','exists:sub_cost_centers,id'],
            'estimate_no' => ['nullable','max:30'],
            'estimate_date' => ['required','date'],
            'valid_until' => ['nullable','date'],
            'reference_no' => ['nullable','max:255'],
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
            'unit_price.*' => ['required','numeric','min:0'],
        ]);
    }

    private function storeLines(Request $request, Estimate $estimate): array
    {
        $subtotal = $tax = $lineDiscount = 0;
        foreach ($request->item_id as $i => $itemId) {
            $item = Item::findOrFail($itemId);
            $qty = (float) $request->quantity[$i];
            $price = (float) $request->unit_price[$i];
            $base = $qty * $price;
            $discount = (($request->discount_type[$i] ?? 'percent') === 'flat') ? (float) ($request->discount_value[$i] ?? 0) : $base * (float) ($request->discount_value[$i] ?? 0) / 100;
            $taxAmount = max(0, $base - $discount) * (float) ($request->tax_percent[$i] ?? 0) / 100;
            $total = max(0, $base - $discount) + $taxAmount;

            EstimateItem::create([
                'estimate_id' => $estimate->id,
                'item_id' => $item->id,
                'description' => $request->description[$i] ?? $item->description,
                'quantity' => $qty,
                'unit' => $request->unit[$i] ?? $item->unit,
                'unit_price' => $price,
                'discount_type' => $request->discount_type[$i] ?? 'percent',
                'discount_value' => $request->discount_value[$i] ?? 0,
                'discount_amount' => $discount,
                'tax_percent' => $request->tax_percent[$i] ?? 0,
                'tax_amount' => $taxAmount,
                'line_total' => $total,
            ]);

            $subtotal += $base;
            $tax += $taxAmount;
            $lineDiscount += $discount;
        }

        $overallDiscount = (float) ($request->discount_amount ?? 0);

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $lineDiscount + $overallDiscount,
            'tax_amount' => $tax,
            'grand_total' => max(0, $subtotal - $lineDiscount - $overallDiscount + $tax),
        ];
    }

    private function nextNo(): string
    {
        $next = Estimate::where('company_id', auth()->user()->current_company_id)->withTrashed()->count() + 1;
        return 'EST-' . now()->format('Y') . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    private function nextSaleNo(): string
    {
        return str_pad((string) (SalesInvoice::where('company_id', auth()->user()->current_company_id)->withTrashed()->count() + 1), 8, '0', STR_PAD_LEFT);
    }

    private function authorizeCompany(Estimate $estimate): void
    {
        abort_unless($estimate->company_id === auth()->user()->current_company_id || auth()->user()->isSuperAdmin(), 403);
    }
}
