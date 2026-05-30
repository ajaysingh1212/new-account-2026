@php $isEdit = $expense->exists; @endphp
<form method="POST" action="{{ $isEdit ? route('admin.expenses.update',$expense) : route('admin.expenses.store') }}" enctype="multipart/form-data">
@csrf @if($isEdit) @method('PUT') @endif
<div class="card">
    <div class="card-header"><h3 class="card-title m-0">Expense Entry</h3></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 form-group"><label>Expense No</label><input name="expense_no" class="form-control" value="{{ old('expense_no',$expense->expense_no) }}"></div>
            <div class="col-md-3 form-group"><label>Date *</label><input type="date" name="expense_date" class="form-control" value="{{ old('expense_date',$expense->expense_date?->format('Y-m-d') ?? now()->toDateString()) }}" required></div>
            <div class="col-md-3 form-group"><label>Ledger *</label><select name="expense_ledger_id" class="form-control select2" required><option value="">Select ledger</option>@foreach($ledgers as $ledger)<option value="{{ $ledger->id }}" @selected(old('expense_ledger_id',$expense->expense_ledger_id)==$ledger->id)>{{ $ledger->name }} | Rs {{ number_format((float)$ledger->current_balance,2) }}</option>@endforeach</select></div>
            <div class="col-md-3 form-group"><label>Bank/Cash *</label><select name="bank_account_id" class="form-control select2" required><option value="">Select account</option>@foreach($accounts as $account)<option value="{{ $account->id }}" @selected(old('bank_account_id',$expense->bank_account_id)==$account->id)>{{ $account->account_name }} | Rs {{ number_format((float)$account->current_balance,2) }}</option>@endforeach</select></div>
        </div>
        <div class="row">
            <div class="col-md-3 form-group"><label>Vendor / Paid To</label><input name="vendor_name" class="form-control" value="{{ old('vendor_name',$expense->vendor_name) }}"></div>
            <div class="col-md-3 form-group"><label>Reference No</label><input name="reference_no" class="form-control" value="{{ old('reference_no',$expense->reference_no) }}"></div>
            <div class="col-md-2 form-group"><label>Amount *</label><input type="number" step="0.01" min="0.01" name="amount" id="expAmount" class="form-control" value="{{ old('amount',$expense->amount) }}" required></div>
            <div class="col-md-2 form-group"><label>Tax</label><input type="number" step="0.01" min="0" name="tax_amount" id="expTax" class="form-control" value="{{ old('tax_amount',$expense->tax_amount ?? 0) }}"></div>
            <div class="col-md-2 form-group"><label>Mode</label><select name="payment_mode" class="form-control"><option>UPI</option><option>NEFT</option><option>Cash</option><option>Cheque</option><option>Card</option><option>Other</option></select></div>
        </div>
        <div class="row">
            <div class="col-md-8 form-group"><label>Description</label><textarea name="description" class="form-control" rows="4">{{ old('description',$expense->description) }}</textarea></div>
            <div class="col-md-4 form-group"><label>Attachment / Bill Proof</label><input type="file" name="attachment" class="form-control">@if($isEdit && $expense->attachment)<small><a target="_blank" href="{{ asset('storage/'.$expense->attachment) }}">Current file</a></small>@endif<div class="mt-3 p-3 bg-light rounded"><small>Total Expense</small><h4 id="expTotal">Rs 0.00</h4></div></div>
        </div>
        @include('admin.partials.entry-visibility', ['entry' => $expense])
    </div>
    <div class="card-footer text-right"><a href="{{ route('admin.expenses.index') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-primary">Submit For Approval</button></div>
</div>
</form>
@push('scripts')<script>function expTotal(){let a=+$('#expAmount').val()||0,t=+$('#expTax').val()||0;$('#expTotal').text('Rs '+(a+t).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2}))}$('#expAmount,#expTax').on('input',expTotal);expTotal();</script>@endpush
