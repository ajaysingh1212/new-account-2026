@extends('layouts.admin')
@section('title','New Replacement')

@push('styles')
<style>
:root{
    --rp-primary:#0f766e;
    --rp-primary-dark:#0c5c56;
    --rp-accent:#d97706;
    --rp-danger:#dc2626;
    --rp-success:#16a34a;
    --rp-ink:#0f172a;
    --rp-muted:#64748b;
    --rp-border:#e2e8f0;
    --rp-bg-soft:#f8fafc;
}

.replacement-hero{background:linear-gradient(135deg,#0f172a,#1e293b);color:#fff;border-radius:12px;padding:24px 26px;margin-bottom:18px;display:flex;justify-content:space-between;gap:16px;align-items:center}
.replacement-hero h2{margin:0;font-weight:800;letter-spacing:-.01em}
.replacement-hero small{color:#cbd5e1}
.replacement-hero .rp-hero-no{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.18);border-radius:6px;padding:2px 8px;font-size:12px;margin-left:8px}

/* Stepper */
.rp-stepper{display:flex;align-items:flex-start;margin-bottom:22px;background:#fff;border:1px solid var(--rp-border);border-radius:12px;padding:18px 20px}
.rp-step{display:flex;flex-direction:column;align-items:center;text-align:center;flex:0 0 auto;width:150px}
.rp-step-circle{width:34px;height:34px;border-radius:50%;background:var(--rp-bg-soft);border:2px solid var(--rp-border);color:var(--rp-muted);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:14px;transition:.2s}
.rp-step-label{margin-top:8px;font-size:12.5px;font-weight:700;color:var(--rp-muted);text-transform:uppercase;letter-spacing:.03em}
.rp-step-sub{font-size:11.5px;color:#94a3b8;margin-top:2px}
.rp-step.is-active .rp-step-circle{background:var(--rp-primary);border-color:var(--rp-primary);color:#fff;box-shadow:0 0 0 4px rgba(15,118,110,.15)}
.rp-step.is-active .rp-step-label{color:var(--rp-ink)}
.rp-step.is-done .rp-step-circle{background:var(--rp-success);border-color:var(--rp-success);color:#fff}
.rp-step.is-done .rp-step-circle::before{content:"\2713"}
.rp-step.is-done .rp-step-circle span{display:none}
.rp-step-line{flex:1 1 auto;height:2px;background:var(--rp-border);margin-top:17px;transition:.2s}
.rp-step-line.is-done{background:var(--rp-success)}
@media(max-width:768px){.rp-stepper{overflow-x:auto}.rp-step{width:110px}.rp-step-label{font-size:11px}}

.lookup-card,.replacement-form,.wizard-panel-inner{background:#fff;border:1px solid var(--rp-border);border-radius:12px;padding:20px;margin-bottom:18px;box-shadow:0 10px 26px rgba(15,23,42,.05)}
.replacement-form{padding:0;box-shadow:none;border:none;background:transparent}
.rp-section-title{font-weight:800;color:var(--rp-ink);margin-bottom:2px}
.rp-section-hint{color:var(--rp-muted);font-size:13px;margin-bottom:14px}

/* Sold item results styled like receipt stubs */
.result-row{position:relative;border:1px solid var(--rp-border);border-left:4px solid var(--rp-primary);border-radius:10px;padding:14px 16px 12px;margin-bottom:12px;cursor:pointer;background:#fff;transition:.15s}
.result-row:hover{box-shadow:0 8px 20px rgba(15,23,42,.08);transform:translateY(-1px)}
.result-row.active{border-left-color:var(--rp-accent);background:#fffbeb;box-shadow:0 0 0 3px rgba(217,119,6,.15)}
.rp-serial{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:700;letter-spacing:.02em}
.result-grid{display:grid;grid-template-columns:1.2fr 1fr 1fr;gap:10px;margin-top:10px}
.result-metric{background:var(--rp-bg-soft);border:1px solid var(--rp-border);border-radius:8px;padding:9px 10px}
.result-metric span{display:block;font-size:10.5px;color:var(--rp-muted);text-transform:uppercase;font-weight:800;letter-spacing:.03em}
.result-metric b{color:var(--rp-ink);font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}

.selected-summary-card{border:1px solid #99e0d8;background:#ecfdf9;border-radius:10px;padding:14px 16px}
.selected-summary-card.is-error{border-color:#fecaca;background:#fef2f2}
.selected-report{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-top:8px}
.selected-report div{background:#fff;border:1px solid #99e0d8;border-radius:8px;padding:10px}
.selected-report span{display:block;font-size:10.5px;color:var(--rp-primary-dark);text-transform:uppercase;font-weight:800;letter-spacing:.03em}
.selected-report b{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
@media(max-width:992px){.selected-report,.result-grid{grid-template-columns:1fr 1fr}}
@media(max-width:768px){.selected-report,.result-grid{grid-template-columns:1fr}.replacement-hero{display:block}.replacement-hero .btn{margin-top:12px}}

.rp-photo-tile{border:1px dashed #cbd5e1;border-radius:10px;padding:10px;background:var(--rp-bg-soft)}
.rp-photo-tile.has-image{border-style:solid;border-color:var(--rp-primary)}
.preview-box{border-radius:8px;height:130px;display:flex;align-items:center;justify-content:center;overflow:hidden;background:#fff;border:1px solid var(--rp-border);color:#94a3b8;font-weight:700;font-size:13px;flex-direction:column;gap:6px}
.preview-box img{width:100%;height:100%;object-fit:cover;border-radius:6px}
.rp-photo-tile label{font-weight:700;color:var(--rp-ink)}

.rp-nav-btns{display:flex;gap:10px;margin-top:18px}
.rp-required-dot{color:var(--rp-danger)}

.wizard-panel{display:none}
.wizard-panel.active{display:block}
</style>
@endpush

@section('content')
<div class="replacement-hero">
    <div>
        <h2><i class="fas fa-sync-alt mr-2"></i>New Replacement <span class="rp-hero-no">{{ $replacementNo }}</span></h2>
        <small>Sold item search karein, production aur sale details verify karein, phir 3 asaan steps me replacement submit karein.</small>
    </div>
    <a href="{{ route('admin.replacements.index') }}" class="btn btn-outline-light btn-sm"><i class="fas fa-list mr-1"></i>Replacement List</a>
</div>

<div class="rp-stepper" id="rpStepper">
    <div class="rp-step is-active" data-step="1">
        <div class="rp-step-circle"><span>1</span></div>
        <div class="rp-step-label">Find Sold Item</div>
        <div class="rp-step-sub">Search &amp; select</div>
    </div>
    <div class="rp-step-line" data-line="1"></div>
    <div class="rp-step" data-step="2">
        <div class="rp-step-circle"><span>2</span></div>
        <div class="rp-step-label">Replacement Details</div>
        <div class="rp-step-sub">Item, party, photos</div>
    </div>
    <div class="rp-step-line" data-line="2"></div>
    <div class="rp-step" data-step="3">
        <div class="rp-step-circle"><span>3</span></div>
        <div class="rp-step-label">Review &amp; Submit</div>
        <div class="rp-step-sub">Final check</div>
    </div>
</div>

<form method="POST" action="{{ route('admin.replacements.store') }}" enctype="multipart/form-data" class="replacement-form">
    @csrf
    <input type="hidden" name="sales_invoice_item_id" id="salesInvoiceItemId" value="{{ old('sales_invoice_item_id') }}">
    <input type="hidden" name="returned_unit" id="returnedUnit" value="{{ old('returned_unit') }}">

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

    {{-- STEP 1: Find sold item --}}
    <div id="wizardStep1" class="wizard-panel active">
        <div class="wizard-panel-inner">
            <div class="rp-section-title">Search original sale</div>
            <div class="rp-section-hint">Bill number, serial number, SKU ya buyer code se search karein.</div>
            <div class="row align-items-end">
                <div class="col-md-9 form-group mb-2"><input id="lookupQ" class="form-control" placeholder="Example: 1392414026"></div>
                <div class="col-md-3 form-group mb-2"><button type="button" id="lookupBtn" class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i>Search</button></div>
            </div>
            <select id="soldUnitSelect" class="form-control mb-3" disabled><option value="">Search first, then select sold serial / item</option></select>
            <div id="lookupResults"></div>
            <div id="selectedSummary" class="selected-summary-card mt-2">Search karke item select karein.</div>
        </div>
        <div class="rp-nav-btns">
            <button type="button" id="goStep2" class="btn btn-primary" disabled>Next: Replacement Details <i class="fas fa-arrow-right ml-1"></i></button>
        </div>
    </div>

    {{-- STEP 2: Replacement details --}}
    <div id="wizardStep2" class="wizard-panel">
        <div class="wizard-panel-inner">
            <div class="rp-section-title">Replacement item &amp; party</div>
            <div class="rp-section-hint">Kaunsa item issue hoga, aur ledger kis party me update hoga.</div>
            <div class="row">
                <div class="col-md-6 form-group"><label>Which stock item will be issued? <span class="rp-required-dot">*</span></label><select name="issued_item_id" id="issuedItemId" class="form-control select2" required><option value="">Select item</option>@foreach($replacementItems as $item)<option value="{{ $item->id }}">{{ $item->name }} | {{ $item->sku ?: $item->item_code }} | Stock {{ number_format((float)$item->current_stock, 0) }}</option>@endforeach</select></div>
                <div class="col-md-6 form-group"><label>Replacement party</label><select name="target_party_id" id="targetPartyId" class="form-control select2"><option value="">Same party as original sale</option>@foreach($parties as $party)<option value="{{ $party->id }}">{{ $party->display_name }}</option>@endforeach</select></div>
            </div>
            <div class="row align-items-center">
                <div class="col-md-6"><label class="mb-0"><input type="checkbox" name="ledger_enabled" id="ledgerEnabled" value="1" checked> Add replacement amount to party ledger</label></div>
                <div class="col-md-6 form-group mb-0"><label>Ledger amount</label><input type="number" step="0.01" min="0" name="ledger_amount" id="ledgerAmount" class="form-control" placeholder="Original bill amount"></div>
            </div>
            <small class="text-muted">Same party select karne par original party rahegi. Different party choose karne par selected party ke ledger me amount add hoga.</small>
        </div>

        <div class="wizard-panel-inner">
            <div class="rp-section-title">Customer details</div>
            <div class="rp-section-hint">Party details auto-fill ho jaate hain, zarurat par edit karein.</div>
            <div class="row">
                <div class="col-md-3 form-group"><label>Customer Name</label><input name="customer_name" id="customerName" class="form-control"></div>
                <div class="col-md-3 form-group"><label>Email</label><input name="customer_email" id="customerEmail" type="email" class="form-control"></div>
                <div class="col-md-3 form-group"><label>Phone</label><input name="customer_phone" id="customerPhone" class="form-control"></div>
                <div class="col-md-3 form-group"><label>Address</label><textarea name="customer_address" id="customerAddress" class="form-control" rows="1"></textarea></div>
                <div class="col-md-12 form-group"><label>Replacement Reason <span class="rp-required-dot">*</span></label><textarea name="request_reason" class="form-control" rows="3" required placeholder="Kya kharabi thi, customer ne kya bataya..."></textarea></div>
            </div>
        </div>

        <div class="wizard-panel-inner">
            <div class="rp-section-title">Product images</div>
            <div class="rp-section-hint">Front photo zaroori hai, baaki teen optional lekin recommended hain.</div>
            <div class="row">
                @foreach(['front'=>'Front Side','back'=>'Back Side','angle_one'=>'Angle 1','angle_two'=>'Angle 2'] as $key => $label)
                    <div class="col-md-3 form-group">
                        <div class="rp-photo-tile">
                            <label>{{ $label }} @if($key === 'front')<span class="rp-required-dot">*</span>@endif</label>
                            <input type="file" name="images[{{ $key }}]" class="form-control image-input" data-preview="preview_{{ $key }}" accept="image/*" @required($key === 'front')>
                            <div class="preview-box mt-2" id="preview_{{ $key }}"><i class="fas fa-camera"></i><span>Preview</span></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rp-nav-btns">
            <button type="button" id="backStep1" class="btn btn-light"><i class="fas fa-arrow-left mr-1"></i>Back</button>
            <button type="button" id="goStep3" class="btn btn-primary">Next: Review <i class="fas fa-arrow-right ml-1"></i></button>
        </div>
    </div>

    {{-- STEP 3: Review & submit --}}
    <div id="wizardStep3" class="wizard-panel">
        <div class="wizard-panel-inner">
            <div class="rp-section-title"><i class="fas fa-check-circle mr-1 text-success"></i>Replacement request ready</div>
            <div id="reviewText" class="mt-2">Details review karke submit karein.</div>
        </div>
        <div class="rp-nav-btns">
            <button type="button" id="backStep2" class="btn btn-light"><i class="fas fa-arrow-left mr-1"></i>Back</button>
            <button id="submitReplacement" class="btn btn-success" @disabled(!old('sales_invoice_item_id'))><i class="fas fa-paper-plane mr-1"></i>Submit Replacement</button>
        </div>
    </div>

    <a href="{{ route('admin.replacements.index') }}" class="btn btn-light mt-2">Cancel</a>
</form>
@endsection

@push('scripts')
<script>
function esc(v){ return $('<div>').text(v || '-').html(); }

function showStep(step){
    $('.rp-step').removeClass('is-active');
    $('.rp-step').each(function(){
        const s = Number($(this).data('step'));
        if(s < step) $(this).addClass('is-done'); else $(this).removeClass('is-done');
    });
    $('.rp-step[data-step="'+step+'"]').addClass('is-active').removeClass('is-done');
    $('.rp-step-line').each(function(){
        const l = Number($(this).data('line'));
        $(this).toggleClass('is-done', l < step);
    });
    $('.wizard-panel').removeClass('active');
    $('#wizardStep'+step).addClass('active');
    $('html,body').animate({scrollTop: $('#rpStepper').offset().top - 20}, 180);
}

$('#goStep2').on('click', function(){ if($('#salesInvoiceItemId').val()) showStep(2); });
$('#backStep1').on('click', function(){ showStep(1); });
$('#backStep2').on('click', function(){ showStep(2); });

$('#goStep3').on('click', function(){
    if(!$('#issuedItemId').val() || !$('textarea[name=request_reason]').val().trim() || !$('input[name="images[front]"]')[0].files.length){
        alert('Replacement item, reason aur front photo required hai.'); return;
    }
    $('#reviewText').html('Sold item: <b>'+esc($('#selectedSummary').text().trim())+'</b><br>Ledger update: <b>'+($('#ledgerEnabled').is(':checked') ? 'Yes' : 'No')+'</b>');
    showStep(3);
});

$('#lookupBtn').on('click', function(){
    const q = $('#lookupQ').val();
    $('#salesInvoiceItemId').val('');
    $('#returnedUnit').val('');
    $('#submitReplacement').prop('disabled', true);
    $('#goStep2').prop('disabled', true);
    $('#lookupResults').html('<div class="text-muted">Searching...</div>');
    $.get(@json(route('admin.replacements.lookup')), {q}).done(function(res){
        if(!res.rows.length){ $('#lookupResults').html('<div class="alert alert-warning">No sale found.</div>'); return; }
        $('#soldUnitSelect').html('<option value="">Select sold serial / item</option>' + res.rows.map((row, i) => {
            const unit = row.unit || {};
            const serial = unit.serial_no || unit.vts_sim || unit.buyer_code || unit.key || 'Non-serial';
            return `<option value="${i}">${esc(serial)} | ${esc(row.item_name)} | Bill ${esc(row.invoice_no)} | ${esc(row.party)}</option>`;
        }).join(''));
        $('#soldUnitSelect').prop('disabled', false).val('').trigger('change');
        $('#lookupResults').html(res.rows.map((row, i) => {
            const unit = row.unit || {};
            const serial = unit.serial_no || unit.vts_sim || unit.buyer_code || unit.key || '-';
            const production = row.production ? `${esc(row.production.batch_no)} | ${esc(row.production.production_date)}` : '-';
            const rawMaterials = row.production?.raw_materials?.length
                ? row.production.raw_materials.map(m => `${esc(m.name)} × ${Number(m.quantity).toFixed(3)} ${esc(m.unit)}`).join('<br>')
                : '-';
            return `<div class="result-row" data-index="${i}">
                <div class="d-flex justify-content-between align-items-start"><strong>${esc(row.item_name)}</strong><span class="badge badge-info rp-serial">${esc(serial)}</span></div>
                <small>Invoice: <b class="rp-serial">${esc(row.invoice_no)}</b> | Date: ${esc(row.date)} | Party: ${esc(row.party)}</small>
                <div class="result-grid">
                    <div class="result-metric"><span>Production</span><b>${production}</b></div>
                    <div class="result-metric"><span>Sold Price / Amount</span><b>Rs ${Number(row.unit_price).toFixed(2)} / Rs ${Number(row.line_total).toFixed(2)}<br><small>Bill Rs ${Number(row.invoice_total).toFixed(2)}</small></b></div>
                    <div class="result-metric"><span>Current Price</span><b>Rs ${Number(row.current_price).toFixed(2)}</b></div>
                </div>
                <small class="d-block mt-2"><b>Tax:</b> ${Number(row.tax_percent).toFixed(2)}% — Rs ${Number(row.tax_amount).toFixed(2)} | Qty: ${Number(row.quantity).toFixed(3)}</small>
                <small class="d-block mt-2"><b>Raw materials:</b> ${rawMaterials}</small>
            </div>`;
        }).join(''));
        window.lookupRows = res.rows;
    }).fail(function(xhr){
        $('#lookupResults').html('<div class="alert alert-danger">Sale lookup failed: '+esc(xhr.responseJSON?.message || 'Please try again.')+'</div>');
        $('#soldUnitSelect').prop('disabled', true).html('<option value="">No sold item loaded</option>');
    });
});

$('#soldUnitSelect').on('change', function(){
    const index = $(this).val();
    if(index !== '') $('.result-row[data-index="'+index+'"]').trigger('click');
});

$(document).on('click','.result-row',function(){
    $('.result-row').removeClass('active'); $(this).addClass('active');
    const row = window.lookupRows[$(this).data('index')];
    const unit = row.unit || {};
    $('#salesInvoiceItemId').val(row.invoice_item_id);
    $('#returnedUnit').val(JSON.stringify(unit));
    $('#issuedItemId').val(row.item_id).trigger('change');
    $('#ledgerAmount').val(Number(row.line_total || 0).toFixed(2));
    $('#customerName').val(row.party);
    $('#customerEmail').val(row.party_email || '');
    $('#customerPhone').val(row.party_phone || '');
    $('#customerAddress').val(row.party_address || '');
    $('#selectedSummary').removeClass('is-error').html(`<div class="d-flex justify-content-between align-items-center"><strong><i class="fas fa-check-circle text-success mr-1"></i>Item selected</strong></div>
    <div class="selected-report">
        <div><span>Item</span><b>${esc(row.item_name)}</b><br><small>${esc(row.sku || row.item_code)}</small></div>
        <div><span>Invoice</span><b>${esc(row.invoice_no)}</b><br><small>${esc(row.date)}</small></div>
        <div><span>Serial / Buyer</span><b>${esc(unit.serial_no || unit.vts_sim || unit.buyer_code || unit.key || '-')}</b></div>
        <div><span>Amount</span><b>Rs ${Number(row.line_total).toFixed(2)}</b><br><small>Current Rs ${Number(row.current_price).toFixed(2)}</small></div>
        <div><span>Tax</span><b>${Number(row.tax_percent).toFixed(2)}%</b><br><small>Rs ${Number(row.tax_amount).toFixed(2)}</small></div>
    </div>`);
    $('#submitReplacement').prop('disabled', false);
    $('#goStep2').prop('disabled', false);
});

$('.replacement-form').on('submit', function(e){
    if(!$('#salesInvoiceItemId').val()){
        e.preventDefault();
        showStep(1);
        $('#selectedSummary').addClass('is-error').html('Please search and select the sold item before submitting replacement.');
        $('html, body').animate({scrollTop: $('#selectedSummary').offset().top - 90}, 250);
    }
});

$('#lookupQ').on('keydown', function(e){ if(e.key === 'Enter'){ e.preventDefault(); $('#lookupBtn').trigger('click'); } });

$('.image-input').on('change', function(){
    const tile = $(this).closest('.rp-photo-tile');
    const target = $('#' + $(this).data('preview'));
    const file = this.files[0];
    if(!file){ target.html('<i class="fas fa-camera"></i><span>Preview</span>'); tile.removeClass('has-image'); return; }
    const reader = new FileReader();
    reader.onload = e => { target.html(`<img src="${e.target.result}" alt="preview">`); tile.addClass('has-image'); };
    reader.readAsDataURL(file);
});
</script>
@endpush
