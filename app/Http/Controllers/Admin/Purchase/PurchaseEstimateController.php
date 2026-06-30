<?php

namespace App\Http\Controllers\Admin\Purchase;

use App\Http\Controllers\Controller;
use App\Models\CostCenter;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\EntryVisibility;
use App\Models\Item;
use App\Models\Party;
use App\Models\PartyPayment;
use App\Models\PartyPaymentAllocation;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\PurchaseEstimate;
use App\Models\PurchaseEstimateItem;
use App\Models\SubCostCenter;
use App\Services\AccountingService;
use App\Services\EntryVisibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseEstimateController extends Controller
{
    public function index(EntryVisibilityService $visibility)
    {
        $estimates = $visibility->scopeForUser(
            PurchaseEstimate::with(['party','convertedBill','creator'])->latest(),
            PurchaseEstimate::class
        )->get();

        return view('admin.purchase-estimates.index', compact('estimates'));
    }

    public function create()
    {
        return view('admin.purchase-estimates.create', $this->formData());
    }

    public function store(Request $request, EntryVisibilityService $visibility)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($request, $data, $visibility) {
            $estimate = PurchaseEstimate::create(array_merge($data, [
                'company_id' => auth()->user()->current_company_id,
                'estimate_no' => $data['estimate_no'] ?: $this->nextNo(),
                'attachment' => $request->hasFile('attachment') ? $request->file('attachment')->store('purchase-estimate-attachments', 'public') : null,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]));

            $estimate->update($this->storeLines($request, $estimate));
            $visibility->syncFromRequest($request, $estimate);
        });

        return redirect()->route('admin.purchase-estimates.index')->with('success', 'Purchase estimate created successfully.');
    }

    public function show(PurchaseEstimate $purchaseEstimate, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($purchaseEstimate);
        $purchaseEstimate->load(['party','items.item','convertedBill','paymentBankAccount']);

        return view('admin.purchase-estimates.show', ['estimate' => $purchaseEstimate, 'bankAccounts' => BankAccount::where('company_id',$purchaseEstimate->company_id)->where('status','active')->get()]);
    }

    public function edit(PurchaseEstimate $purchaseEstimate, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($purchaseEstimate);
        abort_if(in_array($purchaseEstimate->status, ['transit','converted']), 422, 'Transit/converted purchase estimate cannot be edited.');
        $purchaseEstimate->load('items');

        return view('admin.purchase-estimates.edit', array_merge($this->formData(), ['estimate' => $purchaseEstimate]));
    }

    public function update(Request $request, PurchaseEstimate $purchaseEstimate, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($purchaseEstimate);
        abort_if(in_array($purchaseEstimate->status, ['transit','converted']), 422, 'Transit/converted purchase estimate cannot be edited.');
        $data = $this->validated($request);

        DB::transaction(function () use ($request, $purchaseEstimate, $data, $visibility) {
            $purchaseEstimate->update(array_merge($data, [
                'attachment' => $request->hasFile('attachment') ? $request->file('attachment')->store('purchase-estimate-attachments', 'public') : $purchaseEstimate->attachment,
            ]));
            $purchaseEstimate->items()->delete();
            $purchaseEstimate->update($this->storeLines($request, $purchaseEstimate));
            $visibility->syncFromRequest($request, $purchaseEstimate);
        });

        return redirect()->route('admin.purchase-estimates.show', $purchaseEstimate)->with('success', 'Purchase estimate updated successfully.');
    }

    public function print(PurchaseEstimate $purchaseEstimate, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($purchaseEstimate);
        $purchaseEstimate->load(['party','items.item','company']);

        return view('admin.purchase-estimates.print', ['estimate' => $purchaseEstimate]);
    }

    public function cancel(PurchaseEstimate $purchaseEstimate, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($purchaseEstimate);
        abort_if($purchaseEstimate->status === 'converted', 422, 'Converted purchase estimate cannot be cancelled.');
        $purchaseEstimate->update(['status' => 'cancelled']);

        return back()->with('success', 'Purchase estimate cancelled.');
    }

    public function destroy(PurchaseEstimate $purchaseEstimate, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($purchaseEstimate);
        abort_if($purchaseEstimate->status === 'converted', 422, 'Converted purchase estimate cannot be deleted.');
        $purchaseEstimate->delete();

        return redirect()->route('admin.purchase-estimates.index')->with('success', 'Purchase estimate deleted.');
    }

    public function transit(Request $request, PurchaseEstimate $purchaseEstimate, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($purchaseEstimate);
        abort_unless($purchaseEstimate->status === 'draft', 422, 'Only a draft estimate can move to transit.');
        $data = $request->validate([
            'payment_completed'=>['required','boolean'],
            'payment_bank_account_id'=>['nullable','required_if:payment_completed,1','exists:bank_accounts,id'],
            'payment_mode'=>['nullable','required_if:payment_completed,1','max:30'],
            'payment_reference'=>['nullable','required_if:payment_completed,1','max:255'],
        ]);
        if (!empty($data['payment_completed'])) {
            abort_unless(BankAccount::where('company_id',$purchaseEstimate->company_id)->whereKey($data['payment_bank_account_id'])->exists(), 422, 'Selected bank account does not belong to this company.');
        }
        $purchaseEstimate->update(array_merge($data,['status'=>'transit','transit_at'=>now()]));
        return back()->with('success','Items marked in transit. Incoming quantity is now visible in Stocks.');
    }

    public function convert(Request $request, PurchaseEstimate $purchaseEstimate, AccountingService $accounting, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($purchaseEstimate);
        abort_unless($purchaseEstimate->status === 'transit', 422, 'Move this estimate to transit before final purchase.');
        $received = $request->validate(['received_quantity'=>['required','array'],'received_quantity.*'=>['required','numeric','min:0']])['received_quantity'];

        DB::transaction(function () use ($purchaseEstimate, $accounting, $received) {
            $purchaseEstimate->load(['items.item','party']);

            $bill = PurchaseBill::create([
                'company_id' => $purchaseEstimate->company_id,
                'party_id' => $purchaseEstimate->party_id,
                'cost_center_id' => $purchaseEstimate->cost_center_id,
                'sub_cost_center_id' => $purchaseEstimate->sub_cost_center_id,
                'purchase_type' => $purchaseEstimate->payment_completed ? 'cash' : 'credit',
                'invoice_no' => $this->nextPurchaseNo($purchaseEstimate->company_id),
                'supplier_bill_no' => $purchaseEstimate->estimate_no,
                'billing_date' => now()->toDateString(),
                'purchase_bill_date' => now()->toDateString(),
                'reference_no' => $purchaseEstimate->estimate_no,
                'phone' => $purchaseEstimate->phone,
                'billing_address' => $purchaseEstimate->billing_address,
                'shipping_address' => $purchaseEstimate->shipping_address,
                'subtotal' => 0, 'discount_amount' => 0, 'tax_amount' => 0, 'grand_total' => 0,
                'notes' => $purchaseEstimate->notes,
                'terms' => $purchaseEstimate->terms,
                'status' => 'posted',
                'created_by' => auth()->id(),
            ]);
            $this->copyEstimateVisibilityToBill($purchaseEstimate, $bill);

            $subtotal = $tax = $grand = 0;
            foreach ($purchaseEstimate->items as $line) {
                $item = $line->item;
                abort_if($item?->productType?->nature === 'finished_goods', 422, 'Finished goods cannot be purchased. Use Production / CRM Assembly.');

                $qty = (float)($received[$line->id] ?? $line->quantity);
                abort_if($qty > (float)$line->quantity, 422, "Received quantity for {$item->name} cannot exceed ordered quantity.");
                $base = $qty * (float)$line->unit_price;
                $discount = $line->discount_type === 'flat' ? min((float)$line->discount_value,$base) : $base*(float)$line->discount_value/100;
                $taxAmount = max(0,$base-$discount)*(float)$line->tax_percent/100;
                $lineTotal = max(0,$base-$discount)+$taxAmount;
                $line->update(['received_quantity'=>$qty]);
                if ($qty <= 0) continue;
                PurchaseBillItem::create([
                    'purchase_bill_id' => $bill->id,
                    'item_id' => $line->item_id,
                    'description' => $line->description,
                    'quantity' => $qty,
                    'unit' => $line->unit,
                    'unit_price' => $line->unit_price,
                    'discount_type' => $line->discount_type,
                    'discount_value' => $line->discount_value,
                    'discount_amount' => $discount,
                    'tax_percent' => $line->tax_percent,
                    'tax_amount' => $taxAmount,
                    'line_total' => $lineTotal,
                ]);

                $accounting->moveStock($item, [
                    'party_id' => $bill->party_id,
                    'movement_date' => $bill->billing_date,
                    'movement_type' => 'purchase',
                    'direction' => 'in',
                    'quantity' => $qty,
                    'unit_price' => $line->unit_price,
                    'total_value' => $lineTotal,
                    'reference_type' => PurchaseBill::class,
                    'reference_id' => $bill->id,
                    'reference_no' => $bill->invoice_no,
                    'description' => 'Purchase stock in from purchase estimate conversion.',
                ]);
                $subtotal += $base; $tax += $taxAmount; $grand += $lineTotal;
            }
            $bill->update(['subtotal'=>$subtotal,'discount_amount'=>max(0,$subtotal+$tax-$grand),'tax_amount'=>$tax,'grand_total'=>$grand]);

            if ($bill->party_id) {
                $accounting->postPartyLedger($bill->party, [
                    'entry_date' => $bill->billing_date,
                    'entry_type' => 'purchase',
                    'reference_type' => PurchaseBill::class,
                    'reference_id' => $bill->id,
                    'reference_no' => $bill->invoice_no,
                    'credit' => $grand,
                    'debit' => 0,
                    'description' => "Purchase bill converted from purchase estimate {$purchaseEstimate->estimate_no}.",
                ]);
                if ($purchaseEstimate->payment_completed) {
                    $account = BankAccount::lockForUpdate()->findOrFail($purchaseEstimate->payment_bank_account_id);
                    abort_if((float)$account->current_balance < $grand, 422, 'Selected bank account has insufficient balance for this paid purchase.');
                    $payment = PartyPayment::create(['company_id'=>$bill->company_id,'party_id'=>$bill->party_id,'bank_account_id'=>$account->id,
                        'payment_date'=>now()->toDateString(),'payment_type'=>'payment_out','reference_no'=>$purchaseEstimate->payment_reference,
                        'amount'=>$grand,'discount_amount'=>0,'total_amount'=>$grand,'payment_mode'=>$purchaseEstimate->payment_mode,
                        'description'=>'Payment for in-transit purchase '.$purchaseEstimate->estimate_no,'created_by'=>auth()->id()]);
                    PartyPaymentAllocation::create(['party_payment_id'=>$payment->id,'company_id'=>$bill->company_id,'party_id'=>$bill->party_id,
                        'bill_type'=>'purchase','bill_model'=>PurchaseBill::class,'bill_id'=>$bill->id,'bill_no'=>$bill->invoice_no,
                        'bill_date'=>$bill->billing_date,'bill_total'=>$grand,'amount'=>$grand]);
                    $accounting->postPartyLedger($bill->party,['entry_date'=>now()->toDateString(),'entry_type'=>'payment_out','reference_type'=>PartyPayment::class,
                        'reference_id'=>$payment->id,'reference_no'=>$payment->reference_no,'debit'=>$grand,'credit'=>0,'description'=>'Paid purchase amount.']);
                    $balance=(float)$account->current_balance-$grand;$account->update(['current_balance'=>$balance]);
                    BankTransaction::create(['company_id'=>$bill->company_id,'bank_account_id'=>$account->id,'party_id'=>$bill->party_id,
                        'transaction_date'=>now()->toDateString(),'transaction_type'=>'payment_out','direction'=>'out','amount'=>$grand,
                        'balance_after'=>$balance,'reference_no'=>$payment->reference_no,'payment_mode'=>$payment->payment_mode,
                        'reference_type'=>PartyPayment::class,'reference_id'=>$payment->id,'description'=>$payment->description,'created_by'=>auth()->id()]);
                }
            }

            $purchaseEstimate->update([
                'status' => 'converted',
                'converted_purchase_bill_id' => $bill->id,
                'converted_at' => now(),
            ]);
        });

        return redirect()->route('admin.purchase-estimates.show', $purchaseEstimate)->with('success', 'Purchase estimate converted to purchase bill.');
    }

    private function formData(): array
    {
        $companyId = auth()->user()->current_company_id;

        return [
            'parties' => Party::where('company_id', $companyId)->where('status', 'active')->orderBy('display_name')->get(),
            'items' => Item::where('company_id', $companyId)
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->whereDoesntHave('productType')
                        ->orWhereHas('productType', fn($type) => $type->where('nature', '<>', 'finished_goods'));
                })
                ->orderBy('name')
                ->get(),
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
            'tax_percent.*' => ['nullable','numeric','min:0'],
            'discount_value.*' => ['nullable','numeric','min:0'],
        ]);
    }

    private function storeLines(Request $request, PurchaseEstimate $estimate): array
    {
        $subtotal = $tax = $lineDiscount = 0;
        foreach ($request->item_id as $i => $itemId) {
            $item = Item::with('productType')->findOrFail($itemId);
            abort_if($item->productType?->nature === 'finished_goods', 422, 'Finished goods cannot be purchased. Use Production / CRM Assembly.');
            $qty = (float) $request->quantity[$i];
            $price = (float) $request->unit_price[$i];
            $base = $qty * $price;
            $discount = (($request->discount_type[$i] ?? 'percent') === 'flat') ? (float) ($request->discount_value[$i] ?? 0) : $base * (float) ($request->discount_value[$i] ?? 0) / 100;
            $taxAmount = max(0, $base - $discount) * (float) ($request->tax_percent[$i] ?? 0) / 100;
            $total = max(0, $base - $discount) + $taxAmount;

            PurchaseEstimateItem::create([
                'purchase_estimate_id' => $estimate->id,
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
        $next = PurchaseEstimate::where('company_id', auth()->user()->current_company_id)->withTrashed()->count() + 1;
        return 'PEST-' . now()->format('Y') . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    private function copyEstimateVisibilityToBill(PurchaseEstimate $estimate, PurchaseBill $bill): void
    {
        $visibility = EntryVisibility::where('entry_type', PurchaseEstimate::class)
            ->where('entry_id', $estimate->id)
            ->first();

        if (!$visibility) {
            return;
        }

        EntryVisibility::updateOrCreate(
            [
                'entry_type' => PurchaseBill::class,
                'entry_id' => $bill->id,
            ],
            [
                'company_id' => $bill->company_id,
                'visible_to_all_company' => $visibility->visible_to_all_company,
                'visible_to_roles' => $visibility->visible_to_roles ?? [],
                'visible_to_users' => $visibility->visible_to_users ?? [],
            ]
        );
    }

    private function nextPurchaseNo(int $companyId): string
    {
        return str_pad((string) (PurchaseBill::where('company_id', $companyId)->withTrashed()->count() + 1), 8, '0', STR_PAD_LEFT);
    }
}
