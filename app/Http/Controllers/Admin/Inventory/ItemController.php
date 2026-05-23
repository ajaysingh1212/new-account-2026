<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemBom;
use App\Models\ProductType;
use App\Services\EntryVisibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    public function index(EntryVisibilityService $visibility)
    {
        $items = $visibility->scopeForUser(
            Item::with(['productType','creator'])->latest(),
            Item::class
        )->get();
        return view('admin.items.index', compact('items'));
    }

    public function create()
    {
        $item = new Item([
            'item_type' => 'product',
            'item_code' => $this->nextCode(),
            'hsn_code' => $this->nextHsn(),
            'unit' => 'PCS',
            'status' => 'active',
            'track_stock' => true,
        ]);
        return view('admin.items.create', $this->formData($item));
    }

    public function store(Request $request, EntryVisibilityService $visibility)
    {
        $companyId = auth()->user()->current_company_id;
        $data = $this->validated($request, $companyId);

        DB::transaction(function () use ($request, $data, $companyId, $visibility) {
            $item = Item::create(array_merge($data, [
                'company_id'             => $companyId,
                'barcode'                => $data['barcode'] ?: $data['item_code'],
                'qr_code'                => $data['qr_code'] ?: 'QR-' . $data['item_code'],
                // Stock starts at 0 always — added only via Purchase or Production
                'current_stock'          => 0,
                'stock_value'            => 0,
                'track_stock'            => $data['item_type'] !== 'service' && $request->boolean('track_stock'),
                'purchase_tax_inclusive' => $request->boolean('purchase_tax_inclusive'),
                'sale_tax_inclusive'     => $request->boolean('sale_tax_inclusive'),
                'is_bom_enabled'         => $request->boolean('is_bom_enabled'),
                'created_by'             => auth()->id(),
            ]));

            // NOTE: opening_stock field is saved on the item record for reference
            // but NO stock movement is created here.
            // Raw materials get stock via Purchase entries.
            // Finished goods get stock via Production Batches.

            $this->syncBom($request, $item);
            $visibility->syncFromRequest($request, $item);
        });

        return redirect()->route('admin.items.index')->with('success', 'Item created.');
    }

    public function edit(Item $item, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($item);
        $item->load('bomMaterials');
        return view('admin.items.edit', $this->formData($item));
    }

    public function update(Request $request, Item $item, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($item);
        $data = $this->validated($request, $item->company_id, $item->id);
        $item->update(array_merge($data, [
            'track_stock'            => $data['item_type'] !== 'service' && $request->boolean('track_stock'),
            'purchase_tax_inclusive' => $request->boolean('purchase_tax_inclusive'),
            'sale_tax_inclusive'     => $request->boolean('sale_tax_inclusive'),
            'is_bom_enabled'         => $request->boolean('is_bom_enabled'),
        ]));
        $this->syncBom($request, $item);
        $visibility->syncFromRequest($request, $item);

        return redirect()->route('admin.items.index')->with('success', 'Item updated.');
    }

    public function destroy(Item $item, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($item);
        $item->delete();
        return back()->with('success', 'Item deleted.');
    }

    // ─────────────────────────────────────────────
    private function formData(Item $item): array
    {
        $companyId = auth()->user()->current_company_id;
        return [
            'item'     => $item,
            'types'    => ProductType::where('company_id', $companyId)->where('status', 'active')->orderBy('name')->get(),
            'rawItems' => Item::where('company_id', $companyId)->where('item_type', 'product')->where('id', '!=', $item->id)->orderBy('name')->get(),
        ];
    }

    private function validated(Request $request, int $companyId, ?int $id = null): array
    {
        return $request->validate([
            'product_type_id'      => ['nullable', Rule::exists('product_types', 'id')->where('company_id', $companyId)],
            'item_type'            => ['required', Rule::in(['product','service'])],
            'item_code'            => ['required','max:40', Rule::unique('items')->where('company_id', $companyId)->ignore($id)],
            'hsn_code'             => ['nullable','max:20'],
            'barcode'              => ['nullable','max:255'],
            'qr_code'              => ['nullable','max:255'],
            'name'                 => ['required','max:255'],
            'sku'                  => ['nullable','max:255'],
            'unit'                 => ['required','max:20'],
            'brand'                => ['nullable','max:255'],
            'model'                => ['nullable','max:255'],
            'size'                 => ['nullable','max:255'],
            'color'                => ['nullable','max:255'],
            'description'          => ['nullable','string'],
            'purchase_price'       => ['nullable','numeric','min:0'],
            'purchase_gst_percent' => ['nullable','numeric','min:0'],
            'sale_price'           => ['nullable','numeric','min:0'],
            'sale_gst_percent'     => ['nullable','numeric','min:0'],
            'opening_stock'        => ['nullable','numeric','min:0'],
            'low_stock_qty'        => ['nullable','numeric','min:0'],
            'status'               => ['required', Rule::in(['active','inactive'])],
        ]);
    }

    private function syncBom(Request $request, Item $item): void
    {
        ItemBom::where('finished_item_id', $item->id)->delete();
        foreach ($request->input('bom_raw_item_id', []) as $index => $rawId) {
            $qty = (float) ($request->input('bom_qty_per_unit')[$index] ?? 0);
            if ($rawId && $qty > 0) {
                ItemBom::create([
                    'company_id'       => $item->company_id,
                    'finished_item_id' => $item->id,
                    'raw_item_id'      => $rawId,
                    'qty_per_unit'     => $qty,
                ]);
            }
        }
    }

    private function nextCode(): string
    {
        $next = Item::where('company_id', auth()->user()->current_company_id)->withTrashed()->count() + 1;
        return 'ITM-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    private function nextHsn(): string
    {
        return 'HSN' . str_pad((string) (Item::withTrashed()->count() + 1), 6, '0', STR_PAD_LEFT);
    }
}
