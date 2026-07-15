<?php

namespace App\Http\Controllers\Admin\Accounts;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\EntryVisibility;
use App\Models\BankTransaction;
use App\Models\Party;
use App\Services\EntryVisibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BankTransactionController extends Controller
{
    public function index(EntryVisibilityService $visibility)
    {
        $transactions = $visibility->scopeForUser(
            BankTransaction::with(['bankAccount','relatedBankAccount','party','creator'])
                ->latest('transaction_date')
                ->latest(),
            BankTransaction::class
        )->get();

        return view('admin.bank-transactions.index', compact('transactions'));
    }

    public function create(Request $request)
    {
        $type = $request->query('type', 'bank_to_bank');
        $accounts = $this->accounts();
        $parties = Party::where('company_id', auth()->user()->current_company_id)->orderBy('display_name')->get();

        return view('admin.bank-transactions.create', compact('type', 'accounts', 'parties'));
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->current_company_id;
        $data = $request->validate([
            'transaction_type' => ['required', Rule::in(['bank_to_bank','bank_to_cash','cash_to_bank','manual_adjustment'])],
            'from_account_id' => ['nullable', Rule::exists('bank_accounts', 'id')->where('company_id', $companyId)],
            'to_account_id' => ['nullable', Rule::exists('bank_accounts', 'id')->where('company_id', $companyId)],
            'bank_account_id' => ['nullable', Rule::exists('bank_accounts', 'id')->where('company_id', $companyId)],
            'adjustment_type' => ['nullable', Rule::in(['increase','decrease'])],
            'party_id' => ['nullable', Rule::exists('parties', 'id')->where('company_id', $companyId)],
            'transaction_date' => ['required','date'],
            'amount' => ['required','numeric','min:0.01'],
            'reference_no' => ['nullable','string','max:255'],
            'payment_mode' => ['nullable','string','max:40'],
            'description' => ['nullable','string'],
            'attachment' => ['nullable','file','max:4096'],
        ]);

        $attachment = $request->hasFile('attachment')
            ? $request->file('attachment')->store('bank-attachments', 'public')
            : null;

        DB::transaction(function () use ($data, $companyId, $attachment) {
            if ($data['transaction_type'] === 'manual_adjustment') {
                $account = BankAccount::where('company_id', $companyId)->lockForUpdate()->findOrFail($data['bank_account_id']);
                $direction = $data['adjustment_type'] === 'increase' ? 'in' : 'out';
                $newBalance = $direction === 'in'
                    ? (float) $account->current_balance + (float) $data['amount']
                    : (float) $account->current_balance - (float) $data['amount'];

                $account->update(['current_balance' => $newBalance]);
                $this->createLedger($account, null, $data, $direction, $newBalance, $attachment);
                return;
            }

            $from = BankAccount::where('company_id', $companyId)->lockForUpdate()->findOrFail($data['from_account_id']);
            $to = BankAccount::where('company_id', $companyId)->lockForUpdate()->findOrFail($data['to_account_id']);
            abort_if($from->id === $to->id, 422, 'Source and destination account cannot be same.');

            $amount = (float) $data['amount'];
            $group = (string) Str::uuid();

            $fromBalance = (float) $from->current_balance - $amount;
            $toBalance = (float) $to->current_balance + $amount;
            $from->update(['current_balance' => $fromBalance]);
            $to->update(['current_balance' => $toBalance]);

            $this->createLedger($from, $to, $data, 'out', $fromBalance, $attachment, $group);
            $this->createLedger($to, $from, $data, 'in', $toBalance, null, $group);
        });

        return redirect()->route('admin.bank-transactions.index')->with('success', 'Bank transaction saved successfully.');
    }

    public function report(Request $request, EntryVisibilityService $visibility)
    {
        $companyId = auth()->user()->current_company_id;
        $accounts = $this->accounts();
        $selectedAccount = null;
        $transactions = collect();

        if ($request->filled('bank_account_id')) {
            $selectedAccount = $visibility->scopeForUser(
                BankAccount::query(),
                BankAccount::class
            )->findOrFail($request->bank_account_id);
            $transactions = $visibility->scopeForUser(
                BankTransaction::with(['party','relatedBankAccount','creator'])
                    ->where('bank_account_id', $selectedAccount->id),
                BankTransaction::class
            )
                ->when($request->filled('from_date'), fn($q) => $q->whereDate('transaction_date', '>=', $request->from_date))
                ->when($request->filled('to_date'), fn($q) => $q->whereDate('transaction_date', '<=', $request->to_date))
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->get();
        }

        return view('admin.bank-reports.statement', compact('accounts', 'selectedAccount', 'transactions'));
    }

    private function createLedger(BankAccount $account, ?BankAccount $related, array $data, string $direction, float $balanceAfter, ?string $attachment, ?string $group = null): void
    {
        $transaction = BankTransaction::create([
            'company_id' => $account->company_id,
            'bank_account_id' => $account->id,
            'related_bank_account_id' => $related?->id,
            'party_id' => $data['party_id'] ?? null,
            'transaction_date' => $data['transaction_date'],
            'transaction_type' => $data['transaction_type'],
            'direction' => $direction,
            'amount' => $data['amount'],
            'balance_after' => $balanceAfter,
            'reference_no' => $data['reference_no'] ?? null,
            'payment_mode' => $data['payment_mode'] ?? null,
            'description' => $data['description'] ?? null,
            'attachment' => $attachment,
            'transfer_group' => $group,
            'created_by' => auth()->id(),
        ]);

        EntryVisibility::updateOrCreate(
            [
                'entry_type' => BankTransaction::class,
                'entry_id' => $transaction->id,
            ],
            [
                'company_id' => $account->company_id,
                'visible_to_all_company' => true,
                'visible_to_roles' => [],
                'visible_to_users' => [],
            ]
        );
    }

    private function accounts()
    {
        return app(EntryVisibilityService::class)->scopeForUser(
            BankAccount::where('status', 'active')->orderBy('account_name'),
            BankAccount::class
        )->get();
    }
}
