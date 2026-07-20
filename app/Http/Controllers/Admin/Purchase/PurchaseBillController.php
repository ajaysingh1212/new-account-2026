<?php

namespace App\Http\Controllers\Admin\Purchase;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\CostCenter;
use App\Models\Item;
use App\Models\Party;
use App\Models\PartyAdvanceAllocation;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\SubCostCenter;
use App\Models\TermsTemplate;
use App\Services\AccountingService;
use App\Services\EntryVisibilityService;
use App\Services\PartyAdvanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseBillController extends Controller
{
    public function index(EntryVisibilityService $visibility)
    {
        $bills = $visibility->scopeForUser(
            PurchaseBill::with(['party','creator','items.item'])->latest(),
            PurchaseBill::class
        )->get();
        return view('admin.purchases.index', compact('bills'));
    }

    public function create()
    {
        return view('admin.purchases.create', $this->formData());
    }

    public function edit(PurchaseBill $purchase, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($purchase);
        if ($purchase->source_sales_invoice_id) {
            $purchase->load(['sourceSalesInvoice.creator', 'interCompanySourceCompany']);
            return redirect()
                ->route('admin.purchases.show', $purchase)
                ->withErrors([
                    'purchase' => 'Aap ye auto inter-company purchase edit nahi kar sakte. Sirf sale banane wali company sale invoice edit karegi. Sale creator: '
                        . ($purchase->sourceSalesInvoice?->creator?->name ?? 'Unknown')
                        . ' (' . ($purchase->sourceSalesInvoice?->creator?->email ?? 'No email') . ').',
                ]);
        }
        $purchase->load(['items.item', 'party']);
        $advanceApplications = PartyAdvanceAllocation::with('advance')
            ->where('company_id', $purchase->company_id)
            ->where('document_type', PurchaseBill::class)
            ->where('document_id', $purchase->id)
            ->orderBy('id')
            ->get()
            ->map(fn(PartyAdvanceAllocation $allocation) => [
                'id' => $allocation->id,
                'party_advance_id' => $allocation->party_advance_id,
                'amount' => (float) $allocation->amount,
                'advance' => [
                    'id' => $allocation->advance?->id,
                    'advance_date_label' => $allocation->advance?->advance_date?->format('d M Y'),
                    'reference_no' => $allocation->advance?->reference_no ?: '-',
                    'remaining_amount' => (float) ($allocation->advance?->remaining_amount ?? 0),
                    'payment_mode' => $allocation->advance?->payment_mode ?: '-',
                    'description' => $allocation->advance?->description ?: '-',
                ],
            ])
            ->values();

        return view('admin.purchases.edit', array_merge($this->formData($purchase), [
            'bill' => $purchase,
            'advanceApplications' => $advanceApplications,
        ]));
    }

    public function store(Request $request, AccountingService $accounting, EntryVisibilityService $visibility, PartyAdvanceService $advances)
    {
        $data = $this->validated($request);
        $companyId = auth()->user()->current_company_id;

        DB::transaction(function () use ($request, $data, $companyId, $accounting, $visibility, $advances) {
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

            if ($bill->party_id) {
                $advanceTotal = round((float) collect($request->input('advance_applications', []))->sum(fn($row) => (float) ($row['amount'] ?? 0)), 2);
                abort_if($advanceTotal > (float) $bill->grand_total + 0.01, 422, 'Advance settlement cannot exceed bill total.');
                $advances->applyForDocument(
                    (int) $bill->party_id,
                    'out',
                    PurchaseBill::class,
                    $bill->id,
                    $bill->invoice_no,
                    $request->input('advance_applications', [])
                );
            }

            $visibility->syncFromRequest($request, $bill);
        });

        return redirect()->route('admin.purchases.index')->with('success', 'Purchase posted with stock and party ledger.');
    }

    public function update(Request $request, PurchaseBill $purchase, AccountingService $accounting, EntryVisibilityService $visibility, PartyAdvanceService $advances)
    {
        $visibility->authorizeView($purchase);
        abort_if($purchase->source_sales_invoice_id, 403, 'Auto inter-company purchase direct edit nahi ho sakta. Source sale invoice edit karein.');
        $data = $this->validated($request);

        DB::transaction(function () use ($request, $data, $purchase, $accounting, $visibility, $advances) {
            $purchase->load('items');
            $oldValues = $purchase->replicate()->toArray();
            $oldValues['items'] = $purchase->items->toArray();

            $linesChanged = $this->lineSignature($purchase->items->toArray()) !== $this->requestLineSignature($request);
            $headerChanged = $this->purchaseHeaderChanged($purchase, $data);
            $repostStock = $linesChanged;
            $repostLedger = $linesChanged || $headerChanged;

            if ($repostLedger) {
                $advances->releaseForDocument(PurchaseBill::class, $purchase->id);
                $this->reversePurchaseLedger($purchase, $accounting);
            }

            if ($repostStock) {
                $this->reversePurchaseStock($purchase, $accounting);
            }

            $attachment = $purchase->attachment;
            if ($request->hasFile('attachment')) {
                $attachment = $request->file('attachment')->store('purchase-attachments', 'public');
            }

            if ($repostStock) {
                $purchase->items()->delete();
            }

            $purchase->update(array_merge($data, [
                'invoice_no' => $data['invoice_no'] ?: $purchase->invoice_no,
                'attachment' => $attachment,
            ]));

            if ($repostStock) {
                $totals = $this->storeLines($request, $purchase, $accounting, 'purchase');
                $purchase->update($totals);
            }

            if ($repostLedger && $purchase->purchase_type === 'credit' && $purchase->party_id) {
                $accounting->postPartyLedger($purchase->party, [
                    'entry_date' => $purchase->billing_date,
                    'entry_type' => 'purchase',
                    'reference_type' => PurchaseBill::class,
                    'reference_id' => $purchase->id,
                    'reference_no' => $purchase->invoice_no,
                    'credit' => $purchase->grand_total,
                    'debit' => 0,
                    'description' => 'Purchase bill payable updated.',
                ]);
            }

            if ($purchase->party_id && $repostLedger) {
                $advanceTotal = round((float) collect($request->input('advance_applications', []))->sum(fn($row) => (float) ($row['amount'] ?? 0)), 2);
                abort_if($advanceTotal > (float) $purchase->grand_total + 0.01, 422, 'Advance settlement cannot exceed bill total.');
                $advances->applyForDocument(
                    (int) $purchase->party_id,
                    'out',
                    PurchaseBill::class,
                    $purchase->id,
                    $purchase->invoice_no,
                    $request->input('advance_applications', [])
                );
            }

            $visibility->syncFromRequest($request, $purchase);
            if ($repostStock) {
                $this->logUpdate($purchase, $oldValues, $purchase->fresh('items')->toArray());
            } else {
                $this->logUpdate($purchase, $oldValues, $purchase->fresh()->toArray());
            }
        });

        return redirect()->route('admin.purchases.show', $purchase)->with('success', 'Purchase bill updated.');
    }

    public function show(PurchaseBill $purchase, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($purchase);
        $purchase->load(['party','items.item','sourceSalesInvoice.creator','interCompanySourceCompany']);
        $auditLogs = AuditLog::with(['user','company'])
            ->where('model', PurchaseBill::class)
            ->where('model_id', $purchase->id)
            ->latest('created_at')
            ->get();
        return view('admin.purchases.show', ['bill' => $purchase, 'auditLogs' => $auditLogs]);
    }

    public function print(PurchaseBill $purchase, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($purchase);
        $purchase->load(['party','items.item','company']);
        $bankAccount = BankAccount::where('company_id', $purchase->company_id)->where('print_on_invoice', true)->where('status', 'active')->first();
        $defaultTerms = TermsTemplate::where('company_id', $purchase->company_id)->where('status', 'active')->whereIn('document_type', ['purchase','all'])->orderByDesc('is_default')->first();
        return view('admin.purchases.print', ['bill' => $purchase, 'bankAccount' => $bankAccount, 'company' => $purchase->company, 'defaultTerms' => $defaultTerms]);
    }

    private function formData(?PurchaseBill $bill = null): array
    {
        $companyId = auth()->user()->current_company_id;
        return [
            'parties' => Party::where('company_id', $companyId)->orderBy('display_name')->get(),
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
            'invoiceNo' => $bill?->invoice_no ?? $this->nextNo(),
            'termsTemplates' => TermsTemplate::where('company_id', $companyId)
                ->where('status', 'active')
                ->whereIn('document_type', ['all', 'purchase'])
                ->orderByDesc('is_default')
                ->orderBy('title')
                ->get(),
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
            'selected_units.*' => ['nullable','string'],
            'advance_applications' => ['nullable','array'],
            'advance_applications.*.party_advance_id' => ['required_with:advance_applications','integer'],
            'advance_applications.*.amount' => ['required_with:advance_applications','numeric','min:0.01'],
        ]);
    }

    private function storeLines(Request $request, PurchaseBill $bill, AccountingService $accounting, string $mode): array
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
            $selectedUnits = $this->purchaseUnitsFromInput(
                $request->selected_units[$i] ?? null,
                $item,
                $qty,
                $bill->invoice_no,
                $price
            );

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
                'selected_units' => $selectedUnits,
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
                'movement_units' => $selectedUnits,
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

    private function reversePurchasePosting(PurchaseBill $bill, AccountingService $accounting): void
    {
        foreach ($bill->items as $line) {
            if (!$line->item) {
                continue;
            }

            $accounting->moveStock($line->item, [
                'party_id' => $bill->party_id,
                'movement_date' => now()->toDateString(),
                'movement_type' => 'purchase_reversal',
                'direction' => 'out',
                'quantity' => (float) $line->quantity,
                'unit_price' => $line->unit_price,
                'total_value' => $line->line_total,
                'reference_type' => PurchaseBill::class,
                'reference_id' => $bill->id,
                'reference_no' => $bill->invoice_no,
                'description' => 'Purchase stock reversal before update.',
                'movement_units' => $line->selected_units ?? [],
            ]);
        }

        if ($bill->purchase_type === 'credit' && $bill->party_id) {
            $accounting->postPartyLedger($bill->party, [
                'entry_date' => now()->toDateString(),
                'entry_type' => 'purchase_reversal',
                'reference_type' => PurchaseBill::class,
                'reference_id' => $bill->id,
                'reference_no' => $bill->invoice_no,
                'credit' => 0,
                'debit' => $bill->grand_total,
                'description' => 'Purchase ledger reversal before update.',
            ]);
        }
    }

    private function reversePurchaseStock(PurchaseBill $bill, AccountingService $accounting): void
    {
        foreach ($bill->items as $line) {
            if (!$line->item) {
                continue;
            }

            $accounting->moveStock($line->item, [
                'party_id' => $bill->party_id,
                'movement_date' => now()->toDateString(),
                'movement_type' => 'purchase_reversal',
                'direction' => 'out',
                'quantity' => (float) $line->quantity,
                'unit_price' => $line->unit_price,
                'total_value' => $line->line_total,
                'reference_type' => PurchaseBill::class,
                'reference_id' => $bill->id,
                'reference_no' => $bill->invoice_no,
                'description' => 'Purchase stock reversal before update.',
                'movement_units' => $line->selected_units ?? [],
            ]);
        }
    }

    private function reversePurchaseLedger(PurchaseBill $bill, AccountingService $accounting): void
    {
        if ($bill->purchase_type === 'credit' && $bill->party_id) {
            $accounting->postPartyLedger($bill->party, [
                'entry_date' => now()->toDateString(),
                'entry_type' => 'purchase_reversal',
                'reference_type' => PurchaseBill::class,
                'reference_id' => $bill->id,
                'reference_no' => $bill->invoice_no,
                'credit' => 0,
                'debit' => $bill->grand_total,
                'description' => 'Purchase ledger reversal before update.',
            ]);
        }
    }

    private function purchaseHeaderChanged(PurchaseBill $purchase, array $data): bool
    {
        return (string) $purchase->purchase_type !== (string) ($data['purchase_type'] ?? $purchase->purchase_type)
            || (int) $purchase->party_id !== (int) ($data['party_id'] ?? $purchase->party_id);
    }

    private function requestLineSignature(Request $request): string
    {
        $payload = [];
        foreach ((array) $request->input('item_id', []) as $i => $itemId) {
            $payload[] = [
                'item_id' => (int) $itemId,
                'quantity' => (float) ($request->input("quantity.$i") ?? 0),
                'unit_price' => (float) ($request->input("unit_price.$i") ?? 0),
                'discount_type' => (string) ($request->input("discount_type.$i") ?? 'percent'),
                'discount_value' => (float) ($request->input("discount_value.$i") ?? 0),
                'tax_percent' => (float) ($request->input("tax_percent.$i") ?? 0),
            ];
        }

        return md5(json_encode($payload));
    }

    private function lineSignature(array $lines): string
    {
        $payload = collect($lines)->map(fn($line) => [
            'item_id' => (int) ($line['item_id'] ?? 0),
            'quantity' => (float) ($line['quantity'] ?? 0),
            'unit_price' => (float) ($line['unit_price'] ?? 0),
            'discount_type' => (string) ($line['discount_type'] ?? 'percent'),
            'discount_value' => (float) ($line['discount_value'] ?? 0),
            'tax_percent' => (float) ($line['tax_percent'] ?? 0),
        ])->values()->all();

        return md5(json_encode($payload));
    }

    private function logUpdate(PurchaseBill $bill, array $oldValues, array $newValues): void
    {
        $user = auth()->user();
        AuditLog::log('updated', [
            'model' => PurchaseBill::class,
            'model_id' => $bill->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => sprintf(
                'Purchase bill %s updated by %s (%s) for company %s.',
                $bill->invoice_no,
                $user?->name ?? 'System',
                $user?->rolesForCompany($bill->company_id)->pluck('name')->join(', ') ?: 'No role',
                $user?->currentCompany?->name ?? 'Unknown company'
            ),
        ]);
    }

    private function purchaseUnitsFromInput(?string $raw, Item $item, float $qty, string $invoiceNo, float $price): array
    {
        $rows = collect(preg_split('/\r\n|\r|\n|,/', (string) $raw))
            ->map(fn($row) => trim($row))
            ->filter()
            ->values();

        if ($rows->isEmpty()) {
            return [];
        }

        $wholeQty = (int) $qty;
        abort_if((float) $wholeQty !== $qty, 422, "Serial tracking requires whole quantity for {$item->name}.");
        abort_if($rows->count() !== $wholeQty, 422, "Enter exactly {$wholeQty} serial/SKU/VTS value(s) for {$item->name}.");

        return $rows->map(function (string $row, int $index) use ($item, $invoiceNo, $price) {
            $parts = array_map('trim', explode('|', $row));
            $serial = $parts[0] ?? null;
            $vts = $parts[1] ?? null;
            $sku = $parts[2] ?? null;

            return [
                'key' => 'PUR-' . $invoiceNo . '-' . $item->id . '-' . ($index + 1) . '-' . md5($row),
                'item_id' => $item->id,
                'item_name' => $item->name,
                'serial_no' => $serial ?: null,
                'vts_sim' => $vts ?: null,
                'sku' => $sku ?: ($item->sku ?: null),
                'batch_no' => $invoiceNo,
                'production_batch_no' => $invoiceNo,
                'cost_per_unit' => $price,
            ];
        })->all();
    }

    private function nextNo(): string
    {
        return str_pad((string) (PurchaseBill::where('company_id', auth()->user()->current_company_id)->withTrashed()->count() + 1), 8, '0', STR_PAD_LEFT);
    }
}

