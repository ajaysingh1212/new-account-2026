@php
    $estimate = $estimate ?? new \App\Models\PurchaseEstimate;
    $isEdit = $estimate->exists;
    $lines = old('item_id')
        ? collect(old('item_id'))->map(fn($id, $i) => [
            'item_id' => $id,
            'description' => old("description.$i"),
            'quantity' => old("quantity.$i", 1),
            'unit' => old("unit.$i"),
            'unit_price' => old("unit_price.$i", 0),
            'discount_type' => old("discount_type.$i", 'percent'),
            'discount_value' => old("discount_value.$i", 0),
            'tax_percent' => old("tax_percent.$i", 0),
        ])
        : ($isEdit ? $estimate->items->map(fn($line) => [
            'item_id' => $line->item_id,
            'description' => $line->description,
            'quantity' => (float) $line->quantity,
            'unit' => $line->unit,
            'unit_price' => (float) $line->unit_price,
            'discount_type' => $line->discount_type,
            'discount_value' => (float) $line->discount_value,
            'tax_percent' => (float) $line->tax_percent,
        ]) : collect());
@endphp
@push('styles')
<style>.trade-shell{background:#fff;border:1px solid #e8edf5;border-radius:8px;box-shadow:0 16px 36px rgba(15,23,42,.08);overflow:hidden}.trade-head{background:#174151;color:#fff;padding:22px 24px;display:flex;justify-content:space-between;gap:16px;align-items:center}.trade-head h2{font-weight:800;margin:0;font-size:24px}.trade-head small{color:#c7d8df}.trade-section{padding:20px 24px;border-bottom:1px solid #edf1f7}.trade-title{font-size:12px;font-weight:800;color:#0f766e;text-transform:uppercase;letter-spacing:.6px;margin-bottom:12px}.trade-table{border-collapse:separate;border-spacing:0 14px}.trade-table thead{display:none}.trade-table tbody tr{display:grid;grid-template-columns:minmax(360px,2fr) minmax(220px,1fr) 90px 80px 110px 100px 100px 46px;gap:12px;align-items:end;background:#fbfdff;border:1px solid #e5ebf3;border-radius:8px;padding:14px}.trade-table td{display:block;border:0!important;padding:0!important}.trade-table td:before{content:attr(data-label);display:block;font-size:10px;text-transform:uppercase;color:#667085;font-weight:800;margin-bottom:5px}.total-box{background:#0f172a;color:#fff;border-radius:8px;padding:16px}.total-row{display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid rgba(255,255,255,.08)}.total-row:last-child{border-bottom:0;color:#5eead4;font-weight:800;font-size:18px}.form-control{border-radius:6px}.icon-btn{width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;border-radius:8px}@media(max-width:992px){.trade-table tbody tr{grid-template-columns:1fr 1fr}}@media(max-width:576px){.trade-table tbody tr{grid-template-columns:1fr}}</style>
@endpush

<form method="POST" action="{{ $isEdit ? route('admin.purchase-estimates.update', $estimate) : route('admin.purchase-estimates.store') }}" enctype="multipart/form-data">
@csrf
@if($isEdit) @method('PUT') @endif
<div class="trade-shell">
    <div class="trade-head">
        <div>
            <h2><i class="fas fa-file-contract mr-2"></i>{{ $isEdit ? 'Edit Purchase Estimate' : 'Purchase Estimate' }}</h2>
            <small>Draft purchase quotation. Stock and party ledger post only after Convert to Purchase.</small>
        </div>
        <a href="{{ route('admin.purchase-estimates.index') }}" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back</a>
    </div>
    <div class="trade-section">
        <div class="trade-title">Estimate Details</div>
        <div class="row">
            <div class="col-md-4 form-group"><label>Party</label><select name="party_id" class="form-control select2"><option value="">Cash/No Party</option>@foreach($parties as $party)<option value="{{ $party->id }}" @selected((string)old('party_id',$estimate->party_id ?? '')===(string)$party->id)>{{ $party->display_name }} | {{ $party->phone }}</option>@endforeach</select></div>
            <div class="col-md-2 form-group"><label>Estimate No</label><input name="estimate_no" class="form-control" value="{{ old('estimate_no',$estimate->estimate_no ?: $estimateNo) }}"></div>
            <div class="col-md-2 form-group"><label>Date</label><input type="date" name="estimate_date" class="form-control" value="{{ old('estimate_date', $estimate->estimate_date?->format('Y-m-d') ?: now()->toDateString()) }}" required></div>
            <div class="col-md-2 form-group"><label>Valid Until</label><input type="date" name="valid_until" class="form-control" value="{{ old('valid_until', $estimate->valid_until?->format('Y-m-d') ?: now()->addDays(15)->toDateString()) }}"></div>
            <div class="col-md-2 form-group"><label>Reference</label><input name="reference_no" class="form-control" value="{{ old('reference_no',$estimate->reference_no ?? '') }}"></div>
        </div>
        <div class="row">
            <div class="col-md-3 form-group"><label>Cost Center</label><select name="cost_center_id" class="form-control"><option value="">Select</option>@foreach($costCenters as $cc)<option value="{{ $cc->id }}" @selected((string)old('cost_center_id',$estimate->cost_center_id ?? '')===(string)$cc->id)>{{ $cc->name }}</option>@endforeach</select></div>
            <div class="col-md-3 form-group"><label>Sub Cost Center</label><select name="sub_cost_center_id" class="form-control"><option value="">Select</option>@foreach($subCostCenters as $scc)<option value="{{ $scc->id }}" @selected((string)old('sub_cost_center_id',$estimate->sub_cost_center_id ?? '')===(string)$scc->id)>{{ $scc->name }}</option>@endforeach</select></div>
            <div class="col-md-3 form-group"><label>Phone</label><input name="phone" class="form-control" value="{{ old('phone',$estimate->phone ?? '') }}"></div>
            <div class="col-md-3 form-group"><label>Attachment</label><input type="file" name="attachment" class="form-control">@if($isEdit && $estimate->attachment)<small><a target="_blank" href="{{ asset('storage/'.$estimate->attachment) }}">Current attachment</a></small>@endif</div>
        </div>
        <div class="row"><div class="col-md-6 form-group"><label>Billing Address</label><textarea name="billing_address" class="form-control">{{ old('billing_address',$estimate->billing_address ?? '') }}</textarea></div><div class="col-md-6 form-group"><label>Shipping Address</label><textarea name="shipping_address" class="form-control">{{ old('shipping_address',$estimate->shipping_address ?? '') }}</textarea></div></div>
    </div>
    <div class="trade-section">
        <div class="d-flex justify-content-between align-items-center mb-2"><div class="trade-title mb-0">Items</div><button type="button" id="addLine" class="btn btn-outline-primary btn-sm"><i class="fas fa-plus"></i> Add Row</button></div>
        <div class="table-responsive"><table class="table trade-table" id="lineTable"><thead><tr><th>Item</th><th>Description</th><th>Qty</th><th>Unit</th><th>Price</th><th>Disc</th><th>Tax %</th><th></th></tr></thead><tbody></tbody></table></div>
    </div>
    <div class="trade-section">
        <div class="row">
            <div class="col-md-3 form-group"><label>Overall Discount</label><input type="number" step="0.01" name="discount_amount" class="form-control" value="{{ old('discount_amount',$estimate->discount_amount ?? 0) }}"></div>
            <div class="col-md-3 form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="3">{{ old('notes',$estimate->notes ?? '') }}</textarea></div>
            <div class="col-md-3 form-group"><label>Terms</label><textarea name="terms" class="form-control" rows="3">{{ old('terms',$estimate->terms ?? '') }}</textarea></div>
            <div class="col-md-3"><div class="total-box"><div class="total-row"><span>Subtotal</span><b id="uiSubtotal">Rs 0.00</b></div><div class="total-row"><span>Tax</span><b id="uiTax">Rs 0.00</b></div><div class="total-row"><span>Total</span><b id="uiTotal">Rs 0.00</b></div></div></div>
        </div>
        @include('admin.partials.entry-visibility', ['entry' => $estimate->exists ? $estimate : null])
    </div>
    <div class="p-4 text-right"><button class="btn btn-primary btn-lg"><i class="fas fa-save mr-1"></i>{{ $isEdit ? 'Update Purchase Estimate' : 'Save Purchase Estimate' }}</button></div>
</div>
</form>

<template id="lineTpl"><tr>
    <td data-label="Item"><select name="item_id[]" class="form-control item-select" required><option value="">Select purchasable item</option>@foreach($items as $it)<option value="{{ $it->id }}" data-unit="{{ $it->unit }}" data-price="{{ $it->purchase_price }}" data-tax="{{ $it->purchase_gst_percent }}">{{ $it->name }} | {{ $it->item_code }} | Stock {{ $it->current_stock }}</option>@endforeach</select></td>
    <td data-label="Description"><input name="description[]" class="form-control"></td>
    <td data-label="Qty"><input type="number" step="0.001" name="quantity[]" class="form-control" value="1" required></td>
    <td data-label="Unit"><input name="unit[]" class="form-control"></td>
    <td data-label="Price"><input type="number" step="0.01" name="unit_price[]" class="form-control" required></td>
    <td data-label="Discount"><select name="discount_type[]" class="form-control"><option value="percent">%</option><option value="flat">Flat</option></select><input type="number" step="0.01" name="discount_value[]" class="form-control mt-1" value="0"></td>
    <td data-label="Tax %"><input type="number" step="0.01" min="0" name="tax_percent[]" class="form-control" value="0"></td>
    <td data-label="Action"><button type="button" class="btn btn-danger btn-sm remove-row icon-btn"><i class="fas fa-trash"></i></button></td>
</tr></template>

@push('scripts')
<script>
const PREFILL_LINES = @json($lines->values());
function money(n){return 'Rs '+(Number(n)||0).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2})}
function calc(){let sub=0,tax=0,discount=0;$('#lineTable tbody tr').each(function(){let r=$(this),q=+r.find('[name="quantity[]"]').val()||0,p=+r.find('[name="unit_price[]"]').val()||0,d=+r.find('[name="discount_value[]"]').val()||0,dt=r.find('[name="discount_type[]"]').val(),tx=+r.find('[name="tax_percent[]"]').val()||0,b=q*p,da=dt==='flat'?d:b*d/100;sub+=b;discount+=da;tax+=Math.max(0,b-da)*tx/100});let od=+$('[name="discount_amount"]').val()||0;$('#uiSubtotal').text(money(sub));$('#uiTax').text(money(tax));$('#uiTotal').text(money(Math.max(0,sub-discount-od+tax)))}
function addLine(data={}){let $row=$($('#lineTpl').html());$('#lineTable tbody').append($row);if(data.item_id){$row.find('[name="item_id[]"]').val(data.item_id).trigger('change')}['description','quantity','unit','unit_price','discount_type','discount_value','tax_percent'].forEach(k=>$row.find(`[name="${k}[]"]`).val(data[k]??$row.find(`[name="${k}[]"]`).val()));calc()}
$('#addLine').click(()=>addLine());
$(document).on('input change','#lineTable input,#lineTable select,[name="discount_amount"]',calc);
$(document).on('click','.remove-row',function(){$(this).closest('tr').remove();calc()});
$(document).on('change','.item-select',function(){let o=$(this).find(':selected'),r=$(this).closest('tr');r.find('[name="unit[]"]').val(o.data('unit'));r.find('[name="unit_price[]"]').val(o.data('price'));r.find('[name="tax_percent[]"]').val(o.data('tax'));calc()});
if(PREFILL_LINES.length){PREFILL_LINES.forEach(addLine)}else{addLine()}
</script>
@endpush
