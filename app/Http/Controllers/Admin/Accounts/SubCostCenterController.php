<?php

namespace App\Http\Controllers\Admin\Accounts;

use App\Http\Controllers\Controller;
use App\Models\CostCenter;
use App\Models\SubCostCenter;
use App\Services\EntryVisibilityService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubCostCenterController extends Controller
{
    public function index(EntryVisibilityService $visibility)
    {
        $subCostCenters = $visibility->scopeForUser(
            SubCostCenter::with(['costCenter','creator'])->latest(),
            SubCostCenter::class
        )->get();

        return view('admin.sub-cost-centers.index', compact('subCostCenters'));
    }

    public function create()
    {
        $subCostCenter = new SubCostCenter(['code' => $this->nextCode(), 'status' => 'active']);
        $costCenters = $this->costCenters();

        return view('admin.sub-cost-centers.create', compact('subCostCenter', 'costCenters'));
    }

    public function store(Request $request, EntryVisibilityService $visibility)
    {
        $data = $this->validated($request);
        $subCostCenter = SubCostCenter::create(array_merge($data, [
            'company_id' => auth()->user()->current_company_id,
            'created_by' => auth()->id(),
        ]));
        $visibility->syncFromRequest($request, $subCostCenter);

        return redirect()->route('admin.sub-cost-centers.index')->with('success', 'Sub cost center created successfully.');
    }

    public function edit(SubCostCenter $subCostCenter, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($subCostCenter);
        $costCenters = $this->costCenters();

        return view('admin.sub-cost-centers.edit', compact('subCostCenter', 'costCenters'));
    }

    public function update(Request $request, SubCostCenter $subCostCenter, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($subCostCenter);
        $subCostCenter->update($this->validated($request, $subCostCenter->id));
        $visibility->syncFromRequest($request, $subCostCenter);

        return redirect()->route('admin.sub-cost-centers.index')->with('success', 'Sub cost center updated successfully.');
    }

    public function destroy(SubCostCenter $subCostCenter, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($subCostCenter);
        $subCostCenter->delete();

        return redirect()->route('admin.sub-cost-centers.index')->with('success', 'Sub cost center deleted successfully.');
    }

    private function validated(Request $request, ?int $id = null): array
    {
        $companyId = auth()->user()->current_company_id;

        return $request->validate([
            'cost_center_id' => ['required', Rule::exists('cost_centers', 'id')->where('company_id', $companyId)],
            'code' => ['required','string','max:30', Rule::unique('sub_cost_centers')->where('company_id', $companyId)->ignore($id)],
            'name' => ['required','string','max:255'],
            'owner_name' => ['nullable','string','max:255'],
            'budget_amount' => ['nullable','numeric','min:0'],
            'status' => ['required', Rule::in(['active','inactive'])],
            'description' => ['nullable','string'],
        ]);
    }

    private function costCenters()
    {
        return CostCenter::where('company_id', auth()->user()->current_company_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    private function nextCode(): string
    {
        $next = SubCostCenter::where('company_id', auth()->user()->current_company_id)->withTrashed()->count() + 1;
        return 'SCC-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function authorizeCompany(SubCostCenter $subCostCenter): void
    {
        abort_unless($subCostCenter->company_id === auth()->user()->current_company_id || auth()->user()->isSuperAdmin(), 403);
    }
}
