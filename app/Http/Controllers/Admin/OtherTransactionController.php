<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\EntryVisibility;
use App\Models\ExpenseLedger;
use App\Models\OtherTransaction;
use App\Services\EntryVisibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OtherTransactionController extends Controller
{
    public function index(EntryVisibilityService $visibility)
    {
        $transactions = $visibility->scopeForUser(
            OtherTransaction::with(['ledger', 'bankAccount', 'creator', 'approver', 'rejecter'])->latest('transaction_date')->latest(),
            OtherTransaction::class
        )->get();

        return view('admin.other-transactions.index', compact('transactions'));
    }

    public function create()
    {
        return view('admin.other-transactions.create', $this->formData(new OtherTransaction([
            'transaction_no' => $this->nextNo(),
            'transaction_date' => now()->toDateString(),
            'transaction_kind' => 'income',
            'status' => 'pending_approval',
        ])));
    }

    public function store(Request $request, EntryVisibilityService $visibility)
    {
        $companyId = auth()->user()->current_company_id;
        $data = $this->validated($request, $companyId);
        $data['attachment'] = $request->hasFile('attachment') ? $request->file('attachment')->store('other-transactions', 'public') : null;
        $data['total_amount'] = (float) $data['amount'] + (float) ($data['tax_amount'] ?? 0);
        $transaction = OtherTransaction::create(array_merge($data, [
            'company_id' => $companyId,
            'transaction_no' => $data['transaction_no'] ?: $this->nextNo(),
            'status' => 'pending_approval',
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]));
        $visibility->syncFromRequest($request, $transaction);

        return redirect()->route('admin.other-transactions.index')->with('success', 'Other transaction submitted for approval.');
    }

    public function show(OtherTransaction $otherTransaction, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($otherTransaction);
        $otherTransaction->load(['ledger', 'bankAccount', 'creator', 'approver', 'rejecter']);
        $ledgerHistory = OtherTransaction::with(['bankAccount', 'creator'])
            ->where('company_id', $otherTransaction->company_id)
            ->where('expense_ledger_id', $otherTransaction->expense_ledger_id)
            ->where('status', 'approved')
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        return view('admin.other-transactions.show', compact('otherTransaction', 'ledgerHistory'));
    }

    public function edit(OtherTransaction $otherTransaction, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($otherTransaction);
        abort_if($otherTransaction->status === 'approved', 403, 'Approved transaction cannot be edited.');
        return view('admin.other-transactions.edit', $this->formData($otherTransaction));
    }

    public function update(Request $request, OtherTransaction $otherTransaction, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($otherTransaction);
        abort_if($otherTransaction->status === 'approved', 403, 'Approved transaction cannot be edited.');
        $data = $this->validated($request, $otherTransaction->company_id, $otherTransaction->id);
        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('other-transactions', 'public');
        }
        $data['total_amount'] = (float) $data['amount'] + (float) ($data['tax_amount'] ?? 0);
        $otherTransaction->update(array_merge($data, ['status' => 'pending_approval', 'updated_by' => auth()->id()]));
        $visibility->syncFromRequest($request, $otherTransaction);

        return redirect()->route('admin.other-transactions.show', $otherTransaction)->with('success', 'Other transaction updated and sent for approval.');
    }

    public function approve(Request $request, OtherTransaction $otherTransaction)
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || ($user->isAdmin() && (int) $otherTransaction->company_id === (int) $user->current_company_id) || $user->hasPermission('other_transactions.approve', $otherTransaction->company_id), 403);
        abort_if($otherTransaction->status === 'approved', 422, 'Transaction is already approved.');
        $data = $request->validate(['approval_note' => ['nullable', 'string']]);

        DB::transaction(function () use ($otherTransaction, $data) {
            $otherTransaction->load(['ledger', 'bankAccount']);
            $ledger = ExpenseLedger::where('company_id', $otherTransaction->company_id)->lockForUpdate()->findOrFail($otherTransaction->expense_ledger_id);
            $bank = null;
            if ($otherTransaction->bank_account_id) {
                $bank = BankAccount::where('company_id', $otherTransaction->company_id)->lockForUpdate()->findOrFail($otherTransaction->bank_account_id);
            }

            $direction = $otherTransaction->transaction_kind === 'income' ? 'in' : 'out';
            if ($bank) {
                $bankBalance = $direction === 'in'
                    ? (float) $bank->current_balance + (float) $otherTransaction->total_amount
                    : (float) $bank->current_balance - (float) $otherTransaction->total_amount;
                $bank->update(['current_balance' => $bankBalance]);
            }

            $ledgerBalance = (float) $ledger->opening_balance;
            $ledger->otherTransactions()
                ->where('status', 'approved')
                ->where('id', '<', $otherTransaction->id)
                ->orderBy('id')
                ->get()
                ->each(function (OtherTransaction $row) use (&$ledgerBalance) {
                    $ledgerBalance += $row->transaction_kind === 'income'
                        ? (float) $row->total_amount
                        : -(float) $row->total_amount;
                });
            $ledgerBalance += $otherTransaction->transaction_kind === 'income'
                ? (float) $otherTransaction->total_amount
                : -(float) $otherTransaction->total_amount;
            $otherTransaction->update([
                'ledger_balance_after' => $ledgerBalance,
                'bank_balance_after' => $bank?->current_balance,
            ]);

            $transaction = BankTransaction::create([
                'company_id' => $otherTransaction->company_id,
                'bank_account_id' => $bank?->id,
                'expense_ledger_id' => $ledger->id,
                'ledger_name' => $ledger->name,
                'transaction_date' => $otherTransaction->transaction_date,
                'transaction_type' => $otherTransaction->transaction_kind === 'income' ? 'other_income' : 'other_expense',
                'direction' => $direction,
                'amount' => $otherTransaction->total_amount,
                'balance_after' => $bank?->current_balance ?? 0,
                'reference_type' => OtherTransaction::class,
                'reference_id' => $otherTransaction->id,
                'reference_no' => $otherTransaction->transaction_no,
                'payment_mode' => $otherTransaction->payment_mode,
                'description' => trim(($ledger->name ?: 'Ledger') . ' - ' . ($otherTransaction->description ?: 'Other transaction')),
                'attachment' => $otherTransaction->attachment,
                'created_by' => auth()->id(),
            ]);

            EntryVisibility::updateOrCreate(
                [
                    'entry_type' => BankTransaction::class,
                    'entry_id' => $transaction->id,
                ],
                [
                    'company_id' => $otherTransaction->company_id,
                    'visible_to_all_company' => true,
                    'visible_to_roles' => [],
                    'visible_to_users' => [],
                ]
            );

            $otherTransaction->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'approval_note' => $data['approval_note'] ?? null,
                'updated_by' => auth()->id(),
            ]);
        });

        AuditLog::log('approved', [
            'company_id' => $otherTransaction->company_id,
            'model' => OtherTransaction::class,
            'model_id' => $otherTransaction->id,
            'description' => "Other transaction {$otherTransaction->transaction_no} approved and posted to bank.",
        ]);

        return back()->with('success', 'Other transaction approved and bank transaction posted.');
    }

    public function reject(Request $request, OtherTransaction $otherTransaction)
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || ($user->isAdmin() && (int) $otherTransaction->company_id === (int) $user->current_company_id) || $user->hasPermission('other_transactions.approve', $otherTransaction->company_id), 403);
        abort_if($otherTransaction->status === 'approved', 422, 'Approved transaction cannot be rejected.');
        $data = $request->validate(['rejection_reason' => ['required', 'string']]);

        $otherTransaction->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => auth()->id(),
            'rejection_reason' => $data['rejection_reason'],
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Other transaction rejected.');
    }

    private function formData(OtherTransaction $transaction): array
    {
        $companyId = auth()->user()->current_company_id;

        return [
            'transaction' => $transaction,
            'ledgers' => ExpenseLedger::where('company_id', $companyId)->where('status', 'active')->orderBy('name')->get(),
            'accounts' => BankAccount::where('company_id', $companyId)->where('status', 'active')->orderBy('account_name')->get(),
        ];
    }

    private function validated(Request $request, int $companyId, ?int $id = null): array
    {
        return $request->validate([
            'transaction_kind' => ['required', Rule::in(['income', 'expense'])],
            'expense_ledger_id' => ['required', Rule::exists('expense_ledgers', 'id')->where('company_id', $companyId)],
            'bank_account_id' => ['required', Rule::exists('bank_accounts', 'id')->where('company_id', $companyId)],
            'transaction_no' => ['nullable', 'string', 'max:30', Rule::unique('other_transactions', 'transaction_no')->where('company_id', $companyId)->ignore($id)],
            'transaction_date' => ['required', 'date'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'party_name' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_mode' => ['nullable', 'string', 'max:40'],
            'description' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:4096'],
        ]);
    }

    private function nextNo(): string
    {
        $next = OtherTransaction::where('company_id', auth()->user()->current_company_id)->withTrashed()->count() + 1;
        return 'OT-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
