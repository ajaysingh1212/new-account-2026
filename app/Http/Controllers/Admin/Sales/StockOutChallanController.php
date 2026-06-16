<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Party;
use App\Models\StockOutChallan;
use App\Models\StockOutChallanItem;
use App\Services\AccountingService;
use App\Services\EntryVisibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockOutChallanController extends Controller
{
    public function index(EntryVisibilityService $visibility)
    {
        $challans = $visibility->scopeForUser(
            StockOutChallan::with(['party','creator','items.item'])->latest(),
            StockOutChallan::class
        )->get();

        return view('admin.stock-out-challans.index', compact('challans'));
    }

    public function create()
    {
        return view('admin.stock-out-challans.create', $this->formData(new StockOutChallan));
    }

    public function store(Request $request, AccountingService $accounting, EntryVisibilityService $visibility)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($request, $data, $accounting, $visibility) {
            $challan = StockOutChallan::create(array_merge($data, [
                'company_id' => auth()->user()->current_company_id,
                'challan_no' => $data['challan_no'] ?: $this->nextNo(),
                'status' => 'issued',
                'ip_address' => $request->ip(),
                'user_role' => auth()->user()?->rolesForCompany(auth()->user()->current_company_id)->pluck('name')->join(', '),
                'created_by' => auth()->id(),
            ]));

            $challan->update($this->storeLines($request, $challan, $accounting));
            $visibility->syncFromRequest($request, $challan);
        });

        return redirect()->route('admin.stock-out-challans.index')->with('success', 'Special stock out challan created. Stock reduced without party ledger.');
    }

    public function show(StockOutChallan $stockOutChallan, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($stockOutChallan);
        $stockOutChallan->load(['party','items.item','creator']);
        return view('admin.stock-out-challans.show', compact('stockOutChallan'));
    }

    public function edit(StockOutChallan $stockOutChallan, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($stockOutChallan);
        abort_if($stockOutChallan->status === 'cancelled', 422, 'Cancelled stock out challan cannot be edited.');
        $stockOutChallan->load('items');
        return view('admin.stock-out-challans.edit', $this->formData($stockOutChallan));
    }

    public function update(Request $request, StockOutChallan $stockOutChallan, AccountingService $accounting, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($stockOutChallan);
        abort_if($stockOutChallan->status === 'cancelled', 422, 'Cancelled stock out challan cannot be edited.');
        $data = $this->validated($request);

        DB::transaction(function () use ($request, $stockOutChallan, $data, $accounting, $visibility) {
            $stockOutChallan->load('items.item');
            $this->reverseStockOut($stockOutChallan, $accounting, 'stock_out_challan_update_reversal');
            $stockOutChallan->items()->delete();
            $stockOutChallan->update(array_merge($data, [
                'ip_address' => $request->ip(),
                'user_role' => auth()->user()?->rolesForCompany($stockOutChallan->company_id)->pluck('name')->join(', '),
            ]));
            $stockOutChallan->update($this->storeLines($request, $stockOutChallan, $accounting));
            $visibility->syncFromRequest($request, $stockOutChallan);
        });

        return redirect()->route('admin.stock-out-challans.show', $stockOutChallan)->with('success', 'Special stock out challan updated and stock reposted.');
    }

    public function print(StockOutChallan $stockOutChallan, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($stockOutChallan);
        $stockOutChallan->load(['party','items.item']);
        return view('admin.stock-out-challans.print', compact('stockOutChallan'));
    }

    public function cancel(StockOutChallan $stockOutChallan, AccountingService $accounting, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($stockOutChallan);
        abort_if($stockOutChallan->status === 'cancelled', 422, 'Already cancelled.');

        DB::transaction(function () use ($stockOutChallan, $accounting) {
            $stockOutChallan->load('items.item');
            $this->reverseStockOut($stockOutChallan, $accounting, 'stock_out_challan_cancel_reversal');
            $stockOutChallan->update(['status' => 'cancelled']);
        });

        return back()->with('success', 'Special stock out challan cancelled and stock restored.');
    }

    public function destroy(StockOutChallan $stockOutChallan, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($stockOutChallan);
        abort_if($stockOutChallan->status !== 'cancelled', 422, 'Cancel before deleting so stock is restored.');
        $stockOutChallan->delete();
        return redirect()->route('admin.stock-out-challans.index')->with('success', 'Special stock out challan deleted.');
    }

    private function formData(StockOutChallan $stockOutChallan): array
    {
        $companyId = auth()->user()->current_company_id;
        return [
            'stockOutChallan' => $stockOutChallan,
            'parties' => Party::where('company_id', $companyId)->where('status', 'active')->orderBy('display_name')->get(),
            'items' => Item::where('company_id', $companyId)->where('status', 'active')->where('track_stock', true)->orderBy('name')->get(),
            'challanNo' => $stockOutChallan->challan_no ?: $this->nextNo(),
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'party_id' => ['nullable','exists:parties,id'],
            'party_name' => ['nullable','max:255'],
            'challan_no' => ['nullable','max:30'],
            'challan_date' => ['required','date'],
            'reference_no' => ['nullable','max:255'],
            'phone' => ['nullable','max:255'],
            'billing_address' => ['nullable','string'],
            'shipping_address' => ['nullable','string'],
            'notes' => ['nullable','string'],
            'item_id' => ['required','array'],
            'item_id.*' => ['required','exists:items,id'],
            'quantity.*' => ['required','numeric','min:0.001'],
            'unit_price.*' => ['nullable','numeric','min:0'],
            'visible_to_roles' => ['nullable','array'],
            'visible_to_users' => ['nullable','array'],
            'visible_to_all_company' => ['nullable','boolean'],
        ]);
    }

    private function storeLines(Request $request, StockOutChallan $challan, AccountingService $accounting): array
    {
        $subtotal = 0;
        foreach ($request->item_id as $i => $itemId) {
            $item = Item::lockForUpdate()->findOrFail($itemId);
            $qty = (float) $request->quantity[$i];
            abort_if((float) $item->current_stock < $qty, 422, "Insufficient stock for {$item->name}.");
            $price = (float) ($request->unit_price[$i] ?? $item->sale_price ?? 0);
            $lineTotal = $qty * $price;
            StockOutChallanItem::create([
                'stock_out_challan_id' => $challan->id,
                'item_id' => $item->id,
                'description' => $request->description[$i] ?? $item->description,
                'quantity' => $qty,
                'unit' => $request->unit[$i] ?? $item->unit,
                'unit_price' => $price,
                'line_total' => $lineTotal,
            ]);

            $accounting->moveStock($item, [
                'party_id' => $challan->party_id,
                'movement_date' => $challan->challan_date,
                'movement_type' => 'stock_out_challan',
                'direction' => 'out',
                'quantity' => $qty,
                'unit_price' => $item->purchase_price,
                'total_value' => $qty * (float) $item->purchase_price,
                'reference_type' => StockOutChallan::class,
                'reference_id' => $challan->id,
                'reference_no' => $challan->challan_no,
                'description' => 'Special stock out without party ledger for ' . $challan->display_party,
            ]);
            $subtotal += $lineTotal;
        }

        return ['subtotal' => $subtotal, 'grand_total' => $subtotal];
    }

    private function reverseStockOut(StockOutChallan $challan, AccountingService $accounting, string $movementType): void
    {
        foreach ($challan->items as $line) {
            if (!$line->item) {
                continue;
            }
            $accounting->moveStock($line->item, [
                'party_id' => $challan->party_id,
                'movement_date' => now()->toDateString(),
                'movement_type' => $movementType,
                'direction' => 'in',
                'quantity' => (float) $line->quantity,
                'unit_price' => $line->item->purchase_price,
                'total_value' => (float) $line->quantity * (float) $line->item->purchase_price,
                'reference_type' => StockOutChallan::class,
                'reference_id' => $challan->id,
                'reference_no' => $challan->challan_no,
                'description' => 'Special stock out reversal.',
            ]);
        }
    }

    private function nextNo(): string
    {
        $next = StockOutChallan::where('company_id', auth()->user()->current_company_id)->withTrashed()->count() + 1;
        return 'SO-' . now()->format('Y') . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
