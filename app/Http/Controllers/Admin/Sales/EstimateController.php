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

    public function convert(Estimate $estimate, AccountingService $accounting, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($estimate);
        abort_if($estimate->status === 'cancelled', 422, 'Cancelled estimate cannot be converted.');
        abort_if($estimate->status === 'converted', 422, 'Estimate already converted.');

        DB::transaction(function () use ($estimate, $accounting) {
            $estimate->load(['items.item','party']);

            $invoice = SalesInvoice::create([
                'company_id' => $estimate->company_id,
                'party_id' => $estimate->party_id,
                'cost_center_id' => $estimate->cost_center_id,
                'sub_cost_center_id' => $estimate->sub_cost_center_id,
                'sale_type' => 'credit',
                'invoice_no' => $this->nextSaleNo(),
                'billing_date' => now()->toDateString(),
                'reference_no' => $estimate->estimate_no,
                'phone' => $estimate->phone,
                'billing_address' => $estimate->billing_address,
                'shipping_address' => $estimate->shipping_address,
                'subtotal' => $estimate->subtotal,
                'discount_amount' => $estimate->discount_amount,
                'tax_amount' => $estimate->tax_amount,
                'grand_total' => $estimate->grand_total,
                'notes' => $estimate->notes,
                'terms' => $estimate->terms,
                'status' => 'posted',
                'created_by' => auth()->id(),
            ]);

            foreach ($estimate->items as $line) {
                $item = $line->item;
                abort_if($item->track_stock && (float) $item->current_stock < (float) $line->quantity, 422, "Insufficient stock for {$item->name}");

                SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,
                    'item_id' => $line->item_id,
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'unit' => $line->unit,
                    'unit_price' => $line->unit_price,
                    'discount_type' => $line->discount_type,
                    'discount_value' => $line->discount_value,
                    'discount_amount' => $line->discount_amount,
                    'tax_percent' => $line->tax_percent,
                    'tax_amount' => $line->tax_amount,
                    'line_total' => $line->line_total,
                ]);

                $accounting->moveStock($item, [
                    'party_id' => $invoice->party_id,
                    'movement_date' => $invoice->billing_date,
                    'movement_type' => 'sale',
                    'direction' => 'out',
                    'quantity' => $line->quantity,
                    'unit_price' => $item->purchase_price,
                    'total_value' => (float) $line->quantity * (float) $item->purchase_price,
                    'reference_type' => SalesInvoice::class,
                    'reference_id' => $invoice->id,
                    'reference_no' => $invoice->invoice_no,
                    'description' => 'Sales stock out from estimate conversion.',
                ]);
            }

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
