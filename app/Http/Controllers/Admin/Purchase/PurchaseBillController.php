<?php

namespace App\Http\Controllers\Admin\Purchase;

use App\Http\Controllers\Controller;
use App\Models\CostCenter;
use App\Models\Item;
use App\Models\Party;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\SubCostCenter;
use App\Services\AccountingService;
use App\Services\EntryVisibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseBillController extends Controller
{
    public function index(EntryVisibilityService $visibility)
    {
        $bills = $visibility->scopeForUser(
            PurchaseBill::with(['party','creator'])->latest(),
            PurchaseBill::class
        )->get();
        return view('admin.purchases.index', compact('bills'));
    }

    public function create()
    {
        return view('admin.purchases.create', $this->formData());
    }

    public function store(Request $request, AccountingService $accounting, EntryVisibilityService $visibility)
    {
        $data = $this->validated($request);
        $companyId = auth()->user()->current_company_id;

        DB::transaction(function () use ($request, $data, $companyId, $accounting, $visibility) {
            $attachment = $request->hasFile('attachment')
                ? $request->file('attachment')->store('purchase-attachments', 'public')
                : null;
            $bill = PurchaseBill::create(array_merge($data, [
                'company_id' => $companyId,
                'invoice_no' => $data['invoice_no'] ?: $this->nextNo(),
                'attachment' => $attachment,
                'created_by' => auth()->id(),
            ]));

            $totals = $this->storeLines($request, $bill, $accounting, 'purchase');
            $bill->update($totals);

            if ($bill->purchase_type === 'credit' && $bill->party_id) {
                $accounting->postPartyLedger($bill->party, [
                    'entry_date' => $bill->billing_date,
                    'entry_type' => 'purchase',
                    'reference_type' => PurchaseBill::class,
                    'reference_id' => $bill->id,
                    'reference_no' => $bill->invoice_no,
                    'credit' => $bill->grand_total,
                    'debit' => 0,
                    'description' => 'Purchase bill payable.',
                ]);
            }

            $visibility->syncFromRequest($request, $bill);
        });

        return redirect()->route('admin.purchases.index')->with('success', 'Purchase posted with stock and party ledger.');
    }

    public function show(PurchaseBill $purchase, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($purchase);
        $purchase->load(['party','items.item']);
        return view('admin.purchases.show', ['bill' => $purchase]);
    }

    public function print(PurchaseBill $purchase, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($purchase);
        $purchase->load(['party','items.item']);
        return view('admin.purchases.print', ['bill' => $purchase]);
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

    private function validated(Request $request): array
    {
        return $request->validate([
            'party_id' => ['nullable','exists:parties,id'],
            'cost_center_id' => ['nullable','exists:cost_centers,id'],
            'sub_cost_center_id' => ['nullable','exists:sub_cost_centers,id'],
            'purchase_type' => ['required','in:credit,cash'],
            'invoice_no' => ['nullable','max:20'],
            'supplier_bill_no' => ['nullable','max:255'],
            'billing_date' => ['required','date'],
            'purchase_bill_date' => ['nullable','date'],
            'reference_no' => ['nullable','max:255'],
            'docket_no' => ['nullable','max:255'],
            'e_bill_no' => ['nullable','max:255'],
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
            'tax_percent.*' => ['nullable','numeric','min:0'],
            'discount_value.*' => ['nullable','numeric','min:0'],
        ]);
    }

    private function storeLines(Request $request, PurchaseBill $bill, AccountingService $accounting, string $mode): array
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
            PurchaseBillItem::create([
                'purchase_bill_id' => $bill->id,
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
                'party_id' => $bill->party_id,
                'movement_date' => $bill->billing_date,
                'movement_type' => 'purchase',
                'direction' => 'in',
                'quantity' => $qty,
                'unit_price' => $price,
                'total_value' => $total,
                'reference_type' => PurchaseBill::class,
                'reference_id' => $bill->id,
                'reference_no' => $bill->invoice_no,
                'description' => 'Purchase stock in.',
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
        return str_pad((string) (PurchaseBill::where('company_id', auth()->user()->current_company_id)->withTrashed()->count() + 1), 8, '0', STR_PAD_LEFT);
    }
}
