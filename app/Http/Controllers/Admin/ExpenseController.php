<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\EntryVisibility;
use App\Models\Expense;
use App\Models\ExpenseLedger;
use App\Services\EntryVisibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function index(EntryVisibilityService $visibility)
    {
        $expenses = $visibility->scopeForUser(
            Expense::with(['ledger','bankAccount','creator','approver'])->latest('expense_date')->latest(),
            Expense::class
        )->get();
        return view('admin.expenses.index', compact('expenses'));
    }

    public function create()
    {
        return view('admin.expenses.create', $this->formData(new Expense([
            'expense_no' => $this->nextNo(),
            'expense_date' => now()->toDateString(),
            'status' => 'pending_approval',
        ])));
    }

    public function store(Request $request, EntryVisibilityService $visibility)
    {
        $companyId = auth()->user()->current_company_id;
        $data = $this->validated($request, $companyId);
        $data['attachment'] = $request->hasFile('attachment') ? $request->file('attachment')->store('expenses', 'public') : null;
        $data['total_amount'] = (float) $data['amount'] + (float) ($data['tax_amount'] ?? 0);
        $expense = Expense::create(array_merge($data, [
            'company_id' => $companyId,
            'expense_no' => $data['expense_no'] ?: $this->nextNo(),
            'status' => 'pending_approval',
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]));
        $visibility->syncFromRequest($request, $expense);

        return redirect()->route('admin.expenses.index')->with('success', 'Expense submitted for approval.');
    }

    public function show(Expense $expense, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($expense);
        $expense->load(['ledger','bankAccount','creator','approver']);
        return view('admin.expenses.show', compact('expense'));
    }

    public function edit(Expense $expense, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($expense);
        abort_if($expense->status === 'approved', 403, 'Approved expense cannot be edited.');
        return view('admin.expenses.edit', $this->formData($expense));
    }

    public function update(Request $request, Expense $expense, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($expense);
        abort_if($expense->status === 'approved', 403, 'Approved expense cannot be edited.');
        $data = $this->validated($request, $expense->company_id, $expense->id);
        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('expenses', 'public');
        }
        $data['total_amount'] = (float) $data['amount'] + (float) ($data['tax_amount'] ?? 0);
        DB::transaction(function () use ($expense, $data, $request, $visibility) {
            $expense = Expense::whereKey($expense->id)->lockForUpdate()->firstOrFail();
            abort_if($expense->status === 'approved', 403, 'Approved expense cannot be edited.');
            $expense->update(array_merge($data, ['status' => 'pending_approval', 'updated_by' => auth()->id()]));
            $visibility->syncFromRequest($request, $expense);
        });

        return redirect()->route('admin.expenses.show', $expense)->with('success', 'Expense updated and sent for approval.');
    }

    public function approve(Request $request, Expense $expense)
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || ($user->isAdmin() && (int) $expense->company_id === (int) $user->current_company_id) || $user->hasPermission('expenses.approve', $expense->company_id), 403);
        abort_if($expense->status === 'approved', 422, 'Expense is already approved.');
        $data = $request->validate(['approval_note' => ['nullable','string']]);

        DB::transaction(function () use ($expense, $data) {
            $expense = Expense::whereKey($expense->id)->lockForUpdate()->firstOrFail();
            abort_if($expense->status === 'approved', 422, 'Expense is already approved.');
            $expense->load(['bankAccount','ledger']);
            $bank = BankAccount::where('company_id', $expense->company_id)->lockForUpdate()->findOrFail($expense->bank_account_id);
            $ledger = ExpenseLedger::where('company_id', $expense->company_id)->lockForUpdate()->findOrFail($expense->expense_ledger_id);
            abort_if((float) $bank->current_balance < (float) $expense->total_amount, 422, 'Bank balance is insufficient for this expense.');
            $newBalance = (float) $bank->current_balance - (float) $expense->total_amount;
            $bank->update(['current_balance' => $newBalance]);
            $ledger->update(['current_balance' => (float) $ledger->current_balance + (float) $expense->total_amount]);

            $transaction = BankTransaction::create([
                'company_id' => $expense->company_id,
                'bank_account_id' => $bank->id,
                'transaction_date' => $expense->expense_date,
                'transaction_type' => 'expense',
                'direction' => 'out',
                'amount' => $expense->total_amount,
                'balance_after' => $newBalance,
                'reference_type' => Expense::class,
                'reference_id' => $expense->id,
                'reference_no' => $expense->expense_no,
                'payment_mode' => $expense->payment_mode,
                'description' => $expense->description,
                'attachment' => $expense->attachment,
                'created_by' => auth()->id(),
            ]);

            EntryVisibility::updateOrCreate(
                [
                    'entry_type' => BankTransaction::class,
                    'entry_id' => $transaction->id,
                ],
                [
                    'company_id' => $expense->company_id,
                    'visible_to_all_company' => true,
                    'visible_to_roles' => [],
                    'visible_to_users' => [],
                ]
            );

            $expense->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'approval_note' => $data['approval_note'] ?? null,
                'updated_by' => auth()->id(),
            ]);
        });

        AuditLog::log('approved', [
            'company_id' => $expense->company_id,
            'model' => Expense::class,
            'model_id' => $expense->id,
            'description' => "Expense {$expense->expense_no} approved and posted to bank.",
        ]);

        return back()->with('success', 'Expense approved and bank transaction posted.');
    }

    public function reject(Request $request, Expense $expense)
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || ($user->isAdmin() && (int) $expense->company_id === (int) $user->current_company_id) || $user->hasPermission('expenses.approve', $expense->company_id), 403);
        abort_if($expense->status === 'approved', 422, 'Approved expense cannot be rejected.');
        $data = $request->validate(['rejection_reason' => ['required','string']]);
        DB::transaction(function () use ($expense, $data) {
            $expense = Expense::whereKey($expense->id)->lockForUpdate()->firstOrFail();
            abort_if($expense->status === 'approved', 422, 'Approved expense cannot be rejected.');
            $expense->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejected_by' => auth()->id(),
                'rejection_reason' => $data['rejection_reason'],
                'updated_by' => auth()->id(),
            ]);
        });

        return back()->with('success', 'Expense rejected.');
    }

    public function destroy(Expense $expense, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($expense);

        DB::transaction(function () use ($expense, $visibility) {
            $expense = Expense::whereKey($expense->id)->lockForUpdate()->firstOrFail();
            $visibility->authorizeManage($expense);
            $postings = BankTransaction::where('company_id', $expense->company_id)
                ->where('reference_type', Expense::class)->where('reference_id', $expense->id)
                ->orderBy('id')->lockForUpdate()->get();
            $oldValues = $expense->toArray();
            $oldValues['bank_postings'] = $postings->toArray();

            foreach ($postings->groupBy('bank_account_id') as $bankId => $rows) {
                $bank = BankAccount::withTrashed()->where('company_id', $expense->company_id)
                    ->lockForUpdate()->findOrFail($bankId);
                $refund = $rows->sum(fn($row) => $row->direction === 'out' ? (float) $row->amount : -(float) $row->amount);
                $bank->update(['current_balance' => (float) $bank->current_balance + $refund]);

                // Posting balances follow insertion order. Remove only this
                // expense's effects from subsequent running balances.
                foreach ($rows as $row) {
                    $delta = $row->direction === 'out' ? (float) $row->amount : -(float) $row->amount;
                    BankTransaction::where('company_id', $expense->company_id)->where('bank_account_id', $bank->id)
                        ->where('id', '>', $row->id)->increment('balance_after', $delta);
                }
            }

            $ledgerEffect = $postings->isNotEmpty()
                ? $postings->sum(fn($row) => $row->direction === 'out' ? (float) $row->amount : -(float) $row->amount)
                : ($expense->status === 'approved' ? (float) $expense->total_amount : 0);
            if (abs($ledgerEffect) > 0.001) {
                $ledger = ExpenseLedger::withTrashed()->where('company_id', $expense->company_id)
                    ->lockForUpdate()->findOrFail($expense->expense_ledger_id);
                $ledger->update(['current_balance' => (float) $ledger->current_balance - $ledgerEffect]);
            }

            EntryVisibility::where('company_id', $expense->company_id)->where(function ($query) use ($expense, $postings) {
                $query->where(fn($q) => $q->where('entry_type', Expense::class)->where('entry_id', $expense->id))
                    ->orWhere(fn($q) => $q->where('entry_type', BankTransaction::class)->whereIn('entry_id', $postings->pluck('id')));
            })->delete();
            BankTransaction::where('company_id', $expense->company_id)->whereIn('id', $postings->pluck('id'))->delete();
            $expense->update(['updated_by' => auth()->id()]);
            $expense->delete();

            AuditLog::log('deleted', [
                'company_id' => $expense->company_id, 'model' => Expense::class, 'model_id' => $expense->id,
                'old_values' => $oldValues,
                'description' => "Expense {$expense->expense_no} deleted; linked bank postings and expense ledger effects reversed.",
            ]);
        });

        return redirect()->route('admin.expenses.index')->with('success', 'Expense deleted. Its bank postings and expense ledger effects have been reversed.');
    }

    private function formData(Expense $expense): array
    {
        $companyId = auth()->user()->current_company_id;
        return [
            'expense' => $expense,
            'ledgers' => ExpenseLedger::where('company_id', $companyId)->where('status', 'active')->orderBy('name')->get(),
            'accounts' => BankAccount::where('company_id', $companyId)->where('status', 'active')->orderBy('account_name')->get(),
        ];
    }

    private function validated(Request $request, int $companyId, ?int $id = null): array
    {
        return $request->validate([
            'expense_ledger_id' => ['required', Rule::exists('expense_ledgers', 'id')->where('company_id', $companyId)],
            'bank_account_id' => ['required', Rule::exists('bank_accounts', 'id')->where('company_id', $companyId)],
            'expense_date' => ['required','date'],
            'expense_no' => ['nullable','string','max:30', Rule::unique('expenses')->where('company_id', $companyId)->ignore($id)],
            'reference_no' => ['nullable','string','max:255'],
            'vendor_name' => ['nullable','string','max:255'],
            'amount' => ['required','numeric','min:0.01'],
            'tax_amount' => ['nullable','numeric','min:0'],
            'payment_mode' => ['nullable','string','max:40'],
            'description' => ['nullable','string'],
            'attachment' => ['nullable','file','max:4096'],
        ]);
    }

    private function nextNo(): string
    {
        $next = Expense::where('company_id', auth()->user()->current_company_id)->withTrashed()->count() + 1;
        return 'EXP-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
