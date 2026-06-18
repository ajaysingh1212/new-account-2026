<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\CostCenter;
use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\Company;
use App\Models\CompanyMerge;
use App\Models\EntryVisibility;
use App\Models\Item;
use App\Models\Party;
use App\Models\ProductionBatch;
use App\Models\ProductType;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\Role;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SubCostCenter;
use App\Models\TermsTemplate;
use App\Models\User;
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

    public function edit(SalesInvoice $sale, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($sale);
        $sale->load(['items.item', 'party']);

        return view('admin.sales.edit', array_merge($this->formData($sale), [
            'invoice' => $sale,
        ]));
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
            'tax_mode.*' => ['nullable','in:with_gst,without_gst'],
            'tax_percent.*' => ['nullable','numeric','min:0'],
            'selected_units.*' => ['nullable','string'],
            'inter_company_transfer' => ['nullable','boolean'],
            'target_company_ids' => ['nullable','array'],
            'target_company_ids.*' => ['integer'],
            'purchase_visible_to_roles' => ['nullable','array'],
            'purchase_visible_to_users' => ['nullable','array'],
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
                'inter_company_transfer' => $request->boolean('inter_company_transfer'),
                'inter_company_target_company_ids' => $request->boolean('inter_company_transfer') ? $this->validatedTargetCompanyIds($request, auth()->user()->current_company_id) : null,
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

            if ($invoice->inter_company_transfer) {
                $this->createInterCompanyPurchases($invoice->fresh(['items.item', 'party']), $accounting, $request);
            }
        });

        return redirect()->route('admin.sales.index')->with('success', 'Sales invoice posted with stock and party ledger.');
    }

    public function update(Request $request, SalesInvoice $sale, AccountingService $accounting, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($sale);
        $data = $this->validated($request);

        DB::transaction(function () use ($request, $data, $sale, $accounting, $visibility) {
            $sale->load('items');
            $oldValues = $sale->replicate()->toArray();
            $oldValues['items'] = $sale->items->toArray();

            $this->reverseSalePosting($sale, $accounting);

            $attachment = $sale->attachment;
            if ($request->hasFile('attachment')) {
                $attachment = $request->file('attachment')->store('sales-attachments', 'public');
            }

            $sale->items()->delete();
            $sale->update(array_merge($data, [
                'invoice_no' => $data['invoice_no'] ?: $sale->invoice_no,
                'attachment' => $attachment,
                'inter_company_transfer' => $request->boolean('inter_company_transfer'),
                'inter_company_target_company_ids' => $request->boolean('inter_company_transfer') ? $this->validatedTargetCompanyIds($request, $sale->company_id) : null,
            ]));

            // The old lines are gone, so their serialised units are available again.
            // Build the pool once and reuse it while all replacement lines are stored.
            $unitPool = $this->finishedGoodsUnitPool($sale->company_id);
            $totals = $this->storeLines($request, $sale, $accounting, $unitPool);
            $sale->update($totals);

            if ($sale->sale_type === 'credit' && $sale->party_id) {
                $accounting->postPartyLedger($sale->party, [
                    'entry_date' => $sale->billing_date,
                    'entry_type' => 'sale',
                    'reference_type' => SalesInvoice::class,
                    'reference_id' => $sale->id,
                    'reference_no' => $sale->invoice_no,
                    'debit' => $sale->grand_total,
                    'credit' => 0,
                    'description' => 'Sales invoice receivable updated.',
                ]);
            }

            $visibility->syncFromRequest($request, $sale);
            if ($sale->inter_company_transfer) {
                $this->createInterCompanyPurchases($sale->fresh(['items.item', 'party']), $accounting, $request);
            } else {
                $this->removeInterCompanyPurchases($sale, $accounting);
            }
            $this->logUpdate($sale, $oldValues, $sale->fresh('items')->toArray());
        });

        return redirect()->route('admin.sales.show', $sale)->with('success', 'Sales invoice updated with stock and party ledger reposted.');
    }

    public function show(SalesInvoice $sale, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($sale);
        $sale->load(['party','items.item']);
        $auditLogs = AuditLog::with(['user','company'])
            ->where('model', SalesInvoice::class)
            ->where('model_id', $sale->id)
            ->latest('created_at')
            ->get();
        return view('admin.sales.show', ['invoice' => $sale, 'auditLogs' => $auditLogs]);
    }

    public function print(SalesInvoice $sale, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($sale);
        $sale->load(['party','items.item','company']);
        $bankAccount = BankAccount::where('company_id', $sale->company_id)->where('print_on_invoice', true)->where('status', 'active')->first();
        $defaultTerms = TermsTemplate::where('company_id', $sale->company_id)->where('status', 'active')->whereIn('document_type', ['sales','all'])->orderByDesc('is_default')->first();
        return view('admin.sales.print', ['invoice' => $sale, 'bankAccount' => $bankAccount, 'company' => $sale->company, 'defaultTerms' => $defaultTerms]);
    }

    private function formData(?SalesInvoice $invoice = null): array
    {
        $companyId = auth()->user()->current_company_id;
        $items = Item::where('company_id', $companyId)
            ->where('status', 'active')
            ->whereHas('productType', fn($q) => $q->where('nature', 'finished_goods'))
            ->orderBy('name')
            ->get();

        $mergedCompanies = Company::whereIn('id', CompanyMerge::getMergedCompanyIds($companyId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id','name','phone','gst_number']);

        return [
            'parties' => Party::where('company_id', $companyId)->orderBy('display_name')->get(),
            'items' => $items,
            'costCenters' => CostCenter::where('company_id', $companyId)->where('status', 'active')->get(),
            'subCostCenters' => SubCostCenter::where('company_id', $companyId)->where('status', 'active')->get(),
            'invoiceNo' => $invoice?->invoice_no ?? $this->nextNo(),
            'unitPool' => $this->finishedGoodsUnitPool($companyId, $invoice?->id),
            'itemMeta' => $items->mapWithKeys(fn(Item $item) => [
                $item->id => ['requires_gps' => $this->isGpsItem($item), 'weight' => (float) ($item->per_quantity_weight ?? 0)],
            ])->all(),
            'mergedCompanies' => $mergedCompanies,
            'interCompanyVisibility' => $this->interCompanyVisibilityData($mergedCompanies),
            'interCompanySelectedVisibility' => $this->interCompanySelectedVisibility($invoice),
            'termsTemplates' => TermsTemplate::where('company_id', $companyId)
                ->where('status', 'active')
                ->whereIn('document_type', ['all', 'sales'])
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
            'tax_mode.*' => ['nullable','in:with_gst,without_gst'],
            'tax_percent.*' => ['nullable','numeric','min:0'],
            'selected_units.*' => ['nullable','string'],
            'inter_company_transfer' => ['nullable','boolean'],
            'target_company_ids' => ['nullable','array'],
            'target_company_ids.*' => ['integer'],
            'purchase_visible_to_roles' => ['nullable','array'],
            'purchase_visible_to_users' => ['nullable','array'],
        ]);
    }

    private function storeLines(
        Request $request,
        SalesInvoice $invoice,
        AccountingService $accounting,
        ?array $unitPool = null
    ): array
    {
        $subtotal = $tax = $lineDiscount = $totalWeight = 0;
        $unitPool ??= $this->finishedGoodsUnitPool($invoice->company_id, $invoice->id);

        foreach ($request->item_id as $i => $itemId) {
            $item = Item::with('productType')->findOrFail($itemId);
            $qty = (float) $request->quantity[$i];
            abort_if($item->track_stock && (float) $item->current_stock < $qty, 422, "Insufficient stock for {$item->name}");
            abort_if($item->productType?->nature !== 'finished_goods', 422, 'Only finished goods can be sold from Sales.');
            abort_if((float) ((int) $qty) !== $qty, 422, "Quantity must be a whole number for {$item->name}.");

            $selectedUnits = $this->reconcileSelectedUnits(
                $this->decodeSelectedUnits($request->selected_units[$i] ?? null),
                $unitPool[$item->id] ?? [],
                (int) $qty,
                $this->isGpsItem($item)
            );
            abort_if($this->isGpsItem($item) && collect($selectedUnits)->contains(fn($unit) => empty($unit['vts_sim'])), 422, "VTS/SIM number is required for selected GPS units of {$item->name}.");
            $selectedKeys = collect($selectedUnits)->pluck('key')->filter()->values()->all();
            abort_if(count($selectedKeys) !== (int) $qty, 422, "Only " . count($selectedKeys) . " available finished goods unit(s) found for {$item->name}; {$qty} required.");
            $availableKeys = collect($unitPool[$item->id] ?? [])->where('sold', false)->pluck('key')->all();
            abort_if(count(array_diff($selectedKeys, $availableKeys)) > 0, 422, "One or more selected units for {$item->name} are already sold or invalid.");

            // Reserve units in memory so two lines of the same invoice cannot use
            // the same serial/batch unit.
            if (isset($unitPool[$item->id])) {
                foreach ($unitPool[$item->id] as &$poolUnit) {
                    if (in_array($poolUnit['key'], $selectedKeys, true)) {
                        $poolUnit['sold'] = true;
                    }
                }
                unset($poolUnit);
            }

            $price = (float) $request->unit_price[$i];
            $base = $qty * $price;
            $discount = (($request->discount_type[$i] ?? 'percent') === 'flat') ? (float) ($request->discount_value[$i] ?? 0) : $base * (float) ($request->discount_value[$i] ?? 0) / 100;
            $taxMode = $request->tax_mode[$i] ?? 'with_gst';
            $taxPercent = $taxMode === 'with_gst' ? (float) ($request->tax_percent[$i] ?? 18) : 0;
            $grossAfterDiscount = max(0, $base - $discount);
            $taxAmount = $taxPercent > 0 ? $grossAfterDiscount * $taxPercent / (100 + $taxPercent) : 0;
            $taxableAmount = $grossAfterDiscount - $taxAmount;
            $total = $grossAfterDiscount;
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
                'line_total' => $total,
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
                'description' => 'Sales stock out.',
            ]);
            $subtotal += $taxableAmount;
            $tax += $taxAmount;
            $lineDiscount += $discount;
            $totalWeight += $lineWeight;
        }
        $overallDiscount = (float) ($request->discount_amount ?? 0);
        return [
            'subtotal' => $subtotal,
            'discount_amount' => $lineDiscount + $overallDiscount,
            'tax_amount' => $tax,
            'grand_total' => max(0, $subtotal + $tax - $overallDiscount),
            'total_weight' => $totalWeight,
        ];
    }

    private function reverseSalePosting(SalesInvoice $invoice, AccountingService $accounting): void
    {
        foreach ($invoice->items as $line) {
            if (!$line->item) {
                continue;
            }

            $accounting->moveStock($line->item, [
                'party_id' => $invoice->party_id,
                'movement_date' => now()->toDateString(),
                'movement_type' => 'sale_reversal',
                'direction' => 'in',
                'quantity' => (float) $line->quantity,
                'unit_price' => $line->item->purchase_price,
                'total_value' => (float) $line->quantity * (float) $line->item->purchase_price,
                'reference_type' => SalesInvoice::class,
                'reference_id' => $invoice->id,
                'reference_no' => $invoice->invoice_no,
                'description' => 'Sales stock reversal before update.',
            ]);
        }

        if ($invoice->sale_type === 'credit' && $invoice->party_id) {
            $accounting->postPartyLedger($invoice->party, [
                'entry_date' => now()->toDateString(),
                'entry_type' => 'sale_reversal',
                'reference_type' => SalesInvoice::class,
                'reference_id' => $invoice->id,
                'reference_no' => $invoice->invoice_no,
                'debit' => 0,
                'credit' => $invoice->grand_total,
                'description' => 'Sales ledger reversal before update.',
            ]);
        }
    }

    private function finishedGoodsUnitPool(int $companyId, ?int $currentInvoiceId = null): array
    {
        $soldKeys = SalesInvoiceItem::whereHas('salesInvoice', function ($q) use ($companyId, $currentInvoiceId) {
                $q->where('company_id', $companyId);
                if ($currentInvoiceId) {
                    $q->where('id', '<>', $currentInvoiceId);
                }
            })
            ->get()
            ->flatMap(fn($line) => collect($line->selected_units ?? [])->pluck('key'))
            ->filter()
            ->all();

        $producedUnits = ProductionBatch::with('finishedItem')
            ->where('company_id', $companyId)
            ->where('status', 'posted')
            ->get()
            ->flatMap(function (ProductionBatch $batch) use ($soldKeys) {
                return collect($batch->units_data ?? [])
                    ->filter(fn($unit) => empty($unit['reverted_at']))
                    ->map(function ($unit, $index) use ($batch, $soldKeys) {
                    $key = $batch->id . '-' . $index;
                    return array_merge($unit, [
                        'key' => $key,
                        'item_id' => $batch->finished_item_id,
                        'item_name' => $batch->finishedItem?->name,
                        'production_batch_no' => $batch->batch_no,
                        'production_date' => $batch->production_date?->format('Y-m-d'),
                        'cost_per_unit' => (float) $batch->cost_per_unit,
                        'sold' => in_array($key, $soldKeys, true),
                    ]);
                });
            })
            ->groupBy('item_id')
            ->map(fn($rows) => $rows->values()->all())
            ->all();

        $purchasedUnits = $this->purchasedFinishedGoodsUnitPool($companyId, $soldKeys);
        foreach ($purchasedUnits as $itemId => $rows) {
            $producedUnits[$itemId] = array_values(array_merge($producedUnits[$itemId] ?? [], $rows));
        }

        return $producedUnits;
    }

    private function purchasedFinishedGoodsUnitPool(int $companyId, array $soldKeys): array
    {
        return PurchaseBillItem::with(['purchaseBill', 'item.productType'])
            ->whereHas('purchaseBill', fn($q) => $q->where('company_id', $companyId))
            ->whereHas('item.productType', fn($q) => $q->where('nature', 'finished_goods'))
            ->get()
            ->flatMap(function (PurchaseBillItem $line) use ($soldKeys) {
                return collect($line->selected_units ?? [])->map(function ($unit, $index) use ($line, $soldKeys) {
                    $key = 'PBI-' . $line->id . '-' . $index;
                    return array_merge($unit, [
                        'key' => $key,
                        'item_id' => $line->item_id,
                        'item_name' => $line->item?->name,
                        'production_batch_no' => $unit['production_batch_no'] ?? $line->purchaseBill?->invoice_no,
                        'production_date' => $line->purchaseBill?->billing_date?->format('Y-m-d'),
                        'cost_per_unit' => (float) $line->unit_price,
                        'sold' => in_array($key, $soldKeys, true),
                    ]);
                });
            })
            ->groupBy('item_id')
            ->map(fn($rows) => $rows->values()->all())
            ->all();
    }

    private function decodeSelectedUnits(?string $json): array
    {
        $units = json_decode($json ?: '[]', true);
        return is_array($units) ? array_values($units) : [];
    }

    private function normalizeSelectedUnits(array $selectedUnits, array $poolUnits): array
    {
        $availablePoolUnits = collect($poolUnits)->where('sold', false)->values();

        return collect($selectedUnits)->map(function (array $selected) use ($availablePoolUnits) {
            $selectedKey = $selected['key'] ?? null;
            $poolMatch = $availablePoolUnits->firstWhere('key', $selectedKey);
            if ($poolMatch) {
                return $poolMatch;
            }

            $sameUnit = $availablePoolUnits->first(function ($unit) use ($selected) {
                foreach (['serial_no', 'vts_sim', 'buyer_code', 'batch_no', 'production_batch_no'] as $field) {
                    if (!empty($selected[$field]) && !empty($unit[$field]) && (string) $selected[$field] !== (string) $unit[$field]) {
                        return false;
                    }
                }

                return collect(['serial_no', 'vts_sim', 'buyer_code', 'batch_no', 'production_batch_no'])
                    ->contains(fn($field) => !empty($selected[$field]) && !empty($unit[$field]));
            });

            return $sameUnit;
        })->filter()->unique('key')->values()->all();
    }

    private function reconcileSelectedUnits(
        array $selectedUnits,
        array $poolUnits,
        int $quantity,
        bool $requiresGps
    ): array {
        $availableUnits = collect($poolUnits)
            ->where('sold', false)
            ->when($requiresGps, fn($units) => $units->filter(fn($unit) => !empty($unit['vts_sim'])))
            ->values();

        $selected = collect($this->normalizeSelectedUnits($selectedUnits, $availableUnits->all()))
            ->when($requiresGps, fn($units) => $units->filter(fn($unit) => !empty($unit['vts_sim'])))
            ->take($quantity)
            ->values();

        if ($selected->count() < $quantity) {
            $selectedKeys = $selected->pluck('key')->all();
            $selected = $selected->concat(
                $availableUnits
                    ->reject(fn($unit) => in_array($unit['key'], $selectedKeys, true))
                    ->take($quantity - $selected->count())
            );
        }

        return $selected->take($quantity)->values()->all();
    }

    private function validatedTargetCompanyIds(Request $request, int $companyId): array
    {
        $allowed = CompanyMerge::getMergedCompanyIds($companyId);
        $selected = array_values(array_unique(array_map('intval', $request->input('target_company_ids', []))));
        abort_if(empty($selected), 422, 'Select at least one merged company for inter-company sale.');
        abort_if(count(array_diff($selected, $allowed)) > 0, 422, 'Selected company is not merged with current company.');
        return $selected;
    }

    private function createInterCompanyPurchases(SalesInvoice $invoice, AccountingService $accounting, Request $request): void
    {
        $sourceCompany = Company::findOrFail($invoice->company_id);
        $targetIds = array_map('intval', $invoice->inter_company_target_company_ids ?? []);

        PurchaseBill::with(['items.item', 'party'])
            ->where('source_sales_invoice_id', $invoice->id)
            ->whereNotIn('company_id', $targetIds)
            ->get()
            ->each(function (PurchaseBill $purchase) use ($accounting) {
                $this->reverseInterCompanyPurchase($purchase, $accounting);
                $purchase->items()->delete();
                $purchase->delete();
                EntryVisibility::where('entry_type', PurchaseBill::class)->where('entry_id', $purchase->id)->delete();
            });

        foreach ($targetIds as $targetCompanyId) {
            $targetParty = $this->supplierPartyForCompany($targetCompanyId, $sourceCompany);
            $purchase = PurchaseBill::where('company_id', $targetCompanyId)
                ->where('source_sales_invoice_id', $invoice->id)
                ->first();

            $oldValues = null;
            if ($purchase) {
                $purchase->load(['items.item', 'party']);
                $oldValues = $purchase->replicate()->toArray();
                $oldValues['items'] = $purchase->items->toArray();
                $this->reverseInterCompanyPurchase($purchase, $accounting);
                $purchase->items()->delete();
                $purchase->update($this->interCompanyPurchasePayload($invoice, $sourceCompany, $targetParty));
            } else {
                $purchase = PurchaseBill::create($this->interCompanyPurchasePayload($invoice, $sourceCompany, $targetParty, $targetCompanyId));
            }

            foreach ($invoice->items as $line) {
                $targetItem = $this->targetItemForSaleLine($line->item, $targetCompanyId);
                $purchaseLine = PurchaseBillItem::create([
                    'purchase_bill_id' => $purchase->id,
                    'item_id' => $targetItem->id,
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
                    'selected_units' => $line->selected_units,
                ]);

                $movement = $accounting->moveStock($targetItem, [
                    'party_id' => $targetParty->id,
                    'movement_date' => $purchase->billing_date,
                    'movement_type' => 'inter_company_purchase',
                    'direction' => 'in',
                    'quantity' => (float) $purchaseLine->quantity,
                    'unit_price' => $purchaseLine->unit_price,
                    'total_value' => $purchaseLine->line_total,
                    'reference_type' => PurchaseBill::class,
                    'reference_id' => $purchase->id,
                    'reference_no' => $purchase->invoice_no,
                    'description' => 'Auto purchase stock in from inter-company sale.',
                ]);
                $this->syncInterCompanyVisibilityForEntry($request, $targetItem, $targetCompanyId);
                if ($movement) {
                    $this->syncInterCompanyVisibilityForEntry($request, $movement, $targetCompanyId);
                }
            }

            $accounting->postPartyLedger($targetParty, [
                'entry_date' => $purchase->billing_date,
                'entry_type' => 'purchase',
                'reference_type' => PurchaseBill::class,
                'reference_id' => $purchase->id,
                'reference_no' => $purchase->invoice_no,
                'credit' => $purchase->grand_total,
                'debit' => 0,
                'description' => 'Auto inter-company purchase payable.',
            ]);

            $this->syncPurchaseVisibility($request, $purchase, $targetCompanyId);

            if ($oldValues) {
                AuditLog::log('updated', [
                    'company_id' => $purchase->company_id,
                    'model' => PurchaseBill::class,
                    'model_id' => $purchase->id,
                    'old_values' => $oldValues,
                    'new_values' => $purchase->fresh('items')->toArray(),
                    'description' => 'Auto inter-company purchase updated from source sale edit by ' . (auth()->user()?->name ?? 'System') . '.',
                ]);
            }
        }
    }

    private function removeInterCompanyPurchases(SalesInvoice $invoice, AccountingService $accounting): void
    {
        PurchaseBill::with(['items.item', 'party'])
            ->where('source_sales_invoice_id', $invoice->id)
            ->get()
            ->each(function (PurchaseBill $purchase) use ($accounting) {
                $this->reverseInterCompanyPurchase($purchase, $accounting);
                $purchase->items()->delete();
                $purchase->delete();
                EntryVisibility::where('entry_type', PurchaseBill::class)->where('entry_id', $purchase->id)->delete();
            });
    }

    private function interCompanyPurchasePayload(SalesInvoice $invoice, Company $sourceCompany, Party $targetParty, ?int $targetCompanyId = null): array
    {
        $payload = [
            'party_id' => $targetParty->id,
            'purchase_type' => 'credit',
            'supplier_bill_no' => $invoice->invoice_no,
            'billing_date' => $invoice->billing_date,
            'purchase_bill_date' => $invoice->billing_date,
            'reference_no' => 'Auto purchase from sale ' . $invoice->invoice_no,
            'phone' => $invoice->phone ?: $sourceCompany->phone,
            'billing_address' => $sourceCompany->address,
            'shipping_address' => $sourceCompany->address,
            'subtotal' => $invoice->subtotal,
            'discount_amount' => $invoice->discount_amount,
            'tax_amount' => $invoice->tax_amount,
            'grand_total' => $invoice->grand_total,
            'notes' => trim(($invoice->notes ?: '') . "\nInter-company purchase auto-created from {$sourceCompany->name} sale {$invoice->invoice_no}."),
            'terms' => $invoice->terms,
            'status' => 'posted',
            'created_by' => auth()->id(),
            'source_sales_invoice_id' => $invoice->id,
            'inter_company_source_company_id' => $invoice->company_id,
        ];

        if ($targetCompanyId) {
            $payload['company_id'] = $targetCompanyId;
            $payload['invoice_no'] = $this->nextInterCompanyPurchaseNo($targetCompanyId, $invoice);
        }

        return $payload;
    }

    private function reverseInterCompanyPurchase(PurchaseBill $purchase, AccountingService $accounting): void
    {
        foreach ($purchase->items as $line) {
            if (!$line->item) {
                continue;
            }

            $accounting->moveStock($line->item, [
                'party_id' => $purchase->party_id,
                'movement_date' => now()->toDateString(),
                'movement_type' => 'inter_company_purchase_reversal',
                'direction' => 'out',
                'quantity' => (float) $line->quantity,
                'unit_price' => $line->unit_price,
                'total_value' => $line->line_total,
                'reference_type' => PurchaseBill::class,
                'reference_id' => $purchase->id,
                'reference_no' => $purchase->invoice_no,
                'description' => 'Auto purchase reversal before source sale update.',
            ]);
        }

        if ($purchase->party) {
            $accounting->postPartyLedger($purchase->party, [
                'entry_date' => now()->toDateString(),
                'entry_type' => 'purchase_reversal',
                'reference_type' => PurchaseBill::class,
                'reference_id' => $purchase->id,
                'reference_no' => $purchase->invoice_no,
                'credit' => 0,
                'debit' => $purchase->grand_total,
                'description' => 'Auto purchase ledger reversal before source sale update.',
            ]);
        }
    }

    private function syncPurchaseVisibility(Request $request, PurchaseBill $purchase, int $targetCompanyId): void
    {
        $this->syncInterCompanyVisibilityForEntry($request, $purchase, $targetCompanyId);
    }

    private function syncInterCompanyVisibilityForEntry(Request $request, $entry, int $targetCompanyId): void
    {
        EntryVisibility::updateOrCreate(
            [
                'entry_type' => $entry::class,
                'entry_id' => $entry->id,
            ],
            [
                'company_id' => $targetCompanyId,
                'visible_to_all_company' => false,
                'visible_to_roles' => array_values(array_filter(array_map('intval', $request->input("purchase_visible_to_roles.{$targetCompanyId}", [])))),
                'visible_to_users' => array_values(array_filter(array_map('intval', $request->input("purchase_visible_to_users.{$targetCompanyId}", [])))),
            ]
        );
    }

    private function interCompanyVisibilityData($companies): array
    {
        return $companies->mapWithKeys(fn(Company $company) => [
            $company->id => [
                'roles' => Role::where('company_id', $company->id)->orderBy('name')->get(['id','name']),
                'users' => User::where('current_company_id', $company->id)->where('is_active', true)->orderBy('name')->get(['id','name','email']),
            ],
        ])->all();
    }

    private function interCompanySelectedVisibility(?SalesInvoice $invoice): array
    {
        if (!$invoice) {
            return [];
        }

        return PurchaseBill::where('source_sales_invoice_id', $invoice->id)
            ->get()
            ->mapWithKeys(function (PurchaseBill $bill) {
                $visibility = EntryVisibility::where('entry_type', PurchaseBill::class)
                    ->where('entry_id', $bill->id)
                    ->first();

                return [
                    $bill->company_id => [
                        'roles' => $visibility?->visible_to_roles ?? [],
                        'users' => $visibility?->visible_to_users ?? [],
                    ],
                ];
            })
            ->all();
    }

    private function supplierPartyForCompany(int $targetCompanyId, Company $sourceCompany): Party
    {
        return Party::firstOrCreate(
            ['company_id' => $targetCompanyId, 'party_code' => 'CO-' . $sourceCompany->id],
            [
                'party_type' => 'supplier',
                'display_name' => $sourceCompany->name,
                'legal_name' => $sourceCompany->name,
                'email' => $sourceCompany->email,
                'phone' => $sourceCompany->phone,
                'gstin' => $sourceCompany->gst_number,
                'pan_number' => $sourceCompany->pan_number,
                'billing_address' => $sourceCompany->address,
                'shipping_address' => $sourceCompany->address,
                'country' => 'India',
                'status' => 'active',
                'created_by' => auth()->id(),
            ]
        );
    }

    private function targetItemForSaleLine(Item $sourceItem, int $targetCompanyId): Item
    {
        $productType = ProductType::firstOrCreate(
            ['company_id' => $targetCompanyId, 'code' => 'FINISHED'],
            ['name' => 'Finished Goods', 'nature' => 'finished_goods', 'status' => 'active']
        );

        $defaults = [
            'product_type_id' => $productType->id,
            'item_type' => $sourceItem->item_type,
            'hsn_code' => $sourceItem->hsn_code,
            'barcode' => $sourceItem->barcode,
            'qr_code' => $sourceItem->qr_code,
            'name' => $sourceItem->name,
            'sku' => $sourceItem->sku,
            'unit' => $sourceItem->unit,
            'brand' => $sourceItem->brand,
            'model' => $sourceItem->model,
            'size' => $sourceItem->size,
            'color' => $sourceItem->color,
            'description' => $sourceItem->description,
            'purchase_price' => $sourceItem->purchase_price,
            'purchase_tax_inclusive' => $sourceItem->purchase_tax_inclusive,
            'purchase_gst_percent' => $sourceItem->purchase_gst_percent,
            'sale_price' => $sourceItem->sale_price,
            'sale_tax_inclusive' => $sourceItem->sale_tax_inclusive,
            'sale_gst_percent' => $sourceItem->sale_gst_percent,
            'per_quantity_weight' => $sourceItem->per_quantity_weight,
            'track_stock' => true,
            'status' => 'active',
            'created_by' => auth()->id(),
        ];

        $item = Item::firstOrCreate(
            ['company_id' => $targetCompanyId, 'item_code' => $sourceItem->item_code],
            $defaults
        );

        if ($item->product_type_id !== $productType->id) {
            $item->update(['product_type_id' => $productType->id, 'track_stock' => true, 'status' => 'active']);
        }

        return $item;
    }

    private function nextInterCompanyPurchaseNo(int $targetCompanyId, SalesInvoice $invoice): string
    {
        $base = substr('IC-' . $invoice->invoice_no, 0, 20);
        if (!PurchaseBill::where('company_id', $targetCompanyId)->where('invoice_no', $base)->withTrashed()->exists()) {
            return $base;
        }

        $suffix = PurchaseBill::where('company_id', $targetCompanyId)->withTrashed()->count() + 1;
        return substr('IC-' . $invoice->invoice_no . '-' . $suffix, 0, 20);
    }

    private function isGpsItem(Item $item): bool
    {
        return str_contains(strtolower(implode(' ', array_filter([
            $item->name,
            $item->item_code,
            $item->sku,
            $item->brand,
            $item->model,
            $item->description,
        ]))), 'gps');
    }

    private function logUpdate(SalesInvoice $invoice, array $oldValues, array $newValues): void
    {
        $user = auth()->user();
        AuditLog::log('updated', [
            'model' => SalesInvoice::class,
            'model_id' => $invoice->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => sprintf(
                'Sales invoice %s updated by %s (%s) for company %s.',
                $invoice->invoice_no,
                $user?->name ?? 'System',
                $user?->rolesForCompany($invoice->company_id)->pluck('name')->join(', ') ?: 'No role',
                $user?->currentCompany?->name ?? 'Unknown company'
            ),
        ]);
    }

    private function nextNo(): string
    {
        return str_pad((string) (SalesInvoice::where('company_id', auth()->user()->current_company_id)->withTrashed()->count() + 1), 8, '0', STR_PAD_LEFT);
    }
}
