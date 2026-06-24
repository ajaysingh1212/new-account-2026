@extends('layouts.admin')
@section('title','Edit Production Batch')

@push('styles')
<style>
.prod-shell{background:#fff;border:1px solid #e8edf5;border-radius:8px;box-shadow:0 16px 36px rgba(15,23,42,.08);overflow:hidden}.prod-head{background:#1f2937;color:#fff;padding:22px 24px;display:flex;justify-content:space-between;align-items:center}.prod-head h2{font-weight:800;margin:0;font-size:24px}.prod-section{padding:20px 24px;border-bottom:1px solid #edf1f7}.unit-grid{display:grid;grid-template-columns:60px 140px 150px 170px 150px 120px 90px 120px 1fr;gap:8px;align-items:end}.unit-row{border:1px solid #e5e7eb;border-radius:8px;padding:12px;margin-bottom:10px;background:#fbfdff}.unit-row.sold{background:#fff7ed;border-color:#fed7aa}.unit-head{font-size:11px;text-transform:uppercase;color:#667085;font-weight:800}.form-control{border-radius:6px}
.impact-modal .modal-content{border:0;border-radius:16px;overflow:hidden;box-shadow:0 24px 70px rgba(15,23,42,.28)}.impact-modal .modal-header{background:linear-gradient(135deg,#0f172a,#1d4ed8);color:#fff;border:0}.impact-target{display:grid;grid-template-columns:28px 1fr;gap:10px;border:1px solid #e2e8f0;border-radius:11px;padding:12px;margin-bottom:9px;background:#fff}.impact-target small{display:block;color:#64748b}.impact-list{max-height:390px;overflow:auto;background:#f8fafc;padding:12px;border-radius:12px}
@media(max-width:992px){.unit-grid{grid-template-columns:1fr 1fr}}
</style>
@endpush

@section('content')
<form method="POST" action="{{ route('admin.production-batches.update', $batch) }}" id="prodEditForm">
@csrf
@method('PUT')
<div class="prod-shell">
    <div class="prod-head">
        <div>
            <h2><i class="fas fa-cogs mr-2"></i>Edit Production Batch</h2>
            <small>{{ $batch->finishedItem?->name }} | sold units stay protected</small>
        </div>
        <a href="{{ route('admin.production-batches.show', $batch) }}" class="btn btn-outline-light btn-sm">Back</a>
    </div>

    <div class="prod-section">
        <div class="row">
            <div class="col-md-3 form-group"><label>Finished Item</label><input class="form-control" value="{{ $batch->finishedItem?->name }}" readonly></div>
            <div class="col-md-2 form-group"><label>SKU</label><input name="finished_item_sku" class="form-control crm-identifier" value="{{ old('finished_item_sku',$batch->finishedItem?->sku) }}"></div>
            <div class="col-md-2 form-group"><label>Batch No</label><input name="batch_no" class="form-control" value="{{ old('batch_no',$batch->batch_no) }}" required></div>
            <div class="col-md-2 form-group"><label>Production Date</label><input type="date" name="production_date" class="form-control" value="{{ old('production_date',$batch->production_date?->format('Y-m-d')) }}" required></div>
            <div class="col-md-2 form-group"><label>Quantity</label><input type="number" step="1" min="1" name="quantity" id="prodQty" class="form-control" value="{{ old('quantity',(int)$batch->quantity) }}" required></div>
            <div class="col-md-2 form-group"><label>Cost/Unit</label><input class="form-control" value="Rs {{ number_format((float)$batch->cost_per_unit,2) }}" readonly></div>
            <div class="col-md-12 form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="2">{{ old('notes',$batch->notes) }}</textarea></div>
        </div>
        @include('admin.partials.entry-visibility', ['entry' => $batch])
    </div>

    <div class="prod-section">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <b>Individual Finished Goods</b>
            <button type="button" class="btn btn-outline-primary btn-sm" id="syncRows"><i class="fas fa-sync mr-1"></i>Sync Rows With Qty</button>
        </div>
<div class="unit-grid unit-head mb-2"><span>#</span><span>Buyer Code</span><span>Serial No.</span><span>Batch No.</span><span>VTS/SIM No.</span><span>Sale Price</span><span>GST</span><span>Mode</span><span>Warehouse / Notes</span></div>
        <div id="unitRows"></div>
    </div>

    <div class="p-4 text-right"><button class="btn btn-primary btn-lg"><i class="fas fa-save mr-1"></i>Update Production</button></div>
</div>
</form>

<div class="modal fade impact-modal" id="identifierImpactModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document"><div class="modal-content">
    <div class="modal-header"><div><h5 class="modal-title"><i class="fas fa-project-diagram mr-2"></i>Identifier Change Warning</h5><small>Checked locations par Serial / SKU / VTS / SIM update hoga.</small></div><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
    <div class="modal-body"><div class="alert alert-warning"><b>Please verify:</b> merged-company chain aur customer sales सहित सभी detected locations default selected हैं। जिस जगह update नहीं चाहिए उसे uncheck करें।</div><div id="impactTargets" class="impact-list"></div></div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="confirmPropagation"><i class="fas fa-check mr-1"></i>Update Checked Locations</button></div>
  </div></div>
</div>
@endsection

@push('scripts')
<script>
const EXISTING_UNITS = @json(old('unit_serial') ? [] : ($batch->units_data ?? []));
const SOLD_KEYS = @json($soldUnitKeys);
const BATCH_ID = @json($batch->id);
const IMPACT_URL = @json(route('admin.production-batches.identifier-impact', $batch));
function unitRow(i, unit={}){let sold=SOLD_KEYS.includes(`${BATCH_ID}-${i}`);return `<div class="unit-row ${sold?'sold':''}" data-i="${i}">
<div class="unit-grid">
<div><label>#</label><input class="form-control" value="${i+1}${sold?' Sold':''}" readonly></div>
<div><label>Buyer Code</label><input name="unit_buyer_code[${i}]" class="form-control" value="${unit.buyer_code||('BC-AUTO-'+String(i+1).padStart(3,'0'))}" ${sold?'readonly':''}><input type="hidden" name="unit_buyer_id[${i}]" value="${unit.buyer_id||''}"></div>
<div><label>Serial No.</label><input name="unit_serial[${i}]" class="form-control crm-identifier" value="${unit.serial_no||''}"></div>
<div><label>Batch No. (Purchase)</label><input name="unit_batch[${i}]" class="form-control" value="${unit.batch_no||''}" ${sold?'readonly':''}></div>
<div><label>VTS/SIM No.</label><input name="unit_vts_sim[${i}]" class="form-control crm-identifier" value="${unit.vts_sim||''}"></div>
<div><label>Sale Price</label><input type="number" step="0.01" name="unit_sale_price[${i}]" class="form-control" value="${unit.sale_price||0}"></div>
<div><label>GST</label><input type="number" step="0.01" name="unit_gst[${i}]" class="form-control" value="${unit.gst||0}"></div>
<div><label>Mode</label><select name="unit_sale_mode[${i}]" class="form-control"><option value="exclusive" ${(unit.sale_mode||'exclusive')==='exclusive'?'selected':''}>Exclusive</option><option value="inclusive" ${unit.sale_mode==='inclusive'?'selected':''}>Inclusive</option></select></div>
<div><label>Warehouse / Notes</label><div class="row"><div class="col-md-6"><input name="unit_warehouse[${i}]" class="form-control" value="${unit.warehouse||''}" placeholder="Warehouse"></div><div class="col-md-6"><input name="unit_notes[${i}]" class="form-control" value="${unit.notes||''}" placeholder="Notes"></div></div></div>
</div></div>`}
function renderRows(){let q=Math.max(1,parseInt($('#prodQty').val())||1),html='';for(let i=0;i<q;i++){html+=unitRow(i,EXISTING_UNITS[i]||{})}$('#unitRows').html(html)}
$('#syncRows').click(renderRows);$('#prodQty').on('change',renderRows);renderRows();

function escapeImpact(value){return $('<div>').text(value==null?'':String(value)).html()}
$('#prodEditForm').on('submit', async function(event){
    event.preventDefault();
    const form=this, button=$(form).find('button[type="submit"]');
    button.prop('disabled',true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Checking impact...');
    try{
        const payload=new FormData(form); payload.delete('_method');
        const response=await fetch(IMPACT_URL,{method:'POST',body:payload,headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
        const result=await response.json();
        if(!response.ok) throw new Error(result.message||Object.values(result.errors||{}).flat().join('\n')||'Impact check failed.');
        if(!result.changed){ HTMLFormElement.prototype.submit.call(form); return; }
        const targets=[{token:'origin',type:'Current Production / CRM stock',detail:'This batch and its current stock record',locked:true},...(result.targets||[])];
        $('#impactTargets').html(targets.map(target=>`<label class="impact-target"><input type="checkbox" class="impact-check" value="${escapeImpact(target.token)}" checked ${target.locked?'disabled':''}><span><b>${escapeImpact(target.type)}</b><small>${escapeImpact(target.detail)}</small></span></label>`).join(''));
        $('#identifierImpactModal').modal('show');
    }catch(error){ alert(error.message); }
    finally{button.prop('disabled',false).html('<i class="fas fa-save mr-1"></i>Update Production');}
});
$('#confirmPropagation').on('click',function(){
    const form=document.getElementById('prodEditForm');
    form.querySelectorAll('.propagation-token').forEach(node=>node.remove());
    document.querySelectorAll('.impact-check:checked:not(:disabled)').forEach(box=>{
        const input=document.createElement('input');input.type='hidden';input.name='propagation_targets[]';input.value=box.value;input.className='propagation-token';form.appendChild(input);
    });
    $('#identifierImpactModal').modal('hide');
    HTMLFormElement.prototype.submit.call(form);
});
</script>
@endpush
