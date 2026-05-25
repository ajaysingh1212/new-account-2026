<?php

namespace App\Http\Controllers\Admin\Purchase;

use App\Http\Controllers\Controller;
use App\Models\PurchaseBill;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Services\AccountingService;
use App\Services\EntryVisibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseReturnController extends Controller
{
    public function index(EntryVisibilityService $visibility)
    {
        $returns = $visibility->scopeForUser(PurchaseReturn::with(['bill','party','creator'])->latest(), PurchaseReturn::class)->get();
        return view('admin.purchase-returns.index', compact('returns'));
    }

    public function create(EntryVisibilityService $visibility)
    {
        $bills = $visibility->scopeForUser(PurchaseBill::with(['party','items.item'])->latest(), PurchaseBill::class)->get();
        return view('admin.purchase-returns.create', ['bills' => $bills, 'returnNo' => $this->nextNo()]);
    }

    public function store(Request $request, AccountingService $accounting, EntryVisibilityService $visibility)
    {
        $data = $request->validate([
            'purchase_bill_id' => ['required','exists:purchase_bills,id'],
            'return_no' => ['nullable','max:30'],
            'return_date' => ['required','date'],
            'reason' => ['nullable','string'],
            'line_id' => ['required','array'],
            'quantity.*' => ['required','numeric','min:0.001'],
        ]);

        DB::transaction(function () use ($request, $data, $accounting, $visibility) {
            $bill = PurchaseBill::with(['items.item','party'])->findOrFail($data['purchase_bill_id']);
            $return = PurchaseReturn::create([
                'company_id' => $bill->company_id,
                'purchase_bill_id' => $bill->id,
                'party_id' => $bill->party_id,
                'return_no' => $data['return_no'] ?: $this->nextNo(),
                'return_date' => $data['return_date'],
                'reason' => $data['reason'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $subtotal = $tax = 0;
            foreach ($request->line_id as $i => $lineId) {
                $line = $bill->items->firstWhere('id', (int) $lineId);
                if (!$line) continue;
                $qty = min((float) $request->quantity[$i], (float) $line->quantity);
                if ($qty <= 0) continue;
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
                ]);
                $subtotal += max(0, $lineTotal - $taxAmount);
                $tax += $taxAmount;
            }

            $return->update(['subtotal' => $subtotal, 'tax_amount' => $tax, 'grand_total' => $subtotal + $tax]);
            if ($bill->purchase_type === 'credit' && $bill->party_id) {
                $accounting->postPartyLedger($bill->party, [
                    'entry_date' => $return->return_date,
                    'entry_type' => 'purchase_return',
                    'reference_type' => PurchaseReturn::class,
                    'reference_id' => $return->id,
                    'reference_no' => $return->return_no,
                    'credit' => 0,
                    'debit' => $return->grand_total,
                    'description' => 'Purchase return debit adjustment.',
                ]);
            }
            $visibility->syncFromRequest($request, $return);
        });

        return redirect()->route('admin.purchase-returns.index')->with('success', 'Purchase return posted.');
    }

    public function show(PurchaseReturn $purchaseReturn, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($purchaseReturn);
        $purchaseReturn->load(['bill','party','items.item']);
        return view('admin.purchase-returns.show', ['return' => $purchaseReturn]);
    }

    private function nextNo(): string
    {
        return 'PR-' . str_pad((string) (PurchaseReturn::where('company_id', auth()->user()->current_company_id)->withTrashed()->count() + 1), 5, '0', STR_PAD_LEFT);
    }
}
