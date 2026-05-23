<?php

namespace App\Http\Controllers\Admin\Accounts;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Party;
use App\Models\PartyPayment;
use App\Services\AccountingService;
use App\Services\EntryVisibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PartyPaymentController extends Controller
{
    public function index(Request $request, EntryVisibilityService $visibility)
    {
        $type = $request->query('type');
        $payments = $visibility->scopeForUser(
            PartyPayment::with(['party','bankAccount','creator'])
                ->when($type, fn($q) => $q->where('payment_type', $type))
                ->latest('payment_date')
                ->latest(),
            PartyPayment::class
        )->get();

        return view('admin.party-payments.index', compact('payments', 'type'));
    }

    public function create(Request $request)
    {
        $companyId = auth()->user()->current_company_id;
        $type = $request->query('type', 'payment_in');

        return view('admin.party-payments.create', [
            'type' => $type,
            'parties' => Party::where('company_id', $companyId)->where('status', 'active')->orderBy('display_name')->get(),
            'accounts' => BankAccount::where('company_id', $companyId)->where('status', 'active')->orderBy('account_name')->get(),
        ]);
    }

    public function store(Request $request, AccountingService $accounting)
    {
        $companyId = auth()->user()->current_company_id;
        $data = $request->validate([
            'payment_type' => ['required', Rule::in(['payment_in','payment_out'])],
            'party_id' => ['required', Rule::exists('parties', 'id')->where('company_id', $companyId)],
            'bank_account_id' => ['required', Rule::exists('bank_accounts', 'id')->where('company_id', $companyId)],
            'payment_date' => ['required','date'],
            'reference_no' => ['nullable','string','max:255'],
            'amount' => ['required','numeric','min:0.01'],
            'discount_amount' => ['nullable','numeric','min:0'],
            'payment_mode' => ['nullable','string','max:40'],
            'description' => ['nullable','string'],
            'attachment' => ['nullable','file','max:4096'],
        ]);

        $data['discount_amount'] = (float) ($data['discount_amount'] ?? 0);
        $data['total_amount'] = max(0, (float) $data['amount'] - $data['discount_amount']);
        $data['attachment'] = $request->hasFile('attachment')
            ? $request->file('attachment')->store('payment-attachments', 'public')
            : null;

        DB::transaction(function () use ($data, $companyId, $accounting) {
            $party = Party::where('company_id', $companyId)->lockForUpdate()->findOrFail($data['party_id']);
            $account = BankAccount::where('company_id', $companyId)->lockForUpdate()->findOrFail($data['bank_account_id']);

            $payment = PartyPayment::create(array_merge($data, [
                'company_id' => $companyId,
                'created_by' => auth()->id(),
            ]));

            $isIn = $payment->payment_type === 'payment_in';
            $partyDebit = $isIn ? 0 : $payment->total_amount;
            $partyCredit = $isIn ? $payment->total_amount : 0;
            $bankDirection = $isIn ? 'in' : 'out';
            $bankBalance = $isIn
                ? (float) $account->current_balance + (float) $payment->total_amount
                : (float) $account->current_balance - (float) $payment->total_amount;

            $accounting->postPartyLedger($party, [
                'entry_date' => $payment->payment_date,
                'entry_type' => $payment->payment_type,
                'reference_type' => PartyPayment::class,
                'reference_id' => $payment->id,
                'reference_no' => $payment->reference_no,
                'debit' => $partyDebit,
                'credit' => $partyCredit,
                'description' => $payment->description ?: ($isIn ? 'Payment received from party.' : 'Payment paid to party.'),
            ]);

            $account->update(['current_balance' => $bankBalance]);
            BankTransaction::create([
                'company_id' => $companyId,
                'bank_account_id' => $account->id,
                'party_id' => $party->id,
                'transaction_date' => $payment->payment_date,
                'transaction_type' => $payment->payment_type,
                'direction' => $bankDirection,
                'amount' => $payment->total_amount,
                'balance_after' => $bankBalance,
                'reference_type' => PartyPayment::class,
                'reference_id' => $payment->id,
                'reference_no' => $payment->reference_no,
                'payment_mode' => $payment->payment_mode,
                'description' => $payment->description,
                'attachment' => $payment->attachment,
                'created_by' => auth()->id(),
            ]);
        });

        return redirect()->route('admin.party-payments.index', ['type' => $data['payment_type']])
            ->with('success', 'Payment posted to party ledger and bank ledger.');
    }
}
