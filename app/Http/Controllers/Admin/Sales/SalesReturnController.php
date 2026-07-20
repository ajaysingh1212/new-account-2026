<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Services\AccountingService;
use App\Services\EntryVisibilityService;
use App\Services\SerialUnitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesReturnController extends Controller
{
    public function index(EntryVisibilityService $visibility)
    {
        $returns = $visibility->scopeForUser(SalesReturn::with(['invoice','party','creator','items'])->latest(), SalesReturn::class)->get();
        return view('admin.sales-returns.index', compact('returns'));
    }

    public function create(EntryVisibilityService $visibility, SerialUnitService $serialUnits)
    {
        $invoices = $visibility->scopeForUser(
            SalesInvoice::with(['party','items.item'])->latest(),
            SalesInvoice::class
        )->get();

        $invoiceData = [];

        foreach ($invoices as $invoice) {
            $invoiceData[$invoice->id] = [];

            foreach ($invoice->items as $line) {
                $alreadyReturned = (float) SalesReturnItem::where('sales_invoice_item_id', $line->id)->sum('quantity');
                $returnedKeys = $serialUnits->returnedKeysForInvoiceLine($line->id);
                $soldUnits = collect($line->selected_units ?? [])->values();
                $availableUnits = $soldUnits
                    ->reject(fn($unit) => in_array($unit['key'] ?? null, $returnedKeys, true))
                    ->values();
                $invoiceData[$invoice->id][] = [
                    'id' => $line->id,
                    'item' => optional($line->item)->name ?? 'N/A',
                    'qty' => (float) $line->quantity,
                    'already_returned' => round($alreadyReturned, 3),
                    'remaining_qty' => round(max(0, (float) $line->quantity - $alreadyReturned), 3),
                    'unit' => $line->unit ?? '',
                    'price' => (float) $line->unit_price,
                    'tax' => (float) $line->tax_percent,
                    'sold_units' => $soldUnits->all(),
                    'available_units' => $availableUnits->all(),
                ];
            }
        }

        return view('admin.sales-returns.create', [
            'invoices'    => $invoices,
            'invoiceData' => $invoiceData,
            'returnNo'    => $this->nextNo()
        ]);
    }

    public function store(Request $request, AccountingService $accounting, EntryVisibilityService $visibility)
    {
        $data = $request->validate([
            'sales_invoice_id' => ['required','exists:sales_invoices,id'],
            'return_no' => ['nullable','max:30'],
            'return_date' => ['required','date'],
            'reason' => ['nullable','string'],
            'line_id' => ['required','array'],
            'quantity' => ['required','array'],
            'quantity.*' => ['required','numeric','min:0'],
            'returned_units' => ['nullable','array'],
            'returned_units.*' => ['nullable','string'],
        ]);

        DB::transaction(function () use ($request, $data, $accounting, $visibility) {
            $companyId = auth()->user()->current_company_id;
            $invoice = SalesInvoice::with(['items.item','party'])
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->findOrFail($data['sales_invoice_id']);
            $return = SalesReturn::create([
                'company_id' => $invoice->company_id,
                'sales_invoice_id' => $invoice->id,
                'party_id' => $invoice->party_id,
                'return_no' => $data['return_no'] ?: $this->nextNo(),
                'return_date' => $data['return_date'],
                'reason' => $data['reason'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $subtotal = $tax = 0;
            $storedLines = 0;
            foreach ($request->line_id as $i => $lineId) {
                $line = $invoice->items->firstWhere('id', (int) $lineId);
                if (!$line) continue;
                $alreadyReturned = (float) SalesReturnItem::where('sales_invoice_item_id', $line->id)
                    ->lockForUpdate()
                    ->sum('quantity');
                $remainingQty = max(0, (float) $line->quantity - $alreadyReturned);
                $qty = round((float) ($request->quantity[$i] ?? 0), 3);
                if ($qty <= 0) continue;
                if ($qty > $remainingQty) {
                    throw ValidationException::withMessages([
                        "quantity.{$i}" => "Return quantity cannot exceed remaining quantity for {$line->item?->name}.",
                    ]);
                }

                $soldUnits = collect($line->selected_units ?? [])->filter(fn($unit) => !empty($unit['key']))->values();
                $returnedKeys = SalesReturnItem::where('sales_invoice_item_id', $line->id)
                    ->get()
                    ->flatMap(fn($returnLine) => collect($returnLine->selected_units ?? [])->pluck('key'))
                    ->filter()
                    ->all();
                $availableUnits = $soldUnits
                    ->reject(fn($unit) => in_array($unit['key'], $returnedKeys, true))
                    ->keyBy('key');
                $selectedUnits = collect(json_decode($request->returned_units[$i] ?? '[]', true) ?: [])
                    ->pluck('key')
                    ->filter()
                    ->unique()
                    ->map(fn($key) => $availableUnits->get($key))
                    ->filter()
                    ->values();

                if ($soldUnits->isNotEmpty()) {
                    if ((float) ((int) $qty) !== $qty) {
                        throw ValidationException::withMessages([
                            "quantity.{$i}" => "Serialised item return quantity must be a whole number for {$line->item?->name}.",
                        ]);
                    }
                    if ($selectedUnits->count() !== (int) $qty) {
                        throw ValidationException::withMessages([
                            "returned_units.{$i}" => "Select exactly {$qty} serial number(s) to return for {$line->item?->name}.",
                        ]);
                    }
                }

                $ratio = (float) $line->quantity > 0 ? $qty / (float) $line->quantity : 0;
                $taxAmount = (float) $line->tax_amount * $ratio;
                $lineTotal = (float) $line->line_total * $ratio;
                SalesReturnItem::create([
                    'sales_return_id' => $return->id,
                    'sales_invoice_item_id' => $line->id,
                    'item_id' => $line->item_id,
                    'quantity' => $qty,
                    'unit' => $line->unit,
                    'unit_price' => $line->unit_price,
                    'tax_percent' => $line->tax_percent,
                    'tax_amount' => $taxAmount,
                    'line_total' => $lineTotal,
                    'selected_units' => $selectedUnits->all(),
                ]);
                $accounting->moveStock($line->item, [
                    'party_id' => $invoice->party_id,
                    'movement_date' => $return->return_date,
                    'movement_type' => 'sales_return',
                    'direction' => 'in',
                    'quantity' => $qty,
                    'unit_price' => $line->item->purchase_price,
                    'total_value' => $qty * (float) $line->item->purchase_price,
                    'reference_type' => SalesReturn::class,
                    'reference_id' => $return->id,
                    'reference_no' => $return->return_no,
                    'description' => 'Sales return stock in.',
                    'movement_units' => $selectedUnits->all(),
                ]);
                $subtotal += max(0, $lineTotal - $taxAmount);
                $tax += $taxAmount;
                $storedLines++;
            }

            if ($storedLines === 0) {
                throw ValidationException::withMessages(['quantity' => 'Enter return quantity for at least one item.']);
            }
            $return->update(['subtotal' => $subtotal, 'tax_amount' => $tax, 'grand_total' => $subtotal + $tax]);
            if ($invoice->sale_type === 'credit' && $invoice->party_id) {
                $accounting->postPartyLedger($invoice->party, [
                    'entry_date' => $return->return_date,
                    'entry_type' => 'sales_return',
                    'reference_type' => SalesReturn::class,
                    'reference_id' => $return->id,
                    'reference_no' => $return->return_no,
                    'debit' => 0,
                    'credit' => $return->grand_total,
                    'description' => 'Sales return credit adjustment.',
                ]);
            }
            $visibility->syncFromRequest($request, $return);
        });

        return redirect()->route('admin.sales-returns.index')->with('success', 'Sales return posted.');
    }

    public function show(SalesReturn $sales_return, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($sales_return);
        $sales_return->load(['invoice','party','items.item']);
        return view('admin.sales-returns.show', ['return' => $sales_return]);
    }

    public function edit(SalesReturn $sales_return, EntryVisibilityService $visibility, SerialUnitService $serialUnits)
    {
        $visibility->authorizeView($sales_return);
        $sales_return->load(['invoice.items.item','party','items.item','items.invoiceItem.item']);

        return view('admin.sales-returns.edit', [
            'return' => $sales_return,
            'lines' => $this->serialEditLines($sales_return, $serialUnits),
        ]);
    }

    public function update(Request $request, SalesReturn $sales_return, EntryVisibilityService $visibility, SerialUnitService $serialUnits)
    {
        $visibility->authorizeView($sales_return);
        $data = $request->validate([
            'returned_units' => ['nullable','array'],
            'returned_units.*' => ['nullable','string'],
        ]);

        DB::transaction(function () use ($sales_return, $data, $serialUnits) {
            $sales_return->load(['items.invoiceItem.item']);
            foreach ($sales_return->items as $index => $returnLine) {
                $invoiceLine = $returnLine->invoiceItem;
                if (!$invoiceLine) {
                    continue;
                }

                $soldUnits = collect($invoiceLine->selected_units ?? [])->filter(fn($unit) => !empty($unit['key']))->values();
                if ($soldUnits->isEmpty()) {
                    continue;
                }

                $returnedElsewhereKeys = $serialUnits->returnedKeysForInvoiceLine($invoiceLine->id, $sales_return->id);
                $currentUnits = collect($returnLine->selected_units ?? [])->filter(fn($unit) => !empty($unit['key']))->values();
                $allowedUnits = $soldUnits
                    ->reject(fn($unit) => in_array($unit['key'], $returnedElsewhereKeys, true))
                    ->keyBy('key');
                $selectedUnits = collect(json_decode($data['returned_units'][$index] ?? '[]', true) ?: [])
                    ->pluck('key')
                    ->filter()
                    ->unique()
                    ->map(fn($key) => $allowedUnits->get($key))
                    ->filter()
                    ->values();

                $requiredQty = (int) round((float) $returnLine->quantity);
                if ($selectedUnits->count() !== $requiredQty) {
                    throw ValidationException::withMessages([
                        "returned_units.{$index}" => "Select exactly {$requiredQty} serial number(s) for {$returnLine->item?->name}.",
                    ]);
                }

                $returnLine->update(['selected_units' => $selectedUnits->all()]);
            }
        });

        return redirect()->route('admin.sales-returns.show', $sales_return)->with('success', 'Sales return serial numbers updated.');
    }

    private function serialEditLines(SalesReturn $salesReturn, SerialUnitService $serialUnits): array
    {
        return $salesReturn->items->values()->map(function (SalesReturnItem $returnLine, int $index) use ($salesReturn, $serialUnits) {
            $invoiceLine = $returnLine->invoiceItem;
            $soldUnits = collect($invoiceLine?->selected_units ?? [])->filter(fn($unit) => !empty($unit['key']))->values();
            $returnedElsewhereKeys = $invoiceLine ? $serialUnits->returnedKeysForInvoiceLine($invoiceLine->id, $salesReturn->id) : [];
            $currentUnits = collect($returnLine->selected_units ?? [])->filter(fn($unit) => !empty($unit['key']))->values();
            $currentKeys = $currentUnits->pluck('key')->all();

            return [
                'index' => $index,
                'return_item_id' => $returnLine->id,
                'item' => $returnLine->item?->name ?? $invoiceLine?->item?->name ?? 'N/A',
                'quantity' => (float) $returnLine->quantity,
                'unit' => $returnLine->unit ?? '',
                'sold_units' => $soldUnits->all(),
                'available_units' => $soldUnits
                    ->reject(fn($unit) => in_array($unit['key'], $returnedElsewhereKeys, true))
                    ->values()
                    ->all(),
                'selected_units' => $currentUnits->all(),
                'selected_keys' => $currentKeys,
                'has_serials' => $soldUnits->isNotEmpty(),
            ];
        })->all();
    }

    private function nextNo(): string
    {
        return 'SR-' . str_pad((string) (SalesReturn::where('company_id', auth()->user()->current_company_id)->withTrashed()->count() + 1), 5, '0', STR_PAD_LEFT);
    }
}
