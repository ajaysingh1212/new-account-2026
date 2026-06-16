@php
    $isEdit = isset($invoice);
    $lines = old('item_id')
        ? collect(old('item_id'))->map(fn($id, $i) => [
            'item_id' => $id,
            'description' => old("description.$i"),
            'quantity' => old("quantity.$i", 1),
            'unit' => old("unit.$i"),
            'unit_price' => old("unit_price.$i", 0),
            'discount_type' => old("discount_type.$i", 'percent'),
            'discount_value' => old("discount_value.$i", 0),
            'tax_mode' => old("tax_mode.$i", 'with_gst'),
            'tax_percent' => old("tax_percent.$i", 0),
            'selected_units' => json_decode(old("selected_units.$i", '[]'), true) ?: [],
        ])
        : ($isEdit ? $invoice->items->map(fn($line) => [
            'item_id' => $line->item_id,
            'description' => $line->description,
            'quantity' => (float) $line->quantity,
            'unit' => $line->unit,
            'unit_price' => (float) $line->unit_price,
            'discount_type' => $line->discount_type,
            'discount_value' => (float) $line->discount_value,
            'tax_mode' => (float) $line->tax_percent > 0 ? 'with_gst' : 'without_gst',
            'tax_percent' => (float) $line->tax_percent,
            'selected_units' => $line->selected_units ?? [],
        ]) : collect());
@endphp

