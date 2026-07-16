@extends('layouts.admin')
@section('title', $type === 'payment_out' ? 'Payment Out' : 'Payment In')

@push('styles')
<style>
.payment-hero{background:linear-gradient(135deg,#101827,#24154d);border-radius:18px;padding:24px;color:#fff;box-shadow:0 16px 38px rgba(20,16,50,.2)}
.payment-hero h3{font-weight:850;margin:0}.payment-panel{background:#fff;border-radius:18px;box-shadow:0 8px 28px rgba(76,29,149,.08);overflow:hidden}.payment-panel .panel-head{padding:19px 22px;border-bottom:1px solid #f0eaf8;font-weight:850;color:#1a0a3d}
.live-total{background:#f8f6ff;border:1px solid #eee8ff;border-radius:14px;padding:17px}.live-total .amount{font-size:29px;font-weight:850;color:#1a0a3d}
.bill-row{border:1px solid #e8e2f4;border-radius:13px;padding:14px;margin-bottom:10px;background:#fff;transition:.2s}.bill-row.active{border-color:#7c3aed;background:#fbf8ff;box-shadow:0 7px 18px rgba(124,58,237,.08)}.bill-history{display:none;background:#f8fafc;border-radius:10px;padding:10px;margin-top:10px;font-size:12px}.bill-row.active .bill-history{display:block}.allocation-input{max-width:170px}
.opening-action{display:none;border:1px solid #ddd6fe;background:linear-gradient(135deg,#faf5ff,#eef2ff);border-radius:15px;padding:16px;margin-bottom:16px}.opening-icon{width:46px;height:46px;border-radius:13px;background:#6d28d9;color:#fff;display:flex;align-items:center;justify-content:center;font-size:19px;box-shadow:0 8px 18px rgba(109,40,217,.22)}
.opening-chip{display:inline-flex;align-items:center;border-radius:999px;padding:5px 10px;background:#ede9fe;color:#5b21b6;font-size:11px;font-weight:850;text-transform:uppercase;letter-spacing:.35px}.opening-selected{display:none;border:1px solid #86efac;background:#f0fdf4;color:#166534;border-radius:13px;padding:12px 14px;margin-bottom:13px;font-weight:750}
.opening-modal .modal-content{border:0;border-radius:20px;overflow:hidden;box-shadow:0 24px 70px rgba(15,23,42,.24)}.opening-modal .modal-header{border:0;background:linear-gradient(135deg,#111827,#312e81);color:#fff;padding:22px 24px}.opening-modal .modal-body{padding:24px}
.opening-metric{border:1px solid #e5e7eb;border-radius:14px;padding:14px;background:#fff;height:100%}.opening-metric span{display:block;color:#64748b;font-size:11px;font-weight:850;text-transform:uppercase;letter-spacing:.4px}.opening-metric strong{display:block;color:#111827;font-size:21px;margin-top:4px}
.opening-amount-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:15px;padding:16px}.opening-history-list{max-height:220px;overflow:auto}.opening-history-row{display:flex;justify-content:space-between;gap:12px;padding:11px 0;border-bottom:1px solid #eef2f7}.opening-history-row:last-child{border-bottom:0}
</style>
@endpush

@section('content')
<div class="payment-hero mb-4">
    <h3><i class="fas {{ $type === 'payment_out' ? 'fa-arrow-up' : 'fa-arrow-down' }} mr-2"></i>{{ $type === 'payment_out' ? 'Payment Out' : 'Payment In' }}</h3>
    <div style="opacity:.75;font-size:13px;">Party statement and bank/cash balance will be updated together.</div>
</div>
<form method="POST" action="{{ route('admin.party-payments.store') }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="payment_type" value="{{ $type }}">
    <input type="hidden" name="settlement_source" id="settlementSource" value="bills">
    <input type="hidden" name="opening_balance_amount" id="openingBalanceAmount">
    <input type="hidden" name="advance_amount" id="advanceAmount">
    <div class="payment-panel">
        <div class="panel-head">Payment Details</div>
        <div class="p-4">
            @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
            <div class="row">
                <div class="col-md-4 form-group"><label>Select Party *</label><select name="party_id" id="partySelect" class="form-control select2" required><option value="">Select party</option>@foreach($parties as $party)<option value="{{ $party->id }}" @selected((string) old('party_id', request('party_id')) === (string) $party->id)>{{ $party->display_name }} | Balance Rs {{ number_format(abs((float)$party->current_balance),2) }} {{ $party->balance_label }}</option>@endforeach</select></div>
                <div class="col-md-4 form-group"><label>Select Bank/Cash *</label><select name="bank_account_id" class="form-control select2" required><option value="">Select account</option>@foreach($accounts as $account)<option value="{{ $account->id }}" @selected((string) old('bank_account_id') === (string) $account->id)>{{ $account->account_name }} | Rs {{ number_format((float)$account->current_balance,2) }}</option>@endforeach</select></div>
                <div class="col-md-2 form-group"><label>Date *</label><input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', now()->toDateString()) }}" required></div>
                <div class="col-md-2 form-group"><label>Reference No.</label><input name="reference_no" class="form-control" value="{{ old('reference_no') }}"></div>
            </div>
            <div class="row">
                <div class="col-md-3 form-group"><label>Amount *</label><input type="number" step="0.01" min="0.01" name="amount" id="payAmount" class="form-control" readonly required></div>
                <div class="col-md-3 form-group"><label>Discount</label><input type="number" step="0.01" min="0" name="discount_amount" id="payDiscount" class="form-control" value="{{ old('discount_amount', 0) }}"></div>
                <div class="col-md-3 form-group"><label>Payment Mode</label><select name="payment_mode" class="form-control">@foreach(['UPI','NEFT','RTGS','IMPS','Cash','Cheque','Card','Other'] as $mode)<option @selected(old('payment_mode') === $mode)>{{ $mode }}</option>@endforeach</select></div>
                <div class="col-md-3"><div class="live-total"><div style="font-size:12px;color:#7a7194;">Net bank settlement</div><div class="amount" id="payTotal">Rs 0.00</div></div></div>
            </div>

            <div id="openingAction" class="opening-action">
                <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap:14px">
                    <div class="d-flex align-items-center" style="gap:12px"><div class="opening-icon"><i class="fas fa-balance-scale"></i></div><div><span class="opening-chip">Opening balance available</span><div class="mt-1"><b id="openingActionAmount">Rs 0.00</b> remaining</div></div></div>
                    <button type="button" id="openOpeningModal" class="btn btn-primary"><i class="fas fa-wallet mr-1"></i> {{ $type === 'payment_out' ? 'Pay Opening Balance' : 'Receive Opening Balance' }}</button>
                </div>
            </div>
            <div id="openingSelected" class="opening-selected"><div class="d-flex justify-content-between align-items-center"><span><i class="fas fa-check-circle mr-1"></i> <span id="openingSelectedText"></span></span><button type="button" id="clearOpeningSettlement" class="btn btn-sm btn-outline-success">Change to bills</button></div></div>

            <div id="advanceAction" class="opening-action">
                <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap:14px">
                    <div class="d-flex align-items-center" style="gap:12px"><div class="opening-icon" style="background:#0ea5e9"><i class="fas fa-hand-holding-usd"></i></div><div><span class="opening-chip" style="background:#e0f2fe;color:#0369a1">Advance payment</span><div class="mt-1"><b id="advanceActionAmount">Rs 0.00</b> recorded</div><div class="text-muted small" id="advanceActionNote">Select a party to see its current advance balance.</div></div></div>
                    <button type="button" id="openAdvanceModal" class="btn btn-info"><i class="fas fa-plus mr-1"></i> Create advance payment</button>
                </div>
            </div>

            <div class="form-group">
                <label>{{ $type === 'payment_out' ? 'Pending Purchase Bills' : 'Pending Sales Invoices' }}</label>
                <div id="billList" class="text-muted p-3" style="border:1px dashed #ddd;border-radius:12px;">Select party to fetch open bills.</div>
            </div>
            <div class="row">
                <div class="col-md-6 form-group"><label>Description</label><textarea name="description" class="form-control" rows="4">{{ old('description') }}</textarea></div>
                <div class="col-md-6 form-group"><label>Attachment</label><input type="file" name="attachment" class="form-control"><small class="text-muted">Receipt, cheque copy, UTR proof, etc.</small></div>
            </div>
        </div>
        <div class="p-4 text-right" style="border-top:1px solid #f0eaf8;">
            <a href="{{ route('admin.party-payments.index', ['type' => $type]) }}" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-primary"><i class="fas fa-save mr-1"></i> Post Payment</button>
        </div>
    </div>
</form>

<div class="modal fade opening-modal" id="openingBalanceModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document"><div class="modal-content">
        <div class="modal-header"><div><div style="opacity:.7;font-size:12px;text-transform:uppercase;font-weight:800;letter-spacing:.5px">Opening Balance Settlement</div><h4 class="modal-title mt-1">{{ $type === 'payment_out' ? 'Pay party opening balance' : 'Receive party opening balance' }}</h4></div><button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button></div>
        <div class="modal-body">
            <div class="row mb-4">
                <div class="col-md-4 mb-2"><div class="opening-metric"><span>Original balance</span><strong id="openingOriginal">Rs 0.00</strong></div></div>
                <div class="col-md-4 mb-2"><div class="opening-metric"><span>Already settled</span><strong id="openingPaid">Rs 0.00</strong></div></div>
                <div class="col-md-4 mb-2"><div class="opening-metric" style="border-color:#c4b5fd;background:#faf5ff"><span>Remaining</span><strong id="openingRemaining" style="color:#6d28d9">Rs 0.00</strong></div></div>
            </div>
            <div class="opening-amount-box mb-4">
                <label class="font-weight-bold">Amount to {{ $type === 'payment_out' ? 'pay' : 'receive' }}</label>
                <div class="input-group input-group-lg"><div class="input-group-prepend"><span class="input-group-text">Rs</span></div><input type="number" id="openingModalAmount" min="0.01" step="0.01" class="form-control" placeholder="0.00"><div class="input-group-append"><button type="button" id="useFullOpeningBalance" class="btn btn-outline-primary">Use full balance</button></div></div>
                <small class="text-muted">Opening balance date: <span id="openingBalanceDate">-</span>. Amount cannot exceed the remaining balance.</small>
            </div>
            <h6 class="font-weight-bold mb-2"><i class="fas fa-history mr-1 text-primary"></i> Previous settlement history</h6><div id="openingHistory" class="opening-history-list"></div>
        </div>
        <div class="modal-footer border-0 px-4 pb-4"><button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button><button type="button" id="confirmOpeningSettlement" class="btn btn-primary px-4"><i class="fas fa-check mr-1"></i> Use this amount</button></div>
    </div></div>
</div>

<div class="modal fade opening-modal" id="advanceModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document"><div class="modal-content">
        <div class="modal-header"><div><div style="opacity:.7;font-size:12px;text-transform:uppercase;font-weight:800;letter-spacing:.5px">Payment Advance</div><h4 class="modal-title mt-1">{{ $type === 'payment_out' ? 'Pay advance to party' : 'Receive advance from party' }}</h4></div><button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button></div>
        <div class="modal-body">
            <div class="row mb-4">
                <div class="col-md-4 mb-2"><div class="opening-metric"><span>Current advance balance</span><strong id="advanceAvailable">Rs 0.00</strong></div></div>
                <div class="col-md-4 mb-2"><div class="opening-metric"><span>Party</span><strong id="advanceParty">-</strong></div></div>
                <div class="col-md-4 mb-2"><div class="opening-metric" style="border-color:#bae6fd;background:#f0f9ff"><span>Type</span><strong style="color:#0369a1">{{ $type === 'payment_out' ? 'Supplier Advance' : 'Customer Advance' }}</strong></div></div>
            </div>
            <div class="opening-amount-box mb-4">
                <label class="font-weight-bold">Advance amount</label>
                <div class="input-group input-group-lg"><div class="input-group-prepend"><span class="input-group-text">Rs</span></div><input type="number" id="advanceModalAmount" min="0.01" step="0.01" class="form-control" placeholder="0.00"><div class="input-group-append"><button type="button" id="useFullAdvanceAmount" class="btn btn-outline-info">Fill current balance</button></div></div>
                <small class="text-muted">This records a new advance payment for the selected party. It is not limited by the current balance shown above.</small>
            </div>
            <div class="form-group"><label>Advance note</label><textarea id="advanceModalNote" class="form-control" rows="3" placeholder="Optional note about the advance"></textarea></div>
        </div>
        <div class="modal-footer border-0 px-4 pb-4"><button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button><button type="button" id="confirmAdvanceSettlement" class="btn btn-info px-4"><i class="fas fa-check mr-1"></i> Save advance</button></div>
    </div></div>
</div>
@endsection

@push('scripts')
<script>
let openBills = [], openingBalance = null, availableAdvances = [], selectedPartyText = '';
function fmt(n){return 'Rs '+(Number(n)||0).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2})}
function renderTotal(){
    let total=0;
    if($('#settlementSource').val()==='opening_balance') total=parseFloat($('#openingBalanceAmount').val()||0);
    else $('.bill-check:checked').each(function(){total+=parseFloat($(this).closest('.bill-row').find('.allocation-input').val()||0)});
    $('#payAmount').val(total ? total.toFixed(2) : '');
    $('#payTotal').text(fmt(Math.max(0,total-parseFloat($('#payDiscount').val()||0))));
}
function renderOpeningAction(){const usable=openingBalance&&openingBalance.available&&Number(openingBalance.remaining)>0;$('#openingAction').toggle(!!usable);if(usable)$('#openingActionAmount').text(fmt(openingBalance.remaining))}
function renderAdvanceAction(){
    const partyId = $('#partySelect').val();
    if(!partyId){
        $('#advanceAction').hide();
        return;
    }
    const total = availableAdvances.reduce((sum,row)=>sum+(Number(row.remaining_amount)||0),0);
    $('#advanceAction').show();
    $('#advanceActionAmount').text(fmt(total));
    $('#advanceActionNote').text(total > 0
        ? `${availableAdvances.length} advance payment(s) already recorded for this party.`
        : 'No advance payment recorded yet. You can still create one now.');
}
function clearOpeningSettlement(){
    $('#settlementSource').val('bills');$('#openingBalanceAmount').val('');$('#openingSelected').hide();$('#billList').show();renderOpeningAction();renderTotal();
}
function renderBills(){
    if(!openBills.length){$('#billList').html('<div class="text-muted p-3">No pending bills found for this party.</div>');return}
    $('#billList').html(openBills.map((b,i)=>`
        <div class="bill-row" data-due="${b.due}">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <label class="m-0"><input type="checkbox" class="bill-check mr-2"> <b>${b.invoice_no}</b> <span class="text-muted">${b.billing_date||''}</span></label>
                <div class="text-right"><div><b>Due ${fmt(b.due)}</b></div><small class="text-muted">Bill ${fmt(b.grand_total)} | Paid ${fmt(b.paid)}</small></div>
            </div>
            <div class="mt-2 pay-box" style="display:none"><input type="hidden" name="allocations[${i}][bill_id]" value="${b.id}" disabled><input type="number" step="0.01" min="0.01" max="${b.due}" name="allocations[${i}][amount]" class="form-control allocation-input" placeholder="Amount" disabled></div>
            <div class="bill-history"><b>Payment History</b>${b.history.length?b.history.map(h=>`<div>${h.date} | ${fmt(h.amount)} | ${h.mode} | ${h.reference_no}</div>`).join(''):'<div class="text-muted">No previous payment.</div>'}</div>
        </div>`).join(''));
}
async function fetchBills(){
    const partyId=$('#partySelect').val();clearOpeningSettlement();
    if(!partyId){openBills=[];openingBalance=null;availableAdvances=[];selectedPartyText='';renderBills();renderOpeningAction();renderAdvanceAction();return}
    $('#billList').html('<div class="text-muted p-3">Loading bills...</div>');
    const res=await fetch(`{{ route('admin.party-payments.open-bills') }}?party_id=${partyId}&payment_type={{ $type }}`,{headers:{Accept:'application/json'}});
    const payload=res.ok?await res.json():{bills:[],opening_balance:null,available_advances:[]};openBills=payload.bills||[];openingBalance=payload.opening_balance||null;availableAdvances=payload.available_advances||[];selectedPartyText=$('#partySelect option:selected').text().trim();renderBills();renderOpeningAction();renderAdvanceAction();
}
$('#partySelect').on('change',fetchBills);
$('#openOpeningModal').on('click',function(){
    if(!openingBalance||!openingBalance.available)return;
    $('#openingOriginal').text(fmt(openingBalance.total));$('#openingPaid').text(fmt(openingBalance.paid));$('#openingRemaining').text(fmt(openingBalance.remaining));$('#openingBalanceDate').text(openingBalance.date||'-');$('#openingModalAmount').val(Number(openingBalance.remaining).toFixed(2)).attr('max',openingBalance.remaining);
    $('#openingHistory').html(openingBalance.history.length?openingBalance.history.map(h=>`<div class="opening-history-row"><div><b>${h.date||'-'}</b><div class="text-muted">${h.mode} via ${h.account} | Ref: ${h.reference_no}</div></div><b>${fmt(h.amount)}</b></div>`).join(''):'<div class="text-muted py-3">No previous opening balance payment.</div>');
    $('#openingBalanceModal').modal('show');
});
$('#useFullOpeningBalance').on('click',function(){if(openingBalance)$('#openingModalAmount').val(Number(openingBalance.remaining).toFixed(2))});
$('#openingModalAmount').on('input',function(){const remaining=parseFloat(openingBalance?.remaining||0);if(parseFloat(this.value||0)>remaining){alert('Aap opening balance se jyada payment nahi kar sakte.');this.value=remaining.toFixed(2)}});
$('#confirmOpeningSettlement').on('click',function(){
    const amount=parseFloat($('#openingModalAmount').val()||0),remaining=parseFloat(openingBalance?.remaining||0);
    if(amount<=0){alert('Opening balance payment amount enter karein.');return}
    if(amount>remaining){alert('Aap opening balance se jyada payment nahi kar sakte.');$('#openingModalAmount').val(remaining.toFixed(2));return}
    $('.bill-check:checked').prop('checked',false).trigger('change');$('#settlementSource').val('opening_balance');$('#openingBalanceAmount').val(amount.toFixed(2));$('#openingSelectedText').text(`${fmt(amount)} {{ $type === 'payment_out' ? 'payable' : 'receivable' }} against opening balance`);$('#openingSelected').show();$('#openingAction').hide();$('#billList').hide();$('#openingBalanceModal').modal('hide');renderTotal();
});
$('#openAdvanceModal').on('click',function(){
    const partyId = $('#partySelect').val();
    if(!partyId){alert('Advance payment ke liye pehle party select karein.');return;}
    const total=availableAdvances.reduce((sum,row)=>sum+(Number(row.remaining_amount)||0),0);
    $('#advanceAvailable').text(fmt(total));
    $('#advanceParty').text(selectedPartyText || ($('#partySelect option:selected').text().trim() || '-'));
    $('#advanceModalAmount').val(total > 0 ? total.toFixed(2) : '');
    $('#advanceModalNote').val('');
    $('#advanceModal').modal('show');
});
$('#useFullAdvanceAmount').on('click',function(){
    const total=availableAdvances.reduce((sum,row)=>sum+(Number(row.remaining_amount)||0),0);
    if(total>0){ $('#advanceModalAmount').val(total.toFixed(2)); }
});
$('#advanceModalAmount').on('input',function(){
    if(parseFloat(this.value||0)<=0){return}
});
$('#confirmAdvanceSettlement').on('click',function(){
    const amount=parseFloat($('#advanceModalAmount').val()||0);
    if(amount<=0){alert('Advance amount enter karein.');return}
    $('.bill-check:checked').prop('checked',false).trigger('change');
    $('#settlementSource').val('advance');
    $('#advanceAmount').val(amount.toFixed(2));
    $('#payAmount').val(amount.toFixed(2));
    $('#openingBalanceAmount').val('');
    $('#openingSelected').hide();
    $('#openingAction').hide();
    $('#billList').show();
    $('#advanceModal').modal('hide');
    $('#payTotal').text(fmt(Math.max(0, amount-parseFloat($('#payDiscount').val()||0))));
});
$('#clearOpeningSettlement').on('click',clearOpeningSettlement);
$(document).on('change','.bill-check',function(){
    if(this.checked&&$('#settlementSource').val()==='opening_balance')clearOpeningSettlement();
    const row=$(this).closest('.bill-row'),due=parseFloat(row.data('due')||0);row.toggleClass('active',this.checked);row.find('.pay-box').toggle(this.checked);row.find('input[name]').prop('disabled',!this.checked);if(this.checked&&!row.find('.allocation-input').val())row.find('.allocation-input').val(due.toFixed(2));if(!this.checked)row.find('.allocation-input').val('');renderTotal();
});
$(document).on('input','.allocation-input,#payDiscount',function(){const row=$(this).closest('.bill-row');if(row.length&&parseFloat(this.value||0)>parseFloat(row.data('due')||0)){alert('Aap bill due amount se jyada payment nahi kar sakte.');this.value=parseFloat(row.data('due')||0).toFixed(2)}renderTotal()});
renderTotal();if($('#partySelect').val())fetchBills();
</script>
@endpush
