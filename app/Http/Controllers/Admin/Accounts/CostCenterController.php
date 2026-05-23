<?php

namespace App\Http\Controllers\Admin\Accounts;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CostCenter;
use App\Services\EntryVisibilityService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CostCenterController extends Controller
{
    public function index(EntryVisibilityService $visibility)
    {
        $costCenters = $visibility->scopeForUser(
            CostCenter::with(['creator'])->withCount('subCostCenters')->latest(),
            CostCenter::class
        )->get();

        return view('admin.cost-centers.index', compact('costCenters'));
    }

    public function create()
    {
        $costCenter = new CostCenter(['code' => $this->nextCode(), 'status' => 'active']);

        return view('admin.cost-centers.create', compact('costCenter'));
    }

    public function store(Request $request, EntryVisibilityService $visibility)
    {
        $data = $this->validated($request);
        $costCenter = CostCenter::create(array_merge($data, [
            'company_id' => auth()->user()->current_company_id,
            'created_by' => auth()->id(),
        ]));
        $visibility->syncFromRequest($request, $costCenter);

        AuditLog::log('created', [
            'model' => CostCenter::class,
            'model_id' => $costCenter->id,
            'description' => "Cost center created: {$costCenter->name}",
        ]);

        return redirect()->route('admin.cost-centers.index')->with('success', 'Cost center created successfully.');
    }

    public function edit(CostCenter $costCenter, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($costCenter);

        return view('admin.cost-centers.edit', compact('costCenter'));
    }

    public function update(Request $request, CostCenter $costCenter, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($costCenter);
        $costCenter->update($this->validated($request, $costCenter->id));
        $visibility->syncFromRequest($request, $costCenter);

        return redirect()->route('admin.cost-centers.index')->with('success', 'Cost center updated successfully.');
    }

    public function destroy(CostCenter $costCenter, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($costCenter);
        $costCenter->delete();

        return redirect()->route('admin.cost-centers.index')->with('success', 'Cost center deleted successfully.');
    }

    private function validated(Request $request, ?int $id = null): array
    {
        $companyId = auth()->user()->current_company_id;

        return $request->validate([
            'code' => ['required','string','max:30', Rule::unique('cost_centers')->where('company_id', $companyId)->ignore($id)],
            'name' => ['required','string','max:255'],
            'manager_name' => ['nullable','string','max:255'],
            'department' => ['nullable','string','max:255'],
            'budget_amount' => ['nullable','numeric','min:0'],
            'budget_start_date' => ['nullable','date'],
            'budget_end_date' => ['nullable','date','after_or_equal:budget_start_date'],
            'status' => ['required', Rule::in(['active','inactive'])],
            'description' => ['nullable','string'],
        ]);
    }

    private function nextCode(): string
    {
        $next = CostCenter::where('company_id', auth()->user()->current_company_id)->withTrashed()->count() + 1;
        return 'CC-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function authorizeCompany(CostCenter $costCenter): void
    {
        abort_unless($costCenter->company_id === auth()->user()->current_company_id || auth()->user()->isSuperAdmin(), 403);
    }
}
