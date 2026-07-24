@php($editing = isset($target) && $target)
@php($firstItemType = old('global_target_type', $editing ? ($target->items->first()->target_type ?? 'percent') : 'percent'))
@php($initialTotal = old('total_target_value', $editing ? $target->items->sum('target_value') : ''))
<form method="POST" action="{{ $editing ? route('admin.sales-targets.update',$target) : route('admin.sales-targets.store') }}" id="targetForm">
@csrf @if($editing) @method('PUT') @endif
<style>
#targetForm .sgf-unit-section{display:none;animation:sgfSlideDown .35s ease both}
#targetForm .sgf-unit-section.show{display:flex}
@keyframes sgfSlideDown{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
#targetForm .sgf-total-bar{border-radius:14px;padding:14px 18px;background:#f8fafc;border:1.5px solid #e5e7eb;transition:.25s;margin-top:6px}
#targetForm .sgf-total-bar.match{background:#dcfce7;border-color:#86efac}
#targetForm .sgf-total-bar.mismatch{background:#fef3c7;border-color:#fcd34d}
#targetForm .sgf-total-track{height:8px;border-radius:4px;background:#e2e8f0;overflow:hidden;margin-top:8px}
#targetForm .sgf-total-fill{height:100%;border-radius:4px;width:0;background:linear-gradient(90deg,#7C3AED,#6366F1);transition:width .6s cubic-bezier(.2,.8,.2,1)}
#targetForm .sgf-total-bar.match .sgf-total-fill{background:linear-gradient(90deg,#10b981,#5eead4)}
#targetForm .sgf-total-bar.mismatch .sgf-total-fill{background:linear-gradient(90deg,#f59e0b,#fbbf24)}
</style>
<div class="card shadow-sm border-0"><div class="card-body p-4">
    <div class="d-flex align-items-center mb-4"><div class="rounded-circle bg-primary text-white p-3 mr-3"><i class="fas fa-bullseye fa-lg"></i></div><div><h3 class="mb-0">{{ $editing ? 'Refine' : 'Create' }} Sales Target</h3><small class="text-muted">Set category-wise goals for one party and measure every rupee, unit or percentage.</small></div></div>

    <div class="row">
        <div class="col-md-5 form-group"><label>Party *</label>
            <select name="party_id" id="party_id" class="form-control select2" required>
                <option value="">Select Party</option>
                @foreach($parties as $party)<option value="{{ $party->id }}" @selected(old('party_id',$target->party_id ?? '')==$party->id)>{{ $party->display_name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-3 form-group"><label>Target Period *</label>
            <select name="period_type" id="period_type" class="form-control" required>
                @foreach(['daily'=>'Daily','weekly'=>'Weekly','monthly'=>'Monthly','quarterly_3'=>'3 Months','quarterly_6'=>'6 Months','yearly'=>'Yearly'] as $value=>$label)
                    <option value="{{ $value }}" @selected(old('period_type',$target->period_type ?? 'monthly')===$value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 form-group"><label>Start Date *</label><input type="date" name="starts_on" id="starts_on" value="{{ old('starts_on',$target?->starts_on?->toDateString() ?? now()->startOfMonth()->toDateString()) }}" class="form-control" required></div>
        <div class="col-md-2 form-group"><label>End Date *</label><input type="date" name="ends_on" id="ends_on" value="{{ old('ends_on',$target?->ends_on?->toDateString() ?? now()->endOfMonth()->toDateString()) }}" class="form-control" required></div>
    </div>

    <div class="row sgf-unit-section {{ $editing || old('party_id') ? 'show' : '' }}" id="sgfUnitSection">
        <div class="col-md-4 form-group mb-md-0">
            <label>Target Unit * <small class="text-muted d-block">(ek hi baar select karein — sabhi categories isi unit me honge)</small></label>
            <select name="global_target_type" id="global_target_type" class="form-control">
                <option value="percent" @selected($firstItemType==='percent')>Percentage (%)</option>
                <option value="amount" @selected($firstItemType==='amount')>Amount (Rs)</option>
                <option value="quantity" @selected($firstItemType==='quantity')>Quantity</option>
            </select>
        </div>
        <div class="col-md-4 form-group mb-md-0">
            <label id="sgfTotalLabel">Total Target Amount (₹) *</label>
            <input type="number" min="0" step="0.001" name="total_target_value" id="total_target_value" class="form-control" value="{{ $initialTotal }}" placeholder="e.g. 100000">
        </div>
        <div class="col-md-4 form-group mb-md-0 d-flex align-items-end">
            <small class="text-muted">Niche har category ka goal value daalein — sabka total isse match karna chahiye tabhi save hoga.</small>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-md-9 form-group"><label>Notes</label><input name="notes" class="form-control" value="{{ old('notes',$target->notes ?? '') }}" placeholder="Optional focus, incentive or review note"></div>
        <div class="col-md-3 form-group"><label>Status</label><select name="status" class="form-control"><option value="active" @selected(old('status',$target->status ?? 'active')==='active')>Active</option><option value="inactive" @selected(old('status',$target->status ?? '')==='inactive')>Inactive</option></select></div>
    </div>
</div></div>

<div class="card shadow-sm border-0 mt-3"><div class="card-header bg-white d-flex justify-content-between align-items-center">
    <div><h4 class="mb-0">Product Category Goals</h4><small class="text-muted">Pehle upar Target Unit select karein, phir har category ka goal value daalein.</small></div>
    <button type="button" class="btn btn-primary btn-sm" id="addRow"><i class="fas fa-plus mr-1"></i> Add Category</button>
</div>
<div class="card-body bg-light" id="targetRows">
    @php($oldRows = old('product_category_ids', $editing ? $target->items->pluck('product_category_id')->all() : ['']))
    @foreach($oldRows as $i=>$categoryId)
    <div class="target-row bg-white border rounded-lg p-3 mb-3">
        <div class="row align-items-end">
            <div class="col-md-8 form-group mb-md-0"><label>Product Category *</label>
                <select name="product_category_ids[]" class="form-control category-select" required>
                    <option value="">Choose category</option>
                    @foreach($categories as $category)<option value="{{ $category->id }}" @selected((string)$categoryId===(string)$category->id)>{{ $category->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-3 form-group mb-md-0"><label class="row-goal-label">Goal Value *</label>
                <input type="number" min="0" step="0.001" name="target_values[]" class="form-control row-goal-value" value="{{ old('target_values.'.$i,$editing ? $target->items[$i]->target_value ?? '':'') }}" required>
            </div>
            <div class="col-md-1 form-group mb-md-0"><button type="button" class="btn btn-outline-danger remove-row"><i class="fas fa-times"></i></button></div>
        </div>
        <input type="hidden" name="target_types[]" class="row-target-type" value="{{ old('target_types.'.$i,$editing ? ($target->items[$i]->target_type ?? $firstItemType) : $firstItemType) }}">
        <input name="item_notes[]" class="form-control form-control-sm mt-2" value="{{ old('item_notes.'.$i,$editing ? $target->items[$i]->notes ?? '':'') }}" placeholder="Category-specific note (optional)">
    </div>
    @endforeach
</div>
<div class="card-footer bg-white">
    <div class="sgf-total-bar" id="sgfTotalBar">
        <div class="d-flex justify-content-between align-items-center">
            <strong id="sgfTotalStatusText">Total entered: 0 / 0</strong>
            <span id="sgfTotalStatusBadge" class="badge badge-secondary">Pending</span>
        </div>
        <div class="sgf-total-track"><div class="sgf-total-fill" id="sgfTotalFill"></div></div>
    </div>
</div>
</div>

<div class="mt-3"><a href="{{ route('admin.sales-targets.index') }}" class="btn btn-light">Cancel</a><button class="btn btn-primary float-right" id="sgfSubmitBtn"><i class="fas fa-save mr-1"></i> Save Sales Target</button></div>
</form>

<template id="rowTemplate">
<div class="target-row bg-white border rounded-lg p-3 mb-3">
    <div class="row align-items-end">
        <div class="col-md-8 form-group mb-md-0"><label>Product Category *</label>
            <select name="product_category_ids[]" class="form-control category-select" required>
                <option value="">Choose category</option>
                @foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-3 form-group mb-md-0"><label class="row-goal-label">Goal Value *</label>
            <input type="number" min="0" step="0.001" name="target_values[]" class="form-control row-goal-value" required>
        </div>
        <div class="col-md-1 form-group mb-md-0"><button type="button" class="btn btn-outline-danger remove-row"><i class="fas fa-times"></i></button></div>
    </div>
    <input type="hidden" name="target_types[]" class="row-target-type" value="percent">
    <input name="item_notes[]" class="form-control form-control-sm mt-2" placeholder="Category-specific note (optional)">
</div>
</template>

@push('scripts')
<script>
function wireRows(){
    document.querySelectorAll('.remove-row').forEach(b=>b.onclick=()=>{
        if(document.querySelectorAll('.target-row').length>1){ b.closest('.target-row').remove(); recalcTotal(); }
    });
    $('.category-select').select2({width:'100%'});
    document.querySelectorAll('.row-goal-value').forEach(inp=>{ inp.oninput = recalcTotal; });
}

function unitMeta(unit){
    if(unit==='amount') return { label:'Total Target Amount (₹) *', rowLabel:'Goal Value (₹) *', suffix:' ₹' };
    if(unit==='quantity') return { label:'Total Target Quantity *', rowLabel:'Goal Value (Qty) *', suffix:'' };
    return { label:'Total Target % *', rowLabel:'Goal Value (%) *', suffix: '%' };
}

function applyUnit(){
    const unit = $('#global_target_type').val();
    const meta = unitMeta(unit);
    $('#sgfTotalLabel').text(meta.label);
    $('.row-goal-label').text(meta.rowLabel);
    document.querySelectorAll('.row-target-type').forEach(inp=> inp.value = unit);
    recalcTotal();
}

function recalcTotal(){
    let entered = 0;
    document.querySelectorAll('.row-goal-value').forEach(inp=> entered += parseFloat(inp.value)||0);
    const required = parseFloat($('#total_target_value').val())||0;
    const unit = $('#global_target_type').val();
    const meta = unitMeta(unit);
    $('#sgfTotalStatusText').text('Total entered: '+entered.toFixed(2)+meta.suffix+' / '+required.toFixed(2)+meta.suffix);
    const pct = required>0 ? Math.min((entered/required)*100,100) : 0;
    $('#sgfTotalFill').css('width', pct+'%');
    const isMatch = required>0 && Math.abs(entered-required) < 0.01;
    $('#sgfTotalBar').removeClass('match mismatch').addClass(required>0 ? (isMatch?'match':'mismatch') : '');
    $('#sgfTotalStatusBadge').text(isMatch?'Match ✓':'Match nahi hua').removeClass('badge-secondary badge-success badge-warning').addClass(isMatch?'badge-success':(required>0?'badge-warning':'badge-secondary'));
    return isMatch;
}

$('#addRow').on('click', ()=>{
    document.querySelector('#targetRows').insertAdjacentHTML('beforeend', document.querySelector('#rowTemplate').innerHTML);
    wireRows();
    applyUnit();
});

$('#party_id').on('change', function(){
    if($(this).val()){ $('#sgfUnitSection').addClass('show'); }
});

$('#global_target_type').on('change', applyUnit);
$('#total_target_value').on('input', recalcTotal);

$('#period_type').on('change', function(){
    let s = new Date($('#starts_on').val()+'T00:00:00'), d = {daily:0,weekly:6,monthly:new Date(s.getFullYear(),s.getMonth()+1,0).getDate()-1,quarterly_3:89,quarterly_6:179,yearly:364}[this.value];
    if(!isNaN(s) && d!==undefined){ s.setDate(s.getDate()+d); $('#ends_on').val(s.toISOString().slice(0,10)); }
});

$('#targetForm').on('submit', function(e){
    const required = parseFloat($('#total_target_value').val())||0;
    if(required<=0){
        e.preventDefault();
        if(window.Swal) Swal.fire({icon:'warning',title:'Total Target Value daalein',text:'Pehle Total Target Value bharein, phir categories ke goals daalein.'});
        return false;
    }
    const ok = recalcTotal();
    if(!ok){
        e.preventDefault();
        if(window.Swal) Swal.fire({icon:'error',title:'Total match nahi ho raha',text:'Category goals ka total, Total Target Value ke barabar hona chahiye.'});
        return false;
    }
});

wireRows();
applyUnit();
recalcTotal();
</script>
@endpush
