<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Party;
use App\Models\ProductionBatch;
use App\Models\Replacement;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Services\AccountingService;
use App\Services\EntryVisibilityService;
use App\Services\SerialUnitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReplacementController extends Controller
{
    public function index(EntryVisibilityService $visibility)
    {
        $replacements = $visibility->scopeForUser(
            Replacement::with(['party','item','invoice','creator','approver'])->latest(),
            Replacement::class
        )->get();

        $received = $replacements
            ->whereIn('status', ['approved', 'completed'])
            ->groupBy('item_id')
            ->map(fn($rows) => [
                'item' => $rows->first()->item,
                'quantity' => $rows->count(),
                'rows' => $rows->values(),
            ])
            ->values();

        return view('admin.replacements.index', compact('replacements', 'received'));
    }

    public function create()
    {
        $companyId = auth()->user()->current_company_id;

        return view('admin.replacements.create', [
            'replacementNo' => $this->nextNo(),
            'parties' => Party::where('company_id', $companyId)->where('status', 'active')->orderBy('display_name')->get(),
            'replacementItems' => Item::where('company_id', $companyId)
                ->where('status', 'active')->where('track_stock', true)->where('current_stock', '>', 0)
                ->orderBy('name')->get(),
        ]);
    }

    public function lookup(Request $request)
    {
        $companyId = auth()->user()->current_company_id;
        $term = trim((string) $request->input('q', ''));
        abort_if($term === '', 422, 'Search value required.');
        $needle = mb_strtolower($term);
        $alreadyRequestedKeys = Replacement::where('company_id', $companyId)
            ->where('status', 'completed')
            ->get()->pluck('returned_unit')->filter()
            ->map(fn($unit) => is_array($unit) ? ($unit['key'] ?? null) : null)
            ->filter()->values()->all();

        // Do not rely only on a SQL LIKE against the JSON selected_units column.
        // MySQL/SQLite JSON storage differs, while the sales screen reads the
        // same serial from decoded selected_units. Fetch candidate bills and
        // perform the serial match in PHP so every sold serial is searchable.
        $invoices = SalesInvoice::with(['party','items.item'])
            ->where('company_id', $companyId)
            ->where(function ($query) use ($term) {
                $query->where('invoice_no', 'like', "%{$term}%")
                    ->orWhereHas('items.item', fn($q) => $q->where('sku', 'like', "%{$term}%")->orWhere('item_code', 'like', "%{$term}%"));
            })
            ->latest('billing_date')->limit(50)->get();

        $serialInvoices = SalesInvoice::with(['party','items.item'])
            ->where('company_id', $companyId)
            ->latest('billing_date')->limit(500)->get()
            ->filter(fn(SalesInvoice $invoice) => collect($invoice->items)->contains(
                fn(SalesInvoiceItem $line) => collect($line->selected_units ?? [])->contains(
                    fn($unit) => is_array($unit) && collect($unit)->contains(
                        fn($value) => str_contains(mb_strtolower((string) $value), $needle)
                    )
                )
            ));
        $invoices = $invoices->concat($serialInvoices)->unique('id')->values();

        $rows = $invoices->flatMap(function (SalesInvoice $invoice) use ($needle, $alreadyRequestedKeys) {
            return $invoice->items->flatMap(function (SalesInvoiceItem $line) use ($invoice, $needle, $alreadyRequestedKeys) {
                $units = collect($line->selected_units ?? [])
                    ->filter(fn($unit) => is_array($unit) && !in_array($unit['key'] ?? null, $alreadyRequestedKeys, true))
                    ->values();
                $lineMatches = str_contains(mb_strtolower((string) $invoice->invoice_no), $needle)
                    || str_contains(mb_strtolower((string) $line->item?->sku), $needle)
                    || str_contains(mb_strtolower((string) $line->item?->item_code), $needle);

                if ($units->isEmpty()) {
                    return $lineMatches ? [$this->lookupRow($invoice, $line, [])] : [];
                }

                return $units
                    ->filter(function ($unit) use ($lineMatches, $needle) {
                        if ($lineMatches) {
                            return true;
                        }

                        return collect(['key','serial_no','vts_sim','sku','batch_no','buyer_code','production_batch_no'])
                            ->contains(fn($field) => isset($unit[$field]) && str_contains(mb_strtolower((string) $unit[$field]), $needle));
                    })
                    ->map(fn($unit) => $this->lookupRow($invoice, $line, $unit));
            });
        })->values();

        return response()->json(['rows' => $rows]);
    }

    public function store(Request $request, EntryVisibilityService $visibility)
    {
        $companyId = auth()->user()->current_company_id;
        if (!$request->filled('sales_invoice_item_id')) {
            return back()
                ->withErrors(['sales_invoice_item_id' => 'Please search and select the sold item before submitting replacement.'])
                ->withInput();
        }

        $data = $request->validate([
            'sales_invoice_item_id' => ['required','exists:sales_invoice_items,id'],
            'issued_item_id' => ['required','exists:items,id'],
            'target_party_id' => ['nullable','exists:parties,id'],
            'ledger_enabled' => ['nullable','boolean'],
            'ledger_amount' => ['nullable','numeric','min:0'],
            'returned_unit' => ['nullable','string'],
            'customer_name' => ['nullable','string','max:255'],
            'customer_email' => ['nullable','email','max:255'],
            'customer_phone' => ['nullable','string','max:40'],
            'customer_address' => ['nullable','string'],
            'request_reason' => ['required','string'],
            'images.front' => ['required','image','max:4096'],
            'images.back' => ['nullable','image','max:4096'],
            'images.angle_one' => ['nullable','image','max:4096'],
            'images.angle_two' => ['nullable','image','max:4096'],
        ]);

        $line = SalesInvoiceItem::with(['salesInvoice.party','item'])
            ->whereHas('salesInvoice', fn($q) => $q->where('company_id', $companyId))
            ->findOrFail($data['sales_invoice_item_id']);
        $issuedItem = Item::where('company_id', $companyId)->where('status', 'active')
            ->where('track_stock', true)->where('current_stock', '>', 0)->findOrFail($data['issued_item_id']);
        $targetPartyId = $data['target_party_id'] ?: $line->salesInvoice->party_id;
        if ($targetPartyId) {
            Party::where('company_id', $companyId)->findOrFail($targetPartyId);
        }
        $unit = json_decode($data['returned_unit'] ?? '[]', true) ?: [];

        $images = [];
        foreach (['front','back','angle_one','angle_two'] as $slot) {
            if ($request->hasFile("images.{$slot}")) {
                $images[$slot] = $request->file("images.{$slot}")->store('replacement-images', 'public');
            }
        }

        $replacement = Replacement::create([
            'company_id' => $companyId,
            'party_id' => $line->salesInvoice->party_id,
            'target_party_id' => $targetPartyId,
            'sales_invoice_id' => $line->sales_invoice_id,
            'sales_invoice_item_id' => $line->id,
            'item_id' => $line->item_id,
            'issued_item_id' => $issuedItem->id,
            'replacement_no' => $this->nextNo(),
            'request_date' => now()->toDateString(),
            'returned_unit' => $unit,
            'customer_name' => $data['customer_name'] ?: ($line->salesInvoice->party?->display_name ?: 'Customer'),
            'customer_email' => $data['customer_email'] ?? null,
            'customer_phone' => $data['customer_phone'] ?? null,
            'customer_address' => $data['customer_address'] ?? null,
            'request_reason' => $data['request_reason'],
            'ledger_enabled' => $request->boolean('ledger_enabled'),
            'ledger_amount' => $request->boolean('ledger_enabled') ? (float) ($data['ledger_amount'] ?? $line->line_total) : null,
            'product_images' => $images,
            'created_by' => auth()->id(),
        ]);
        $visibility->syncFromRequest($request, $replacement);

        return redirect()->route('admin.replacements.show', $replacement)->with('success', 'Replacement request submitted.');
    }

    public function show(Replacement $replacement, EntryVisibilityService $visibility, SerialUnitService $serialUnits)
    {
        $visibility->authorizeView($replacement);
        $replacement->load(['party','targetParty','item','issuedItem','invoice.items.item','invoiceItem.item','creator','approver']);
        $availableUnits = [];

        if ($replacement->status === 'approved') {
            $issuedItemId = $replacement->issued_item_id ?: $replacement->item_id;
            $availableUnits = collect($serialUnits->currentStockUnitsByItem($replacement->company_id, $issuedItemId)[$issuedItemId] ?? [])
                ->values()
                ->all();
        }

        return view('admin.replacements.show', compact('replacement', 'availableUnits'));
    }

    public function edit(Replacement $replacement, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($replacement);
        abort_unless(in_array($replacement->status, ['pending', 'rejected'], true), 422, 'Only pending or rejected replacements can be edited.');
        $replacement->load(['party','targetParty','item','issuedItem','invoice','invoiceItem']);

        return view('admin.replacements.edit', compact('replacement'));
    }

    public function update(Request $request, Replacement $replacement, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($replacement);
        abort_unless(in_array($replacement->status, ['pending', 'rejected'], true), 422, 'Only pending or rejected replacements can be edited.');

        $companyId = auth()->user()->current_company_id;
        $data = $request->validate([
            'sales_invoice_item_id' => ['required','exists:sales_invoice_items,id'],
            'issued_item_id' => ['required','exists:items,id'],
            'target_party_id' => ['nullable','exists:parties,id'],
            'ledger_enabled' => ['nullable','boolean'],
            'ledger_amount' => ['nullable','numeric','min:0'],
            'returned_unit' => ['nullable','string'],
            'customer_name' => ['nullable','string','max:255'],
            'customer_email' => ['nullable','email','max:255'],
            'customer_phone' => ['nullable','string','max:40'],
            'customer_address' => ['nullable','string'],
            'request_reason' => ['required','string'],
            'images.front' => ['nullable','image','max:4096'],
            'images.back' => ['nullable','image','max:4096'],
            'images.angle_one' => ['nullable','image','max:4096'],
            'images.angle_two' => ['nullable','image','max:4096'],
        ]);

        $line = SalesInvoiceItem::with(['salesInvoice.party','item'])
            ->whereHas('salesInvoice', fn($q) => $q->where('company_id', $companyId))
            ->findOrFail($data['sales_invoice_item_id']);
        $issuedItem = Item::where('company_id', $companyId)->where('status', 'active')
            ->where('track_stock', true)->where('current_stock', '>', 0)->findOrFail($data['issued_item_id']);
        $targetPartyId = $data['target_party_id'] ?: $line->salesInvoice->party_id;
        if ($targetPartyId) {
            Party::where('company_id', $companyId)->findOrFail($targetPartyId);
        }
        $unit = json_decode($data['returned_unit'] ?? '[]', true) ?: [];

        $images = $replacement->product_images ?? [];
        foreach (['front','back','angle_one','angle_two'] as $slot) {
            if ($request->hasFile("images.{$slot}")) {
                $images[$slot] = $request->file("images.{$slot}")->store('replacement-images', 'public');
            }
        }

        $replacement->fill([
            'party_id' => $line->salesInvoice->party_id,
            'target_party_id' => $targetPartyId,
            'sales_invoice_id' => $line->sales_invoice_id,
            'sales_invoice_item_id' => $line->id,
            'item_id' => $line->item_id,
            'issued_item_id' => $issuedItem->id,
            'returned_unit' => $unit,
            'customer_name' => $data['customer_name'] ?: ($line->salesInvoice->party?->display_name ?: $replacement->customer_name),
            'customer_email' => $data['customer_email'] ?? null,
            'customer_phone' => $data['customer_phone'] ?? null,
            'customer_address' => $data['customer_address'] ?? null,
            'request_reason' => $data['request_reason'],
            'ledger_enabled' => $request->boolean('ledger_enabled'),
            'ledger_amount' => $request->boolean('ledger_enabled') ? (float) ($data['ledger_amount'] ?? $line->line_total) : null,
            'product_images' => $images,
        ]);
        $replacement->save();
        $visibility->syncFromRequest($request, $replacement);

        return redirect()->route('admin.replacements.show', $replacement)->with('success', 'Replacement request updated.');
    }

    public function destroy(Replacement $replacement, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($replacement);
        abort_unless(in_array($replacement->status, ['pending', 'rejected'], true), 422, 'Only pending or rejected replacements can be deleted.');
        $replacement->delete();

        return redirect()->route('admin.replacements.index')->with('success', 'Replacement deleted.');
    }

    public function approve(Request $request, Replacement $replacement, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($replacement);
        abort_unless($replacement->status === 'pending', 422, 'Only pending replacements can be approved.');
        $data = $request->validate([
            'admin_reason' => ['nullable','string'],
            'admin_attachment' => ['nullable','file','max:4096'],
        ]);

        $replacement->update([
            'status' => 'approved',
            'admin_reason' => $data['admin_reason'] ?? null,
            'admin_attachment' => $request->hasFile('admin_attachment') ? $request->file('admin_attachment')->store('replacement-admin', 'public') : null,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('admin.replacements.show', $replacement)->with('success', 'Replacement approved. Now issue replacement stock.');
    }

    public function reject(Request $request, Replacement $replacement, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($replacement);
        abort_unless($replacement->status === 'pending', 422, 'Only pending replacements can be rejected.');
        $data = $request->validate([
            'admin_reason' => ['required','string'],
            'admin_attachment' => ['nullable','file','max:4096'],
        ]);

        $replacement->update([
            'status' => 'rejected',
            'admin_reason' => $data['admin_reason'],
            'admin_attachment' => $request->hasFile('admin_attachment') ? $request->file('admin_attachment')->store('replacement-admin', 'public') : null,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('admin.replacements.show', $replacement)->with('success', 'Replacement rejected.');
    }

    public function issue(Request $request, Replacement $replacement, EntryVisibilityService $visibility, SerialUnitService $serialUnits, AccountingService $accounting)
    {
        $visibility->authorizeManage($replacement);
        abort_unless($replacement->status === 'approved', 422, 'Approve replacement before issuing stock.');
        $data = $request->validate([
            'issued_unit' => ['required','string'],
            'issue_narration' => ['nullable','string'],
            'issue_attachment' => ['nullable','file','max:4096'],
        ]);

        DB::transaction(function () use ($replacement, $data, $request, $serialUnits, $accounting) {
            $replacement->refresh()->load(['item','party','targetParty','invoiceItem']);
            $item = Item::lockForUpdate()->findOrFail($replacement->issued_item_id ?: $replacement->item_id);
            $targetParty = $replacement->targetParty ?: $replacement->party;
            $requestedUnit = json_decode($data['issued_unit'], true) ?: [];
            $requestedIdentity = $serialUnits->unitIdentity($requestedUnit);
            $availableUnit = collect($serialUnits->currentStockUnitsByItem($replacement->company_id, $item->id)[$item->id] ?? [])
                ->first(fn($unit) => $serialUnits->unitIdentity($unit) === $requestedIdentity);

            if (!$availableUnit) {
                throw ValidationException::withMessages(['issued_unit' => 'Selected replacement serial is not available in current stock.']);
            }

            $movement = $accounting->moveStock($item, [
                'party_id' => $targetParty?->id,
                'movement_date' => now()->toDateString(),
                'movement_type' => 'replacement_issue',
                'direction' => 'out',
                'quantity' => 1,
                'unit_price' => $item->purchase_price,
                'total_value' => (float) $item->purchase_price,
                'reference_type' => Replacement::class,
                'reference_id' => $replacement->id,
                'reference_no' => $replacement->replacement_no,
                'description' => $data['issue_narration'] ?: 'Replacement item issued.',
                'movement_units' => [$availableUnit],
            ]);

            if ($targetParty && $replacement->ledger_enabled && (float) $replacement->ledger_amount > 0) {
                $accounting->postPartyLedger($targetParty, [
                    'entry_date' => now()->toDateString(),
                    'entry_type' => 'replacement',
                    'reference_type' => Replacement::class,
                    'reference_id' => $replacement->id,
                    'reference_no' => $replacement->replacement_no,
                    'debit' => (float) $replacement->ledger_amount,
                    'credit' => 0,
                    'description' => 'Replacement item payable added to party ledger.',
                ]);
            }

            $replacement->update([
                'status' => 'completed',
                'issued_item_id' => $item->id,
                'issued_unit' => $availableUnit,
                'stock_movement_id' => $movement?->id,
                'issue_narration' => $data['issue_narration'],
                'issue_attachment' => $request->hasFile('issue_attachment') ? $request->file('issue_attachment')->store('replacement-issue', 'public') : null,
                'issued_at' => now(),
            ]);
        });

        return redirect()->route('admin.replacements.show', $replacement)->with('success', 'Replacement item issued and stock moved out.');
    }

    private function nextNo(): string
    {
        $count = Replacement::where('company_id', auth()->user()->current_company_id)->withTrashed()->count() + 1;
        return 'RPL-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }

    private function lookupRow(SalesInvoice $invoice, SalesInvoiceItem $line, array $unit): array
    {
        $production = $this->productionTrace($invoice->company_id, $line->item_id, $unit);

        return [
            'invoice_item_id' => $line->id,
            'invoice_id' => $invoice->id,
            'invoice_no' => $invoice->invoice_no,
            'date' => $invoice->billing_date?->format('d M Y'),
            'party' => $invoice->party?->display_name ?: 'Cash',
            'party_id' => $invoice->party_id,
            'party_email' => $invoice->party?->email,
            'party_phone' => $invoice->party?->phone ?: $invoice->phone,
            'party_address' => $invoice->shipping_address ?: $invoice->billing_address ?: $invoice->party?->shipping_address ?: $invoice->party?->billing_address,
            'party_gstin' => $invoice->party?->gstin,
            'item_id' => $line->item_id,
            'item_name' => $line->item?->name,
            'item_code' => $line->item?->item_code,
            'sku' => $line->item?->sku,
            'quantity' => (float) $line->quantity,
            'unit_price' => (float) $line->unit_price,
            'tax_percent' => (float) $line->tax_percent,
            'tax_amount' => (float) $line->tax_amount,
            'discount_amount' => (float) $line->discount_amount,
            'line_total' => (float) $line->line_total,
            'invoice_total' => (float) $invoice->grand_total,
            'current_price' => (float) ($line->item?->sale_price ?? 0),
            'unit' => $unit,
            'production' => $production,
        ];
    }

    private function productionTrace(int $companyId, int $itemId, array $unit): ?array
    {
        if (empty($unit)) {
            return null;
        }

        $identityValues = collect(['key','serial_no','vts_sim','buyer_code','production_batch_no','batch_no'])
            ->mapWithKeys(fn($field) => [$field => isset($unit[$field]) ? (string) $unit[$field] : null])
            ->filter();

        if ($identityValues->isEmpty()) {
            return null;
        }

        $batch = ProductionBatch::where('company_id', $companyId)
            ->where('finished_item_id', $itemId)
            ->get()
            ->first(function (ProductionBatch $batch) use ($identityValues) {
                if ($identityValues->get('production_batch_no') && $batch->batch_no === $identityValues->get('production_batch_no')) {
                    return true;
                }

                return collect($batch->units_data ?? [])->contains(function ($batchUnit, $index) use ($batch, $identityValues) {
                    if (!is_array($batchUnit)) {
                        return false;
                    }

                    $batchUnit = array_merge($batchUnit, ['key' => $batch->id . '-' . $index]);
                    return $identityValues->contains(fn($value, $field) => isset($batchUnit[$field]) && (string) $batchUnit[$field] === $value);
                });
            });

        if (!$batch) {
            return null;
        }

        return [
            'batch_no' => $batch->batch_no,
            'production_date' => $batch->production_date?->format('d M Y'),
            'quantity' => (float) $batch->quantity,
            'cost_per_unit' => (float) $batch->cost_per_unit,
            'raw_materials' => Item::with('bomMaterials.rawItem')->find($itemId)?->bomMaterials
                ->map(fn($bom) => [
                    'name' => $bom->rawItem?->name ?: 'Service',
                    'quantity' => (float) $bom->qty_per_unit,
                    'unit' => $bom->rawItem?->unit ?: '',
                    'line_type' => $bom->line_type ?? 'raw_material',
                ])->values()->all() ?: [],
        ];
    }
}
