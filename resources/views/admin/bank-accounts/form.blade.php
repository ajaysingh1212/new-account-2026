@php $isEdit = $bankAccount->exists; @endphp

<form method="POST" action="{{ $isEdit ? route('admin.bank-accounts.update', $bankAccount) : route('admin.bank-accounts.store') }}">
    @csrf
    @if($isEdit) @method('PUT') @endif
    <div class="card">
        <div class="card-header"><h3 class="card-title m-0"><i class="fas fa-university mr-2 text-purple"></i> Account Details</h3></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-2 form-group"><label>Code *</label><input name="account_code" class="form-control" value="{{ old('account_code', $bankAccount->account_code) }}" required>@error('account_code')<small class="text-danger">{{ $message }}</small>@enderror</div>
                <div class="col-md-3 form-group"><label>Account Type *</label><select name="account_type" id="account_type" class="form-control"><option value="bank" @selected(old('account_type', $bankAccount->account_type)==='bank')>Bank Account</option><option value="cash" @selected(old('account_type', $bankAccount->account_type)==='cash')>Cash Account</option></select></div>
                <div class="col-md-5 form-group"><label>Account Name *</label><input name="account_name" class="form-control" value="{{ old('account_name', $bankAccount->account_name) }}" required></div>
                <div class="col-md-2 form-group"><label>Status *</label><select name="status" class="form-control"><option value="active" @selected(old('status', $bankAccount->status)==='active')>Active</option><option value="inactive" @selected(old('status', $bankAccount->status)==='inactive')>Inactive</option></select></div>
            </div>
            <div class="row bank-fields">
                <div class="col-md-3 form-group"><label>Bank Name</label><input name="bank_name" class="form-control" value="{{ old('bank_name', $bankAccount->bank_name) }}"></div>
                <div class="col-md-3 form-group"><label>Branch</label><input name="branch_name" class="form-control" value="{{ old('branch_name', $bankAccount->branch_name) }}"></div>
                <div class="col-md-3 form-group"><label>Account Holder</label><input name="account_holder_name" class="form-control" value="{{ old('account_holder_name', $bankAccount->account_holder_name) }}"></div>
                <div class="col-md-3 form-group"><label>Account Number</label><input name="account_number" class="form-control" value="{{ old('account_number', $bankAccount->account_number) }}"></div>
            </div>
            <div class="row bank-fields">
                <div class="col-md-3 form-group"><label>IFSC</label><input name="ifsc_code" class="form-control text-uppercase" value="{{ old('ifsc_code', $bankAccount->ifsc_code) }}"></div>
                <div class="col-md-3 form-group"><label>SWIFT</label><input name="swift_code" class="form-control text-uppercase" value="{{ old('swift_code', $bankAccount->swift_code) }}"></div>
                <div class="col-md-3 form-group"><label>UPI ID</label><input name="upi_id" class="form-control" value="{{ old('upi_id', $bankAccount->upi_id) }}"></div>
                <div class="col-md-3 form-group"><label>Phone</label><input name="phone" class="form-control" value="{{ old('phone', $bankAccount->phone) }}"></div>
            </div>
            <div class="row">
                <div class="col-md-3 form-group"><label>Email</label><input type="email" name="email" class="form-control" value="{{ old('email', $bankAccount->email) }}"></div>
                <div class="col-md-3 form-group"><label>Opening Balance</label><input type="number" step="0.01" min="0" name="opening_balance" class="form-control" value="{{ old('opening_balance', $bankAccount->opening_balance ?? 0) }}"></div>
                <div class="col-md-3 form-group"><label>Opening Date</label><input type="date" name="opening_balance_date" class="form-control" value="{{ old('opening_balance_date', optional($bankAccount->opening_balance_date)->format('Y-m-d') ?? now()->toDateString()) }}"></div>
                <div class="col-md-3 form-group d-flex align-items-end">
                    <div>
                        <div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="is_primary" name="is_primary" value="1" @checked(old('is_primary', $bankAccount->is_primary))><label class="custom-control-label" for="is_primary">Main Account</label></div>
                        <div class="custom-control custom-switch mt-2"><input type="checkbox" class="custom-control-input" id="print_on_invoice" name="print_on_invoice" value="1" @checked(old('print_on_invoice', $bankAccount->print_on_invoice))><label class="custom-control-label" for="print_on_invoice">Print on Bill</label></div>
                    </div>
                </div>
            </div>
            <div class="form-group"><label>Address</label><textarea name="address" class="form-control" rows="3">{{ old('address', $bankAccount->address) }}</textarea></div>
            <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="3">{{ old('notes', $bankAccount->notes) }}</textarea></div>
            @include('admin.partials.entry-visibility', ['entry' => $bankAccount])
        </div>
        <div class="card-footer text-right">
            <a href="{{ route('admin.bank-accounts.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Account</button>
        </div>
    </div>
</form>

@push('scripts')
<script>
function toggleBankFields() { $('.bank-fields').toggle($('#account_type').val() === 'bank'); }
$('#account_type').on('change', toggleBankFields);
toggleBankFields();
</script>
@endpush
