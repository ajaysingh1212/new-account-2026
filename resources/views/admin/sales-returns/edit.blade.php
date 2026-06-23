@extends('layouts.admin')
@section('title','Edit Sales Return Serials')

@push('styles')
<style>
.serial-edit-shell{background:#fff;border:1px solid #e7edf5;border-radius:18px;overflow:hidden;box-shadow:0 14px 36px rgba(15,23,42,.08)}.serial-edit-head{background:linear-gradient(135deg,#111827,#4c1d95);color:#fff;padding:23px 25px}.serial-edit-head h3{font-weight:850;margin:0}.serial-edit-body{padding:22px 24px}.serial-pill{display:inline-flex;align-items:center;gap:5px;border:1px solid #99f6e4;background:#f0fdfa;color:#0f766e;border-radius:999px;padding:4px 9px;margin:2px;font-size:11px;font-weight:750}.serial-btn{width:40px;height:40px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;position:relative}.serial-count{position:absolute;right:-7px;top:-7px;min-width:20px;height:20px;padding:0 5px;border-radius:999px;background:#7c3aed;color:#fff;font-size:10px;font-weight:850;display:flex;align-items:center;justify-content:center}.serial-modal .modal-content{border:0;border-radius:20px;overflow:hidden;box-shadow:0 25px 70px rgba(15,23,42,.25)}.serial-modal .modal-header{border:0;background:linear-gradient(135deg,#0f172a,#3730a3);color:#fff;padding:21px 24px}.serial-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:11px;max-height:420px;overflow:auto}.serial-card{border:1px solid #e2e8f0;border-radius:13px;padding:12px;display:grid;grid-template-columns:26px 1fr;gap:9px;background:#fff;cursor:pointer;transition:.15s}.serial-card.selected{border-color:#7c3aed;background:#faf5ff}.serial-card.returned{background:#f8fafc;color:#94a3b8;cursor:not-allowed}.serial-meta{font-size:11px;color:#64748b;margin-top:3px}
</style>
@endpush

@section('content')
<form method="POST" action="{{ route('admin.sales-returns.update',$return) }}" id="serialEditForm">@csrf @method('PUT')
<div class="serial-edit-shell">
    <div class="serial-edit-head"><h3><i class="fas fa-barcode mr-2"></i>Update Returned Serials</h3><small style="opacity:.75">Return {{ $return->return_no }} | Invoice {{ $return->invoice?->invoice_no }} | Old returns me missing serial yahan assign kar sakte hain.</small></div>
    <div class="serial-edit-body">
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Item</th><th>Returned Qty</th><th>Serial Action</th><th>Selected Serials</th></tr></thead>
                <tbody>
                @foreach($lines as $line)
                    <tr class="serial-line" data-index="{{ $line['index'] }}">
                        <td><b>{{ $line['item'] }}</b><input type="hidden" name="returned_units[]" class="returned-units" data-index="{{ $line['index'] }}" value='@json($line['selected_units'])'><div class="text-muted small">{{ $line['has_serials'] ? 'Serial tracked' : 'Non-serial item' }}</div></td>
                        <td>{{ number_format($line['quantity'],3) }} {{ $line['unit'] }}</td>
                        <td>@if($line['has_serials'])<button type="button" class="btn btn-outline-primary serial-btn open-serials" data-index="{{ $line['index'] }}"><i class="fas fa-barcode"></i><span class="serial-count" data-index="{{ $line['index'] }}">{{ count($line['selected_units']) }}</span></button>@else<span class="text-muted">Not applicable</span>@endif</td>
                        <td><div class="serial-summary" data-index="{{ $line['index'] }}">@forelse($line['selected_units'] as $unit)<span class="serial-pill"><i class="fas fa-barcode"></i>{{ $unit['serial_no'] ?? $unit['vts_sim'] ?? $unit['buyer_code'] ?? $unit['key'] ?? 'Serial' }}</span>@empty<span class="text-muted small">{{ $line['has_serials'] ? 'No serial selected yet' : '-' }}</span>@endforelse</div></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="p-4 text-right" style="border-top:1px solid #edf1f7"><a href="{{ route('admin.sales-returns.show',$return) }}" class="btn btn-outline-secondary">Cancel</a> <button class="btn btn-primary px-4"><i class="fas fa-save mr-1"></i> Update Serials</button></div>
</div>
</form>

<div class="modal fade serial-modal" id="returnSerialModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document"><div class="modal-content">
        <div class="modal-header"><div><div style="font-size:11px;text-transform:uppercase;opacity:.7;font-weight:800">Returned Serial Selection</div><h4 class="modal-title mt-1" id="serialModalTitle">Select returned serials</h4></div><button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button></div>
        <div class="modal-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap:10px"><div><b id="serialRequirement">Select serials</b><div class="text-muted small">Is return quantity ke barabar serial select karna zaroori hai.</div></div><button type="button" class="btn btn-outline-primary btn-sm" id="autoSelectSerials"><i class="fas fa-magic mr-1"></i>Auto select</button></div>
            <div id="serialGrid" class="serial-grid"></div>
        </div>
        <div class="modal-footer border-0 px-4 pb-4"><button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button><button type="button" id="saveSerialSelection" class="btn btn-primary px-4">Use selected serials</button></div>
    </div></div>
</div>
@endsection

@push('scripts')
<script>
const LINES=@json($lines);
let activeIndex=null, modalSelection=[];
function unitLabel(unit){return unit.serial_no||unit.vts_sim||unit.buyer_code||unit.batch_no||unit.key||'Serial unit'}
function lineFor(index){return LINES.find(line=>Number(line.index)===Number(index))}
function selectedFor(index){try{return JSON.parse($(`.returned-units[data-index="${index}"]`).val()||'[]')}catch(e){return[]}}
function setSelected(index,units){
    $(`.returned-units[data-index="${index}"]`).val(JSON.stringify(units));
    $(`.serial-count[data-index="${index}"]`).text(units.length);
    $(`.serial-summary[data-index="${index}"]`).html(units.length?units.map(u=>`<span class="serial-pill"><i class="fas fa-barcode"></i>${unitLabel(u)}</span>`).join(''):'<span class="text-muted small">No serial selected yet</span>');
}
function requiredQty(index){return Math.max(0,Math.round(Number(lineFor(index).quantity)||0))}
function renderSerialModal(){
    const line=lineFor(activeIndex),required=requiredQty(activeIndex),selectedKeys=modalSelection.map(u=>u.key),availableKeys=(line.available_units||[]).map(u=>u.key);
    $('#serialModalTitle').text(line.item);$('#serialRequirement').text(`Select exactly ${required} serial${required===1?'':'s'} (${modalSelection.length} selected)`);
    $('#serialGrid').html((line.sold_units||[]).map(unit=>{
        const available=availableKeys.includes(unit.key),selected=selectedKeys.includes(unit.key);
        return `<label class="serial-card ${available?'':'returned'} ${selected?'selected':''}"><input type="checkbox" class="modal-serial-check" data-key="${unit.key}" ${selected?'checked':''} ${available?'':'disabled'}><span><b>${unitLabel(unit)}</b><div class="serial-meta">Batch ${unit.batch_no||'-'} | VTS/SIM ${unit.vts_sim||'-'} | Buyer ${unit.buyer_code||'-'}</div><div class="${available?'text-success':'text-muted'} small font-weight-bold">${available?'Available for this return':'Already used in another return'}</div></span></label>`;
    }).join(''));
}
$('.open-serials').on('click',function(){activeIndex=$(this).data('index');modalSelection=selectedFor(activeIndex);renderSerialModal();$('#returnSerialModal').modal('show')});
$(document).on('change','.modal-serial-check',function(){
    const line=lineFor(activeIndex),required=requiredQty(activeIndex),unit=(line.available_units||[]).find(u=>u.key===$(this).data('key'));
    if(this.checked){if(modalSelection.length>=required){alert(`Aap sirf ${required} serial select kar sakte hain.`);this.checked=false;return}if(unit)modalSelection.push(unit)}
    else modalSelection=modalSelection.filter(u=>u.key!==$(this).data('key'));
    renderSerialModal();
});
$('#autoSelectSerials').on('click',function(){const line=lineFor(activeIndex);modalSelection=(line.available_units||[]).slice(0,requiredQty(activeIndex));renderSerialModal()});
$('#saveSerialSelection').on('click',function(){const required=requiredQty(activeIndex);if(modalSelection.length!==required){alert(`Exactly ${required} serial select karein.`);return}setSelected(activeIndex,modalSelection);$('#returnSerialModal').modal('hide')});
$('#serialEditForm').on('submit',function(e){let ok=true;LINES.forEach(line=>{if(line.has_serials&&selectedFor(line.index).length!==requiredQty(line.index))ok=false});if(!ok){e.preventDefault();alert('Har serial tracked return line me returned qty ke barabar serial select karein.')}});
</script>
@endpush
