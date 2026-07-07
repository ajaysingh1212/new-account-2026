@extends('layouts.admin')
@section('title','Edit Replacement')

@push('styles')
<style>
.lookup-card,.replacement-form{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;margin-bottom:16px}.result-row{border:1px solid #e5e7eb;border-radius:10px;padding:12px;margin-bottom:10px;cursor:pointer}.result-row.active{border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.12)}.preview-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}.preview-box{border:1px dashed #cbd5e1;border-radius:8px;height:120px;display:flex;align-items:center;justify-content:center;overflow:hidden;background:#f8fafc}.preview-box img{width:100%;height:100%;object-fit:cover}@media(max-width:768px){.preview-grid{grid-template-columns:repeat(2,1fr)}}</style>
@endpush

@section('content')
<div class="lookup-card">
    <h3>Update Replacement Request</h3>
    <p class="text-muted mb-0">Search the original sale again to refresh the customer and sold item details.</p>
</div>

<form method="POST" action="{{ route('admin.replacements.update', $replacement) }}" enctype="multipart/form-data" class="replacement-form">
    @csrf
    @method('PUT')
    <input type="hidden" name="sales_invoice_item_id" id="salesInvoiceItemId" value="{{ $replacement->sales_invoice_item_id }}">
    <input type="hidden" name="returned_unit" id="returnedUnit" value='@json($replacement->returned_unit)'>
    <div class="row">
        <div class="col-md-8 form-group"><label>Bill / Serial / SKU / Buyer Code</label><input id="lookupQ" class="form-control" placeholder="Example: 1392414026"></div>
        <div class="col-md-2 form-group"><button type="button" id="lookupBtn" class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i>Search</button></div>
    </div>
    <div id="lookupResults"></div>
    <div id="selectedSummary" class="alert alert-info">Current selection will be updated here.</div>
    <div class="row">
        <div class="col-md-3 form-group"><label>Customer Name</label><input name="customer_name" id="customerName" class="form-control" value="{{ old('customer_name', $replacement->customer_name) }}"></div>
        <div class="col-md-3 form-group"><label>Email</label><input name="customer_email" id="customerEmail" type="email" class="form-control" value="{{ old('customer_email', $replacement->customer_email) }}"></div>
        <div class="col-md-3 form-group"><label>Phone</label><input name="customer_phone" id="customerPhone" class="form-control" value="{{ old('customer_phone', $replacement->customer_phone) }}"></div>
        <div class="col-md-3 form-group"><label>Address</label><textarea name="customer_address" id="customerAddress" class="form-control" rows="1">{{ old('customer_address', $replacement->customer_address) }}</textarea></div>
        <div class="col-md-12 form-group"><label>Replacement Reason</label><textarea name="request_reason" class="form-control" rows="3" required>{{ old('request_reason', $replacement->request_reason) }}</textarea></div>
    </div>
    <h5>Update Images</h5>
    <div class="row">
        @foreach(['front'=>'Front Side','back'=>'Back Side','angle_one'=>'Angle 1','angle_two'=>'Angle 2'] as $key => $label)
            <div class="col-md-3 form-group"><label>{{ $label }}</label><input type="file" name="images[{{ $key }}]" class="form-control image-input" data-preview="preview_{{ $key }}" accept="image/*"><div class="preview-box mt-2" id="preview_{{ $key }}">@if(!empty($replacement->product_images[$key]))<img src="{{ asset('storage/'.$replacement->product_images[$key]) }}" alt="{{ $label }}">@else Preview @endif</div></div>
        @endforeach
    </div>
    <button class="btn btn-success"><i class="fas fa-save mr-1"></i>Update Replacement</button>
    <a href="{{ route('admin.replacements.show', $replacement) }}" class="btn btn-light">Cancel</a>
</form>
@endsection

@push('scripts')
<script>
function esc(v){ return $('<div>').text(v || '-').html(); }
$('#lookupBtn').on('click', function(){
    const q = $('#lookupQ').val();
    $('#lookupResults').html('<div class="text-muted">Searching...</div>');
    $.get(@json(route('admin.replacements.lookup')), {q}).done(function(res){
        if(!res.rows.length){ $('#lookupResults').html('<div class="alert alert-warning">No sale found.</div>'); return; }
        $('#lookupResults').html(res.rows.map((row, i) => {
            const serial = row.unit.serial_no || row.unit.vts_sim || row.unit.buyer_code || row.unit.key || '-';
            return `<div class="result-row" data-index="${i}"><strong>${esc(row.item_name)}</strong> <span class="badge badge-info">${esc(serial)}</span><br><small>Invoice: ${esc(row.invoice_no)} | Date: ${esc(row.date)} | Party: ${esc(row.party)} | Sale price: Rs ${Number(row.unit_price).toFixed(2)} | Current price: Rs ${Number(row.current_price).toFixed(2)}</small></div>`;
        }).join(''));
        window.lookupRows = res.rows;
    });
});
$(document).on('click','.result-row',function(){
    $('.result-row').removeClass('active'); $(this).addClass('active');
    const row = window.lookupRows[$(this).data('index')];
    $('#salesInvoiceItemId').val(row.invoice_item_id);
    $('#returnedUnit').val(JSON.stringify(row.unit || {}));
    $('#customerName').val(row.party);
    $('#customerEmail').val(row.party_email || '');
    $('#customerPhone').val(row.party_phone || '');
    $('#customerAddress').val(row.party_address || '');
    $('#selectedSummary').html(`<b>Selected:</b> ${esc(row.item_name)} | Invoice ${esc(row.invoice_no)} | Serial ${esc(row.unit.serial_no || row.unit.vts_sim || row.unit.buyer_code || '-')}`);
});
$('.image-input').on('change', function(){
    const target = $('#' + $(this).data('preview')); target.text('Preview');
    const file = this.files[0]; if(!file) return;
    const reader = new FileReader();
    reader.onload = e => target.html(`<img src="${e.target.result}" alt="preview">`);
    reader.readAsDataURL(file);
});
</script>
@endpush
