<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExpenseLedger;
use App\Services\EntryVisibilityService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseLedgerController extends Controller
{
    public function index(EntryVisibilityService $visibility)
    {
        $ledgers = $visibility->scopeForUser(ExpenseLedger::with('creator')->latest(), ExpenseLedger::class)->get();
        return view('admin.expense-ledgers.index', compact('ledgers'));
    }

    public function create()
    {
        $ledger = new ExpenseLedger([
            'ledger_code' => $this->nextCode(),
            'opening_balance_date' => now()->toDateString(),
            'status' => 'active',
        ]);
        return view('admin.expense-ledgers.create', compact('ledger'));
    }

    public function store(Request $request, EntryVisibilityService $visibility)
    {
        $companyId = auth()->user()->current_company_id;
        $data = $this->validated($request, $companyId);
        $data['attachment'] = $request->hasFile('attachment') ? $request->file('attachment')->store('expense-ledgers', 'public') : null;
        $ledger = ExpenseLedger::create(array_merge($data, [
            'company_id' => $companyId,
            'current_balance' => $data['opening_balance'] ?? 0,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]));
        $visibility->syncFromRequest($request, $ledger);

        return redirect()->route('admin.expense-ledgers.index')->with('success', 'Expense ledger created.');
    }

    public function edit(ExpenseLedger $expenseLedger, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($expenseLedger);
        return view('admin.expense-ledgers.edit', ['ledger' => $expenseLedger]);
    }

    public function update(Request $request, ExpenseLedger $expenseLedger, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($expenseLedger);
        $data = $this->validated($request, $expenseLedger->company_id, $expenseLedger->id);
        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('expense-ledgers', 'public');
        }
        $approvedTotal = (float) $expenseLedger->expenses()->where('status', 'approved')->sum('total_amount');
        $expenseLedger->update(array_merge($data, [
            'current_balance' => ((float) ($data['opening_balance'] ?? 0)) + $approvedTotal,
            'updated_by' => auth()->id(),
        ]));
        $visibility->syncFromRequest($request, $expenseLedger);

        return redirect()->route('admin.expense-ledgers.index')->with('success', 'Expense ledger updated.');
    }

    private function validated(Request $request, int $companyId, ?int $id = null): array
    {
        return $request->validate([
            'ledger_code' => ['required','string','max:30', Rule::unique('expense_ledgers')->where('company_id', $companyId)->ignore($id)],
            'name' => ['required','string','max:255'],
            'category' => ['nullable','string','max:80'],
            'opening_balance' => ['nullable','numeric','min:0'],
            'opening_balance_date' => ['nullable','date'],
            'status' => ['required', Rule::in(['active','inactive'])],
            'description' => ['nullable','string'],
            'attachment' => ['nullable','file','max:4096'],
        ]);
    }

    private function nextCode(): string
    {
        $next = ExpenseLedger::where('company_id', auth()->user()->current_company_id)->withTrashed()->count() + 1;
        return 'EL-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
