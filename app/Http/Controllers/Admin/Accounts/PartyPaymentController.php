<?php

namespace App\Http\Controllers\Admin\Accounts;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\EntryVisibility;
use App\Models\Party;
use App\Models\PartyPayment;
use App\Models\PartyPaymentAllocation;
use App\Models\PurchaseBill;
use App\Models\SalesInvoice;
use App\Services\AccountingService;
use App\Services\EntryVisibilityService;
use App\Services\PartyOutstandingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PartyPaymentController extends Controller
{
    public function index(Request $request, EntryVisibilityService $visibility)
    {
        $type = $request->query('type');
        $payments = $visibility->scopeForUser(
            PartyPayment::with(['party','bankAccount','creator','allocations'])
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

    public function openBills(Request $request, PartyOutstandingService $outstanding)
    {
        $companyId = auth()->user()->current_company_id;
        $data = $request->validate([
            'party_id' => ['required', Rule::exists('parties', 'id')->where('company_id', $companyId)],
            'payment_type' => ['required', Rule::in(['payment_in','payment_out'])],
        ]);

        $party = Party::where('company_id', $companyId)->findOrFail($data['party_id']);
        $bills = $outstanding->openBillsForPayment($party->id, $data['payment_type']);

        $openingBalanceAvailable = ($data['payment_type'] === 'payment_in' && $party->opening_balance_type === 'receivable')
            || ($data['payment_type'] === 'payment_out' && $party->opening_balance_type === 'payable');
        $openingHistory = PartyPaymentAllocation::with('payment.bankAccount')
            ->where('company_id', $companyId)
            ->where('party_id', $party->id)
            ->where('bill_type', 'opening_balance')
            ->whereHas('payment', fn($query) => $query->where('payment_type', $data['payment_type']))
            ->latest()
            ->get();
        $openingPaid = (float) $openingHistory->sum('amount');
        $openingTotal = $openingBalanceAvailable ? (float) $party->opening_balance : 0;

        return response()->json([
            'bills' => $bills,
            'opening_balance' => [
                'available' => $openingBalanceAvailable && $openingTotal > 0,
                'type' => $party->opening_balance_type,
                'total' => round($openingTotal, 2),
                'paid' => round($openingPaid, 2),
                'remaining' => round(max(0, $openingTotal - $openingPaid), 2),
                'date' => $party->opening_balance_date?->format('d M Y'),
                'history' => $openingHistory->map(fn($allocation) => [
                    'date' => $allocation->payment?->payment_date?->format('d M Y'),
                    'reference_no' => $allocation->payment?->reference_no ?: '-',
                    'amount' => round((float) $allocation->amount, 2),
                    'mode' => $allocation->payment?->payment_mode ?: '-',
                    'account' => $allocation->payment?->bankAccount?->account_name ?: '-',
                ])->values(),
            ],
        ]);
    }

    public function store(Request $request, AccountingService $accounting, EntryVisibilityService $visibility, PartyOutstandingService $outstanding)
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
            'allocations' => ['nullable','array'],
            'allocations.*.bill_id' => ['required_with:allocations','integer'],
            'allocations.*.amount' => ['required_with:allocations','numeric','min:0.01'],
            'settlement_source' => ['nullable', Rule::in(['bills','opening_balance'])],
            'opening_balance_amount' => ['nullable','numeric','min:0.01'],
        ]);

        $data['discount_amount'] = (float) ($data['discount_amount'] ?? 0);
        $data['total_amount'] = max(0, (float) $data['amount'] - $data['discount_amount']);
        $data['attachment'] = $request->hasFile('attachment')
            ? $request->file('attachment')->store('payment-attachments', 'public')
            : null;

        DB::transaction(function () use ($data, $companyId, $accounting, $visibility, $outstanding) {
            $party = Party::where('company_id', $companyId)->lockForUpdate()->findOrFail($data['party_id']);
            $account = BankAccount::where('company_id', $companyId)->lockForUpdate()->findOrFail($data['bank_account_id']);
            $allocations = collect($data['allocations'] ?? [])
                ->filter(fn($row) => (float) ($row['amount'] ?? 0) > 0)
                ->values();
            $isOpeningSettlement = ($data['settlement_source'] ?? null) === 'opening_balance';
            abort_if(!$isOpeningSettlement && $allocations->isEmpty(), 422, 'Select at least one bill and enter payment amount.');
            abort_if($isOpeningSettlement && $allocations->isNotEmpty(), 422, 'Opening balance and invoice payments must be posted separately.');

            $payment = PartyPayment::create(array_merge($data, [
                'company_id' => $companyId,
                'created_by' => auth()->id(),
            ]));

            $billModel = $payment->payment_type === 'payment_in' ? SalesInvoice::class : PurchaseBill::class;
            $billType = $payment->payment_type === 'payment_in' ? 'sales' : 'purchase';
            $typeColumn = $payment->payment_type === 'payment_in' ? 'sale_type' : 'purchase_type';
            $allocatedTotal = 0;

            if ($isOpeningSettlement) {
                $openingTypeAllowed = ($payment->payment_type === 'payment_in' && $party->opening_balance_type === 'receivable')
                    || ($payment->payment_type === 'payment_out' && $party->opening_balance_type === 'payable');
                abort_unless($openingTypeAllowed, 422, 'This party opening balance is not applicable for the selected payment type.');
                $alreadyPaid = (float) PartyPaymentAllocation::where('company_id', $companyId)
                    ->where('party_id', $party->id)
                    ->where('bill_type', 'opening_balance')
                    ->whereHas('payment', fn($query) => $query->where('payment_type', $payment->payment_type))
                    ->lockForUpdate()
                    ->sum('amount');
                $remaining = max(0, (float) $party->opening_balance - $alreadyPaid);
                $amount = round((float) ($data['opening_balance_amount'] ?? 0), 2);
                abort_if($amount <= 0, 422, 'Enter opening balance payment amount.');
                abort_if($amount > $remaining, 422, 'Aap opening balance se jyada payment nahi kar sakte.');
                abort_if(abs($amount - (float) $payment->amount) > 0.01, 422, 'Opening balance settlement must match payment amount.');
                $allocatedTotal = $amount;
                PartyPaymentAllocation::create([
                    'party_payment_id' => $payment->id,
                    'company_id' => $companyId,
                    'party_id' => $party->id,
                    'bill_type' => 'opening_balance',
                    'bill_model' => Party::class,
                    'bill_id' => $party->id,
                    'bill_no' => 'Opening Balance',
                    'bill_date' => $party->opening_balance_date,
                    'bill_total' => $party->opening_balance,
                    'amount' => $amount,
                ]);
            } else {
                foreach ($allocations as $row) {
                    $bill = $billModel::where('company_id', $companyId)
                        ->where('party_id', $party->id)
                        ->where($typeColumn, 'credit')
                        ->lockForUpdate()
                        ->findOrFail($row['bill_id']);
                    $outstandingRow = $outstanding->billPayload($visibility, $billModel, $bill->id);
                    $due = (float) ($outstandingRow['due'] ?? 0);
                    $amount = round((float) $row['amount'], 2);
                    abort_if($amount > $due, 422, "Payment cannot be more than due amount for bill {$bill->invoice_no}.");
                    $allocatedTotal += $amount;

                    PartyPaymentAllocation::create([
                        'party_payment_id' => $payment->id,
                        'company_id' => $companyId,
                        'party_id' => $party->id,
                        'bill_type' => $billType,
                        'bill_model' => $billModel,
                        'bill_id' => $bill->id,
                        'bill_no' => $bill->invoice_no,
                        'bill_date' => $bill->billing_date,
                        'bill_total' => $bill->grand_total,
                        'amount' => $amount,
                    ]);
                }
            }

            abort_if(abs($allocatedTotal - (float) $payment->amount) > 0.01, 422, 'Invoice allocation total must match payment amount.');

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
                'description' => $payment->description ?: ($isOpeningSettlement
                    ? ($isIn ? 'Payment received against opening balance.' : 'Payment paid against opening balance.')
                    : ($isIn ? 'Payment received from party.' : 'Payment paid to party.')),
            ]);

            $account->update(['current_balance' => $bankBalance]);
            $transaction = BankTransaction::create([
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
                'description' => $payment->description ?: ($isOpeningSettlement ? 'Against party opening balance.' : null),
                'attachment' => $payment->attachment,
                'created_by' => auth()->id(),
            ]);

            EntryVisibility::updateOrCreate(
                [
                    'entry_type' => BankTransaction::class,
                    'entry_id' => $transaction->id,
                ],
                [
                    'company_id' => $companyId,
                    'visible_to_all_company' => true,
                    'visible_to_roles' => [],
                    'visible_to_users' => [],
                ]
            );
        });

        return redirect()->route('admin.party-payments.index', ['type' => $data['payment_type']])
            ->with('success', 'Payment posted to party ledger and bank ledger.');
    }
}
