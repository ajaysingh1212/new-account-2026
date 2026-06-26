<?php

namespace App\Http\Controllers\Admin\Purchase;

use App\Http\Controllers\Controller;
use App\Models\PurchaseBill;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\StockMovement;
use App\Services\AccountingService;
use App\Services\EntryVisibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'bills'    => $bills,
            'returnNo' => $this->nextNo(),
        ]);
    }

    /**
     * AJAX: Return bill items with current stock for the selected bill.
     */
    public function billItems(Request $request, EntryVisibilityService $visibility)
    {
        $bill = PurchaseBill::with(['party', 'items.item'])->findOrFail($request->bill_id);
        $visibility->authorizeView($bill);

        $lines = $bill->items->map(function ($line) {
            $currentStock = $this->currentStock($line->item_id);
            return [
                'id'            => $line->id,
                'item_id'       => $line->item_id,
                'item_name'     => $line->item?->name ?? '—',
                'purchased_qty' => (float) $line->quantity,
                'unit'          => $line->unit ?? '',
                'unit_price'    => (float) $line->unit_price,
                'tax_percent'   => (float) $line->tax_percent,
                'line_total'    => (float) $line->line_total,
                'tax_amount'    => (float) $line->tax_amount,
                'current_stock' => $currentStock,
            ];
        })->values();

        return response()->json([
            'party'         => $bill->party?->display_name ?? 'Cash',
            'purchase_type' => $bill->purchase_type,
            'lines'         => $lines,
        ]);
    }

    public function store(Request $request, AccountingService $accounting, EntryVisibilityService $visibility)
    {
        $request->validate([
            'purchase_bill_id' => ['required', 'exists:purchase_bills,id'],
            'return_no'        => ['nullable', 'max:30'],
            'return_date'      => ['required', 'date'],
            'reason'           => ['nullable', 'string'],
            'line_id'          => ['required', 'array'],
            'quantity.*'       => ['required', 'numeric', 'min:0.001'],
        ]);

        DB::transaction(function () use ($request, $accounting, $visibility) {
            $bill = PurchaseBill::with(['items.item', 'party'])->findOrFail($request->purchase_bill_id);

            $return = PurchaseReturn::create([
                'company_id'       => $bill->company_id,
                'purchase_bill_id' => $bill->id,
                'party_id'         => $bill->party_id,
                'return_no'        => $request->return_no ?: $this->nextNo(),
                'return_date'      => $request->return_date,
                'reason'           => $request->reason,
                'created_by'       => auth()->id(),
            ]);

            [$subtotal, $tax] = $this->processLines($request, $bill, $return, $accounting);

            $return->update([
                'subtotal'    => $subtotal,
                'tax_amount'  => $tax,
                'grand_total' => $subtotal + $tax,
            ]);

            $this->postPartyLedger($bill, $return, $accounting, $subtotal + $tax);
            $visibility->syncFromRequest($request, $return);
        });

        return redirect()->route('admin.purchase-returns.index')
            ->with('success', 'Purchase return posted successfully.');
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
        $purchaseReturn->load(['bill.items.item', 'items', 'party']);

        $bills = $visibility->scopeForUser(
            PurchaseBill::with(['party', 'items.item'])->latest(),
            PurchaseBill::class
        )->get();

        // Build existing return quantities keyed by bill line id
        $existingQty = $purchaseReturn->items->keyBy('purchase_bill_item_id')
            ->map(fn($ri) => (float) $ri->quantity);

        return view('admin.purchase-returns.edit', [
            'return'      => $purchaseReturn,
            'bills'       => $bills,
            'existingQty' => $existingQty,
        ]);
    }

    public function update(Request $request, PurchaseReturn $purchaseReturn, AccountingService $accounting, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($purchaseReturn);

        $request->validate([
            'return_no'   => ['nullable', 'max:30'],
            'return_date' => ['required', 'date'],
            'reason'      => ['nullable', 'string'],
            'line_id'     => ['required', 'array'],
            'quantity.*'  => ['required', 'numeric', 'min:0.001'],
        ]);

        DB::transaction(function () use ($request, $purchaseReturn, $accounting, $visibility) {
            $bill = PurchaseBill::with(['items.item', 'party'])->findOrFail($purchaseReturn->purchase_bill_id);

            // ── 1. Reverse old stock movements & ledger ──────────────────
            $this->reverseOldEntries($purchaseReturn, $accounting, $bill);

            // ── 2. Delete old return items ────────────────────────────────
            $purchaseReturn->items()->delete();

            // ── 3. Update header ──────────────────────────────────────────
            $purchaseReturn->update([
                'return_no'   => $request->return_no ?: $purchaseReturn->return_no,
                'return_date' => $request->return_date,
                'reason'      => $request->reason,
            ]);

            // ── 4. Re-create lines ────────────────────────────────────────
            [$subtotal, $tax] = $this->processLines($request, $bill, $purchaseReturn, $accounting);

            $purchaseReturn->update([
                'subtotal'    => $subtotal,
                'tax_amount'  => $tax,
                'grand_total' => $subtotal + $tax,
            ]);

            $this->postPartyLedger($bill, $purchaseReturn, $accounting, $subtotal + $tax);
            $visibility->syncFromRequest($request, $purchaseReturn);
        });

        return redirect()->route('admin.purchase-returns.show', $purchaseReturn)
            ->with('success', 'Purchase return updated successfully.');
    }

    // ═══════════════════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ═══════════════════════════════════════════════════════════════════

    private function processLines(Request $request, PurchaseBill $bill, PurchaseReturn $return, AccountingService $accounting): array
    {
        $subtotal = $tax = 0;

        foreach ($request->line_id as $i => $lineId) {
            $line = $bill->items->firstWhere('id', (int) $lineId);
            if (!$line) continue;

            $quantities = $request->input('quantity', []);
            $qty = min((float) ($quantities[$i] ?? 0), (float) $line->quantity);
            if ($qty <= 0) continue;

            $ratio      = (float) $line->quantity > 0 ? $qty / (float) $line->quantity : 0;
            $taxAmount  = (float) $line->tax_amount  * $ratio;
            $lineTotal  = (float) $line->line_total   * $ratio;

            PurchaseReturnItem::create([
                'purchase_return_id'    => $return->id,
                'purchase_bill_item_id' => $line->id,
                'item_id'               => $line->item_id,
                'quantity'              => $qty,
                'unit'                  => $line->unit,
                'unit_price'            => $line->unit_price,
                'tax_percent'           => $line->tax_percent,
                'tax_amount'            => $taxAmount,
                'line_total'            => $lineTotal,
            ]);

            // Stock OUT from our warehouse (goods going back to supplier)
            $accounting->moveStock($line->item, [
                'party_id'       => $bill->party_id,
                'movement_date'  => $return->return_date,
                'movement_type'  => 'purchase_return',
                'direction'      => 'out',
                'quantity'       => $qty,
                'unit_price'     => $line->unit_price,
                'total_value'    => $lineTotal,
                'reference_type' => PurchaseReturn::class,
                'reference_id'   => $return->id,
                'reference_no'   => $return->return_no,
                'description'    => 'Purchase return — stock out.',
            ]);

            $subtotal += max(0, $lineTotal - $taxAmount);
            $tax      += $taxAmount;
        }

        return [$subtotal, $tax];
    }

    private function postPartyLedger(PurchaseBill $bill, PurchaseReturn $return, AccountingService $accounting, float $amount): void
    {
        if ($bill->purchase_type === 'credit' && $bill->party_id) {
            $accounting->postPartyLedger($bill->party, [
                'entry_date'      => $return->return_date,
                'entry_type'      => 'purchase_return',
                'reference_type'  => PurchaseReturn::class,
                'reference_id'    => $return->id,
                'reference_no'    => $return->return_no,
                'credit'          => 0,
                'debit'           => $amount,   // reduces payable (debit the supplier)
                'description'     => 'Purchase return — debit adjustment.',
            ]);
        }
    }

    /**
     * Reverse old stock movements and ledger entry before re-posting on update.
     */
    private function reverseOldEntries(PurchaseReturn $return, AccountingService $accounting, PurchaseBill $bill): void
    {
        foreach ($return->items as $ri) {
            if (!$ri->item) continue;
            // Stock IN reversal (bring stock back as if return never happened)
            $accounting->moveStock($ri->item, [
                'party_id'       => $return->party_id,
                'movement_date'  => $return->return_date,
                'movement_type'  => 'purchase_return_reversal',
                'direction'      => 'in',
                'quantity'       => $ri->quantity,
                'unit_price'     => $ri->unit_price,
                'total_value'    => $ri->line_total,
                'reference_type' => PurchaseReturn::class,
                'reference_id'   => $return->id,
                'reference_no'   => $return->return_no,
                'description'    => 'Purchase return update — reversal.',
            ]);
        }

        // Reverse ledger debit
        if ($bill->purchase_type === 'credit' && $bill->party_id) {
            $accounting->postPartyLedger($bill->party, [
                'entry_date'      => $return->return_date,
                'entry_type'      => 'purchase_return_reversal',
                'reference_type'  => PurchaseReturn::class,
                'reference_id'    => $return->id,
                'reference_no'    => $return->return_no,
                'credit'          => $return->grand_total,  // re-credit to reverse debit
                'debit'           => 0,
                'description'     => 'Purchase return update — ledger reversal.',
            ]);
        }
    }

    private function currentStock(int $itemId): float
    {
        // Sum all stock movements for this item in the current company
        return (float) StockMovement::where('item_id', $itemId)
            ->where('company_id', auth()->user()->current_company_id)
            ->selectRaw("SUM(CASE WHEN direction='in' THEN quantity ELSE -quantity END) as net")
            ->value('net');
    }

    private function nextNo(): string
    {
        $count = PurchaseReturn::where('company_id', auth()->user()->current_company_id)
            ->withTrashed()->count() + 1;
        return 'PR-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }
}
