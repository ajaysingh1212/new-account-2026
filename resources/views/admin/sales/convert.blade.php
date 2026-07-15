@php
    $lineData = collect($lineData ?? []);
@endphp
@push('styles')
<style>
.convert-shell{background:#fff;border:1px solid #e8edf5;border-radius:16px;box-shadow:0 16px 36px rgba(15,23,42,.08);overflow:hidden}
.convert-head{background:linear-gradient(135deg,#0f172a,#0f766e);color:#fff;padding:22px 24px;display:flex;justify-content:space-between;gap:16px;align-items:center}
.convert-head h2{font-weight:850;margin:0;font-size:24px}
.convert-head small{color:#cbd5e1}
.convert-section{padding:20px 24px;border-bottom:1px solid #edf1f7}
.convert-title{font-size:12px;font-weight:800;color:#0f766e;text-transform:uppercase;letter-spacing:.6px;margin-bottom:12px}
.convert-table{border-collapse:separate;border-spacing:0 14px}
.convert-table thead{display:none}
.convert-table tbody tr{display:grid;grid-template-columns:minmax(280px,1.6fr) minmax(170px,1fr) 90px minmax(210px,1.1fr) 90px 110px 80px 80px 120px 80px 46px;gap:12px;align-items:end;background:#fbfdff;border:1px solid #e5ebf3;border-radius:10px;padding:14px}
.convert-table td{display:block;border:0!important;padding:0!important}
.convert-table td:before{content:attr(data-label);display:block;font-size:10px;text-transform:uppercase;color:#667085;font-weight:800;margin-bottom:5px}
.total-box{background:#0f172a;color:#fff;border-radius:14px;padding:18px}
.total-row{display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid rgba(255,255,255,.08)}
.total-row:last-child{border-bottom:0;color:#5eead4;font-weight:900;font-size:18px}
.unit-pill{display:inline-flex;align-items:center;gap:5px;border:1px solid #99f6e4;background:#f0fdfa;color:#0f766e;border-radius:999px;padding:3px 8px;font-size:11px;margin:2px}
.line-meta{font-size:12px;color:#64748b}
.stock-badge{display:inline-flex;align-items:center;border-radius:999px;padding:3px 8px;font-size:11px;font-weight:800}
.stock-ok{background:#ecfdf5;color:#047857}
.stock-low{background:#fef2f2;color:#b91c1c}
@media(max-width:1400px){.convert-table tbody tr{grid-template-columns:1fr 1fr 90px 1fr 90px 110px 80px 80px 120px 80px 46px}}
@media(max-width:768px){.convert-table tbody tr{grid-template-columns:1fr}}
</style>
@endpush

<form method="POST" action="{{ $actionRoute }}" id="convertForm">
@csrf
<div class="convert-shell">
    <div class="convert-head">
        <div>
            <h2><i class="fas fa-exchange-alt mr-2"></i>Convert {{ $sourceLabel }} to Sale</h2>
            <small>Final quantity, available serial units, stock validation, and sale preview before posting.</small>
        </div>
        <a href="{{ $backRoute }}" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back</a>
    </div>

    <div class="convert-section">
        <div class="convert-title">Sale Header</div>
        <div class="row">
            <div class="col-md-3 form-group">
                <label>Sale Type</label>
                <select name="sale_type" class="form-control">
                    <option value="credit" @selected(old('sale_type', $source->party_id ? 'credit' : 'cash') === 'credit')>Credit</option>
                    <option value="cash" @selected(old('sale_type', $source->party_id ? '' : 'cash') === 'cash')>Cash</option>
                </select>
            </div>
            <div class="col-md-3 form-group">
                <label>Invoice No</label>
                <input name="invoice_no" class="form-control" value="{{ old('invoice_no', $saleNo) }}">
            </div>
            <div class="col-md-3 form-group">
                <label>Billing Date</label>
                <input type="date" name="billing_date" class="form-control" value="{{ old('billing_date', now()->toDateString()) }}" required>
            </div>
            <div class="col-md-3 form-group">
                <label>Reference</label>
                <input name="reference_no" class="form-control" value="{{ old('reference_no', $source->estimate_no) }}">
            </div>
            <div class="col-md-4 form-group">
                <label>Party</label>
                <select name="party_id" class="form-control select2">
                    <option value="">Cash / No Party</option>
                    @foreach($parties as $party)
                        <option value="{{ $party->id }}" @selected((string) old('party_id', $source->party_id) === (string) $party->id)>{{ $party->display_name }} | {{ $party->phone }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 form-group">
                <label>Phone</label>
                <input name="phone" class="form-control" value="{{ old('phone', $source->phone) }}">
            </div>
            <div class="col-md-4 form-group">
                <label>Overall Discount</label>
                <input type="number" step="0.01" name="discount_amount" class="form-control" value="{{ old('discount_amount', $source->discount_amount ?? 0) }}">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 form-group">
                <label>Billing Address</label>
                <textarea name="billing_address" class="form-control" rows="2">{{ old('billing_address', $source->billing_address) }}</textarea>
            </div>
            <div class="col-md-6 form-group">
                <label>Shipping Address</label>
                <textarea name="shipping_address" class="form-control" rows="2">{{ old('shipping_address', $source->shipping_address) }}</textarea>
            </div>
        </div>
    </div>

    <div class="convert-section">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="convert-title mb-0">Final Items</div>
            <small class="text-muted">Select final quantity, then choose serials from production stock.</small>
        </div>
        <div class="table-responsive">
            <table class="table convert-table" id="lineTable">
                <thead>
                    <tr>
                        <th>Item</th><th>Description</th><th>Qty</th><th>Serials</th><th>Unit</th><th>Price</th><th>Disc</th><th>GST Mode</th><th>Tax %</th><th>Weight</th><th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($lineData as $line)
                    <tr>
                        <td data-label="Item">
                            <select name="item_id[]" class="form-control item-select" required>
                                <option value="">Select item</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}"
                                        data-unit="{{ $item->unit }}"
                                        data-price="{{ $item->sale_price }}"
                                        data-tax="{{ $item->sale_gst_percent ?: 18 }}"
                                        data-weight="{{ (float) ($item->per_quantity_weight ?? 0) }}"
                                        data-stock="{{ (float) ($item->current_stock ?? 0) }}"
                                        data-gps="{{ $itemMeta[$item->id]['requires_gps'] ? 1 : 0 }}"
                                        @selected((string) $line['item_id'] === (string) $item->id)>
                                        {{ $item->name }} | Stock {{ (float) $item->current_stock }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="line-meta mt-1">
                                <span class="stock-badge stock-ok">Stock: <span class="stock-text">{{ number_format((float) ($line['current_stock'] ?? 0), 3) }}</span></span>
                            </div>
                        </td>
                        <td data-label="Description"><input name="description[]" class="form-control" value="{{ old('description.' . $loop->index, $line['description'] ?? '') }}"></td>
                        <td data-label="Qty"><input type="number" step="1" min="1" name="quantity[]" class="form-control line-qty" value="{{ old('quantity.' . $loop->index, $line['quantity'] ?? 1) }}" required></td>
                        <td data-label="Serials">
                            <button type="button" class="btn btn-outline-info btn-sm choose-units">
                                <i class="fas fa-barcode mr-1"></i> Units (<span class="unit-count">0</span>)
                            </button>
                            <input type="hidden" name="selected_units[]" class="selected-units-json" value='@json($line["selected_units"] ?? [])'>
                            <div class="selected-units mt-1"></div>
                        </td>
                        <td data-label="Unit"><input name="unit[]" class="form-control" value="{{ old('unit.' . $loop->index, $line['unit'] ?? '') }}"></td>
                        <td data-label="Price"><input type="number" step="0.01" name="unit_price[]" class="form-control" value="{{ old('unit_price.' . $loop->index, $line['unit_price'] ?? 0) }}" required></td>
                        <td data-label="Disc">
                            <select name="discount_type[]" class="form-control">
                                <option value="percent" @selected(old('discount_type.' . $loop->index, $line['discount_type'] ?? 'percent') === 'percent')>%</option>
                                <option value="flat" @selected(old('discount_type.' . $loop->index, $line['discount_type'] ?? '') === 'flat')>Flat</option>
                            </select>
                            <input type="number" step="0.01" name="discount_value[]" class="form-control mt-1" value="{{ old('discount_value.' . $loop->index, $line['discount_value'] ?? 0) }}">
                        </td>
                        <td data-label="GST Mode">
                            <select name="tax_mode[]" class="form-control">
                                <option value="with_gst" @selected((float) ($line['tax_percent'] ?? 18) > 0)>With GST</option>
                                <option value="without_gst" @selected((float) ($line['tax_percent'] ?? 18) <= 0)>Without GST</option>
                            </select>
                        </td>
                        <td data-label="Tax %"><input type="number" step="0.01" name="tax_percent[]" class="form-control" value="{{ old('tax_percent.' . $loop->index, $line['tax_percent'] ?? 18) }}"></td>
                        <td data-label="Weight"><span class="line-weight">0.000</span> kg</td>
                        <td data-label="Action"><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button></td>
                    </tr>
                @empty
                    <tr>
                        <td data-label="Item">
                            <select name="item_id[]" class="form-control item-select" required>
                                <option value="">Select item</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}" data-unit="{{ $item->unit }}" data-price="{{ $item->sale_price }}" data-tax="{{ $item->sale_gst_percent ?: 18 }}" data-weight="{{ (float) ($item->per_quantity_weight ?? 0) }}" data-stock="{{ (float) ($item->current_stock ?? 0) }}" data-gps="{{ $itemMeta[$item->id]['requires_gps'] ? 1 : 0 }}">{{ $item->name }} | Stock {{ (float) $item->current_stock }}</option>
                                @endforeach
                            </select>
                            <div class="line-meta mt-1">
                                <span class="stock-badge stock-ok">Stock: <span class="stock-text">0.000</span></span>
                            </div>
                        </td>
                        <td data-label="Description"><input name="description[]" class="form-control"></td>
                        <td data-label="Qty"><input type="number" step="1" min="1" name="quantity[]" class="form-control line-qty" value="1" required></td>
                        <td data-label="Serials">
                            <button type="button" class="btn btn-outline-info btn-sm choose-units">
                                <i class="fas fa-barcode mr-1"></i> Units (<span class="unit-count">0</span>)
                            </button>
                            <input type="hidden" name="selected_units[]" class="selected-units-json" value="[]">
                            <div class="selected-units mt-1"></div>
                        </td>
                        <td data-label="Unit"><input name="unit[]" class="form-control"></td>
                        <td data-label="Price"><input type="number" step="0.01" name="unit_price[]" class="form-control" value="0" required></td>
                        <td data-label="Disc">
                            <select name="discount_type[]" class="form-control">
                                <option value="percent">%</option>
                                <option value="flat">Flat</option>
                            </select>
                            <input type="number" step="0.01" name="discount_value[]" class="form-control mt-1" value="0">
                        </td>
                        <td data-label="GST Mode">
                            <select name="tax_mode[]" class="form-control">
                                <option value="with_gst" selected>With GST</option>
                                <option value="without_gst">Without GST</option>
                            </select>
                        </td>
                        <td data-label="Tax %"><input type="number" step="0.01" name="tax_percent[]" class="form-control" value="18"></td>
                        <td data-label="Weight"><span class="line-weight">0.000</span> kg</td>
                        <td data-label="Action"><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button></td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <button type="button" id="addLine" class="btn btn-outline-primary btn-sm"><i class="fas fa-plus mr-1"></i> Add Row</button>
    </div>

    <div class="convert-section">
        <div class="row">
            <div class="col-md-4 form-group">
                <label>Notes</label>
                <textarea name="notes" class="form-control" rows="3">{{ old('notes', $source->notes) }}</textarea>
            </div>
            <div class="col-md-4 form-group">
                <label>Terms</label>
                <textarea name="terms" class="form-control" rows="3">{{ old('terms', $source->terms) }}</textarea>
            </div>
            <div class="col-md-4">
                <div class="total-box">
                    <div class="total-row"><span>Subtotal</span><b id="uiSubtotal">Rs 0.00</b></div>
                    <div class="total-row"><span>Tax</span><b id="uiTax">Rs 0.00</b></div>
                    <div class="total-row"><span>Total Weight</span><b id="uiWeight">0.000 kg</b></div>
                    <div class="total-row"><span>Total</span><b id="uiTotal">Rs 0.00</b></div>
                </div>
            </div>
        </div>
    </div>

    <div class="p-4 text-right">
        <button class="btn btn-success btn-lg"><i class="fas fa-check-circle mr-1"></i> Convert to Sale</button>
    </div>
</div>
</form>

<template id="lineTpl">
<tr>
    <td data-label="Item">
        <select name="item_id[]" class="form-control item-select" required>
            <option value="">Select item</option>
            @foreach($items as $item)
                <option value="{{ $item->id }}" data-unit="{{ $item->unit }}" data-price="{{ $item->sale_price }}" data-tax="{{ $item->sale_gst_percent ?: 18 }}" data-weight="{{ (float) ($item->per_quantity_weight ?? 0) }}" data-stock="{{ (float) ($item->current_stock ?? 0) }}" data-gps="{{ $itemMeta[$item->id]['requires_gps'] ? 1 : 0 }}">{{ $item->name }} | Stock {{ (float) $item->current_stock }}</option>
            @endforeach
        </select>
        <div class="line-meta mt-1">
            <span class="stock-badge stock-ok">Stock: <span class="stock-text">0.000</span></span>
        </div>
    </td>
    <td data-label="Description"><input name="description[]" class="form-control"></td>
    <td data-label="Qty"><input type="number" step="1" min="1" name="quantity[]" class="form-control line-qty" value="1" required></td>
    <td data-label="Serials">
        <button type="button" class="btn btn-outline-info btn-sm choose-units">
            <i class="fas fa-barcode mr-1"></i> Units (<span class="unit-count">0</span>)
        </button>
        <input type="hidden" name="selected_units[]" class="selected-units-json" value="[]">
        <div class="selected-units mt-1"></div>
    </td>
    <td data-label="Unit"><input name="unit[]" class="form-control"></td>
    <td data-label="Price"><input type="number" step="0.01" name="unit_price[]" class="form-control" value="0" required></td>
    <td data-label="Disc">
        <select name="discount_type[]" class="form-control">
            <option value="percent">%</option>
            <option value="flat">Flat</option>
        </select>
        <input type="number" step="0.01" name="discount_value[]" class="form-control mt-1" value="0">
    </td>
    <td data-label="GST Mode">
        <select name="tax_mode[]" class="form-control">
            <option value="with_gst" selected>With GST</option>
            <option value="without_gst">Without GST</option>
        </select>
    </td>
    <td data-label="Tax %"><input type="number" step="0.01" name="tax_percent[]" class="form-control" value="18"></td>
    <td data-label="Weight"><span class="line-weight">0.000</span> kg</td>
    <td data-label="Action"><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button></td>
</tr>
</template>

@include('admin.partials.serial-unit-drawer')
@push('scripts')
<script>
function money(value){return 'Rs ' + (Number(value)||0).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2});}
function rowUnits($row){try{return JSON.parse($row.find('.selected-units-json').val()||'[]')}catch(e){return [];}}
function setRowUnits($row, units){
    $row.find('.selected-units-json').val(JSON.stringify(units));
    $row.find('.selected-units').html(units.map(u => `<span class="unit-pill">${u.serial_no||'No serial'}${u.vts_sim ? ' / ' + u.vts_sim : ''}</span>`).join(''));
    $row.find('.choose-units').toggleClass('btn-success', units.length > 0).find('.unit-count').text(units.length);
}
function recalc(){
    let subtotal = 0, tax = 0, weight = 0;
    $('#lineTable tbody tr').each(function(){
        const $row = $(this);
        const qty = parseFloat($row.find('[name="quantity[]"]').val()) || 0;
        const price = parseFloat($row.find('[name="unit_price[]"]').val()) || 0;
        const discType = $row.find('[name="discount_type[]"]').val();
        const discValue = parseFloat($row.find('[name="discount_value[]"]').val()) || 0;
        const taxMode = $row.find('[name="tax_mode[]"]').val();
        const taxPercent = taxMode === 'without_gst' ? 0 : (parseFloat($row.find('[name="tax_percent[]"]').val()) || 0);
        const base = qty * price;
        const discount = discType === 'flat' ? discValue : (base * discValue / 100);
        const gross = Math.max(0, base - discount);
        const lineTax = taxPercent > 0 ? gross * taxPercent / (100 + taxPercent) : 0;
        const itemWeight = parseFloat($row.find('.item-select option:selected').data('weight')) || 0;
        subtotal += gross - lineTax;
        tax += lineTax;
        weight += qty * itemWeight;
        $row.find('.line-weight').text((qty * itemWeight).toFixed(3));
        const stock = parseFloat($row.find('.item-select option:selected').data('stock')) || 0;
        const $stockText = $row.find('.stock-text');
        const $stockBadge = $row.find('.stock-badge');
        $stockText.text(stock.toFixed(3));
        $stockBadge.toggleClass('stock-low', qty > stock).toggleClass('stock-ok', qty <= stock);
    });
    const overall = parseFloat($('[name="discount_amount"]').val()) || 0;
    $('#uiSubtotal').text(money(subtotal));
    $('#uiTax').text(money(tax));
    $('#uiWeight').text(weight.toFixed(3) + ' kg');
    $('#uiTotal').text(money(Math.max(0, subtotal + tax - overall)));
}
function addLine(data){
    const $row = $($('#lineTpl').html());
    $('#lineTable tbody').append($row);
    if (data) {
        $row.find('[name="item_id[]"]').val(data.item_id || '');
        $row.find('[name="description[]"]').val(data.description || '');
        $row.find('[name="quantity[]"]').val(data.quantity || 1);
        $row.find('[name="unit[]"]').val(data.unit || '');
        $row.find('[name="unit_price[]"]').val(data.unit_price || 0);
        $row.find('[name="discount_type[]"]').val(data.discount_type || 'percent');
        $row.find('[name="discount_value[]"]').val(data.discount_value || 0);
        $row.find('[name="tax_mode[]"]').val((data.tax_percent || 0) > 0 ? 'with_gst' : 'without_gst');
        $row.find('[name="tax_percent[]"]').val(data.tax_percent || 18);
        setRowUnits($row, data.selected_units || []);
    }
    recalc();
}

$('#addLine').on('click', function(){ addLine(); });
$(document).on('input change', '#lineTable input,#lineTable select,[name="discount_amount"]', recalc);
$(document).on('click', '.remove-row', function(){ $(this).closest('tr').remove(); recalc(); });
$(document).on('change', '.item-select', function(){
    const $row = $(this).closest('tr');
    const opt = $(this).find(':selected');
    $row.find('[name="unit[]"]').val(opt.data('unit'));
    $row.find('[name="unit_price[]"]').val(opt.data('price'));
    if ($row.find('[name="tax_mode[]"]').val() !== 'without_gst') {
        $row.find('[name="tax_percent[]"]').val(opt.data('tax') || 18);
    }
    $row.find('.stock-text').text((parseFloat(opt.data('stock')) || 0).toFixed(3));
    recalc();
});

$(document).on('submit', '#convertForm', function(){
    let ok = true;
    $('#lineTable tbody tr').each(function(){
        const qty = parseInt($(this).find('[name="quantity[]"]').val()) || 0;
        const units = rowUnits($(this));
        if (qty > 0 && units.length !== qty) {
            ok = false;
        }
    });
    if (!ok) {
        alert('Har line ke liye quantity ke barabar serial/unit select karein.');
        return false;
    }
});

const prefill = @json($lineData->values());
if (prefill.length) {
    $('#lineTable tbody').empty();
    prefill.forEach(line => addLine(line));
} else {
    addLine();
}
</script>
@endpush
