@extends('layouts.admin')
@section('title', 'New Bank Transaction')
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('admin.bank-transactions.index') }}">Bank Transactions</a></li><li class="breadcrumb-item active">Create</li>@endsection

@section('content')
<form method="POST" action="{{ route('admin.bank-transactions.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="card">
        <div class="card-header"><h3 class="card-title m-0"><i class="fas fa-exchange-alt mr-2 text-purple"></i> Transfer / Adjustment</h3></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 form-group">
                    <label>Transaction Type *</label>
                    <select name="transaction_type" id="transaction_type" class="form-control" required>
                        <option value="bank_to_bank" @selected($type==='bank_to_bank')>Bank To Bank</option>
                        <option value="bank_to_cash" @selected($type==='bank_to_cash')>Bank To Cash</option>
                        <option value="cash_to_bank" @selected($type==='cash_to_bank')>Cash To Bank</option>
                        <option value="manual_adjustment" @selected($type==='manual_adjustment')>Manual Adjustment</option>
                    </select>
                </div>
                <div class="col-md-3 form-group"><label>Date *</label><input type="date" name="transaction_date" class="form-control" value="{{ old('transaction_date', now()->toDateString()) }}" required></div>
                <div class="col-md-3 form-group"><label>Amount *</label><input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required></div>
                <div class="col-md-3 form-group"><label>Reference No.</label><input name="reference_no" class="form-control" value="{{ old('reference_no') }}"></div>
            </div>
            <div class="row transfer-fields">
                <div class="col-md-6 form-group"><label>From Account *</label><select name="from_account_id" class="form-control select2"><option value="">Select source</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->account_name }} - ₹ {{ number_format((float) $account->current_balance, 2) }}</option>@endforeach</select></div>
                <div class="col-md-6 form-group"><label>To Account *</label><select name="to_account_id" class="form-control select2"><option value="">Select destination</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->account_name }} - ₹ {{ number_format((float) $account->current_balance, 2) }}</option>@endforeach</select></div>
            </div>
            <div class="row adjustment-fields">
                <div class="col-md-6 form-group"><label>Account *</label><select name="bank_account_id" class="form-control select2"><option value="">Select account</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->account_name }} - ₹ {{ number_format((float) $account->current_balance, 2) }}</option>@endforeach</select></div>
                <div class="col-md-6 form-group"><label>Adjustment Type *</label><select name="adjustment_type" class="form-control"><option value="increase">Increase Balance</option><option value="decrease">Decrease Balance</option></select></div>
            </div>
            <div class="row">
                <div class="col-md-4 form-group"><label>Party Optional</label><select name="party_id" class="form-control select2"><option value="">No party</option>@foreach($parties as $party)<option value="{{ $party->id }}">{{ $party->display_name }}</option>@endforeach</select></div>
                <div class="col-md-4 form-group"><label>Payment Mode</label><select name="payment_mode" class="form-control"><option value="">Select mode</option><option>NEFT</option><option>RTGS</option><option>IMPS</option><option>UPI</option><option>Cheque</option><option>Cash</option><option>Card</option><option>Other</option></select></div>
                <div class="col-md-4 form-group"><label>Attachment</label><input type="file" name="attachment" class="form-control"></div>
            </div>
            <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="4">{{ old('description') }}</textarea></div>
        </div>
        <div class="card-footer text-right">
            <a href="{{ route('admin.bank-transactions.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Transaction</button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
function toggleType() {
    const isAdjustment = $('#transaction_type').val() === 'manual_adjustment';
    $('.transfer-fields').toggle(!isAdjustment);
    $('.adjustment-fields').toggle(isAdjustment);
}
$('#transaction_type').on('change', toggleType);
toggleType();
</script>
@endpush
