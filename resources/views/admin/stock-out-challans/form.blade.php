@php($isEdit = $stockOutChallan->exists)
@push('styles')
<style>.so-shell{background:#fff;border:1px solid #e8edf5;border-radius:12px;box-shadow:0 14px 34px rgba(15,23,42,.08);overflow:hidden}.so-head{background:#111827;color:#fff;padding:22px 24px}.so-head h2{margin:0;font-weight:850}.so-section{padding:20px 24px;border-bottom:1px solid #edf2f7}.so-title{font-size:12px;text-transform:uppercase;font-weight:850;color:#0f766e;letter-spacing:.7px}.total-box{background:#0f172a;color:#fff;border-radius:10px;padding:16px}.total-row{display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid rgba(255,255,255,.08)}.total-row:last-child{border-bottom:0;color:#5eead4;font-weight:850;font-size:18px}</style>
@endpush
<form method="POST" action="{{ $isEdit ? route('admin.stock-out-challans.update', $stockOutChallan) : route('admin.stock-out-challans.store') }}">
@csrf @if($isEdit) @method('PUT') @endif
<div class="so-shell">
    <div class="so-head">
        <h2><i class="fas fa-dolly mr-2"></i>{{ $isEdit ? 'Edit Special Stock Out' : 'Special Stock Out' }}</h2>
        <small>Stock will reduce immediately. No party ledger entry will be created.</small>
    </div>
    <div class="so-section">
        <div class="row">
            <div class="col-md-4 form-group"><label>Select Party (optional)</label><select name="party_id" class="form-control select2"><option value="">Manual name / no party</option>@foreach($parties as $party)<option value="{{ $party->id }}" @selected((string)old('party_id',$stockOutChallan->party_id)===(string)$party->id)>{{ $party->display_name }} | {{ $party->phone }}</option>@endforeach</select></div>
            <div class="col-md-4 form-group"><label>Manual Party / Receiver Name</label><input name="party_name" class="form-control" value="{{ old('party_name',$stockOutChallan->party_name) }}" placeholder="Enter name if party not in master"></div>
            <div class="col-md-2 form-group"><label>Challan No</label><input name="challan_no" class="form-control" value="{{ old('challan_no',$challanNo) }}"></div>
            <div class="col-md-2 form-group"><label>Date</label><input type="date" name="challan_date" class="form-control" value="{{ old('challan_date',$stockOutChallan->challan_date?->toDateString() ?: now()->toDateString()) }}" required></div>
        </div>
        <div class="row">
            <div class="col-md-3 form-group"><label>Reference</label><input name="reference_no" class="form-control" value="{{ old('reference_no',$stockOutChallan->reference_no) }}"></div>
            <div class="col-md-3 form-group"><label>Phone</label><input name="phone" class="form-control" value="{{ old('phone',$stockOutChallan->phone) }}"></div>
            <div class="col-md-3 form-group"><label>Billing Address</label><textarea name="billing_address" class="form-control" rows="2">{{ old('billing_address',$stockOutChallan->billing_address) }}</textarea></div>
            <div class="col-md-3 form-group"><label>Shipping Address</label><textarea name="shipping_address" class="form-control" rows="2">{{ old('shipping_address',$stockOutChallan->shipping_address) }}</textarea></div>
        </div>
    </div>
    <div class="so-section">
        <div class="d-flex justify-content-between mb-2"><div class="so-title">Items</div><button type="button" id="addLine" class="btn btn-outline-primary btn-sm"><i class="fas fa-plus"></i> Add Row</button></div>
        <div class="table-responsive"><table class="table" id="lineTable"><thead><tr><th>Item</th><th>Description</th><th>Qty</th><th>Serial / VTS</th><th>Unit</th><th>Display Rate</th><th>Total</th><th></th></tr></thead><tbody>
            @foreach($stockOutChallan->items ?? [] as $line)
            <tr><td><select name="item_id[]" class="form-control item-select" required><option value="">Select</option>@foreach($items as $it)<option value="{{ $it->id }}" data-unit="{{ $it->unit }}" data-price="{{ $it->sale_price }}" @selected($line->item_id === $it->id)>{{ $it->name }} | Stock {{ $it->current_stock }}</option>@endforeach</select></td><td><input name="description[]" class="form-control" value="{{ $line->description }}"></td><td><input type="number" step="1" min="1" name="quantity[]" class="form-control qty" value="{{ $line->quantity }}" required></td><td><input type="hidden" name="selected_units[]" class="selected-units-json" value='@json($line->selected_units ?? [])'><button type="button" class="btn btn-outline-primary btn-sm choose-units"><i class="fas fa-barcode"></i> Units (<span class="unit-count">0</span>)</button><div class="serial-summary"></div></td><td><input name="unit[]" class="form-control" value="{{ $line->unit }}"></td><td><input type="number" step="0.01" name="unit_price[]" class="form-control price" value="{{ $line->unit_price }}"></td><td class="line-total">Rs 0.00</td><td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button></td></tr>
            @endforeach
        </tbody></table></div>
    </div>
    <div class="so-section">
        <div class="row">
            <div class="col-md-8 form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="3">{{ old('notes',$stockOutChallan->notes) }}</textarea></div>
            <div class="col-md-4"><div class="total-box"><div class="total-row"><span>Display Value</span><b id="uiTotal">Rs 0.00</b></div><div class="small text-muted mt-2">Ledger is not posted from this document.</div></div></div>
        </div>
        @include('admin.partials.entry-visibility', ['entry' => $stockOutChallan])
    </div>
    <div class="p-4 text-right"><a href="{{ route('admin.stock-out-challans.index') }}" class="btn btn-light">Cancel</a><button class="btn btn-primary btn-lg"><i class="fas fa-save mr-1"></i>{{ $isEdit ? 'Update' : 'Save' }}</button></div>
</div>
</form>
<template id="lineTpl"><tr><td><select name="item_id[]" class="form-control item-select" required><option value="">Select</option>@foreach($items as $it)<option value="{{ $it->id }}" data-unit="{{ $it->unit }}" data-price="{{ $it->sale_price }}">{{ $it->name }} | Stock {{ $it->current_stock }}</option>@endforeach</select></td><td><input name="description[]" class="form-control"></td><td><input type="number" step="1" min="1" name="quantity[]" class="form-control qty" value="1" required></td><td><input type="hidden" name="selected_units[]" class="selected-units-json" value="[]"><button type="button" class="btn btn-outline-primary btn-sm choose-units"><i class="fas fa-barcode"></i> Units (<span class="unit-count">0</span>)</button><div class="serial-summary"></div></td><td><input name="unit[]" class="form-control"></td><td><input type="number" step="0.01" name="unit_price[]" class="form-control price" value="0"></td><td class="line-total">Rs 0.00</td><td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button></td></tr></template>
@include('admin.partials.serial-unit-drawer')
@push('scripts')
<script>
function money(n){return 'Rs '+(Number(n)||0).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2})}
function calc(){let total=0;$('#lineTable tbody tr').each(function(){let r=$(this),q=+r.find('.qty').val()||0,p=+r.find('.price').val()||0,line=q*p;total+=line;r.find('.line-total').text(money(line))});$('#uiTotal').text(money(total))}
function addLine(){ $('#lineTable tbody').append($('#lineTpl').html()); calc(); }
$('#addLine').click(addLine);$(document).on('input change','#lineTable input,#lineTable select',calc);$(document).on('click','.remove-row',function(){$(this).closest('tr').remove();calc()});$(document).on('change','.item-select',function(){let o=$(this).find(':selected'),r=$(this).closest('tr');r.find('[name="unit[]"]').val(o.data('unit'));r.find('.price').val(o.data('price')||0);calc()});if(!$('#lineTable tbody tr').length){addLine()}else{calc()}
</script>
@endpush
