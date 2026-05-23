<?php

namespace App\Http\Controllers\Admin\Accounts;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Services\EntryVisibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BankAccountController extends Controller
{
    public function index(EntryVisibilityService $visibility)
    {
        $accounts = $visibility->scopeForUser(
            BankAccount::with('creator')->latest(),
            BankAccount::class
        )->get();
        $summary = [
            'total' => $accounts->count(),
            'bank' => $accounts->where('account_type', 'bank')->sum('current_balance'),
            'cash' => $accounts->where('account_type', 'cash')->sum('current_balance'),
            'print' => $accounts->where('print_on_invoice', true)->count(),
        ];

        return view('admin.bank-accounts.index', compact('accounts', 'summary'));
    }

    public function create()
    {
        $bankAccount = new BankAccount([
            'account_code' => $this->nextCode(),
            'account_type' => 'bank',
            'opening_balance_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        return view('admin.bank-accounts.create', compact('bankAccount'));
    }

    public function store(Request $request, EntryVisibilityService $visibility)
    {
        $companyId = auth()->user()->current_company_id;
        $data = $this->validated($request, $companyId);

        $account = DB::transaction(function () use ($data, $companyId) {
            if (!empty($data['is_primary'])) {
                BankAccount::where('company_id', $companyId)->update(['is_primary' => false]);
            }

            $account = BankAccount::create(array_merge($data, [
                'company_id' => $companyId,
                'current_balance' => $data['opening_balance'] ?? 0,
                'print_on_invoice' => !empty($data['print_on_invoice']),
                'is_primary' => !empty($data['is_primary']),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]));

            if ((float) $account->opening_balance > 0) {
                BankTransaction::create([
                    'company_id' => $companyId,
                    'bank_account_id' => $account->id,
                    'transaction_date' => $account->opening_balance_date ?? now()->toDateString(),
                    'transaction_type' => 'opening_balance',
                    'direction' => 'in',
                    'amount' => $account->opening_balance,
                    'balance_after' => $account->current_balance,
                    'reference_type' => BankAccount::class,
                    'reference_id' => $account->id,
                    'reference_no' => $account->account_code,
                    'description' => 'Opening balance entered during bank account creation.',
                    'created_by' => auth()->id(),
                ]);
            }

            return $account;
        });
        $visibility->syncFromRequest($request, $account);

        AuditLog::log('created', [
            'model' => BankAccount::class,
            'model_id' => $account->id,
            'description' => "Bank account created: {$account->account_name}",
        ]);

        return redirect()->route('admin.bank-accounts.index')->with('success', 'Bank account created successfully.');
    }

    public function show(BankAccount $bankAccount, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($bankAccount);
        $bankAccount->load(['transactions' => fn($q) => $q->with(['party','relatedBankAccount'])->latest('transaction_date')->latest()]);

        return view('admin.bank-accounts.show', compact('bankAccount'));
    }

    public function edit(BankAccount $bankAccount, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($bankAccount);

        return view('admin.bank-accounts.edit', compact('bankAccount'));
    }

    public function update(Request $request, BankAccount $bankAccount, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($bankAccount);
        $data = $this->validated($request, $bankAccount->company_id, $bankAccount->id);

        DB::transaction(function () use ($bankAccount, $data) {
            if (!empty($data['is_primary'])) {
                BankAccount::where('company_id', $bankAccount->company_id)
                    ->where('id', '!=', $bankAccount->id)
                    ->update(['is_primary' => false]);
            }

            $nonOpeningBalance = (float) $bankAccount->transactions()
                ->where('transaction_type', '!=', 'opening_balance')
                ->sum(DB::raw("CASE WHEN direction = 'in' THEN amount ELSE -amount END"));

            $bankAccount->update(array_merge($data, [
                'current_balance' => ((float) ($data['opening_balance'] ?? 0)) + $nonOpeningBalance,
                'print_on_invoice' => !empty($data['print_on_invoice']),
                'is_primary' => !empty($data['is_primary']),
                'updated_by' => auth()->id(),
            ]));

            $bankAccount->transactions()->where('transaction_type', 'opening_balance')->delete();
            if ((float) $bankAccount->opening_balance > 0) {
                BankTransaction::create([
                    'company_id' => $bankAccount->company_id,
                    'bank_account_id' => $bankAccount->id,
                    'transaction_date' => $bankAccount->opening_balance_date ?? now()->toDateString(),
                    'transaction_type' => 'opening_balance',
                    'direction' => 'in',
                    'amount' => $bankAccount->opening_balance,
                    'balance_after' => $bankAccount->current_balance,
                    'reference_type' => BankAccount::class,
                    'reference_id' => $bankAccount->id,
                    'reference_no' => $bankAccount->account_code,
                    'description' => 'Opening balance updated from bank master.',
                    'created_by' => auth()->id(),
                ]);
            }
        });
        $visibility->syncFromRequest($request, $bankAccount);

        return redirect()->route('admin.bank-accounts.index')->with('success', 'Bank account updated successfully.');
    }

    public function destroy(BankAccount $bankAccount, EntryVisibilityService $visibility)
    {
        $visibility->authorizeManage($bankAccount);
        if ($bankAccount->transactions()->where('transaction_type', '!=', 'opening_balance')->exists()) {
            return back()->with('error', 'This account has transactions and cannot be deleted.');
        }
        $bankAccount->delete();

        return redirect()->route('admin.bank-accounts.index')->with('success', 'Bank account deleted successfully.');
    }

    private function validated(Request $request, int $companyId, ?int $id = null): array
    {
        return $request->validate([
            'account_code' => ['required','string','max:30', Rule::unique('bank_accounts')->where('company_id', $companyId)->ignore($id)],
            'account_type' => ['required', Rule::in(['bank','cash'])],
            'account_name' => ['required','string','max:255'],
            'bank_name' => ['nullable','string','max:255'],
            'branch_name' => ['nullable','string','max:255'],
            'account_holder_name' => ['nullable','string','max:255'],
            'account_number' => ['nullable','string','max:255'],
            'ifsc_code' => ['nullable','string','max:20'],
            'swift_code' => ['nullable','string','max:30'],
            'upi_id' => ['nullable','string','max:255'],
            'phone' => ['nullable','string','max:30'],
            'email' => ['nullable','email','max:255'],
            'address' => ['nullable','string'],
            'opening_balance' => ['nullable','numeric','min:0'],
            'opening_balance_date' => ['nullable','date'],
            'is_primary' => ['nullable','boolean'],
            'print_on_invoice' => ['nullable','boolean'],
            'status' => ['required', Rule::in(['active','inactive'])],
            'notes' => ['nullable','string'],
        ]);
    }

    private function nextCode(): string
    {
        $next = BankAccount::where('company_id', auth()->user()->current_company_id)->withTrashed()->count() + 1;
        return 'BA-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function authorizeCompany(BankAccount $bankAccount): void
    {
        abort_unless($bankAccount->company_id === auth()->user()->current_company_id || auth()->user()->isSuperAdmin(), 403);
    }
}
