@php
    $isEdit = $party->exists;
@endphp

@push('styles')
<style>
    .wizard-shell { background:#fff; border-radius:16px; box-shadow:0 4px 20px rgba(124,58,237,.08); overflow:hidden; }
    .wizard-steps { display:grid; grid-template-columns:repeat(4,1fr); border-bottom:1px solid #F0EAF8; }
    .wizard-step { padding:16px; font-weight:700; font-size:13px; color:#7A7194; cursor:pointer; border-right:1px solid #F0EAF8; }
    .wizard-step:last-child { border-right:0; }
    .wizard-step.active { color:#fff; background:linear-gradient(135deg,#7C3AED,#5B21B6); }
    .wizard-step span { display:inline-flex; width:26px; height:26px; align-items:center; justify-content:center; border-radius:50%; margin-right:8px; background:rgba(124,58,237,.12); color:#7C3AED; }
    .wizard-step.active span { background:#fff; color:#7C3AED; }
    .wizard-pane { display:none; padding:24px; }
    .wizard-pane.active { display:block; }
    .form-section-title { font-size:12px; font-weight:800; color:#7C3AED; text-transform:uppercase; letter-spacing:.8px; margin:0 0 14px; }
    .balance-preview { background:linear-gradient(135deg,#101827,#251A4E); color:#fff; border-radius:14px; padding:18px; min-height:132px; }
    .balance-preview .amount { font-size:28px; font-weight:800; }
    .balance-preview .label { color:rgba(255,255,255,.72); font-size:12px; }
    @media (max-width: 767px) {
        .wizard-steps { grid-template-columns:1fr; }
        .wizard-step { border-right:0; border-bottom:1px solid #F0EAF8; }
    }
</style>
@endpush

<div class="wizard-shell">
    <div class="wizard-steps">
        <div class="wizard-step active" data-step="1"><span>1</span> Identity</div>
        <div class="wizard-step" data-step="2"><span>2</span> Tax & Address</div>
        <div class="wizard-step" data-step="3"><span>3</span> Balance & Credit</div>
        <div class="wizard-step" data-step="4"><span>4</span> Bank & Notes</div>
    </div>

    <form method="POST" action="{{ $isEdit ? route('admin.parties.update', $party) : route('admin.parties.store') }}" id="partyForm">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="wizard-pane active" data-pane="1">
            <p class="form-section-title">Party identity</p>
            <div class="row">
                <div class="col-md-3 form-group">
                    <label>Party Code *</label>
                    <input name="party_code" class="form-control" value="{{ old('party_code', $party->party_code) }}" required>
                    @error('party_code') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="col-md-3 form-group">
                    <label>Party Type *</label>
                    <select name="party_type" class="form-control" required>
                        @foreach(['both'=>'Customer + Supplier','customer'=>'Customer','supplier'=>'Supplier'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('party_type', $party->party_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label>Status *</label>
                    <select name="status" class="form-control" required>
                        @foreach(['active'=>'Active','inactive'=>'Inactive','blocked'=>'Blocked'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $party->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label>Contact Person</label>
                    <input name="contact_person" class="form-control" value="{{ old('contact_person', $party->contact_person) }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Display Name *</label>
                    <input name="display_name" class="form-control" value="{{ old('display_name', $party->display_name) }}" required>
                    @error('display_name') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="col-md-6 form-group">
                    <label>Legal / Trade Name</label>
                    <input name="legal_name" class="form-control" value="{{ old('legal_name', $party->legal_name) }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 form-group"><label>Phone</label><input name="phone" class="form-control" value="{{ old('phone', $party->phone) }}"></div>
                <div class="col-md-3 form-group"><label>Alternate Phone</label><input name="alternate_phone" class="form-control" value="{{ old('alternate_phone', $party->alternate_phone) }}"></div>
                <div class="col-md-3 form-group"><label>WhatsApp</label><input name="whatsapp_number" class="form-control" value="{{ old('whatsapp_number', $party->whatsapp_number) }}"></div>
                <div class="col-md-3 form-group"><label>Email</label><input type="email" name="email" class="form-control" value="{{ old('email', $party->email) }}"></div>
            </div>
        </div>

        <div class="wizard-pane" data-pane="2">
            <p class="form-section-title">GST, legal and address details</p>
            <div class="row">
                <div class="col-md-3 form-group">
                    <label>Tax Type *</label>
                    <select name="tax_type" class="form-control" required>
                        @foreach(['registered'=>'Registered','composition'=>'Composition','unregistered'=>'Unregistered','consumer'=>'Consumer','overseas'=>'Overseas'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('tax_type', $party->tax_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-group"><label>GSTIN</label><input name="gstin" class="form-control text-uppercase" value="{{ old('gstin', $party->gstin) }}"></div>
                <div class="col-md-2 form-group"><label>PAN</label><input name="pan_number" class="form-control text-uppercase" value="{{ old('pan_number', $party->pan_number) }}"></div>
                <div class="col-md-2 form-group"><label>TAN</label><input name="tan_number" class="form-control text-uppercase" value="{{ old('tan_number', $party->tan_number) }}"></div>
                <div class="col-md-2 form-group"><label>CIN</label><input name="cin_number" class="form-control text-uppercase" value="{{ old('cin_number', $party->cin_number) }}"></div>
            </div>
            <div class="row">
                <div class="col-md-4 form-group"><label>Place of Supply</label><input name="place_of_supply" class="form-control" value="{{ old('place_of_supply', $party->place_of_supply) }}"></div>
                <div class="col-md-2 form-group"><label>City</label><input name="city" class="form-control" value="{{ old('city', $party->city) }}"></div>
                <div class="col-md-2 form-group"><label>State</label><input name="state" class="form-control" value="{{ old('state', $party->state) }}"></div>
                <div class="col-md-2 form-group"><label>Pincode</label><input name="pincode" class="form-control" value="{{ old('pincode', $party->pincode) }}"></div>
                <div class="col-md-2 form-group"><label>Country</label><input name="country" class="form-control" value="{{ old('country', $party->country ?? 'India') }}"></div>
            </div>
            <div class="row">
                <div class="col-md-6 form-group"><label>Billing Address</label><textarea name="billing_address" class="form-control" rows="4">{{ old('billing_address', $party->billing_address) }}</textarea></div>
                <div class="col-md-6 form-group"><label>Shipping Address</label><textarea name="shipping_address" class="form-control" rows="4">{{ old('shipping_address', $party->shipping_address) }}</textarea></div>
            </div>
        </div>

        <div class="wizard-pane" data-pane="3">
            <p class="form-section-title">Opening balance and credit control</p>
            <div class="row">
                <div class="col-lg-8">
                    <div class="row">
                        <div class="col-md-4 form-group"><label>Opening Balance</label><input type="number" step="0.01" min="0" name="opening_balance" id="opening_balance" class="form-control" value="{{ old('opening_balance', $party->opening_balance ?? 0) }}"></div>
                        <div class="col-md-4 form-group">
                            <label>Balance Nature *</label>
                            <select name="opening_balance_type" id="opening_balance_type" class="form-control" required>
                                <option value="payable" @selected(old('opening_balance_type', $party->opening_balance_type) === 'payable')>We Pay Party</option>
                                <option value="receivable" @selected(old('opening_balance_type', $party->opening_balance_type) === 'receivable')>Party Pays Us</option>
                            </select>
                        </div>
                        <div class="col-md-4 form-group"><label>Opening Balance Date</label><input type="date" name="opening_balance_date" class="form-control" value="{{ old('opening_balance_date', optional($party->opening_balance_date)->format('Y-m-d') ?? now()->toDateString()) }}"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group"><label>Credit Limit</label><input type="number" step="0.01" min="0" name="credit_limit" class="form-control" value="{{ old('credit_limit', $party->credit_limit) }}"></div>
                        <div class="col-md-4 form-group"><label>Credit Days</label><input type="number" min="0" name="credit_days" class="form-control" value="{{ old('credit_days', $party->credit_days) }}"></div>
                        <div class="col-md-4 form-group"><label>Payment Terms</label><input name="payment_terms" class="form-control" value="{{ old('payment_terms', $party->payment_terms) }}" placeholder="Net 30, Advance, COD"></div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="balance-preview">
                        <div class="label">Current balance will start as</div>
                        <div class="amount" id="balancePreview">₹ 0.00</div>
                        <div class="label mt-2" id="balanceMeaning">No opening dues</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="wizard-pane" data-pane="4">
            <p class="form-section-title">Banking and internal notes</p>
            <div class="row">
                <div class="col-md-4 form-group"><label>Bank Name</label><input name="bank_name" class="form-control" value="{{ old('bank_name', $party->bank_name) }}"></div>
                <div class="col-md-4 form-group"><label>Account Holder</label><input name="account_holder_name" class="form-control" value="{{ old('account_holder_name', $party->account_holder_name) }}"></div>
                <div class="col-md-4 form-group"><label>Account Number</label><input name="account_number" class="form-control" value="{{ old('account_number', $party->account_number) }}"></div>
            </div>
            <div class="row">
                <div class="col-md-4 form-group"><label>IFSC Code</label><input name="ifsc_code" class="form-control text-uppercase" value="{{ old('ifsc_code', $party->ifsc_code) }}"></div>
                <div class="col-md-4 form-group"><label>Branch</label><input name="branch_name" class="form-control" value="{{ old('branch_name', $party->branch_name) }}"></div>
                <div class="col-md-4 form-group"><label>UPI ID</label><input name="upi_id" class="form-control" value="{{ old('upi_id', $party->upi_id) }}"></div>
            </div>
            <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="4">{{ old('notes', $party->notes) }}</textarea></div>
            @include('admin.partials.entry-visibility', ['entry' => $party])
        </div>

        <div class="d-flex justify-content-between align-items-center p-4" style="border-top:1px solid #F0EAF8;">
            <button type="button" class="btn btn-light" id="prevStep"><i class="fas fa-arrow-left mr-1"></i> Back</button>
            <div>
                <a href="{{ route('admin.parties.index') }}" class="btn btn-outline-secondary mr-2">Cancel</a>
                <button type="button" class="btn btn-primary" id="nextStep">Next <i class="fas fa-arrow-right ml-1"></i></button>
                <button type="submit" class="btn btn-primary d-none" id="saveParty"><i class="fas fa-save mr-1"></i> Save Party</button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
let step = 1;
const maxStep = 4;

function renderStep() {
    $('.wizard-step, .wizard-pane').removeClass('active');
    $(`.wizard-step[data-step="${step}"], .wizard-pane[data-pane="${step}"]`).addClass('active');
    $('#prevStep').prop('disabled', step === 1);
    $('#nextStep').toggleClass('d-none', step === maxStep);
    $('#saveParty').toggleClass('d-none', step !== maxStep);
}

function renderBalance() {
    const amount = parseFloat($('#opening_balance').val() || '0');
    const type = $('#opening_balance_type').val();
    $('#balancePreview').text('₹ ' + amount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#balanceMeaning').text(amount <= 0 ? 'No opening dues' : (type === 'payable' ? 'Payable: company has to pay this party' : 'Receivable: party has to pay company'));
}

$('#nextStep').on('click', function () { if (step < maxStep) { step++; renderStep(); } });
$('#prevStep').on('click', function () { if (step > 1) { step--; renderStep(); } });
$('.wizard-step').on('click', function () { step = parseInt($(this).data('step')); renderStep(); });
$('#opening_balance, #opening_balance_type').on('input change', renderBalance);
renderStep();
renderBalance();
</script>
@endpush
