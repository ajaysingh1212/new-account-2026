<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\CostCenter;
use App\Models\DeliveryChallan;
use App\Models\DeliveryChallanItem;
use App\Models\Item;
use App\Models\Party;
use App\Models\SubCostCenter;
use App\Services\EntryVisibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryChallanController extends Controller
{
    public function index(EntryVisibilityService $visibility)
    {
        $challans = $visibility->scopeForUser(
            DeliveryChallan::with(['party','creator'])->latest(),
            DeliveryChallan::class
        )->get();

        return view('admin.delivery-challans.index', compact('challans'));
    }

    public function create()
    {
        return view('admin.delivery-challans.create', $this->formData());
    }

    public function store(Request $request, EntryVisibilityService $visibility)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($request, $data, $visibility) {
            $attachment = $request->hasFile('attachment')
                ? $request->file('attachment')->store('challan-attachments', 'public')
                : null;

            $challan = DeliveryChallan::create(array_merge($data, [
                'company_id' => auth()->user()->current_company_id,
                'challan_no' => $data['challan_no'] ?: $this->nextNo(),
                'attachment' => $attachment,
                'status' => 'issued',
                'created_by' => auth()->id(),
            ]));

            $challan->update($this->storeLines($request, $challan));
            $visibility->syncFromRequest($request, $challan);
        });

        return redirect()->route('admin.delivery-challans.index')->with('success', 'Delivery challan created.');
    }

    public function show(DeliveryChallan $deliveryChallan, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($deliveryChallan);
        $deliveryChallan->load(['party','items.item']);

        return view('admin.delivery-challans.show', compact('deliveryChallan'));
    }

    public function edit(DeliveryChallan $deliveryChallan, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($deliveryChallan);
        abort_if($deliveryChallan->status === 'cancelled', 422, 'Cancelled challan cannot be edited.');
        $deliveryChallan->load('items');

        return view('admin.delivery-challans.edit', array_merge($this->formData(), compact('deliveryChallan')));
    }

    public function update(Request $request, DeliveryChallan $deliveryChallan, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($deliveryChallan);
        abort_if($deliveryChallan->status === 'cancelled', 422, 'Cancelled challan cannot be edited.');
        $data = $this->validated($request);

        DB::transaction(function () use ($request, $deliveryChallan, $data, $visibility) {
            $attachment = $request->hasFile('attachment')
                ? $request->file('attachment')->store('challan-attachments', 'public')
                : $deliveryChallan->attachment;

            $deliveryChallan->update(array_merge($data, ['attachment' => $attachment]));
            $deliveryChallan->items()->delete();
            $deliveryChallan->update($this->storeLines($request, $deliveryChallan));
            $visibility->syncFromRequest($request, $deliveryChallan);
        });

        return redirect()->route('admin.delivery-challans.show', $deliveryChallan)->with('success', 'Delivery challan updated.');
    }

    public function print(DeliveryChallan $deliveryChallan, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($deliveryChallan);
        $deliveryChallan->load(['party','items.item']);

        return view('admin.delivery-challans.print', compact('deliveryChallan'));
    }

    public function cancel(DeliveryChallan $deliveryChallan, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($deliveryChallan);
        $deliveryChallan->update(['status' => 'cancelled']);

        return back()->with('success', 'Delivery challan cancelled.');
    }

    public function destroy(DeliveryChallan $deliveryChallan, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($deliveryChallan);
        $deliveryChallan->delete();

        return redirect()->route('admin.delivery-challans.index')->with('success', 'Delivery challan deleted.');
    }

    private function formData(): array
    {
        $companyId = auth()->user()->current_company_id;

        return [
            'parties' => Party::where('company_id', $companyId)->where('status', 'active')->orderBy('display_name')->get(),
            'items' => Item::where('company_id', $companyId)->where('status', 'active')->orderBy('name')->get(),
            'costCenters' => CostCenter::where('company_id', $companyId)->where('status', 'active')->get(),
            'subCostCenters' => SubCostCenter::where('company_id', $companyId)->where('status', 'active')->get(),
            'challanNo' => $this->nextNo(),
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'party_id' => ['nullable','exists:parties,id'],
            'cost_center_id' => ['nullable','exists:cost_centers,id'],
            'sub_cost_center_id' => ['nullable','exists:sub_cost_centers,id'],
            'challan_no' => ['nullable','max:30'],
            'challan_date' => ['required','date'],
            'reference_no' => ['nullable','max:255'],
            'dispatch_through' => ['nullable','max:255'],
            'vehicle_no' => ['nullable','max:255'],
            'driver_name' => ['nullable','max:255'],
            'driver_phone' => ['nullable','max:30'],
            'lr_no' => ['nullable','max:255'],
            'lr_date' => ['nullable','date'],
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
            'unit_price.*' => ['nullable','numeric','min:0'],
        ]);
    }

    private function storeLines(Request $request, DeliveryChallan $challan): array
    {
        $subtotal = $tax = $lineDiscount = 0;
        foreach ($request->item_id as $i => $itemId) {
            $item = Item::findOrFail($itemId);
            $qty = (float) $request->quantity[$i];
            $price = (float) ($request->unit_price[$i] ?? 0);
            $base = $qty * $price;
            $discount = (($request->discount_type[$i] ?? 'percent') === 'flat') ? (float) ($request->discount_value[$i] ?? 0) : $base * (float) ($request->discount_value[$i] ?? 0) / 100;
            $taxAmount = max(0, $base - $discount) * (float) ($request->tax_percent[$i] ?? 0) / 100;
            $total = max(0, $base - $discount) + $taxAmount;

            DeliveryChallanItem::create([
                'delivery_challan_id' => $challan->id,
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

    private function nextNo(): string
    {
        $next = DeliveryChallan::where('company_id', auth()->user()->current_company_id)->withTrashed()->count() + 1;
        return 'DC-' . now()->format('Y') . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    private function authorizeCompany(DeliveryChallan $deliveryChallan): void
    {
        abort_unless($deliveryChallan->company_id === auth()->user()->current_company_id || auth()->user()->isSuperAdmin(), 403);
    }
}