@push('styles')
<style>
.trade-shell{background:#fff;border:1px solid #e8edf5;border-radius:8px;box-shadow:0 16px 36px rgba(15,23,42,.08);overflow:hidden}.trade-head{background:#122033;color:#fff;padding:22px 24px;display:flex;justify-content:space-between;gap:16px;align-items:center}.trade-head h2{font-weight:800;margin:0;font-size:24px;letter-spacing:0}.trade-head small{color:#a9b7ca}.trade-section{padding:20px 24px;border-bottom:1px solid #edf1f7}.trade-title{font-size:12px;font-weight:800;color:#0f766e;text-transform:uppercase;letter-spacing:.6px;margin-bottom:12px}.trade-table{border-collapse:separate;border-spacing:0 14px}.trade-table thead{display:none}.trade-table tbody tr{display:grid;grid-template-columns:minmax(320px,1.8fr) minmax(190px,1fr) 80px minmax(230px,1.3fr) 75px 105px 85px 120px 70px 46px;gap:12px;align-items:end;background:#fbfdff;border:1px solid #e5ebf3;border-radius:8px;padding:14px}.trade-table td{display:block;border:0!important;padding:0!important}.trade-table td:before{content:attr(data-label);display:block;font-size:10px;text-transform:uppercase;color:#667085;font-weight:800;margin-bottom:5px}.trade-table .item-cell,.trade-table .desc-cell{min-width:0}.total-box{background:#0f172a;color:#fff;border-radius:8px;padding:16px}.total-row{display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid rgba(255,255,255,.08)}.total-row:last-child{border-bottom:0;color:#5eead4;font-weight:800;font-size:18px}.unit-drawer{position:fixed;top:0;right:-480px;width:460px;max-width:calc(100vw - 20px);height:100vh;background:#fff;z-index:1060;box-shadow:-18px 0 36px rgba(15,23,42,.22);transition:right .2s ease;display:flex;flex-direction:column}.unit-drawer.open{right:0}.unit-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.35);z-index:1059;display:none}.unit-backdrop.open{display:block}.unit-head{padding:18px;border-bottom:1px solid #e5e7eb;background:#f8fafc}.unit-list{padding:12px 14px;overflow:auto;flex:1}.unit-card{border:1px solid #e5e7eb;border-radius:8px;padding:10px;margin-bottom:10px;display:grid;grid-template-columns:28px 1fr;gap:10px}.unit-card.sold{background:#f9fafb;color:#98a2b3}.unit-card.selected{border-color:#0f766e;background:#f0fdfa}.unit-meta{font-size:12px;color:#667085}.selected-pill{display:inline-flex;align-items:center;gap:6px;border:1px solid #99f6e4;background:#f0fdfa;color:#0f766e;border-radius:999px;padding:3px 9px;font-size:12px;margin:2px}.icon-btn{width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;border-radius:8px}.filter-pills .btn{border-radius:999px}.form-control,.custom-select{border-radius:6px}.wide-select{min-width:100%}.tax-percent[readonly]{background:#f8fafc}@media(max-width:1400px){.trade-table tbody tr{grid-template-columns:1fr 1fr 80px 1fr 75px 105px 85px 120px 70px 46px}}@media(max-width:768px){.trade-table tbody tr{grid-template-columns:1fr}}
</style>
@endpush

<form method="POST" action="{{ $isEdit ? route('admin.sales.update', $invoice) : route('admin.sales.store') }}" enctype="multipart/form-data">
@csrf
@if($isEdit) @method('PUT') @endif
<div class="trade-shell">
    <div class="trade-head">
        <div>
            <h2><i class="fas fa-file-invoice-dollar mr-2"></i>{{ $isEdit ? 'Edit Sales Invoice' : 'Sales Invoice' }}</h2>
            <small>Finished goods only, serial-aware selection, stock out and party ledger posting.</small>
        </div>
        <a href="{{ route('admin.sales.index') }}" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back</a>
    </div>

    <div class="trade-section">
        <div class="trade-title">Invoice Details</div>
        <div class="row">
            <div class="col-md-2 form-group"><label>Type</label><select name="sale_type" class="form-control"><option value="credit" @selected(old('sale_type',$invoice->sale_type ?? 'credit')==='credit')>Credit</option><option value="cash" @selected(old('sale_type',$invoice->sale_type ?? '')==='cash')>Cash</option></select></div>
            <div class="col-md-4 form-group"><label>Party</label><select name="party_id" class="form-control select2"><option value="">Cash/No Party</option>@foreach($parties as $party)<option value="{{ $party->id }}" @selected((string)old('party_id',$invoice->party_id ?? '')===(string)$party->id)>{{ $party->display_name }} | {{ $party->phone }}</option>@endforeach</select></div>
            <div class="col-md-2 form-group"><label>Invoice No</label><input name="invoice_no" class="form-control" value="{{ old('invoice_no',$invoiceNo) }}"></div>
            <div class="col-md-2 form-group"><label>Billing Date</label><input type="date" name="billing_date" class="form-control" value="{{ old('billing_date', isset($invoice) ? $invoice->billing_date?->format('Y-m-d') : now()->toDateString()) }}" required></div>
            <div class="col-md-2 form-group"><label>Reference</label><input name="reference_no" class="form-control" value="{{ old('reference_no',$invoice->reference_no ?? '') }}"></div>
        </div>
        <div class="row">
            <div class="col-md-3 form-group"><label>Cost Center</label><select name="cost_center_id" class="form-control"><option value="">Select</option>@foreach($costCenters as $cc)<option value="{{ $cc->id }}" @selected((string)old('cost_center_id',$invoice->cost_center_id ?? '')===(string)$cc->id)>{{ $cc->name }}</option>@endforeach</select></div>
            <div class="col-md-3 form-group"><label>Sub Cost Center</label><select name="sub_cost_center_id" class="form-control"><option value="">Select</option>@foreach($subCostCenters as $scc)<option value="{{ $scc->id }}" @selected((string)old('sub_cost_center_id',$invoice->sub_cost_center_id ?? '')===(string)$scc->id)>{{ $scc->name }}</option>@endforeach</select></div>
            <div class="col-md-3 form-group"><label>Phone</label><input name="phone" class="form-control" value="{{ old('phone',$invoice->phone ?? '') }}"></div>
            <div class="col-md-3 form-group"><label>Attachment</label><input type="file" name="attachment" class="form-control">@if($isEdit && $invoice->attachment)<small><a target="_blank" href="{{ asset('storage/'.$invoice->attachment) }}">Current attachment</a></small>@endif</div>
        </div>
        @if($mergedCompanies->count())
        <div class="border rounded p-3 mt-2">
            <label class="mb-2"><input type="checkbox" name="inter_company_transfer" value="1" id="interCompanyTransfer" @checked(old('inter_company_transfer', $invoice->inter_company_transfer ?? false))> Auto purchase in merged company</label>
            <div id="mergedCompanyBox" class="row" style="display:none">
                @foreach($mergedCompanies as $company)
                    <div class="col-md-4">
                        <label class="d-block border rounded p-2">
                            <input type="checkbox" name="target_company_ids[]" value="{{ $company->id }}" @checked(in_array($company->id, old('target_company_ids', $invoice->inter_company_target_company_ids ?? [])))>
                            <strong>{{ $company->name }}</strong><br><small>{{ $company->phone ?: 'No phone' }} | GST {{ $company->gst_number ?: '-' }}</small>
                        </label>
                        <div class="pl-3 pr-2 pb-2">
                            <label class="small mb-1">Purchase visible to roles</label>
                            <select name="purchase_visible_to_roles[{{ $company->id }}][]" class="form-control select2" multiple style="width:100%">
                                @foreach(($interCompanyVisibility[$company->id]['roles'] ?? collect()) as $role)
                                    <option value="{{ $role->id }}" @selected(in_array($role->id, old("purchase_visible_to_roles.{$company->id}", $interCompanySelectedVisibility[$company->id]['roles'] ?? [])))>{{ $role->name }}</option>
                                @endforeach
                            </select>
                            <label class="small mt-2 mb-1">Purchase visible to users</label>
                            <select name="purchase_visible_to_users[{{ $company->id }}][]" class="form-control select2" multiple style="width:100%">
                                @foreach(($interCompanyVisibility[$company->id]['users'] ?? collect()) as $user)
                                    <option value="{{ $user->id }}" @selected(in_array($user->id, old("purchase_visible_to_users.{$company->id}", $interCompanySelectedVisibility[$company->id]['users'] ?? [])))>{{ $user->name }} | {{ $user->email }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <div class="trade-section">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="trade-title mb-0">Items</div>
            <button type="button" id="addLine" class="btn btn-outline-primary btn-sm"><i class="fas fa-plus"></i> Add Row</button>
        </div>
        <div class="table-responsive">
            <table class="table trade-table" id="lineTable">
                <thead><tr><th class="item-cell">Item</th><th class="desc-cell">Description</th><th>Qty</th><th>Units</th><th>Unit</th><th>Price</th><th>Disc</th><th>GST Mode</th><th>Tax %</th><th></th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="trade-section">
        <div class="row">
            <div class="col-md-4 form-group"><label>Overall Discount</label><input type="number" step="0.01" name="discount_amount" class="form-control" value="{{ old('discount_amount',$invoice->discount_amount ?? 0) }}"></div>
            <div class="col-md-4 form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="3">{{ old('notes',$invoice->notes ?? '') }}</textarea></div>
            <div class="col-md-4"><div class="total-box"><div class="total-row"><span>Subtotal</span><b id="uiSubtotal">Rs 0.00</b></div><div class="total-row"><span>Tax</span><b id="uiTax">Rs 0.00</b></div><div class="total-row"><span>Total Weight</span><b id="uiWeight">0.000 kg</b></div><div class="total-row"><span>Total</span><b id="uiTotal">Rs 0.00</b></div></div></div>
            <div class="col-md-8 form-group"><label>Terms</label><select id="termsTemplate" class="form-control mb-2"><option value="">Manual / no template</option>@foreach($termsTemplates as $template)<option value="{{ e($template->content) }}" @selected(!$isEdit && $template->is_default)>{{ $template->title }}{{ $template->is_default ? ' (Default)' : '' }}</option>@endforeach</select><textarea name="terms" id="termsBox" class="form-control" rows="2">{{ old('terms',$invoice->terms ?? ($termsTemplates->firstWhere('is_default', true)?->content ?? '')) }}</textarea></div>
        </div>
        @include('admin.partials.entry-visibility', ['entry' => $invoice ?? null])
    </div>

    <div class="p-4 text-right"><button class="btn btn-primary btn-lg"><i class="fas fa-save mr-1"></i>{{ $isEdit ? 'Update Sale' : 'Post Sale' }}</button></div>
</div>
</form>

<div class="unit-backdrop" id="unitBackdrop"></div>
<aside class="unit-drawer" id="unitDrawer">
    <div class="unit-head">
        <div class="d-flex justify-content-between align-items-start">
            <div><b id="drawerTitle">Finished Goods Units</b><div class="unit-meta" id="drawerHint"></div></div>
            <button type="button" class="btn btn-light icon-btn" id="closeDrawer"><i class="fas fa-times"></i></button>
        </div>
        <div class="filter-pills mt-3 btn-group btn-group-sm">
            <button type="button" class="btn btn-primary unit-filter" data-filter="available">Available</button>
            <button type="button" class="btn btn-outline-secondary unit-filter" data-filter="selected">Selected</button>
            <button type="button" class="btn btn-outline-secondary unit-filter" data-filter="sold">Sold</button>
            <button type="button" class="btn btn-outline-secondary unit-filter" data-filter="all">All</button>
        </div>
    </div>
    <div class="unit-list" id="unitList"></div>
</aside>

<template id="lineTpl">
<tr>
    <td class="item-cell" data-label="Item"><select name="item_id[]" class="form-control item-select wide-select" required><option value="">Select finished goods</option>@foreach($items as $it)<option value="{{ $it->id }}" data-unit="{{ $it->unit }}" data-price="{{ $it->sale_price }}" data-tax="{{ (float) $it->sale_gst_percent ?: 18 }}" data-weight="{{ (float) ($it->per_quantity_weight ?? 0) }}" data-gps="{{ str_contains(strtolower(implode(' ', array_filter([$it->name, $it->item_code, $it->sku, $it->brand, $it->model, $it->description]))), 'gps') ? 1 : 0 }}">{{ $it->name }} | {{ $it->item_code }} | Stock {{ $it->current_stock }} @if($it->per_quantity_weight)| {{ $it->per_quantity_weight }} kg/qty @endif</option>@endforeach</select></td>
    <td class="desc-cell" data-label="Description"><input name="description[]" class="form-control"></td>
    <td class="num-cell" data-label="Qty"><input type="number" step="1" min="1" name="quantity[]" class="form-control line-qty" value="1" required></td>
    <td data-label="Serial Selection"><button type="button" class="btn btn-outline-info icon-btn choose-units" title="Select serials"><i class="fas fa-barcode"></i></button><input type="hidden" name="selected_units[]" class="selected-units-json"><div class="selected-units mt-1"></div></td>
    <td class="mini-cell" data-label="Unit"><input name="unit[]" class="form-control"></td>
    <td class="num-cell" data-label="Price"><input type="number" step="0.01" name="unit_price[]" class="form-control" required></td>
    <td class="mini-cell" data-label="Discount"><select name="discount_type[]" class="form-control"><option value="percent">%</option><option value="flat">Flat</option></select><input type="number" step="0.01" name="discount_value[]" class="form-control mt-1" value="0"></td>
    <td class="mini-cell" data-label="GST Mode"><select name="tax_mode[]" class="form-control tax-mode"><option value="with_gst" selected>With GST</option><option value="without_gst">Without GST</option></select></td>
    <td class="mini-cell" data-label="Tax %"><input type="number" step="0.01" name="tax_percent[]" class="form-control tax-percent" value="18" readonly></td>
    <td data-label="Action"><button type="button" class="btn btn-danger btn-sm remove-row icon-btn"><i class="fas fa-trash"></i></button></td>
</tr>
</template>

@push('scripts')
<script>
const UNIT_POOL = @json($unitPool);
const PREFILL_LINES = @json($lines->values());
const ITEM_META = @json($itemMeta);
let activeRow = null;
let activeFilter = 'available';

function money(n){return 'Rs '+(Number(n)||0).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2})}
function rowSelected($row){try{return JSON.parse($row.find('.selected-units-json').val()||'[]')}catch(e){return []}}
function setRowSelected($row, units){$row.find('.selected-units-json').val(JSON.stringify(units));$row.find('.selected-units').html(units.map(u=>`<span class="selected-pill">${u.serial_no||'No serial'}${u.vts_sim?' / '+u.vts_sim:''} <small>${u.production_batch_no||''}</small></span>`).join(''));calc()}
function autoSelectUnits($row){let itemId=$row.find('[name="item_id[]"]').val(),qty=parseInt($row.find('[name="quantity[]"]').val())||1;if(!itemId)return;let used=[];$('#lineTable tbody tr').not($row).each(function(){used=used.concat(rowSelected($(this)).map(u=>u.key))});let requiresGps=ITEM_META[itemId]&&ITEM_META[itemId].requires_gps;let units=(UNIT_POOL[itemId]||[]).filter(u=>!u.sold&&!used.includes(u.key)&&(!requiresGps||u.vts_sim)).slice(0,qty);setRowSelected($row,units)}
function syncTaxMode($row){let mode=$row.find('[name="tax_mode[]"]').val(),$tax=$row.find('[name="tax_percent[]"]');if(mode==='without_gst'){$tax.val(0)}else if((+$tax.val()||0)<=0){let itemTax=+$row.find('.item-select option:selected').data('tax')||18;$tax.val(itemTax)}}
function calc(){let sub=0,tax=0,weight=0;$('#lineTable tbody tr').each(function(){let r=$(this);syncTaxMode(r);let q=+r.find('[name="quantity[]"]').val()||0,p=+r.find('[name="unit_price[]"]').val()||0,d=+r.find('[name="discount_value[]"]').val()||0,dt=r.find('[name="discount_type[]"]').val(),tx=+r.find('[name="tax_percent[]"]').val()||0,itemWeight=+r.find('.item-select option:selected').data('weight')||0,b=q*p,da=dt==='flat'?d:b*d/100,g=Math.max(0,b-da),ta=tx>0?g*tx/(100+tx):0;sub+=g-ta;tax+=ta;weight+=q*itemWeight});let od=+$('[name="discount_amount"]').val()||0;$('#uiSubtotal').text(money(sub));$('#uiTax').text(money(tax));$('#uiWeight').text(weight.toLocaleString('en-IN',{minimumFractionDigits:3,maximumFractionDigits:3})+' kg');$('#uiTotal').text(money(Math.max(0,sub+tax-od)))}
function addLine(data={}){let $row=$($('#lineTpl').html());$('#lineTable tbody').append($row);if(data.item_id){$row.find('[name="item_id[]"]').val(data.item_id).trigger('change')}['description','quantity','unit','unit_price','discount_type','discount_value','tax_mode','tax_percent'].forEach(k=>$row.find(`[name="${k}[]"]`).val(data[k]??$row.find(`[name="${k}[]"]`).val()));syncTaxMode($row);setRowSelected($row,data.selected_units||[]);calc()}
function renderDrawer(){if(!activeRow)return;let itemId=activeRow.find('[name="item_id[]"]').val(),qty=parseInt(activeRow.find('[name="quantity[]"]').val())||1,selected=rowSelected(activeRow),selectedKeys=selected.map(u=>u.key),units=UNIT_POOL[itemId]||[];$('#drawerTitle').text(activeRow.find('.item-select option:selected').text()||'Finished Goods Units');$('#drawerHint').text(`Select ${qty} unit(s). ${selected.length} selected.`);let filtered=units.filter(u=>activeFilter==='all'||(activeFilter==='sold'&&u.sold)||(activeFilter==='selected'&&selectedKeys.includes(u.key))||(activeFilter==='available'&&!u.sold));$('#unitList').html(filtered.map(u=>{let checked=selectedKeys.includes(u.key),disabled=u.sold&&!checked;return `<label class="unit-card ${u.sold?'sold':''} ${checked?'selected':''}"><input type="checkbox" class="unit-check" data-key="${u.key}" ${checked?'checked':''} ${disabled?'disabled':''}><span><b>${u.serial_no||'No serial'} / ${u.batch_no||'No purchase batch'}</b><div class="unit-meta">VTS/SIM ${u.vts_sim||'-'} | Buyer ${u.buyer_code||'-'} | Production ${u.production_batch_no||'-'} | ${u.production_date||'-'}</div><div class="unit-meta">${u.sold?'Sold':'Available'} | Warehouse ${u.warehouse||'-'} | Cost ${money(u.cost_per_unit)}</div></span></label>`}).join('')||'<div class="text-muted p-3">No units found for this filter.</div>')}
function openDrawer($row){activeRow=$row;activeFilter='available';$('.unit-filter').removeClass('btn-primary').addClass('btn-outline-secondary');$('.unit-filter[data-filter="available"]').addClass('btn-primary').removeClass('btn-outline-secondary');$('#unitDrawer,#unitBackdrop').addClass('open');renderDrawer()}

