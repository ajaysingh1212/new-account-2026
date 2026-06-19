@extends('layouts.admin')
@section('title','New Sales Return')

@push('styles')
<style>
.return-shell{background:#fff;border:1px solid #e7edf5;border-radius:18px;overflow:hidden;box-shadow:0 14px 36px rgba(15,23,42,.08)}.return-head{background:linear-gradient(135deg,#111827,#312e81);color:#fff;padding:23px 25px}.return-head h3{font-weight:850;margin:0}.return-body{padding:22px 24px}.return-table thead th{border-top:0;background:#f8fafc;color:#475569;font-size:11px;text-transform:uppercase;letter-spacing:.4px}.return-table td{vertical-align:middle}
.serial-btn{width:39px;height:39px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;position:relative}.serial-count{position:absolute;right:-7px;top:-7px;min-width:20px;height:20px;padding:0 5px;border-radius:999px;background:#7c3aed;color:#fff;font-size:10px;font-weight:850;display:flex;align-items:center;justify-content:center}
.serial-summary{max-width:290px}.serial-pill{display:inline-flex;align-items:center;gap:5px;border:1px solid #99f6e4;background:#f0fdfa;color:#0f766e;border-radius:999px;padding:4px 9px;margin:2px;font-size:11px;font-weight:750}
.serial-modal .modal-content{border:0;border-radius:20px;overflow:hidden;box-shadow:0 25px 70px rgba(15,23,42,.25)}.serial-modal .modal-header{border:0;background:linear-gradient(135deg,#0f172a,#3730a3);color:#fff;padding:21px 24px}.serial-modal .modal-body{padding:22px 24px}.serial-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:11px;max-height:420px;overflow:auto}
.serial-card{border:1px solid #e2e8f0;border-radius:13px;padding:12px;display:grid;grid-template-columns:26px 1fr;gap:9px;background:#fff;cursor:pointer;transition:.15s}.serial-card:hover{border-color:#a78bfa}.serial-card.selected{border-color:#7c3aed;background:#faf5ff}.serial-card.returned{background:#f8fafc;color:#94a3b8;cursor:not-allowed}.serial-meta{font-size:11px;color:#64748b;margin-top:3px}.return-status{font-size:11px;font-weight:800}.qty-note{font-size:11px;color:#64748b;margin-top:4px}
</style>
@endpush

@section('content')
<form method="POST" action="{{ route('admin.sales-returns.store') }}" id="salesReturnForm">@csrf
<div class="return-shell">
    <div class="return-head"><h3><i class="fas fa-undo-alt mr-2"></i>New Sales Return</h3><small style="opacity:.72">Return exact sold serials and restore them for future sales.</small></div>
    <div class="return-body">
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <div class="row">
            <div class="col-md-4 form-group"><label>Sale Invoice</label><select name="sales_invoice_id" id="sourceInvoice" class="form-control select2" required><option value="">Select invoice</option>@foreach($invoices as $invoice)<option value="{{ $invoice->id }}" @selected((string) old('sales_invoice_id') === (string) $invoice->id)>{{ $invoice->invoice_no }} | {{ $invoice->party?->display_name ?: 'Cash' }} | Rs {{ number_format((float)$invoice->grand_total,2) }}</option>@endforeach</select></div>
            <div class="col-md-2 form-group"><label>Return No</label><input name="return_no" class="form-control" value="{{ old('return_no',$returnNo) }}"></div>
            <div class="col-md-2 form-group"><label>Date</label><input type="date" name="return_date" class="form-control" value="{{ old('return_date',now()->toDateString()) }}" required></div>
            <div class="col-md-4 form-group"><label>Reason</label><input name="reason" class="form-control" value="{{ old('reason') }}"></div>
        </div>
        <div id="returnLines"><div class="text-center text-muted py-5"><i class="fas fa-file-invoice fa-2x mb-2"></i><div>Select a sales invoice to view returnable items.</div></div></div>
        @include('admin.partials.entry-visibility')
    </div>
    <div class="p-4 text-right" style="border-top:1px solid #edf1f7"><button class="btn btn-primary px-4"><i class="fas fa-check mr-1"></i> Post Sales Return</button></div>
</div>
</form>

<div class="modal fade serial-modal" id="returnSerialModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document"><div class="modal-content">
        <div class="modal-header"><div><div style="font-size:11px;text-transform:uppercase;opacity:.7;font-weight:800">Related Serial Selection</div><h4 class="modal-title mt-1" id="serialModalTitle">Select returned serials</h4></div><button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button></div>
        <div class="modal-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap:10px"><div><b id="serialRequirement">Select 0 serials</b><div class="text-muted small">Return quantity ke barabar exact serial select karein.</div></div><button type="button" class="btn btn-outline-primary btn-sm" id="autoSelectSerials"><i class="fas fa-magic mr-1"></i>Auto select</button></div>
            <div id="serialGrid" class="serial-grid"></div>
        </div>
        <div class="modal-footer border-0 px-4 pb-4"><button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button><button type="button" id="saveSerialSelection" class="btn btn-primary px-4">Use selected serials</button></div>
    </div></div>
</div>
@endsection

@push('scripts')
<script>
const INVOICES=@json($invoiceData);
let activeLineIndex=null, modalSelection=[];
function unitLabel(unit){return unit.serial_no||unit.vts_sim||unit.buyer_code||unit.batch_no||unit.key||'Serial unit'}
function selectedFor(index){try{return JSON.parse($(`.returned-units[data-index="${index}"]`).val()||'[]')}catch(e){return[]}}
function setSelected(index,units){
    $(`.returned-units[data-index="${index}"]`).val(JSON.stringify(units));
    const box=$(`.serial-summary[data-index="${index}"]`);
    box.html(units.length?units.map(u=>`<span class="serial-pill"><i class="fas fa-barcode"></i>${unitLabel(u)}</span>`).join(''):'<span class="text-muted small">No serial selected</span>');
    $(`.serial-count[data-index="${index}"]`).text(units.length);
}
function requiredQty(index){return Math.max(0,Math.floor(parseFloat($(`.return-qty[data-index="${index}"]`).val()||0)))}
function reconcileSelection(index){
    const line=$(`.return-row[data-index="${index}"]`).data('line'),required=requiredQty(index),available=line.available_units||[];
    let chosen=selectedFor(index).filter(selected=>available.some(u=>u.key===selected.key)).slice(0,required);
    const keys=chosen.map(u=>u.key);
    if(chosen.length<required)chosen=chosen.concat(available.filter(u=>!keys.includes(u.key)).slice(0,required-chosen.length));
    setSelected(index,chosen);
}
function renderLines(lines){
    const returnable=lines.filter(line=>Number(line.remaining_qty)>0);
    if(!returnable.length){$('#returnLines').html('<div class="alert alert-info">This invoice has no remaining quantity available for return.</div>');return}
    const rows=returnable.map((line,index)=>{
        const hasSerials=(line.sold_units||[]).length>0;
        return `<tr class="return-row" data-index="${index}">
            <td><b>${line.item}</b><input type="hidden" name="line_id[]" value="${line.id}"><input type="hidden" name="returned_units[]" class="returned-units" data-index="${index}" value="[]"><div class="qty-note">${hasSerials?'Serial tracked item':'Non-serial item'}</div></td>
            <td>${Number(line.qty).toLocaleString('en-IN')} ${line.unit}</td>
            <td>${Number(line.already_returned).toLocaleString('en-IN')} ${line.unit}</td>
            <td style="max-width:150px"><input type="number" name="quantity[]" class="form-control return-qty" data-index="${index}" min="0" max="${line.remaining_qty}" step="${hasSerials?'1':'0.001'}" value="${line.remaining_qty}"><div class="qty-note">Max ${line.remaining_qty}</div></td>
            <td>${hasSerials?`<button type="button" class="btn btn-outline-primary serial-btn open-serials" data-index="${index}" title="View or change serials"><i class="fas fa-barcode"></i><span class="serial-count" data-index="${index}">0</span></button>`:'<span class="text-muted">Not applicable</span>'}</td>
            <td><div class="serial-summary" data-index="${index}"></div></td>
            <td>Rs ${Number(line.price).toFixed(2)}</td>
        </tr>`;
    }).join('');
    $('#returnLines').html(`<div class="table-responsive"><table class="table return-table"><thead><tr><th>Item</th><th>Sold Qty</th><th>Already Returned</th><th>Return Qty</th><th>Serials</th><th>Returning Serials</th><th>Unit Price</th></tr></thead><tbody>${rows}</tbody></table></div>`);
    returnable.forEach((line,index)=>{$(`.return-row[data-index="${index}"]`).data('line',line);reconcileSelection(index)});
}
function renderSerialModal(){
    const line=$(`.return-row[data-index="${activeLineIndex}"]`).data('line'),required=requiredQty(activeLineIndex),selectedKeys=modalSelection.map(u=>u.key),availableKeys=(line.available_units||[]).map(u=>u.key);
    $('#serialRequirement').text(`Select exactly ${required} serial${required===1?'':'s'} (${modalSelection.length} selected)`);
    $('#serialGrid').html((line.sold_units||[]).map(unit=>{
        const returned=!availableKeys.includes(unit.key),selected=selectedKeys.includes(unit.key);
        return `<label class="serial-card ${returned?'returned':''} ${selected?'selected':''}"><input type="checkbox" class="modal-serial-check" data-key="${unit.key}" ${selected?'checked':''} ${returned?'disabled':''}><span><b>${unitLabel(unit)}</b><div class="serial-meta">Batch ${unit.batch_no||'-'} | VTS/SIM ${unit.vts_sim||'-'} | Buyer ${unit.buyer_code||'-'}</div><div class="return-status ${returned?'text-muted':'text-success'}">${returned?'Already returned':'Available to return'}</div></span></label>`;
    }).join(''));
}
$('#sourceInvoice').on('change',function(){renderLines(INVOICES[this.value]||[])});
$(document).on('input change','.return-qty',function(){
    const index=$(this).data('index'),line=$(`.return-row[data-index="${index}"]`).data('line'),max=Number(line.remaining_qty);
    if(Number(this.value)>max){alert('Return quantity remaining quantity se jyada nahi ho sakti.');this.value=max}
    if((line.sold_units||[]).length&&Number(this.value)%1!==0){alert('Serial item quantity whole number me honi chahiye.');this.value=Math.floor(Number(this.value)||0)}
    reconcileSelection(index);
});
$(document).on('click','.open-serials',function(){activeLineIndex=$(this).data('index');const line=$(`.return-row[data-index="${activeLineIndex}"]`).data('line');modalSelection=selectedFor(activeLineIndex);$('#serialModalTitle').text(line.item);renderSerialModal();$('#returnSerialModal').modal('show')});
$(document).on('change','.modal-serial-check',function(){
    const line=$(`.return-row[data-index="${activeLineIndex}"]`).data('line'),required=requiredQty(activeLineIndex),unit=(line.available_units||[]).find(u=>u.key===$(this).data('key'));
    if(this.checked){if(modalSelection.length>=required){alert(`Aap sirf ${required} serial select kar sakte hain.`);this.checked=false;return}if(unit)modalSelection.push(unit)}
    else modalSelection=modalSelection.filter(u=>u.key!==$(this).data('key'));
    renderSerialModal();
});
$('#autoSelectSerials').on('click',function(){const line=$(`.return-row[data-index="${activeLineIndex}"]`).data('line');modalSelection=(line.available_units||[]).slice(0,requiredQty(activeLineIndex));renderSerialModal()});
$('#saveSerialSelection').on('click',function(){const required=requiredQty(activeLineIndex);if(modalSelection.length!==required){alert(`Return quantity ke liye exactly ${required} serial select karein.`);return}setSelected(activeLineIndex,modalSelection);$('#returnSerialModal').modal('hide')});
$('#salesReturnForm').on('submit',function(e){let valid=true;$('.return-row').each(function(){const index=$(this).data('index'),line=$(this).data('line'),qty=requiredQty(index);if((line.sold_units||[]).length&&selectedFor(index).length!==qty)valid=false});if(!valid){e.preventDefault();alert('Har serialised item ke return quantity ke barabar serial select karein.')}});
if($('#sourceInvoice').val())$('#sourceInvoice').trigger('change');
</script>
@endpush
