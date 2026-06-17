<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Models\ProductType;
use App\Services\EntryVisibilityService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductTypeController extends Controller
{
    public function index(EntryVisibilityService $visibility)
    {
        $types = $visibility->scopeForUser(
            ProductType::with(['creator','productCategory'])->latest(),
            ProductType::class
        )->get();
        return view('admin.product-types.index', compact('types'));
    }

    public function create()
    {
        $type = new ProductType(['code' => $this->nextCode(), 'nature' => 'finished_goods', 'status' => 'active']);
        return view('admin.product-types.create', [
            'type' => $type,
            'categories' => $this->categories(),
        ]);
    }

    public function store(Request $request, EntryVisibilityService $visibility)
    {
        $type = ProductType::create(array_merge($this->validated($request), [
            'company_id' => auth()->user()->current_company_id,
            'created_by' => auth()->id(),
        ]));
        $visibility->syncFromRequest($request, $type);
        return redirect()->route('admin.product-types.index')->with('success', 'Product type created.');
    }

    public function edit(ProductType $productType, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($productType);
        return view('admin.product-types.edit', [
            'type' => $productType,
            'categories' => $this->categories(),
        ]);
    }

    public function update(Request $request, ProductType $productType, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($productType);
        $productType->update($this->validated($request, $productType->id));
        $visibility->syncFromRequest($request, $productType);
        return redirect()->route('admin.product-types.index')->with('success', 'Product type updated.');
    }

    public function destroy(ProductType $productType, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($productType);
        $productType->delete();
        return back()->with('success', 'Product type deleted.');
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','max:255'],
        ]);

        $category = ProductCategory::firstOrCreate(
            [
                'company_id' => auth()->user()->current_company_id,
                'name' => trim($data['name']),
            ],
            [
                'status' => 'active',
                'created_by' => auth()->id(),
            ]
        );

        return response()->json([
            'id' => $category->id,
            'name' => $category->name,
        ]);
    }

    private function validated(Request $request, ?int $id = null): array
    {
        $companyId = auth()->user()->current_company_id;
        $data = $request->validate([
            'code' => ['required','max:30', Rule::unique('product_types')->where('company_id', $companyId)->ignore($id)],
            'name' => ['required','max:255'],
            'nature' => ['required', Rule::in(['finished_goods','raw_material','readymade','service'])],
            'product_category_id' => [
                'nullable',
                'required_if:nature,finished_goods',
                Rule::exists('product_categories', 'id')->where('company_id', $companyId),
            ],
            'status' => ['required', Rule::in(['active','inactive'])],
            'description' => ['nullable','string'],
        ]);

        if ($data['nature'] !== 'finished_goods') {
            $data['product_category_id'] = null;
        }

        return $data;
    }

    private function nextCode(): string
    {
        $next = ProductType::where('company_id', auth()->user()->current_company_id)->withTrashed()->count() + 1;
        return 'PT-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function categories()
    {
        $companyId = auth()->user()->current_company_id;
        foreach (['GPS','GPS Inbuilt Android','Simple Android','LED Light','Horn','Speaker'] as $name) {
            ProductCategory::firstOrCreate(
                ['company_id' => $companyId, 'name' => $name],
                ['status' => 'active', 'created_by' => auth()->id()]
            );
        }

        return ProductCategory::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    private function authorizeCompany(ProductType $type): void
    {
        abort_unless($type->company_id === auth()->user()->current_company_id || auth()->user()->isSuperAdmin(), 403);
    }
}