$('#addLine').click(()=>addLine());
$(document).on('input change','#lineTable input,#lineTable select,[name="discount_amount"]',calc);
$(document).on('click','.remove-row',function(){$(this).closest('tr').remove();calc()});
$(document).on('change','.item-select',function(){let o=$(this).find(':selected'),r=$(this).closest('tr');r.find('[name="unit[]"]').val(o.data('unit'));r.find('[name="unit_price[]"]').val(o.data('price'));if(r.find('[name="tax_mode[]"]').val()!=='without_gst'){r.find('[name="tax_percent[]"]').val(o.data('tax')||18)}autoSelectUnits(r);calc()});
$(document).on('change','.tax-mode',function(){syncTaxMode($(this).closest('tr'));calc()});
$(document).on('click','.choose-units',function(){openDrawer($(this).closest('tr'))});
$(document).on('change','.line-qty',function(){autoSelectUnits($(this).closest('tr'));});
$(document).on('change','.unit-check',function(){let selected=rowSelected(activeRow),itemId=activeRow.find('[name="item_id[]"]').val(),units=UNIT_POOL[itemId]||[],unit=units.find(u=>u.key===$(this).data('key')),qty=parseInt(activeRow.find('[name="quantity[]"]').val())||1;if(this.checked){if(selected.length>=qty){this.checked=false;alert(`You can select only ${qty} unit(s).`);return}if(ITEM_META[itemId]&&ITEM_META[itemId].requires_gps&&!unit.vts_sim&&!confirm('Aap GPS product select kiye hain, lekin is unit me SIM/VTS number nahi hai. Kya aap isko select karna chahte hain?')){this.checked=false;return}selected.push(unit)}else{selected=selected.filter(u=>u.key!==$(this).data('key'))}setRowSelected(activeRow,selected);renderDrawer()});
$('#interCompanyTransfer').on('change',function(){$('#mergedCompanyBox').toggle(this.checked)}).trigger('change');
$('.unit-filter').click(function(){activeFilter=$(this).data('filter');$('.unit-filter').removeClass('btn-primary').addClass('btn-outline-secondary');$(this).addClass('btn-primary').removeClass('btn-outline-secondary');renderDrawer()});
$('#closeDrawer,#unitBackdrop').click(()=>$('#unitDrawer,#unitBackdrop').removeClass('open'));
$('#termsTemplate').on('change',function(){if(this.value){$('#termsBox').val(this.value)}});

if(PREFILL_LINES.length){PREFILL_LINES.forEach(addLine)}else{addLine()}
</script>
@endpush
