<?php

namespace App\Http\Controllers\Admin\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\PurchaseBill;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\StockMovement;
use App\Services\AccountingService;
use App\Services\EntryVisibilityService;
use App\Services\SerialUnitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseReturnController extends Controller
{
    public function index(EntryVisibilityService $visibility)
    {
        $returns = $visibility->scopeForUser(
            PurchaseReturn::with(['bill', 'party', 'creator'])->latest(),
            PurchaseReturn::class
        )->get();

        return view('admin.purchase-returns.index', compact('returns'));
    }

    public function create(EntryVisibilityService $visibility)
    {
        $bills = $visibility->scopeForUser(
            PurchaseBill::with(['party', 'items.item'])->latest(),
            PurchaseBill::class
        )->get();

        return view('admin.purchase-returns.create', [
            'bills' => $bills,
            'returnNo' => $this->nextNo(),
        ]);
    }

    public function billItems(Request $request, EntryVisibilityService $visibility)
    {
        $bill = PurchaseBill::with(['party', 'items.item'])->findOrFail($request->bill_id);
        $visibility->authorizeView($bill);

        $lines = $bill->items->map(function ($line) use ($bill) {
            return [
                'id' => $line->id,
                'item_id' => $line->item_id,
                'item_name' => $line->item?->name ?? '-',
                'purchased_qty' => (float) $line->quantity,
                'unit' => $line->unit ?? '',
                'unit_price' => (float) $line->unit_price,
                'tax_percent' => (float) $line->tax_percent,
                'line_total' => (float) $line->line_total,
                'tax_amount' => (float) $line->tax_amount,
                'current_stock' => $this->currentStock($line->item_id, $bill->company_id),
                'purchased_units' => collect($line->selected_units ?? [])->values()->all(),
                'available_units' => $this->availableUnitsForPurchaseLine($line),
            ];
        })->values();

        return response()->json([
            'party' => $bill->party?->display_name ?? 'Cash',
            'purchase_type' => $bill->purchase_type,
            'lines' => $lines,
        ]);
    }

    public function store(Request $request, AccountingService $accounting, EntryVisibilityService $visibility)
    {
        $request->validate([
            'purchase_bill_id' => ['required', 'exists:purchase_bills,id'],
            'return_no' => ['nullable', 'max:30'],
            'return_date' => ['required', 'date'],
            'reason' => ['nullable', 'string'],
            'line_id' => ['required', 'array'],
            'quantity.*' => ['required', 'numeric', 'min:0.001'],
            'returned_units' => ['nullable', 'array'],
            'returned_units.*' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $accounting, $visibility) {
            $bill = PurchaseBill::with(['items.item', 'party'])->lockForUpdate()->findOrFail($request->purchase_bill_id);

            $return = PurchaseReturn::create([
                'company_id' => $bill->company_id,
                'purchase_bill_id' => $bill->id,
                'party_id' => $bill->party_id,
                'return_no' => $request->return_no ?: $this->nextNo(),
                'return_date' => $request->return_date,
                'reason' => $request->reason,
                'created_by' => auth()->id(),
            ]);

            [$subtotal, $tax] = $this->processLines($request, $bill, $return, $accounting);

            $return->update([
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'grand_total' => $subtotal + $tax,
            ]);

            $this->postPartyLedger($bill, $return, $accounting, $subtotal + $tax);
            $visibility->syncFromRequest($request, $return);
        });

        return redirect()->route('admin.purchase-returns.index')->with('success', 'Purchase return posted successfully.');
    }

    public function show(PurchaseReturn $purchaseReturn, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($purchaseReturn);
        $purchaseReturn->load(['bill', 'party', 'items.item']);

        return view('admin.purchase-returns.show', ['return' => $purchaseReturn]);
    }

    public function edit(PurchaseReturn $purchaseReturn, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($purchaseReturn);
        $purchaseReturn->load(['bill.items.item', 'items.item', 'party']);

        $existingQty = $purchaseReturn->items->keyBy('purchase_bill_item_id')
            ->map(fn($ri) => (float) $ri->quantity);

        return view('admin.purchase-returns.edit', [
            'return' => $purchaseReturn,
            'existingQty' => $existingQty,
            'serialLines' => $this->serialEditLines($purchaseReturn),
        ]);
    }

    public function update(Request $request, PurchaseReturn $purchaseReturn, AccountingService $accounting, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($purchaseReturn);

        $request->validate([
            'return_no' => ['nullable', 'max:30'],
            'return_date' => ['required', 'date'],
            'reason' => ['nullable', 'string'],
            'line_id' => ['required', 'array'],
            'quantity.*' => ['required', 'numeric', 'min:0.001'],
            'returned_units' => ['nullable', 'array'],
            'returned_units.*' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $purchaseReturn, $accounting, $visibility) {
            $bill = PurchaseBill::with(['items.item', 'party'])->lockForUpdate()->findOrFail($purchaseReturn->purchase_bill_id);

            $this->reverseOldEntries($purchaseReturn->load('items.item'), $accounting, $bill);
            $purchaseReturn->items()->delete();

            $purchaseReturn->update([
                'return_no' => $request->return_no ?: $purchaseReturn->return_no,
                'return_date' => $request->return_date,
                'reason' => $request->reason,
            ]);

            [$subtotal, $tax] = $this->processLines($request, $bill, $purchaseReturn, $accounting);

            $purchaseReturn->update([
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'grand_total' => $subtotal + $tax,
            ]);

            $this->postPartyLedger($bill, $purchaseReturn, $accounting, $subtotal + $tax);
            $visibility->syncFromRequest($request, $purchaseReturn);
        });

        return redirect()->route('admin.purchase-returns.show', $purchaseReturn)->with('success', 'Purchase return updated successfully.');
    }

    private function processLines(Request $request, PurchaseBill $bill, PurchaseReturn $return, AccountingService $accounting): array
    {
        $subtotal = $tax = 0;

        foreach ($request->line_id as $i => $lineId) {
            $line = $bill->items->firstWhere('id', (int) $lineId);
            if (!$line) {
                continue;
            }

            $qty = min((float) ($request->input("quantity.{$i}") ?? 0), (float) $line->quantity);
            if ($qty <= 0) {
                continue;
            }

            $selectedUnits = $this->selectedReturnUnits($request, $line, $return, $qty, $i);
            $ratio = (float) $line->quantity > 0 ? $qty / (float) $line->quantity : 0;
            $taxAmount = (float) $line->tax_amount * $ratio;
            $lineTotal = (float) $line->line_total * $ratio;

            PurchaseReturnItem::create([
                'purchase_return_id' => $return->id,
                'purchase_bill_item_id' => $line->id,
                'item_id' => $line->item_id,
                'quantity' => $qty,
                'unit' => $line->unit,
                'unit_price' => $line->unit_price,
                'tax_percent' => $line->tax_percent,
                'tax_amount' => $taxAmount,
                'line_total' => $lineTotal,
                'selected_units' => $selectedUnits,
            ]);

            $accounting->moveStock($line->item, [
                'party_id' => $bill->party_id,
                'movement_date' => $return->return_date,
                'movement_type' => 'purchase_return',
                'direction' => 'out',
                'quantity' => $qty,
                'unit_price' => $line->unit_price,
                'total_value' => $lineTotal,
                'reference_type' => PurchaseReturn::class,
                'reference_id' => $return->id,
                'reference_no' => $return->return_no,
                'description' => 'Purchase return stock out.',
                'movement_units' => $selectedUnits,
            ]);

            $this->moveInterCompanyReturnStock($bill, $line, $return, $accounting, $selectedUnits, 'in', $qty, $lineTotal);

            $subtotal += max(0, $lineTotal - $taxAmount);
            $tax += $taxAmount;
        }

        if ($subtotal + $tax <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Enter return quantity for at least one item.']);
        }

        return [$subtotal, $tax];
    }

    private function selectedReturnUnits(Request $request, $line, PurchaseReturn $return, float $qty, int $index): array
    {
        $purchasedUnits = collect($line->selected_units ?? [])->filter(fn($unit) => !empty($unit['key']))->values();
        if ($purchasedUnits->isEmpty()) {
            return [];
        }

        if ((float) ((int) $qty) !== $qty) {
            throw ValidationException::withMessages([
                "quantity.{$index}" => "Serial item return quantity must be a whole number for {$line->item?->name}.",
            ]);
        }

        $availableUnits = collect($this->availableUnitsForPurchaseLine($line, $return->id))->keyBy('key');
        $selectedUnits = collect(json_decode($request->input("returned_units.{$index}", '[]'), true) ?: [])
            ->pluck('key')
            ->filter()
            ->unique()
            ->map(fn($key) => $availableUnits->get($key))
            ->filter()
            ->values();

        if ($selectedUnits->isEmpty()) {
            $selectedUnits = $availableUnits->values()->take((int) $qty);
        }

        if ($selectedUnits->count() !== (int) $qty) {
            throw ValidationException::withMessages([
                "returned_units.{$index}" => "Select exactly {$qty} serial number(s) to return for {$line->item?->name}.",
            ]);
        }

        return $selectedUnits->values()->all();
    }

    private function postPartyLedger(PurchaseBill $bill, PurchaseReturn $return, AccountingService $accounting, float $amount): void
    {
        if ($bill->purchase_type === 'credit' && $bill->party_id) {
            $accounting->postPartyLedger($bill->party, [
                'entry_date' => $return->return_date,
                'entry_type' => 'purchase_return',
                'reference_type' => PurchaseReturn::class,
                'reference_id' => $return->id,
                'reference_no' => $return->return_no,
                'credit' => 0,
                'debit' => $amount,
                'description' => 'Purchase return debit adjustment.',
            ]);
        }
    }

    private function reverseOldEntries(PurchaseReturn $return, AccountingService $accounting, PurchaseBill $bill): void
    {
        foreach ($return->items as $ri) {
            if (!$ri->item) {
                continue;
            }

            $accounting->moveStock($ri->item, [
                'party_id' => $return->party_id,
                'movement_date' => $return->return_date,
                'movement_type' => 'purchase_return_reversal',
                'direction' => 'in',
                'quantity' => $ri->quantity,
                'unit_price' => $ri->unit_price,
                'total_value' => $ri->line_total,
                'reference_type' => PurchaseReturn::class,
                'reference_id' => $return->id,
                'reference_no' => $return->return_no,
                'description' => 'Purchase return update reversal.',
                'movement_units' => $ri->selected_units ?? [],
            ]);

            $this->moveInterCompanyReturnStock($bill, $ri->billItem ?: $ri, $return, $accounting, $ri->selected_units ?? [], 'out', (float) $ri->quantity, (float) $ri->line_total);
        }

        if ($bill->purchase_type === 'credit' && $bill->party_id) {
            $accounting->postPartyLedger($bill->party, [
                'entry_date' => $return->return_date,
                'entry_type' => 'purchase_return_reversal',
                'reference_type' => PurchaseReturn::class,
                'reference_id' => $return->id,
                'reference_no' => $return->return_no,
                'credit' => $return->grand_total,
                'debit' => 0,
                'description' => 'Purchase return update ledger reversal.',
            ]);
        }
    }

    private function serialEditLines(PurchaseReturn $purchaseReturn): array
    {
        return $purchaseReturn->bill->items->values()->map(function ($line, int $index) use ($purchaseReturn) {
            $returnLine = $purchaseReturn->items->firstWhere('purchase_bill_item_id', $line->id);
            $selectedUnits = collect($returnLine?->selected_units ?? [])->filter(fn($unit) => !empty($unit['key']))->values();
            $available = collect($this->availableUnitsForPurchaseLine($line, $purchaseReturn->id))
                ->concat($selectedUnits)
                ->unique('key')
                ->values();

            return [
                'index' => $index,
                'line_id' => $line->id,
                'has_serials' => collect($line->selected_units ?? [])->filter(fn($unit) => !empty($unit['key']))->isNotEmpty(),
                'purchased_units' => collect($line->selected_units ?? [])->values()->all(),
                'available_units' => $available->all(),
                'selected_units' => $selectedUnits->all(),
            ];
        })->all();
    }

    private function availableUnitsForPurchaseLine($line, ?int $includeReturnId = null): array
    {
        $bill = $line->purchaseBill ?? PurchaseBill::find($line->purchase_bill_id);
        $companyId = (int) ($bill?->company_id ?? auth()->user()->current_company_id);
        $purchasedUnits = collect($line->selected_units ?? [])->filter(fn($unit) => !empty($unit['key']))->values();

        if ($purchasedUnits->isEmpty()) {
            return [];
        }

        $serialUnits = app(SerialUnitService::class);
        $activeStockCounts = StockMovement::where('company_id', $companyId)
            ->where('item_id', $line->item_id)
            ->get()
            ->flatMap(function (StockMovement $movement) use ($serialUnits) {
                $multiplier = $movement->direction === 'in' ? 1 : -1;

                return collect($serialUnits->movementUnits($movement))
                    ->map(fn($unit) => is_array($unit) ? $serialUnits->unitIdentity($unit) : null)
                    ->filter()
                    ->map(fn($identity) => ['identity' => $identity, 'count' => $multiplier]);
            })
            ->groupBy('identity')
            ->map(fn($rows) => $rows->sum('count'));

        $returnedElsewhere = PurchaseReturnItem::where('purchase_bill_item_id', $line->id)
            ->when($includeReturnId, fn($query) => $query->where('purchase_return_id', '<>', $includeReturnId))
            ->get()
            ->flatMap(fn($returnLine) => collect($returnLine->selected_units ?? [])->pluck('key'))
            ->filter()
            ->all();

        return $purchasedUnits
            ->reject(fn($unit) => in_array($unit['key'], $returnedElsewhere, true))
            ->filter(fn($unit) => (int) ($activeStockCounts->get($serialUnits->unitIdentity($unit), 0)) > 0)
            ->values()
            ->all();
    }

    private function moveInterCompanyReturnStock(PurchaseBill $bill, $line, PurchaseReturn $return, AccountingService $accounting, array $units, string $direction, float $qty, float $lineTotal): void
    {
        if (!$bill->inter_company_source_company_id || !$line->item) {
            return;
        }

        $sourceItem = Item::where('company_id', $bill->inter_company_source_company_id)
            ->where('item_code', $line->item->item_code)
            ->first();

        if (!$sourceItem) {
            return;
        }

        $accounting->moveStock($sourceItem, [
            'party_id' => null,
            'movement_date' => $return->return_date,
            'movement_type' => $direction === 'in' ? 'inter_company_purchase_return_in' : 'inter_company_purchase_return_reversal',
            'direction' => $direction,
            'quantity' => $qty,
            'unit_price' => $line->unit_price,
            'total_value' => $lineTotal,
            'reference_type' => PurchaseReturn::class,
            'reference_id' => $return->id,
            'reference_no' => $return->return_no,
            'description' => $direction === 'in' ? 'Stock received from inter-company purchase return.' : 'Inter-company purchase return reversal.',
            'movement_units' => $units,
        ]);
    }

    private function currentStock(int $itemId, ?int $companyId = null): float
    {
        return (float) StockMovement::where('item_id', $itemId)
            ->where('company_id', $companyId ?: auth()->user()->current_company_id)
            ->selectRaw("SUM(CASE WHEN direction='in' THEN quantity ELSE -quantity END) as net")
            ->value('net');
    }

    private function nextNo(): string
    {
        $count = PurchaseReturn::where('company_id', auth()->user()->current_company_id)->withTrashed()->count() + 1;

        return 'PR-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }
}
