@extends('layouts.admin')
@section('title', $type === 'payment_out' ? 'Payment Out' : 'Payment In')

@push('styles')
<style>
.payment-hero{background:linear-gradient(135deg,#101827,#24154d);border-radius:16px;padding:22px;color:#fff;box-shadow:0 12px 30px rgba(20,16,50,.18)}
.payment-hero h3{font-weight:800;margin:0}.payment-panel{background:#fff;border-radius:16px;box-shadow:0 4px 20px rgba(124,58,237,.08);overflow:hidden}.payment-panel .panel-head{padding:18px 22px;border-bottom:1px solid #f0eaf8;font-weight:800;color:#1A0A3D}
.live-total{background:#f8f6ff;border-radius:14px;padding:18px}.live-total .amount{font-size:30px;font-weight:800;color:#1A0A3D}
</style>
@endpush

@section('content')
<div class="payment-hero mb-4">
    <h3><i class="fas {{ $type === 'payment_out' ? 'fa-arrow-up' : 'fa-arrow-down' }} mr-2"></i>{{ $type === 'payment_out' ? 'Payment Out' : 'Payment In' }}</h3>
    <div style="opacity:.75;font-size:13px;">Party ledger and bank ledger will be posted together.</div>
</div>
<form method="POST" action="{{ route('admin.party-payments.store') }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="payment_type" value="{{ $type }}">
    <div class="payment-panel">
        <div class="panel-head">Payment Details</div>
        <div class="p-4">
            <div class="row">
                <div class="col-md-4 form-group"><label>Select Party *</label><select name="party_id" class="form-control select2" required><option value="">Select party</option>@foreach($parties as $party)<option value="{{ $party->id }}">{{ $party->display_name }} | Balance ₹ {{ number_format(abs((float)$party->current_balance),2) }} {{ $party->balance_label }}</option>@endforeach</select></div>
                <div class="col-md-4 form-group"><label>Select Bank/Cash *</label><select name="bank_account_id" class="form-control select2" required><option value="">Select account</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->account_name }} | ₹ {{ number_format((float)$account->current_balance,2) }}</option>@endforeach</select></div>
                <div class="col-md-2 form-group"><label>Date *</label><input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
                <div class="col-md-2 form-group"><label>Reference No.</label><input name="reference_no" class="form-control"></div>
            </div>
            <div class="row">
                <div class="col-md-3 form-group"><label>Amount *</label><input type="number" step="0.01" min="0.01" name="amount" id="payAmount" class="form-control" required></div>
                <div class="col-md-3 form-group"><label>Discount</label><input type="number" step="0.01" min="0" name="discount_amount" id="payDiscount" class="form-control" value="0"></div>
                <div class="col-md-3 form-group"><label>Payment Mode</label><select name="payment_mode" class="form-control"><option>UPI</option><option>NEFT</option><option>RTGS</option><option>IMPS</option><option>Cash</option><option>Cheque</option><option>Card</option><option>Other</option></select></div>
                <div class="col-md-3"><div class="live-total"><div style="font-size:12px;color:#7a7194;">Total settlement</div><div class="amount" id="payTotal">₹ 0.00</div></div></div>
            </div>
            <div class="row">
                <div class="col-md-6 form-group"><label>Description</label><textarea name="description" class="form-control" rows="4"></textarea></div>
                <div class="col-md-6 form-group"><label>Attachment</label><input type="file" name="attachment" class="form-control"><small class="text-muted">Receipt, cheque copy, UTR proof, etc.</small></div>
            </div>
        </div>
        <div class="p-4 text-right" style="border-top:1px solid #f0eaf8;">
            <a href="{{ route('admin.party-payments.index', ['type' => $type]) }}" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-primary"><i class="fas fa-save mr-1"></i> Post Payment</button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
function renderTotal(){const a=parseFloat($('#payAmount').val()||0),d=parseFloat($('#payDiscount').val()||0);$('#payTotal').text('₹ '+Math.max(0,a-d).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2}))}
$('#payAmount,#payDiscount').on('input',renderTotal);renderTotal();
</script>
@endpush
