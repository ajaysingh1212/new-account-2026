<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SalesInvoice;
use App\Models\PurchaseBill;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\StockMovement;
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
                if ($invoice->inter_company_transfer) {
                    $availableKeys = $this->interCompanyAvailableUnits($invoice, $line)
                        ->pluck('unit.key')
                        ->filter()
                        ->flip();
                    $availableUnits = $availableUnits
                        ->filter(fn($unit) => $availableKeys->has($unit['key'] ?? null))
                        ->values();
                }
                $remainingQty = round(max(0, (float) $line->quantity - $alreadyReturned), 3);
                if ($invoice->inter_company_transfer && $soldUnits->isNotEmpty()) {
                    $remainingQty = min($remainingQty, $availableUnits->count());
                } elseif ($invoice->inter_company_transfer) {
                    $targetStock = $this->interCompanyTargetLines($invoice, $line)
                        ->sum(fn($targetLine) => max(0, (float) $targetLine->item?->current_stock));
                    $remainingQty = min($remainingQty, $targetStock);
                }
                $invoiceData[$invoice->id][] = [
                    'id' => $line->id,
                    'item' => optional($line->item)->name ?? 'N/A',
                    'qty' => (float) $line->quantity,
                    'already_returned' => round($alreadyReturned, 3),
                    'remaining_qty' => $remainingQty,
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
                $this->moveInterCompanySalesReturnStock(
                    $invoice,
                    $line,
                    $return,
                    $selectedUnits->all(),
                    $qty,
                    $accounting
                );
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

    public function update(Request $request, SalesReturn $sales_return, EntryVisibilityService $visibility, SerialUnitService $serialUnits, AccountingService $accounting)
    {
        if (!$sales_return->exists) {
            $routeReturn = $request->route('sales_return');
            $sales_return = SalesReturn::findOrFail($routeReturn instanceof SalesReturn ? $routeReturn->getKey() : $routeReturn);
        }
        $visibility->authorizeView($sales_return);
        $data = $request->validate([
            'returned_units' => ['nullable','array'],
            'returned_units.*' => ['nullable','string'],
        ]);

        $sales_return->loadMissing('invoice');
        $isInterCompanyReturn = (bool) $sales_return->invoice?->inter_company_transfer;
        $hadInterCompanyStockOut = $isInterCompanyReturn && StockMovement::where('reference_type', SalesReturn::class)
            ->where('reference_id', $sales_return->id)
            ->whereIn('movement_type', ['inter_company_sales_return_out', 'inter_company_sales_return_serial_repair'])
            ->exists();

        DB::transaction(function () use ($sales_return, $data, $serialUnits, $accounting) {
            $sales_return->load(['items.invoiceItem.item']);
            $invoice = $sales_return->invoice;
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
                StockMovement::where('reference_type', SalesReturn::class)
                    ->where('reference_id', $sales_return->id)
                    ->where('item_id', $returnLine->item_id)
                    ->where('movement_type', 'sales_return')
                    ->get()
                    ->each(fn(StockMovement $movement) => $movement->update(['movement_units' => $selectedUnits->all()]));

            }

            if ($invoice?->inter_company_transfer) {
                $this->synchronizeInterCompanySalesReturnStock($sales_return, $accounting);
            }
        });

        $message = 'Sales return serial numbers updated.';
        if ($isInterCompanyReturn) {
            $movements = StockMovement::where('reference_type', SalesReturn::class)
                ->where('reference_id', $sales_return->id)
                ->whereIn('movement_type', ['inter_company_sales_return_out', 'inter_company_sales_return_serial_repair'])
                ->get();
            $companyNames = Company::whereIn('id', $movements->pluck('company_id')->unique())
                ->pluck('name')
                ->implode(', ');
            $quantity = $movements->sum('quantity');

            if ($movements->isNotEmpty()) {
                $action = $hadInterCompanyStockOut ? 'verified and removed' : 'successfully deducted';
                $message .= " Inter-company confirmation: {$quantity} item(s) {$companyNames} stock se {$action}.";
            } else {
                $message .= ' Inter-company confirmation: no tracked stock movement was required.';
            }
        }

        return redirect()->route('admin.sales-returns.show', $sales_return)->with('success', $message);
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

    private function interCompanyAvailableUnits(SalesInvoice $invoice, $invoiceLine): \Illuminate\Support\Collection
    {
        if (!$invoice->inter_company_transfer || !$invoiceLine->item) {
            return collect();
        }

        $serialUnits = app(SerialUnitService::class);
        $soldUnits = collect($invoiceLine->selected_units ?? [])->filter(fn($unit) => !empty($unit['key']));
        if ($soldUnits->isEmpty()) {
            return collect();
        }

        return PurchaseBill::with(['items.item'])
            ->where('source_sales_invoice_id', $invoice->id)
            ->get()
            ->flatMap(function (PurchaseBill $bill) use ($invoiceLine, $serialUnits, $soldUnits) {
                return $bill->items
                    ->filter(fn($line) => $line->item?->item_code === $invoiceLine->item?->item_code)
                    ->flatMap(function ($targetLine) use ($bill, $serialUnits, $soldUnits) {
                        $currentUnits = collect($serialUnits->currentStockUnitsByItem(
                            (int) $bill->company_id,
                            (int) $targetLine->item_id
                        )[$targetLine->item_id] ?? []);

                        return $soldUnits->map(function ($soldUnit) use ($currentUnits, $targetLine) {
                            $currentUnit = $currentUnits->first(fn($unit) => $this->unitsMatch($soldUnit, $unit));

                            return $currentUnit ? [
                                'unit' => $soldUnit,
                                'target_unit' => $currentUnit,
                                'target_item' => $targetLine->item,
                            ] : null;
                        })->filter();
                    });
            })
            ->unique(fn($row) => $row['unit']['key'])
            ->values();
    }

    private function moveInterCompanySalesReturnStock(
        SalesInvoice $invoice,
        $invoiceLine,
        SalesReturn $return,
        array $selectedUnits,
        float $quantity,
        AccountingService $accounting
    ): void {
        if (!$invoice->inter_company_transfer) {
            return;
        }

        if (empty($selectedUnits)) {
            $remaining = $quantity;
            foreach ($this->interCompanyTargetLines($invoice, $invoiceLine) as $targetLine) {
                if ($remaining <= 0) {
                    break;
                }

                $targetItem = $targetLine->item;
                $qty = min($remaining, max(0, (float) $targetItem->current_stock));
                if ($qty <= 0) {
                    continue;
                }

                $this->postInterCompanySalesReturnOut($targetItem, $return, $qty, [], $accounting);
                $remaining -= $qty;
            }

            if ($remaining > 0.0001) {
                throw ValidationException::withMessages([
                    'quantity' => "Return quantity for {$invoiceLine->item?->name} is not available in the merged company's stock.",
                ]);
            }

            return;
        }

        $available = $this->interCompanyAvailableUnits($invoice, $invoiceLine)
            ->keyBy(fn($row) => $row['unit']['key']);
        $selectedKeys = collect($selectedUnits)->pluck('key')->filter()->unique();
        $destinations = $selectedKeys->map(fn($key) => $available->get($key))->filter();

        if ($destinations->count() !== $selectedKeys->count()) {
            throw ValidationException::withMessages([
                'returned_units' => "Selected serial for {$invoiceLine->item?->name} is not currently available in the merged company's stock.",
            ]);
        }

        $destinations->groupBy(fn($row) => $row['target_item']->id)
            ->each(function ($rows) use ($return, $accounting, $invoiceLine) {
                $targetItem = $rows->first()['target_item'];
                $movementUnits = $rows->pluck('target_unit')->values()->all();
                $qty = count($movementUnits);
                $this->postInterCompanySalesReturnOut($targetItem, $return, $qty, $movementUnits, $accounting);
            });
    }

    private function interCompanyTargetLines(SalesInvoice $invoice, $invoiceLine): \Illuminate\Support\Collection
    {
        return PurchaseBill::with(['items.item'])
            ->where('source_sales_invoice_id', $invoice->id)
            ->get()
            ->flatMap(fn(PurchaseBill $bill) => $bill->items)
            ->filter(fn($line) => $line->item?->item_code === $invoiceLine->item?->item_code)
            ->values();
    }

    private function postInterCompanySalesReturnOut($targetItem, SalesReturn $return, float $qty, array $units, AccountingService $accounting): void
    {
        $accounting->moveStock($targetItem, [
            'party_id' => null,
            'movement_date' => $return->return_date,
            'movement_type' => 'inter_company_sales_return_out',
            'direction' => 'out',
            'quantity' => $qty,
            'unit_price' => $targetItem->purchase_price,
            'total_value' => $qty * (float) $targetItem->purchase_price,
            'reference_type' => SalesReturn::class,
            'reference_id' => $return->id,
            'reference_no' => $return->return_no,
            'description' => 'Stock returned to source company through inter-company sales return.',
            'movement_units' => $units,
        ]);
    }

    private function synchronizeInterCompanySalesReturnStock(SalesReturn $return, AccountingService $accounting): void
    {
        $return->loadMissing(['invoice', 'items.invoiceItem.item']);
        $serialUnits = app(SerialUnitService::class);

        foreach ($return->items as $returnLine) {
            $invoiceLine = $returnLine->invoiceItem;
            if (!$invoiceLine || !$invoiceLine->item) {
                continue;
            }

            $selectedUnits = collect($returnLine->selected_units ?? [])->filter(fn($unit) => is_array($unit))->values();
            if ($selectedUnits->isEmpty()) {
                continue;
            }

            $targetLines = $this->interCompanyTargetLines($return->invoice, $invoiceLine);
            $targetItemIds = $targetLines->pluck('item_id')->map(fn($id) => (int) $id)->all();
            $existingMovements = StockMovement::where('reference_type', SalesReturn::class)
                ->where('reference_id', $return->id)
                ->where('movement_type', 'inter_company_sales_return_out')
                ->whereIn('item_id', $targetItemIds)
                ->orderBy('id')
                ->get();

            $destinations = $selectedUnits->map(function ($selectedUnit) use ($targetLines, $existingMovements, $serialUnits) {
                foreach ($targetLines as $targetLine) {
                    $currentUnits = collect($serialUnits->currentStockUnitsByItem(
                        (int) $targetLine->item->company_id,
                        (int) $targetLine->item_id
                    )[$targetLine->item_id] ?? []);
                    $targetUnit = $currentUnits->first(fn($unit) => $this->unitsMatch($selectedUnit, $unit));
                    if ($targetUnit) {
                        return ['target_item' => $targetLine->item, 'target_unit' => $targetUnit];
                    }

                    $movementUnit = $existingMovements
                        ->where('item_id', $targetLine->item_id)
                        ->flatMap(fn(StockMovement $movement) => collect($movement->movement_units ?? []))
                        ->first(fn($unit) => is_array($unit) && $this->unitsMatch($selectedUnit, $unit));
                    if ($movementUnit) {
                        return ['target_item' => $targetLine->item, 'target_unit' => array_merge($movementUnit, ['item_id' => $targetLine->item_id])];
                    }
                }

                return null;
            })->filter();

            if ($destinations->count() !== $selectedUnits->count()) {
                throw ValidationException::withMessages([
                    'returned_units' => "One or more serials for {$invoiceLine->item->name} could not be verified in the merged company's stock history.",
                ]);
            }

            $destinations->groupBy(fn($row) => $row['target_item']->id)
                ->each(function ($rows, $targetItemId) use ($existingMovements, $return, $accounting) {
                    $targetItem = $rows->first()['target_item'];
                    $desiredUnits = $rows->pluck('target_unit')->values();
                    $movements = $existingMovements->where('item_id', (int) $targetItemId)->values();
                    $existingQty = (int) round($movements->sum('quantity'));

                    if ($movements->isNotEmpty()) {
                        $offset = 0;
                        foreach ($movements as $movement) {
                            $movementQty = (int) round((float) $movement->quantity);
                            $movement->update([
                                'movement_units' => $desiredUnits->slice($offset, $movementQty)->values()->all(),
                            ]);
                            $offset += $movementQty;
                        }
                    }

                    if ($existingQty < $desiredUnits->count()) {
                        $missingUnits = $desiredUnits->slice($existingQty)->values()->all();
                        $this->postInterCompanySalesReturnOut(
                            $targetItem,
                            $return,
                            count($missingUnits),
                            $missingUnits,
                            $accounting
                        );
                    }

                    $currentUnits = collect(app(SerialUnitService::class)->currentStockUnitsByItem(
                        (int) $targetItem->company_id,
                        (int) $targetItem->id
                    )[$targetItem->id] ?? []);
                    $stillActiveUnits = $currentUnits
                        ->filter(fn($currentUnit) => $desiredUnits->contains(
                            fn($desiredUnit) => $this->unitsMatch($desiredUnit, $currentUnit)
                        ))
                        ->values();

                    if ($stillActiveUnits->isNotEmpty()) {
                        $latestMovementDate = StockMovement::where('company_id', $targetItem->company_id)
                            ->where('item_id', $targetItem->id)
                            ->max('movement_date');
                        $repairDate = collect([
                            $return->return_date?->format('Y-m-d'),
                            $latestMovementDate,
                        ])->filter()->max();

                        $accounting->moveStock($targetItem, [
                            'party_id' => null,
                            'movement_date' => $repairDate ?: now()->toDateString(),
                            'movement_type' => 'inter_company_sales_return_serial_repair',
                            'direction' => 'out',
                            'quantity' => 0,
                            'unit_price' => 0,
                            'total_value' => 0,
                            'reference_type' => SalesReturn::class,
                            'reference_id' => $return->id,
                            'reference_no' => $return->return_no,
                            'description' => 'Serial ownership repair after inter-company sales return.',
                            'movement_units' => $stillActiveUnits->all(),
                        ]);
                    }
                });
        }

        $this->assertInterCompanyReturnedUnitsRemoved($return);
    }

    private function assertInterCompanyReturnedUnitsRemoved(SalesReturn $return): void
    {
        $serialUnits = app(SerialUnitService::class);

        foreach ($return->items as $returnLine) {
            $invoiceLine = $returnLine->invoiceItem;
            if (!$invoiceLine) {
                continue;
            }

            $selectedUnits = collect($returnLine->selected_units ?? [])->filter(fn($unit) => is_array($unit));
            foreach ($this->interCompanyTargetLines($return->invoice, $invoiceLine) as $targetLine) {
                $currentUnits = collect($serialUnits->currentStockUnitsByItem(
                    (int) $targetLine->item->company_id,
                    (int) $targetLine->item_id
                )[$targetLine->item_id] ?? []);
                $stillPresent = $currentUnits->filter(fn($currentUnit) => $selectedUnits->contains(
                    fn($selectedUnit) => $this->unitsMatch($selectedUnit, $currentUnit)
                ));

                if ($stillPresent->isNotEmpty()) {
                    $labels = $stillPresent->map(fn($unit) => $unit['serial_no'] ?? $unit['sku'] ?? $unit['key'] ?? 'unknown')->implode(', ');
                    throw ValidationException::withMessages([
                        'returned_units' => "Stock verification failed. These serials are still present in the merged company: {$labels}.",
                    ]);
                }
            }
        }
    }

    private function unitsMatch(array $left, array $right): bool
    {
        foreach (['key', 'serial_no', 'vts_sim', 'buyer_code', 'sku'] as $field) {
            if (!empty($left[$field]) && !empty($right[$field]) && (string) $left[$field] === (string) $right[$field]) {
                return true;
            }
        }

        return false;
    }
}
