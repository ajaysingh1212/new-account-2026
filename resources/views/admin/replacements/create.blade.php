@extends('layouts.admin')
@section('title','New Replacement')

@push('styles')
<style>
.replacement-hero{background:#111827;color:#fff;border-radius:8px;padding:22px;margin-bottom:16px;display:flex;justify-content:space-between;gap:16px;align-items:center}.replacement-hero h2{margin:0;font-weight:800}.lookup-card,.replacement-form{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:18px;margin-bottom:16px;box-shadow:0 10px 26px rgba(15,23,42,.06)}.result-row{border:1px solid #e5e7eb;border-radius:8px;padding:14px;margin-bottom:10px;cursor:pointer;background:#fff}.result-row:hover{border-color:#0ea5e9;background:#f0f9ff}.result-row.active{border-color:#0ea5e9;box-shadow:0 0 0 3px rgba(14,165,233,.14)}.result-grid{display:grid;grid-template-columns:1.2fr 1fr 1fr;gap:10px;margin-top:10px}.result-metric{background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:10px}.result-metric span{display:block;font-size:11px;color:#64748b;text-transform:uppercase;font-weight:800}.result-metric b{color:#111827}.selected-report{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}.selected-report div{background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:10px}.selected-report span{display:block;font-size:11px;color:#1d4ed8;text-transform:uppercase;font-weight:800}.preview-box{border:1px dashed #cbd5e1;border-radius:8px;height:130px;display:flex;align-items:center;justify-content:center;overflow:hidden;background:#f8fafc;color:#64748b;font-weight:700}.preview-box img{width:100%;height:100%;object-fit:cover}@media(max-width:992px){.selected-report,.result-grid{grid-template-columns:1fr 1fr}}@media(max-width:768px){.selected-report,.result-grid{grid-template-columns:1fr}.replacement-hero{display:block}}
</style>
@endpush

@section('content')
<div class="replacement-hero">
    <div>
        <h2><i class="fas fa-sync-alt mr-2"></i>New Replacement</h2>
        <small>Search sold item, verify production and sale details, then submit four product images.</small>
    </div>
    <a href="{{ route('admin.replacements.index') }}" class="btn btn-outline-light btn-sm"><i class="fas fa-list mr-1"></i>Replacement List</a>
</div>

<div class="lookup-card">
    <h3>Search Original Sale</h3>
    <div class="row align-items-end">
        <div class="col-md-8 form-group"><label>Bill / Serial / SKU / Buyer Code</label><input id="lookupQ" class="form-control" placeholder="Example: 1392414026"></div>
        <div class="col-md-2 form-group"><button type="button" id="lookupBtn" class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i>Search</button></div>
    </div>
    <div id="lookupResults"></div>
</div>

<form method="POST" action="{{ route('admin.replacements.store') }}" enctype="multipart/form-data" class="replacement-form">
    @csrf
    <input type="hidden" name="sales_invoice_item_id" id="salesInvoiceItemId" value="{{ old('sales_invoice_item_id') }}">
    <input type="hidden" name="returned_unit" id="returnedUnit" value="{{ old('returned_unit') }}">
    <h3>Replacement Form <small class="text-muted">{{ $replacementNo }}</small></h3>
    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Please fix:</strong>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div id="selectedSummary" class="alert alert-info">Search karke item select karein.</div>
    <div class="row">
        <div class="col-md-3 form-group"><label>Customer Name</label><input name="customer_name" id="customerName" class="form-control"></div>
        <div class="col-md-3 form-group"><label>Email</label><input name="customer_email" id="customerEmail" type="email" class="form-control"></div>
        <div class="col-md-3 form-group"><label>Phone</label><input name="customer_phone" id="customerPhone" class="form-control"></div>
        <div class="col-md-3 form-group"><label>Address</label><textarea name="customer_address" id="customerAddress" class="form-control" rows="1"></textarea><small class="text-muted">Party detail auto fill hota hai, zarurat par change karein.</small></div>
        <div class="col-md-12 form-group"><label>Replacement Reason</label><textarea name="request_reason" class="form-control" rows="3" required></textarea></div>
    </div>
    <h5>Product Images</h5>
    <div class="row">
        @foreach(['front'=>'Front Side','back'=>'Back Side','angle_one'=>'Angle 1','angle_two'=>'Angle 2'] as $key => $label)
            <div class="col-md-3 form-group"><label>{{ $label }} @if($key === 'front')<span class="text-danger">*</span>@endif</label><input type="file" name="images[{{ $key }}]" class="form-control image-input" data-preview="preview_{{ $key }}" accept="image/*" @required($key === 'front')><div class="preview-box mt-2" id="preview_{{ $key }}">Preview</div></div>
        @endforeach
    </div>
    <button id="submitReplacement" class="btn btn-success" @disabled(!old('sales_invoice_item_id'))><i class="fas fa-paper-plane mr-1"></i>Submit Replacement</button>
    <a href="{{ route('admin.replacements.index') }}" class="btn btn-light">Cancel</a>
</form>
@endsection

@push('scripts')
<script>
function esc(v){ return $('<div>').text(v || '-').html(); }
$('#lookupBtn').on('click', function(){
    const q = $('#lookupQ').val();
    $('#salesInvoiceItemId').val('');
    $('#returnedUnit').val('');
    $('#submitReplacement').prop('disabled', true);
    $('#lookupResults').html('<div class="text-muted">Searching...</div>');
    $.get(@json(route('admin.replacements.lookup')), {q}).done(function(res){
        if(!res.rows.length){ $('#lookupResults').html('<div class="alert alert-warning">No sale found.</div>'); return; }
        $('#lookupResults').html(res.rows.map((row, i) => {
            const unit = row.unit || {};
            const serial = unit.serial_no || unit.vts_sim || unit.buyer_code || unit.key || '-';
            const production = row.production ? `${esc(row.production.batch_no)} | ${esc(row.production.production_date)}` : '-';
            return `<div class="result-row" data-index="${i}">
                <div class="d-flex justify-content-between"><strong>${esc(row.item_name)}</strong><span class="badge badge-info">${esc(serial)}</span></div>
                <small>Invoice: ${esc(row.invoice_no)} | Date: ${esc(row.date)} | Party: ${esc(row.party)}</small>
                <div class="result-grid">
                    <div class="result-metric"><span>Production</span><b>${production}</b></div>
                    <div class="result-metric"><span>Sold Price / Amount</span><b>Rs ${Number(row.unit_price).toFixed(2)} / Rs ${Number(row.line_total).toFixed(2)}</b></div>
                    <div class="result-metric"><span>Current Price</span><b>Rs ${Number(row.current_price).toFixed(2)}</b></div>
                </div>
            </div>`;
        }).join(''));
        window.lookupRows = res.rows;
    });
});
$(document).on('click','.result-row',function(){
    $('.result-row').removeClass('active'); $(this).addClass('active');
    const row = window.lookupRows[$(this).data('index')];
    const unit = row.unit || {};
    $('#salesInvoiceItemId').val(row.invoice_item_id);
    $('#returnedUnit').val(JSON.stringify(unit));
    $('#customerName').val(row.party);
    $('#customerEmail').val(row.party_email || '');
    $('#customerPhone').val(row.party_phone || '');
    $('#customerAddress').val(row.party_address || '');
    $('#selectedSummary').html(`<div class="selected-report">
        <div><span>Item</span><b>${esc(row.item_name)}</b><br><small>${esc(row.sku || row.item_code)}</small></div>
        <div><span>Invoice</span><b>${esc(row.invoice_no)}</b><br><small>${esc(row.date)}</small></div>
        <div><span>Serial / Buyer</span><b>${esc(unit.serial_no || unit.vts_sim || unit.buyer_code || unit.key || '-')}</b></div>
        <div><span>Amount</span><b>Rs ${Number(row.line_total).toFixed(2)}</b><br><small>Current Rs ${Number(row.current_price).toFixed(2)}</small></div>
    </div>`);
    $('#submitReplacement').prop('disabled', false);
});
$('.replacement-form').on('submit', function(e){
    if(!$('#salesInvoiceItemId').val()){
        e.preventDefault();
        $('#selectedSummary').removeClass('alert-info').addClass('alert-danger').html('Please search and select the sold item before submitting replacement.');
        $('html, body').animate({scrollTop: $('#selectedSummary').offset().top - 90}, 250);
    }
});
$('#lookupQ').on('keydown', function(e){ if(e.key === 'Enter'){ e.preventDefault(); $('#lookupBtn').trigger('click'); } });
$('.image-input').on('change', function(){
    const target = $('#' + $(this).data('preview')); target.text('Preview');
    const file = this.files[0]; if(!file) return;
    const reader = new FileReader();
    reader.onload = e => target.html(`<img src="${e.target.result}" alt="preview">`);
    reader.readAsDataURL(file);
});
</script>
@endpush
