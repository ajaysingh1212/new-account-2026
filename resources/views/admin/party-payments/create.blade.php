@extends('layouts.admin')
@section('title', $type === 'payment_out' ? 'Payment Out' : 'Payment In')

@push('styles')
<style>
.payment-hero{background:linear-gradient(135deg,#101827,#24154d);border-radius:16px;padding:22px;color:#fff;box-shadow:0 12px 30px rgba(20,16,50,.18)}
.payment-hero h3{font-weight:800;margin:0}.payment-panel{background:#fff;border-radius:16px;box-shadow:0 4px 20px rgba(124,58,237,.08);overflow:hidden}.payment-panel .panel-head{padding:18px 22px;border-bottom:1px solid #f0eaf8;font-weight:800;color:#1A0A3D}
.live-total{background:#f8f6ff;border-radius:14px;padding:18px}.live-total .amount{font-size:30px;font-weight:800;color:#1A0A3D}
.bill-row{border:1px solid #e8e2f4;border-radius:12px;padding:14px;margin-bottom:10px;background:#fff}.bill-row.active{border-color:#7C3AED;background:#fbf8ff}.bill-history{display:none;background:#f8fafc;border-radius:10px;padding:10px;margin-top:10px;font-size:12px}.bill-row.active .bill-history{display:block}.allocation-input{max-width:170px}
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
                <div class="col-md-4 form-group"><label>Select Party *</label><select name="party_id" id="partySelect" class="form-control select2" required><option value="">Select party</option>@foreach($parties as $party)<option value="{{ $party->id }}">{{ $party->display_name }} | Balance Rs {{ number_format(abs((float)$party->current_balance),2) }} {{ $party->balance_label }}</option>@endforeach</select></div>
                <div class="col-md-4 form-group"><label>Select Bank/Cash *</label><select name="bank_account_id" class="form-control select2" required><option value="">Select account</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->account_name }} | ₹ {{ number_format((float)$account->current_balance,2) }}</option>@endforeach</select></div>
                <div class="col-md-2 form-group"><label>Date *</label><input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
                <div class="col-md-2 form-group"><label>Reference No.</label><input name="reference_no" class="form-control"></div>
            </div>
            <div class="row">
                <div class="col-md-3 form-group"><label>Amount *</label><input type="number" step="0.01" min="0.01" name="amount" id="payAmount" class="form-control" required></div>
                <div class="col-md-3 form-group"><label>Discount</label><input type="number" step="0.01" min="0" name="discount_amount" id="payDiscount" class="form-control" value="0"></div>
                <div class="col-md-3 form-group"><label>Payment Mode</label><select name="payment_mode" class="form-control"><option>UPI</option><option>NEFT</option><option>RTGS</option><option>IMPS</option><option>Cash</option><option>Cheque</option><option>Card</option><option>Other</option></select></div>
                <div class="col-md-3"><div class="live-total"><div style="font-size:12px;color:#7a7194;">Total settlement</div><div class="amount" id="payTotal">Rs 0.00</div></div></div>
            </div>
            <div class="form-group">
                <label>{{ $type === 'payment_out' ? 'Pending Purchase Bills' : 'Pending Sales Invoices' }}</label>
                <div id="billList" class="text-muted p-3" style="border:1px dashed #ddd;border-radius:12px;">Select party to fetch open bills.</div>
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
let openBills = [];
function fmt(n){return 'Rs '+(Number(n)||0).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2})}
function renderTotal(){
    let total = 0;
    $('.bill-check:checked').each(function(){ total += parseFloat($(this).closest('.bill-row').find('.allocation-input').val()||0); });
    $('#payAmount').val(total ? total.toFixed(2) : '');
    const d=parseFloat($('#payDiscount').val()||0);
    $('#payTotal').text(fmt(Math.max(0,total-d)));
}
function renderBills(){
    if(!openBills.length){ $('#billList').html('<div class="text-muted p-3">No pending bills found for this party.</div>'); return; }
    $('#billList').html(openBills.map((b,i)=>`
        <div class="bill-row" data-due="${b.due}">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <label class="m-0"><input type="checkbox" class="bill-check mr-2"> <b>${b.invoice_no}</b> <span class="text-muted">${b.billing_date||''}</span></label>
                <div class="text-right">
                    <div><b>Due ${fmt(b.due)}</b></div>
                    <small class="text-muted">Bill ${fmt(b.grand_total)} | Paid ${fmt(b.paid)}</small>
                </div>
            </div>
            <div class="mt-2 pay-box" style="display:none">
                <input type="hidden" name="allocations[${i}][bill_id]" value="${b.id}" disabled>
                <input type="number" step="0.01" min="0.01" max="${b.due}" name="allocations[${i}][amount]" class="form-control allocation-input" placeholder="Amount" disabled>
            </div>
            <div class="bill-history">
                <b>Payment History</b>
                ${b.history.length ? b.history.map(h=>`<div>${h.date} | ${fmt(h.amount)} | ${h.mode} | ${h.reference_no}</div>`).join('') : '<div class="text-muted">No previous payment.</div>'}
            </div>
        </div>`).join(''));
}
async function fetchBills(){
    const partyId = $('#partySelect').val();
    if(!partyId){ openBills=[]; renderBills(); return; }
    $('#billList').html('<div class="text-muted p-3">Loading bills...</div>');
    const url = `{{ route('admin.party-payments.open-bills') }}?party_id=${partyId}&payment_type={{ $type }}`;
    const res = await fetch(url, {headers:{'Accept':'application/json'}});
    openBills = res.ok ? (await res.json()).bills : [];
    renderBills();
}
$('#partySelect').on('change', fetchBills);
$(document).on('change','.bill-check',function(){
    const row=$(this).closest('.bill-row'), due=parseFloat(row.data('due')||0);
    row.toggleClass('active', this.checked);
    row.find('.pay-box').toggle(this.checked);
    row.find('input[name]').prop('disabled', !this.checked);
    if(this.checked && !row.find('.allocation-input').val()) row.find('.allocation-input').val(due.toFixed(2));
    if(!this.checked) row.find('.allocation-input').val('');
    renderTotal();
});
$(document).on('input','.allocation-input,#payDiscount',function(){
    const row=$(this).closest('.bill-row');
    if(row.length && parseFloat(this.value||0) > parseFloat(row.data('due')||0)){
        alert('Aap bill due amount se jyada payment nahi le sakte.');
        this.value = parseFloat(row.data('due')||0).toFixed(2);
    }
    renderTotal();
});
renderTotal();
</script>
@endpush
