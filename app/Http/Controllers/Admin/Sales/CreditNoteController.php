<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\SalesInvoice;
use App\Models\SalesReturnItem;
use App\Models\TermsTemplate;
use App\Services\EntryVisibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreditNoteController extends Controller
{
    public function index(EntryVisibilityService $visibility)
    {
        $creditNotes = $visibility->scopeForUser(
            CreditNote::with(['invoice', 'party', 'creator', 'items'])->latest('credit_note_date')->latest('id'),
            CreditNote::class
        )->get();

        return view('admin.credit-notes.index', compact('creditNotes'));
    }

    public function create(EntryVisibilityService $visibility)
    {
        return view('admin.credit-notes.create', $this->formData($visibility));
    }

    public function store(Request $request, EntryVisibilityService $visibility)
    {
        $creditNote = DB::transaction(fn () => $this->persist($request, new CreditNote, $visibility));

        return redirect()->route('admin.credit-notes.show', $creditNote)->with('success', 'Credit Note created successfully.');
    }

    public function show(CreditNote $credit_note, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($credit_note);
        $credit_note->load(['company', 'invoice', 'party', 'creator', 'items.item']);

        return view('admin.credit-notes.show', ['creditNote' => $credit_note]);
    }

    public function edit(CreditNote $credit_note, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($credit_note);
        $credit_note->load('items');

        return view('admin.credit-notes.edit', array_merge($this->formData($visibility, $credit_note), [
            'creditNote' => $credit_note,
        ]));
    }

    public function update(Request $request, CreditNote $credit_note, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($credit_note);
        DB::transaction(fn () => $this->persist($request, $credit_note, $visibility));

        return redirect()->route('admin.credit-notes.show', $credit_note)->with('success', 'Credit Note updated successfully.');
    }

    public function destroy(CreditNote $credit_note, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($credit_note);
        $credit_note->delete();

        return redirect()->route('admin.credit-notes.index')->with('success', 'Credit Note deleted.');
    }

    public function print(CreditNote $credit_note, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($credit_note);
        $credit_note->load(['company', 'invoice.creditNotes', 'party', 'items.item']);
        $bankAccount = BankAccount::where('company_id', $credit_note->company_id)->where('print_on_invoice', true)->where('status', 'active')->first();
        $defaultTerms = TermsTemplate::where('company_id', $credit_note->company_id)->where('status', 'active')->whereIn('document_type', ['sales', 'all'])->orderByDesc('is_default')->first();

        return view('admin.credit-notes.print', compact('credit_note', 'bankAccount', 'defaultTerms'));
    }

    private function formData(EntryVisibilityService $visibility, ?CreditNote $editing = null): array
    {
        $invoices = $visibility->scopeForUser(
            SalesInvoice::with(['party', 'items.item', 'returns.items', 'creditNotes.items'])->latest(),
            SalesInvoice::class
        )->get();

        $invoiceData = $invoices->mapWithKeys(function (SalesInvoice $invoice) use ($editing) {
            return [$invoice->id => [
                'invoice_no' => $invoice->invoice_no,
                'date' => $invoice->billing_date?->format('d M Y'),
                'party' => $invoice->party?->display_name ?: 'Cash',
                'phone' => $invoice->phone ?: $invoice->party?->phone,
                'address' => $invoice->billing_address ?: $invoice->party?->billing_address,
                'grand_total' => (float) $invoice->grand_total,
                'lines' => $invoice->items->map(function ($line) use ($invoice, $editing) {
                    $creditedElsewhere = $invoice->creditNotes
                        ->reject(fn ($note) => $editing && $note->id === $editing->id)
                        ->flatMap->items->where('sales_invoice_item_id', $line->id)->sum('quantity');
                    $returnedLines = $invoice->returns->flatMap->items->where('sales_invoice_item_id', $line->id);
                    $returnedUnits = $returnedLines->flatMap(fn ($row) => $row->selected_units ?? [])->pluck('key')->filter()->unique()->values();
                    $creditedUnitKeys = $invoice->creditNotes
                        ->reject(fn ($note) => $editing && $note->id === $editing->id)
                        ->flatMap->items->where('sales_invoice_item_id', $line->id)
                        ->flatMap(fn ($row) => $row->selected_units ?? [])->pluck('key')->filter()->unique()->values();

                    return [
                        'id' => $line->id,
                        'item_id' => $line->item_id,
                        'item' => $line->item?->name ?: 'Item',
                        'sku' => $line->item?->item_code ?: '-',
                        'description' => $line->description,
                        'sold_qty' => (float) $line->quantity,
                        'available_qty' => max(0, round((float) $line->quantity - (float) $creditedElsewhere, 3)),
                        'returned_qty' => round((float) $returnedLines->sum('quantity'), 3),
                        'unit' => $line->unit,
                        'unit_price' => (float) $line->unit_price,
                        'tax_percent' => (float) $line->tax_percent,
                        'tax_amount' => (float) $line->tax_amount,
                        'line_total' => (float) $line->line_total,
                        'sold_units' => array_values($line->selected_units ?? []),
                        'returned_unit_keys' => $returnedUnits,
                        'credited_unit_keys' => $creditedUnitKeys,
                    ];
                })->values(),
            ]];
        });

        return ['invoices' => $invoices, 'invoiceData' => $invoiceData, 'creditNoteNo' => $editing?->credit_note_no ?: $this->nextNo()];
    }

    private function persist(Request $request, CreditNote $creditNote, EntryVisibilityService $visibility): CreditNote
    {
        $data = $request->validate([
            'sales_invoice_id' => ['required', 'exists:sales_invoices,id'],
            'credit_note_no' => ['nullable', 'string', 'max:30'],
            'credit_note_date' => ['required', 'date'],
            'reason' => ['nullable', 'string'],
            'line_id' => ['required', 'array'],
            'line_id.*' => ['integer'],
            'quantity' => ['required', 'array'],
            'quantity.*' => ['numeric', 'min:0'],
            'selected_units' => ['nullable', 'array'],
            'selected_units.*' => ['nullable', 'string'],
            'allow_without_return' => ['nullable', 'boolean'],
        ]);

        $invoice = SalesInvoice::with(['items.item', 'returns.items', 'creditNotes.items'])
            ->where('company_id', auth()->user()->current_company_id)->lockForUpdate()->findOrFail($data['sales_invoice_id']);
        $number = $data['credit_note_no'] ?: ($creditNote->exists ? $creditNote->credit_note_no : $this->nextNo());
        $duplicate = CreditNote::where('company_id', $invoice->company_id)->where('credit_note_no', $number)
            ->when($creditNote->exists, fn ($query) => $query->whereKeyNot($creditNote->id))->exists();
        if ($duplicate) throw ValidationException::withMessages(['credit_note_no' => 'Credit Note number already exists.']);

        $rows = collect();
        $hasReturnGap = false;
        foreach ($data['line_id'] as $index => $lineId) {
            $qty = round((float) ($data['quantity'][$index] ?? 0), 3);
            if ($qty <= 0) continue;
            $line = $invoice->items->firstWhere('id', (int) $lineId);
            if (!$line) throw ValidationException::withMessages(['line_id' => 'Selected item does not belong to this invoice.']);
            $creditedElsewhere = $invoice->creditNotes->reject(fn ($note) => $creditNote->exists && $note->id === $creditNote->id)
                ->flatMap->items->where('sales_invoice_item_id', $line->id)->sum('quantity');
            if ($qty > (float) $line->quantity - (float) $creditedElsewhere + 0.0001) {
                throw ValidationException::withMessages(["quantity.$index" => "Credit quantity exceeds available sold quantity for {$line->item?->name}."]);
            }

            $soldUnits = collect($line->selected_units ?? [])->filter(fn ($unit) => !empty($unit['key']))->keyBy('key');
            $selected = collect(json_decode($data['selected_units'][$index] ?? '[]', true) ?: [])->pluck('key')->filter()->unique()
                ->map(fn ($key) => $soldUnits->get($key))->filter()->values();
            if ($soldUnits->isNotEmpty() && ($qty != (int) $qty || $selected->count() !== (int) $qty)) {
                throw ValidationException::withMessages(["selected_units.$index" => "Select exactly {$qty} sold serial number(s) for {$line->item?->name}."]);
            }
            $creditedKeys = $invoice->creditNotes->reject(fn ($note) => $creditNote->exists && $note->id === $creditNote->id)
                ->flatMap->items->where('sales_invoice_item_id', $line->id)
                ->flatMap(fn ($row) => $row->selected_units ?? [])->pluck('key')->filter();
            if ($selected->pluck('key')->intersect($creditedKeys)->isNotEmpty()) {
                throw ValidationException::withMessages(["selected_units.$index" => "One or more serial numbers already have a Credit Note for {$line->item?->name}."]);
            }

            $returnedLines = $invoice->returns->flatMap->items->where('sales_invoice_item_id', $line->id);
            $returnedKeys = $returnedLines->flatMap(fn ($row) => $row->selected_units ?? [])->pluck('key')->filter()->unique();
            $returnCovered = $soldUnits->isNotEmpty()
                ? $selected->pluck('key')->every(fn ($key) => $returnedKeys->contains($key))
                : $qty <= (float) $returnedLines->sum('quantity') + 0.0001;
            $hasReturnGap = $hasReturnGap || !$returnCovered;
            $ratio = (float) $line->quantity > 0 ? $qty / (float) $line->quantity : 0;
            $rows->push([
                'sales_invoice_item_id' => $line->id, 'item_id' => $line->item_id,
                'description' => 'SKU: '.($line->item?->item_code ?: '-').($line->description ? ' | '.$line->description : ''),
                'quantity' => $qty, 'unit' => $line->unit, 'unit_price' => $line->unit_price,
                'tax_percent' => $line->tax_percent, 'tax_amount' => round((float) $line->tax_amount * $ratio, 2),
                'line_total' => round((float) $line->line_total * $ratio, 2), 'selected_units' => $selected->all(),
            ]);
        }
        if ($rows->isEmpty()) throw ValidationException::withMessages(['quantity' => 'Select at least one item and enter quantity.']);
        if ($hasReturnGap && !$request->boolean('allow_without_return')) {
            throw ValidationException::withMessages(['return_confirmation' => 'Some selected items are not received in Sales Return. Confirm to create Credit Note without adding those items to stock.']);
        }

        $creditNote->fill([
            'company_id' => $invoice->company_id, 'sales_invoice_id' => $invoice->id, 'party_id' => $invoice->party_id,
            'credit_note_no' => $number, 'credit_note_date' => $data['credit_note_date'], 'reason' => $data['reason'] ?? null,
            'subtotal' => round($rows->sum(fn ($row) => $row['line_total'] - $row['tax_amount']), 2),
            'tax_amount' => round($rows->sum('tax_amount'), 2), 'grand_total' => round($rows->sum('line_total'), 2),
            'return_status' => $hasReturnGap ? 'pending' : 'received', 'created_by' => $creditNote->created_by ?: auth()->id(),
        ])->save();
        $creditNote->items()->delete();
        $creditNote->items()->createMany($rows->all());
        $visibility->syncFromRequest($request, $creditNote);

        return $creditNote;
    }

    private function nextNo(): string
    {
        return 'CN-' . str_pad((string) (CreditNote::where('company_id', auth()->user()->current_company_id)->withTrashed()->count() + 1), 5, '0', STR_PAD_LEFT);
    }
}
