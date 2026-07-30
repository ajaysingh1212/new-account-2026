<?php

namespace App\Http\Controllers\Admin\Accounts;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\AuditLog;
use App\Models\EntryVisibility;
use App\Models\Party;
use App\Models\PartyAdvance;
use App\Models\PartyPayment;
use App\Models\PartyPaymentAllocation;
use App\Models\PartyOpeningBalanceAdjustment;
use App\Models\PartyLedger;
use App\Models\PurchaseBill;
use App\Models\SalesInvoice;
use App\Services\AccountingService;
use App\Services\EntryVisibilityService;
use App\Services\PartyOutstandingService;
use App\Services\PartyAdvanceService;
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
        $type = $request->query('type', 'payment_in');

        return view('admin.party-payments.create', $this->formData($type));
    }

    public function edit(PartyPayment $partyPayment)
    {
        $this->authorizePayment($partyPayment);

        return view('admin.party-payments.create', $this->formData($partyPayment->payment_type, $partyPayment));
    }

    public function openBills(Request $request, PartyOutstandingService $outstanding, PartyAdvanceService $advances)
    {
        $companyId = auth()->user()->current_company_id;
        $data = $request->validate([
            'party_id' => ['required', Rule::exists('parties', 'id')->where('company_id', $companyId)],
            'payment_type' => ['required', Rule::in(['payment_in','payment_out'])],
        ]);

        $party = Party::where('company_id', $companyId)->findOrFail($data['party_id']);
        $bills = $outstanding->openBillsForPayment($party->id, $data['payment_type']);
        $advanceDirection = $data['payment_type'] === 'payment_in' ? 'in' : 'out';
        $availableAdvances = $advances->availableForParty($party->id, $advanceDirection);

        $adjustmentTotal = (float) $party->openingBalanceAdjustments()
            ->whereDate('adjustment_date', '<=', now()->toDateString())
            ->sum('adjustment_amount');
        $effectiveOpeningBalance = max(0, (float) $party->opening_balance + $adjustmentTotal);

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
        $openingTotal = $openingBalanceAvailable ? $effectiveOpeningBalance : 0;

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
            'available_advances' => $availableAdvances,
            'adjustment_history' => $party->openingBalanceAdjustments()
                ->with('creator')
                ->latest('adjustment_date')
                ->latest('id')
                ->get()
                ->map(fn(PartyOpeningBalanceAdjustment $adjustment) => [
                    'date' => $adjustment->adjustment_date?->format('d M Y'),
                    'direction' => $adjustment->direction,
                    'previous_amount' => round((float) $adjustment->previous_amount, 2),
                    'adjustment_amount' => round((float) abs($adjustment->adjustment_amount), 2),
                    'new_amount' => round((float) $adjustment->new_amount, 2),
                    'reason' => $adjustment->reason ?: '-',
                    'user' => $adjustment->creator?->name ?: 'System',
                    'role' => $adjustment->created_role ?: 'No role',
                ])->values(),
        ]);
    }

    public function store(Request $request, AccountingService $accounting, EntryVisibilityService $visibility, PartyOutstandingService $outstanding, PartyAdvanceService $advanceService)
    {
        $data = $this->validatedData($request);
        $adjustmentOnly = $this->isAdjustmentOnly($data);

        $payment = DB::transaction(fn() => $this->persistPayment($data, $request, $accounting, $visibility, $outstanding, $advanceService));

        if (!$payment) {
            return redirect()->route('admin.party-payments.index', ['type' => $data['payment_type']])
                ->with('success', 'Opening balance adjustment saved successfully.');
        }

        AuditLog::log('created', [
            'model' => PartyPayment::class,
            'model_id' => $payment->id,
            'description' => "Party payment created: {$payment->payment_type} {$payment->reference_no}",
            'new_values' => $payment->load('allocations')->toArray(),
        ]);

        return redirect()->route('admin.party-payments.index', ['type' => $payment->payment_type])
            ->with('success', 'Payment posted to party ledger and bank ledger.');
    }

    public function update(Request $request, PartyPayment $partyPayment, AccountingService $accounting, EntryVisibilityService $visibility, PartyOutstandingService $outstanding, PartyAdvanceService $advanceService)
    {
        $this->authorizePayment($partyPayment);
        $old = $partyPayment->load('allocations')->toArray();
        $data = $this->validatedData($request, $partyPayment);

        $payment = DB::transaction(function () use ($partyPayment, $data, $request, $accounting, $visibility, $outstanding, $advanceService) {
            $this->revertPayment($partyPayment, $advanceService);

            return $this->persistPayment($data, $request, $accounting, $visibility, $outstanding, $advanceService, $partyPayment);
        });

        AuditLog::log('updated', [
            'model' => PartyPayment::class,
            'model_id' => $payment->id,
            'description' => "Party payment updated: {$payment->payment_type} {$payment->reference_no}",
            'old_values' => $old,
            'new_values' => $payment->fresh(['allocations'])->toArray(),
        ]);

        return redirect()->route('admin.party-payments.index', ['type' => $payment->payment_type])
            ->with('success', 'Payment updated and all ledger effects recalculated.');
    }

    public function destroy(PartyPayment $partyPayment, PartyAdvanceService $advanceService)
    {
        $this->authorizePayment($partyPayment);
        $old = $partyPayment->load(['allocations', 'party', 'bankAccount'])->toArray();

        DB::transaction(function () use ($partyPayment, $advanceService) {
            $this->revertPayment($partyPayment, $advanceService, true);
            $partyPayment->delete();
        });

        AuditLog::log('deleted', [
            'model' => PartyPayment::class,
            'model_id' => $partyPayment->id,
            'description' => "Party payment deleted and reverted: {$partyPayment->reference_no}",
            'old_values' => $old,
        ]);

        return redirect()->route('admin.party-payments.index', ['type' => $partyPayment->payment_type])
            ->with('success', 'Payment deleted and ledger/bank impact reverted successfully.');
    }

    private function validatedData(Request $request, ?PartyPayment $payment = null): array
    {
        $companyId = auth()->user()->current_company_id;

        $data = $request->validate([
            'payment_type' => ['required', Rule::in(['payment_in','payment_out'])],
            'party_id' => ['required', Rule::exists('parties', 'id')->where('company_id', $companyId)],
            'bank_account_id' => ['nullable', Rule::exists('bank_accounts', 'id')->where('company_id', $companyId)],
            'payment_date' => ['required','date'],
            'reference_no' => ['nullable','string','max:255'],
            'amount' => ['nullable','numeric','min:0'],
            'discount_amount' => ['nullable','numeric','min:0'],
            'payment_mode' => ['nullable','string','max:40'],
            'description' => ['nullable','string'],
            'attachment' => ['nullable','file','max:4096'],
            'allocations' => ['nullable','array'],
            'allocations.*.bill_id' => ['required_with:allocations','integer'],
            'allocations.*.amount' => ['required_with:allocations','numeric','min:0.01'],
            'settlement_source' => ['nullable', Rule::in(['bills','opening_balance','advance'])],
            'opening_balance_amount' => ['nullable','numeric','min:0.01'],
            'adjustment_type' => ['nullable', Rule::in(['increase','decrease'])],
            'adjustment_amount' => ['nullable','numeric','min:0.01'],
            'adjustment_note' => ['nullable','string','max:1000'],
            'existing_attachment' => ['nullable','string'],
        ]);

        $data['discount_amount'] = (float) ($data['discount_amount'] ?? 0);
        $data['total_amount'] = max(0, (float) $data['amount'] - $data['discount_amount']);
        $data['attachment'] = $request->hasFile('attachment')
            ? $request->file('attachment')->store('payment-attachments', 'public')
            : ($payment?->attachment ?: ($data['existing_attachment'] ?? null));

        $adjustmentOnly = $this->isAdjustmentOnly($data);
        if (!$adjustmentOnly) {
            abort_if(empty($data['bank_account_id']), 422, 'Bank/Cash account select karein.');
            abort_if((float) ($data['amount'] ?? 0) <= 0, 422, 'Payment amount enter karein.');
        }

        return $data;
    }

    private function persistPayment(array $data, Request $request, AccountingService $accounting, EntryVisibilityService $visibility, PartyOutstandingService $outstanding, PartyAdvanceService $advanceService, ?PartyPayment $payment = null): ?PartyPayment
    {
        $companyId = auth()->user()->current_company_id;
        $party = Party::where('company_id', $companyId)->lockForUpdate()->findOrFail($data['party_id']);

        $this->applyOpeningAdjustmentIfNeeded($party, $data);
        if ($this->isAdjustmentOnly($data)) {
            return null;
        }

        $account = BankAccount::where('company_id', $companyId)->lockForUpdate()->findOrFail($data['bank_account_id']);

        $allocations = collect($data['allocations'] ?? [])
            ->filter(fn($row) => (float) ($row['amount'] ?? 0) > 0)
            ->values();
        $isOpeningSettlement = ($data['settlement_source'] ?? null) === 'opening_balance';
        $isAdvanceSettlement = ($data['settlement_source'] ?? null) === 'advance';
        abort_if(!$isOpeningSettlement && !$isAdvanceSettlement && $allocations->isEmpty(), 422, 'Select at least one bill and enter payment amount.');
        abort_if($isOpeningSettlement && $allocations->isNotEmpty(), 422, 'Opening balance and invoice payments must be posted separately.');
        abort_if($isAdvanceSettlement && $allocations->isNotEmpty(), 422, 'Advance payment must be posted without bill allocations.');

        $payment = $payment
            ? tap($payment)->update(array_merge($data, ['company_id' => $companyId, 'created_by' => $payment->created_by ?: auth()->id()]))
            : PartyPayment::create(array_merge($data, ['company_id' => $companyId, 'created_by' => auth()->id()]));

        $billModel = $payment->payment_type === 'payment_in' ? SalesInvoice::class : PurchaseBill::class;
        $billType = $payment->payment_type === 'payment_in' ? 'sales' : 'purchase';
        $typeColumn = $payment->payment_type === 'payment_in' ? 'sale_type' : 'purchase_type';
        $allocatedTotal = 0;

        if ($isOpeningSettlement) {
            $openingTypeAllowed = ($payment->payment_type === 'payment_in' && $party->opening_balance_type === 'receivable')
                || ($payment->payment_type === 'payment_out' && $party->opening_balance_type === 'payable');
            abort_unless($openingTypeAllowed, 422, 'This party opening balance is not applicable for the selected payment type.');
            $effectiveOpening = max(0, (float) $party->opening_balance + (float) $party->openingBalanceAdjustments()->sum('adjustment_amount'));
            $alreadyPaid = (float) PartyPaymentAllocation::where('company_id', $companyId)
                ->where('party_id', $party->id)
                ->where('bill_type', 'opening_balance')
                ->whereHas('payment', fn($query) => $query->where('payment_type', $payment->payment_type))
                ->lockForUpdate()
                ->sum('amount');
            $remaining = max(0, $effectiveOpening - $alreadyPaid);
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
                'bill_total' => $effectiveOpening,
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

        if (!$isAdvanceSettlement) {
            abort_if(abs($allocatedTotal - (float) $payment->amount) > 0.01, 422, 'Invoice allocation total must match payment amount.');
        }

        $isIn = $payment->payment_type === 'payment_in';
        $partyDebit = $isIn ? 0 : $payment->total_amount;
        $partyCredit = $isIn ? $payment->total_amount : 0;
        $bankDirection = $isIn ? 'in' : 'out';
        $bankBalance = $isIn
            ? (float) $account->current_balance + (float) $payment->total_amount
            : (float) $account->current_balance - (float) $payment->total_amount;

        $ledgerDescription = $payment->description ?: ($isOpeningSettlement
            ? ($isIn ? 'Payment received against opening balance.' : 'Payment paid against opening balance.')
            : ($isAdvanceSettlement
                ? ($isIn ? 'Advance payment received from party.' : 'Advance payment paid to party.')
                : ($isIn ? 'Payment received from party.' : 'Payment paid to party.')));

        $accounting->postPartyLedger($party, [
            'entry_date' => $payment->payment_date,
            'entry_type' => $payment->payment_type,
            'reference_type' => PartyPayment::class,
            'reference_id' => $payment->id,
            'reference_no' => $payment->reference_no,
            'debit' => $partyDebit,
            'credit' => $partyCredit,
            'description' => $ledgerDescription,
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
            'description' => $payment->description ?: ($isAdvanceSettlement
                ? 'Advance payment.'
                : ($isOpeningSettlement ? 'Against party opening balance.' : null)),
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

        if ($isAdvanceSettlement) {
            $advanceService->createAdvanceFromPayment($payment);
        }

        return $payment;
    }

    private function isAdjustmentOnly(array $data): bool
    {
        $hasAdjustment = !empty($data['adjustment_type']) && (float) ($data['adjustment_amount'] ?? 0) > 0;
        $hasBills = collect($data['allocations'] ?? [])->contains(fn($row) => (float) ($row['amount'] ?? 0) > 0);
        $hasOpeningSettlement = ($data['settlement_source'] ?? null) === 'opening_balance' && (float) ($data['opening_balance_amount'] ?? 0) > 0;
        $hasAdvanceSettlement = ($data['settlement_source'] ?? null) === 'advance';
        $hasPaymentAmount = (float) ($data['amount'] ?? 0) > 0;

        return $hasAdjustment && !$hasBills && !$hasOpeningSettlement && !$hasAdvanceSettlement && !$hasPaymentAmount;
    }

    private function applyOpeningAdjustmentIfNeeded(Party $party, array $data): void
    {
        $adjustmentAmount = round((float) ($data['adjustment_amount'] ?? 0), 2);
        $adjustmentType = $data['adjustment_type'] ?? null;
        if ($adjustmentAmount <= 0 || !$adjustmentType) {
            return;
        }

        $signedAmount = $adjustmentType === 'decrease' ? -abs($adjustmentAmount) : abs($adjustmentAmount);
        $previousAmount = (float) $party->opening_balance;
        $newAmount = max(0, $previousAmount + $signedAmount);
        abort_if($adjustmentType === 'decrease' && $newAmount < 0.01, 422, 'Opening balance ko zero se neeche nahi le ja sakte.');

        $role = auth()->user()?->rolesForCompany($party->company_id)->pluck('name')->join(', ');
        $party->update([
            'opening_balance' => $newAmount,
            'updated_by' => auth()->id(),
        ]);

        $adjustment = PartyOpeningBalanceAdjustment::create([
            'company_id' => $party->company_id,
            'party_id' => $party->id,
            'adjustment_date' => $data['payment_date'] ?? now()->toDateString(),
            'previous_amount' => $previousAmount,
            'adjustment_amount' => $signedAmount,
            'new_amount' => $newAmount,
            'direction' => $adjustmentType,
            'reason' => $data['adjustment_note'] ?? null,
            'created_by' => auth()->id(),
            'created_role' => $role ?: 'No role',
        ]);

        PartyLedger::create([
            'company_id' => $party->company_id,
            'party_id' => $party->id,
            'entry_date' => $data['payment_date'] ?? now()->toDateString(),
            'entry_type' => 'opening_balance_adjustment',
            'reference_type' => PartyOpeningBalanceAdjustment::class,
            'reference_id' => $adjustment->id,
            'reference_no' => $party->party_code,
            'debit' => $party->opening_balance_type === 'receivable' && $signedAmount > 0 ? abs($signedAmount) : ($party->opening_balance_type === 'payable' && $signedAmount < 0 ? abs($signedAmount) : 0),
            'credit' => $party->opening_balance_type === 'payable' && $signedAmount > 0 ? abs($signedAmount) : ($party->opening_balance_type === 'receivable' && $signedAmount < 0 ? abs($signedAmount) : 0),
            'balance_after' => (float) $party->current_balance + (($party->opening_balance_type === 'receivable' ? -1 : 1) * $signedAmount),
            'description' => trim('Opening balance adjusted ' . $adjustmentType . ($data['adjustment_note'] ? ': ' . $data['adjustment_note'] : '.')),
            'created_by' => auth()->id(),
        ]);

        $party->update([
            'current_balance' => (float) $party->current_balance + (($party->opening_balance_type === 'receivable' ? -1 : 1) * $signedAmount),
        ]);
    }

    private function revertPayment(PartyPayment $payment, PartyAdvanceService $advanceService, bool $keepPaymentRecord = false): void
    {
        $companyId = $payment->company_id;
        $party = Party::where('company_id', $companyId)->lockForUpdate()->findOrFail($payment->party_id);
        $account = BankAccount::where('company_id', $companyId)->lockForUpdate()->findOrFail($payment->bank_account_id);

        if ($payment->advance) {
            $advanceService->releaseForDocument(PartyAdvance::class, $payment->advance->id);
            PartyAdvance::where('party_payment_id', $payment->id)->delete();
        }

        $ledger = PartyLedger::where('reference_type', PartyPayment::class)->where('reference_id', $payment->id)->first();
        if ($ledger) {
            $party->update([
                'current_balance' => (float) $party->current_balance - ((float) $ledger->credit - (float) $ledger->debit),
            ]);
            $ledger->delete();
        }

        $transaction = BankTransaction::where('reference_type', PartyPayment::class)->where('reference_id', $payment->id)->first();
        if ($transaction) {
            $delta = $transaction->direction === 'in' ? -(float) $transaction->amount : (float) $transaction->amount;
            $account->update(['current_balance' => (float) $account->current_balance + $delta]);
            EntryVisibility::where('entry_type', BankTransaction::class)->where('entry_id', $transaction->id)->delete();
            $transaction->delete();
        }

        PartyPaymentAllocation::where('party_payment_id', $payment->id)->delete();

        if (!$keepPaymentRecord) {
            $payment->fill([
                'amount' => 0,
                'discount_amount' => 0,
                'total_amount' => 0,
            ]);
        }
    }

    private function formData(string $type, ?PartyPayment $payment = null): array
    {
        $companyId = auth()->user()->current_company_id;

        return [
            'type' => $type,
            'payment' => $payment?->load('allocations'),
            'parties' => Party::where('company_id', $companyId)->where('status', 'active')->orderBy('display_name')->get(),
            'accounts' => BankAccount::where('company_id', $companyId)->where('status', 'active')->orderBy('account_name')->get(),
        ];
    }

    private function authorizePayment(PartyPayment $payment): void
    {
        abort_unless($payment->company_id === auth()->user()->current_company_id || auth()->user()->isSuperAdmin(), 403);
    }
}
