@php $isEdit = $transaction->exists; @endphp
<form method="POST" action="{{ $isEdit ? route('admin.other-transactions.update', $transaction) : route('admin.other-transactions.store') }}" enctype="multipart/form-data">
@csrf
@if($isEdit) @method('PUT') @endif
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0">Other Income / Other Expense</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 form-group">
                <label>Type *</label>
                <select name="transaction_kind" class="form-control" required>
                    <option value="income" @selected(old('transaction_kind', $transaction->transaction_kind) === 'income')>Other Income</option>
                    <option value="expense" @selected(old('transaction_kind', $transaction->transaction_kind) === 'expense')>Other Expense</option>
                </select>
            </div>
            <div class="col-md-3 form-group">
                <label>Transaction No</label>
                <input name="transaction_no" class="form-control" value="{{ old('transaction_no', $transaction->transaction_no) }}">
            </div>
            <div class="col-md-3 form-group">
                <label>Date *</label>
                <input type="date" name="transaction_date" class="form-control" value="{{ old('transaction_date', $transaction->transaction_date?->format('Y-m-d') ?? now()->toDateString()) }}" required>
            </div>
            <div class="col-md-3 form-group">
                <label>Ledger *</label>
                <select name="expense_ledger_id" class="form-control select2" required>
                    <option value="">Select ledger</option>
                    @foreach($ledgers as $ledger)
                        <option value="{{ $ledger->id }}" @selected(old('expense_ledger_id', $transaction->expense_ledger_id) == $ledger->id)>
                            {{ $ledger->name }} | Rs {{ number_format((float) $ledger->current_balance, 2) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3 form-group">
                <label>Bank Account</label>
                <select name="bank_account_id" class="form-control select2">
                    <option value="">Select bank/cash</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" @selected(old('bank_account_id', $transaction->bank_account_id) == $account->id)>
                            {{ $account->account_name }} | Rs {{ number_format((float) $account->current_balance, 2) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 form-group">
                <label>Reference No</label>
                <input name="reference_no" class="form-control" value="{{ old('reference_no', $transaction->reference_no) }}">
            </div>
            <div class="col-md-3 form-group">
                <label>Party / Narration Name</label>
                <input name="party_name" class="form-control" value="{{ old('party_name', $transaction->party_name) }}">
            </div>
            <div class="col-md-3 form-group">
                <label>Payment Mode</label>
                <select name="payment_mode" class="form-control">
                    @foreach(['UPI','NEFT','Cash','Cheque','Card','Other'] as $mode)
                        <option value="{{ $mode }}" @selected(old('payment_mode', $transaction->payment_mode) === $mode)>{{ $mode }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3 form-group">
                <label>Amount *</label>
                <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ old('amount', $transaction->amount) }}" required>
            </div>
            <div class="col-md-3 form-group">
                <label>Tax</label>
                <input type="number" step="0.01" min="0" name="tax_amount" class="form-control" value="{{ old('tax_amount', $transaction->tax_amount ?? 0) }}">
            </div>
            <div class="col-md-3 form-group">
                <label>Attachment</label>
                <input type="file" name="attachment" class="form-control">
                @if($isEdit && $transaction->attachment)
                    <small><a href="{{ asset('storage/'.$transaction->attachment) }}" target="_blank">Current file</a></small>
                @endif
            </div>
            <div class="col-md-3 form-group">
                <div class="p-3 bg-light rounded">
                    <small>Total</small>
                    <h4 class="m-0" id="otherTxnTotal">Rs 0.00</h4>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="4">{{ old('description', $transaction->description) }}</textarea>
        </div>
        @include('admin.partials.entry-visibility', ['entry' => $transaction])
    </div>
    <div class="card-footer text-right">
        <a href="{{ route('admin.other-transactions.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary">{{ $isEdit ? 'Update Entry' : 'Submit For Approval' }}</button>
    </div>
</div>
</form>
@push('scripts')
<script>
function recalcOtherTxnTotal(){
    const amount = parseFloat($('[name="amount"]').val()) || 0;
    const tax = parseFloat($('[name="tax_amount"]').val()) || 0;
    $('#otherTxnTotal').text('Rs ' + (amount + tax).toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2}));
}
$('[name="amount"],[name="tax_amount"]').on('input', recalcOtherTxnTotal);
recalcOtherTxnTotal();
</script>
@endpush
