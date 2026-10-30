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
        $manageableCount = $transactions->filter(fn(OtherTransaction $entry) => $visibility->canManage(auth()->user(), $entry))->count();

        return view('admin.other-transactions.index', compact('transactions', 'manageableCount'));
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
            $otherTransaction = OtherTransaction::whereKey($otherTransaction->id)->lockForUpdate()->firstOrFail();
            abort_if($otherTransaction->status === 'approved', 422, 'Transaction is already approved.');
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
            BankTransaction::where('company_id', $otherTransaction->company_id)
                ->where('expense_ledger_id', $ledger->id)
                ->where('reference_type', OtherTransaction::class)
                ->where('reference_id', '<', $otherTransaction->id)
                ->orderBy('id')
                ->get()
                ->each(function (BankTransaction $row) use (&$ledgerBalance) {
                    $ledgerBalance += $row->direction === 'in'
                        ? (float) $row->amount
                        : -(float) $row->amount;
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

    public function destroy(Request $request, OtherTransaction $otherTransaction, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($otherTransaction);
        $request->validate(['revert_transactions' => ['sometimes', 'boolean']]);
        $revert = $request->boolean('revert_transactions', true);

        DB::transaction(function () use ($otherTransaction, $visibility, $revert) {
            $this->deleteEntry($otherTransaction, $visibility, $revert);
        });

        return redirect()->route('admin.other-transactions.index')->with('success', $revert
            ? 'Entry deleted and linked transactions reversed.'
            : 'Entry deleted. Linked transactions and balances have been kept.');
    }

    public function bulkDestroy(Request $request, EntryVisibilityService $visibility)
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(['selected', 'all'])],
            'transaction_ids' => ['required_if:mode,selected', 'array', 'min:1'],
            'transaction_ids.*' => ['required', 'integer', 'distinct', 'exists:other_transactions,id'],
            'revert_transactions' => ['required', 'boolean'],
        ]);
        $revert = (bool) $data['revert_transactions'];

        if ($data['mode'] === 'all') {
            $entries = $visibility->scopeForUser(OtherTransaction::query(), OtherTransaction::class)
                ->get()
                ->filter(fn(OtherTransaction $entry) => $visibility->canManage(auth()->user(), $entry))
                ->values();
        } else {
            $ids = collect($data['transaction_ids'])->map(fn($id) => (int) $id)->unique();
            $entries = OtherTransaction::whereIn('id', $ids)->get();
            if ($entries->count() !== $ids->count()) {
                return back()->withErrors(['transaction_ids' => 'One or more selected entries are no longer available.']);
            }
            foreach ($entries as $entry) {
                $visibility->authorizeManage($entry);
            }
        }

        if ($entries->isEmpty()) {
            return back()->withErrors(['transaction_ids' => 'No manageable Other Income / Expense entries were found.']);
        }

        DB::transaction(function () use ($entries, $visibility, $revert) {
            foreach ($entries as $entry) {
                $this->deleteEntry($entry, $visibility, $revert);
            }
        });

        $count = $entries->count();
        $message = $revert
            ? "{$count} entr" . ($count === 1 ? 'y deleted and linked transactions reversed.' : 'ies deleted and linked transactions reversed.')
            : "{$count} entr" . ($count === 1 ? 'y deleted; linked transactions and balances were kept.' : 'ies deleted; linked transactions and balances were kept.');

        return redirect()->route('admin.other-transactions.index')->with('success', $message);
    }

    private function deleteEntry(OtherTransaction $otherTransaction, EntryVisibilityService $visibility, bool $revert): void
    {
        $entry = OtherTransaction::whereKey($otherTransaction->id)->lockForUpdate()->firstOrFail();
        $visibility->authorizeManage($entry);
        $postings = BankTransaction::where('company_id', $entry->company_id)
            ->where('reference_type', OtherTransaction::class)->where('reference_id', $entry->id)
            ->orderBy('id')->lockForUpdate()->get();
        $oldValues = $entry->toArray();
        $oldValues['bank_postings'] = $postings->toArray();

        if ($revert) {
            foreach ($postings->whereNotNull('bank_account_id')->groupBy('bank_account_id') as $bankId => $rows) {
                $bank = BankAccount::withTrashed()->where('company_id', $entry->company_id)
                    ->lockForUpdate()->findOrFail($bankId);
                $refund = $rows->sum(fn ($row) => $row->direction === 'out' ? (float) $row->amount : -(float) $row->amount);
                $bank->update(['current_balance' => (float) $bank->current_balance + $refund]);
                foreach ($rows as $row) {
                    $delta = $row->direction === 'out' ? (float) $row->amount : -(float) $row->amount;
                    $later = BankTransaction::where('company_id', $entry->company_id)
                        ->where('bank_account_id', $bank->id)->where('id', '>', $row->id);
                    OtherTransaction::withTrashed()->where('company_id', $entry->company_id)
                        ->whereIn('id', (clone $later)->where('reference_type', OtherTransaction::class)->select('reference_id'))
                        ->increment('bank_balance_after', $delta);
                    $later->increment('balance_after', $delta);
                }
            }

            // Other Income / Expense stores its ledger effect in running snapshots,
            // rather than changing the expense ledger's current_balance.
            $ledgerDelta = $postings->sum(fn ($row) => $row->direction === 'out' ? (float) $row->amount : -(float) $row->amount);
            if (abs($ledgerDelta) > 0.001) {
                ExpenseLedger::withTrashed()->where('company_id', $entry->company_id)
                    ->lockForUpdate()->findOrFail($entry->expense_ledger_id);
                OtherTransaction::withTrashed()->where('company_id', $entry->company_id)
                    ->where('expense_ledger_id', $entry->expense_ledger_id)->where('status', 'approved')
                    ->where('id', '>', $entry->id)->increment('ledger_balance_after', $ledgerDelta);
            }
            EntryVisibility::where('company_id', $entry->company_id)->where('entry_type', BankTransaction::class)
                ->whereIn('entry_id', $postings->pluck('id'))->delete();
            BankTransaction::where('company_id', $entry->company_id)->whereIn('id', $postings->pluck('id'))->delete();
        }

        EntryVisibility::where('company_id', $entry->company_id)->where('entry_type', OtherTransaction::class)
            ->where('entry_id', $entry->id)->delete();
        $entry->update(['updated_by' => auth()->id()]);
        $entry->delete();
        AuditLog::log('deleted', [
            'company_id' => $entry->company_id, 'model' => OtherTransaction::class, 'model_id' => $entry->id,
            'old_values' => $oldValues, 'new_values' => ['revert_transactions' => $revert],
            'description' => "Other transaction {$entry->transaction_no} deleted; ".($revert ? 'linked transactions reversed.' : 'linked transactions preserved.'),
        ]);
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
