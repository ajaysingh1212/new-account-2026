<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\CostCenter;
use App\Models\AuditLog;
use App\Models\Item;
use App\Models\Party;
use App\Models\ProductionBatch;
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
            ]));

            $totals = $this->storeLines($request, $sale, $accounting);
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
        $sale->load(['party','items.item']);
        return view('admin.sales.print', ['invoice' => $sale]);
    }

    private function formData(?SalesInvoice $invoice = null): array
    {
        $companyId = auth()->user()->current_company_id;
        $items = Item::where('company_id', $companyId)
            ->where('status', 'active')
            ->whereHas('productType', fn($q) => $q->where('nature', 'finished_goods'))
            ->orderBy('name')
            ->get();

        return [
            'parties' => Party::where('company_id', $companyId)->orderBy('display_name')->get(),
            'items' => $items,
            'costCenters' => CostCenter::where('company_id', $companyId)->where('status', 'active')->get(),
            'subCostCenters' => SubCostCenter::where('company_id', $companyId)->where('status', 'active')->get(),
            'invoiceNo' => $invoice?->invoice_no ?? $this->nextNo(),
            'unitPool' => $this->finishedGoodsUnitPool($companyId, $invoice?->id),
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
            'selected_units.*' => ['nullable','string'],
        ]);
    }

    private function storeLines(Request $request, SalesInvoice $invoice, AccountingService $accounting): array
    {
        $subtotal = $tax = $lineDiscount = 0;
        $unitPool = $this->finishedGoodsUnitPool($invoice->company_id, $invoice->id);
        foreach ($request->item_id as $i => $itemId) {
            $item = Item::with('productType')->findOrFail($itemId);
            $qty = (float) $request->quantity[$i];
            abort_if($item->track_stock && (float) $item->current_stock < $qty, 422, "Insufficient stock for {$item->name}");
            abort_if($item->productType?->nature !== 'finished_goods', 422, 'Only finished goods can be sold from Sales.');

            $selectedUnits = $this->decodeSelectedUnits($request->selected_units[$i] ?? null);
            $selectedKeys = collect($selectedUnits)->pluck('key')->filter()->values()->all();
            abort_if(count($selectedKeys) !== (int) $qty || (float) ((int) $qty) !== $qty, 422, "Select exactly {$qty} finished goods unit(s) for {$item->name}.");
            $availableKeys = collect($unitPool[$item->id] ?? [])->where('sold', false)->pluck('key')->all();
            abort_if(count(array_diff($selectedKeys, $availableKeys)) > 0, 422, "One or more selected units for {$item->name} are already sold or invalid.");

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

        return ProductionBatch::with('finishedItem')
            ->where('company_id', $companyId)
            ->get()
            ->flatMap(function (ProductionBatch $batch) use ($soldKeys) {
                return collect($batch->units_data ?? [])->map(function ($unit, $index) use ($batch, $soldKeys) {
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
    }

    private function decodeSelectedUnits(?string $json): array
    {
        $units = json_decode($json ?: '[]', true);
        return is_array($units) ? array_values($units) : [];
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
