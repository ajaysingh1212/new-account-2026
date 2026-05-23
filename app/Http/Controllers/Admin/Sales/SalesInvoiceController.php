<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\CostCenter;
use App\Models\Item;
use App\Models\Party;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SubCostCenter;
use App\Services\AccountingService;
use App\Services\EntryVisibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesInvoiceController extends Controller
{
    public function index(EntryVisibilityService $visibility)
    {
        $invoices = $visibility->scopeForUser(
            SalesInvoice::with(['party','creator'])->latest(),
            SalesInvoice::class
        )->get();
        return view('admin.sales.index', compact('invoices'));
    }

    public function create()
    {
        return view('admin.sales.create', $this->formData());
    }

    public function store(Request $request, AccountingService $accounting, EntryVisibilityService $visibility)
    {
        $data = $request->validate([
            'party_id' => ['nullable','exists:parties,id'],
            'cost_center_id' => ['nullable','exists:cost_centers,id'],
            'sub_cost_center_id' => ['nullable','exists:sub_cost_centers,id'],
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
            'attachment' => ['nullable','file','max:4096'],
            'item_id' => ['required','array'],
            'item_id.*' => ['required','exists:items,id'],
            'quantity.*' => ['required','numeric','min:0.001'],
            'unit_price.*' => ['required','numeric','min:0'],
        ]);

        DB::transaction(function () use ($request, $data, $accounting, $visibility) {
            $attachment = $request->hasFile('attachment')
                ? $request->file('attachment')->store('sales-attachments', 'public')
                : null;
            $invoice = SalesInvoice::create(array_merge($data, [
                'company_id' => auth()->user()->current_company_id,
                'invoice_no' => $data['invoice_no'] ?: $this->nextNo(),
                'attachment' => $attachment,
                'created_by' => auth()->id(),
            ]));
            $totals = $this->storeLines($request, $invoice, $accounting);
            $invoice->update($totals);

            if ($invoice->sale_type === 'credit' && $invoice->party_id) {
                $accounting->postPartyLedger($invoice->party, [
                    'entry_date' => $invoice->billing_date,
                    'entry_type' => 'sale',
                    'reference_type' => SalesInvoice::class,
                    'reference_id' => $invoice->id,
                    'reference_no' => $invoice->invoice_no,
                    'debit' => $invoice->grand_total,
                    'credit' => 0,
                    'description' => 'Sales invoice receivable.',
                ]);
            }

            $visibility->syncFromRequest($request, $invoice);
        });

        return redirect()->route('admin.sales.index')->with('success', 'Sales invoice posted with stock and party ledger.');
    }

    public function show(SalesInvoice $sale, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($sale);
        $sale->load(['party','items.item']);
        return view('admin.sales.show', ['invoice' => $sale]);
    }

    public function print(SalesInvoice $sale, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($sale);
        $sale->load(['party','items.item']);
        return view('admin.sales.print', ['invoice' => $sale]);
    }

    private function formData(): array
    {
        $companyId = auth()->user()->current_company_id;
        return [
            'parties' => Party::where('company_id', $companyId)->orderBy('display_name')->get(),
            'items' => Item::where('company_id', $companyId)->where('status', 'active')->orderBy('name')->get(),
            'costCenters' => CostCenter::where('company_id', $companyId)->where('status', 'active')->get(),
            'subCostCenters' => SubCostCenter::where('company_id', $companyId)->where('status', 'active')->get(),
            'invoiceNo' => $this->nextNo(),
        ];
    }

    private function storeLines(Request $request, SalesInvoice $invoice, AccountingService $accounting): array
    {
        $subtotal = $tax = $lineDiscount = 0;
        foreach ($request->item_id as $i => $itemId) {
            $item = Item::findOrFail($itemId);
            $qty = (float) $request->quantity[$i];
            abort_if($item->track_stock && (float) $item->current_stock < $qty, 422, "Insufficient stock for {$item->name}");
            $price = (float) $request->unit_price[$i];
            $base = $qty * $price;
            $discount = (($request->discount_type[$i] ?? 'percent') === 'flat') ? (float) ($request->discount_value[$i] ?? 0) : $base * (float) ($request->discount_value[$i] ?? 0) / 100;
            $taxAmount = max(0, $base - $discount) * (float) ($request->tax_percent[$i] ?? 0) / 100;
            $total = max(0, $base - $discount) + $taxAmount;
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
                'tax_percent' => $request->tax_percent[$i] ?? 0,
                'tax_amount' => $taxAmount,
                'line_total' => $total,
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
                'description' => 'Sales stock out.',
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
        return str_pad((string) (SalesInvoice::where('company_id', auth()->user()->current_company_id)->withTrashed()->count() + 1), 8, '0', STR_PAD_LEFT);
    }
}
