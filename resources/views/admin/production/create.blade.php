@extends('layouts.admin')
@section('title','New Production Batch')

@push('styles')
<style>
/* ── Base ── */
.pb-page{background:#0d111a;border-radius:18px;padding:28px;color:#fff}
/* ── Header ── */
.pb-head{display:flex;gap:18px;align-items:center;margin-bottom:24px}
.pb-icon{width:62px;height:62px;border-radius:16px;background:linear-gradient(135deg,#f59e0b,#d97706);display:flex;align-items:center;justify-content:center;font-size:26px;flex-shrink:0}
.pb-head h1{font-size:32px;font-weight:800;margin:0;line-height:1}
.pb-head p{color:#7890b5;margin:4px 0 0}
/* ── Steps ── */
.pb-steps{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:22px}
.pb-step{display:inline-flex;align-items:center;gap:8px;background:#1f2937;border:1px solid #334155;border-radius:12px;padding:10px 18px;color:#94a3b8;font-weight:600;font-size:13px;transition:.2s}
.pb-step.done{border-color:#22c55e;color:#22c55e;background:#0f2318}
.pb-step.active{background:linear-gradient(135deg,#f59e0b,#d97706);color:#111827;border-color:transparent}
.pb-step-num{width:22px;height:22px;border-radius:50%;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700}
.pb-step.active .pb-step-num{background:rgba(0,0,0,.25)}
/* ── Cards ── */
.pb-card{background:#1d2433;border:1px solid #2a3447;border-radius:16px;padding:22px;margin-top:18px}
.pb-card-title{font-size:14px;font-weight:700;color:#e2e8f0;letter-spacing:.5px;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.pb-badge{display:inline-block;background:#f59e0b;color:#111;font-size:11px;font-weight:700;padding:2px 10px;border-radius:20px}
.pb-badge-blue{background:#3b82f6;color:#fff}
/* ── Form controls ── */
.pb-card label{color:#93a4bf;text-transform:uppercase;font-size:11px;letter-spacing:.8px;font-weight:600;margin-bottom:4px;display:block}
.pb-card .form-control,.pb-card .custom-select{background:#111827!important;color:#fff!important;border:1px solid #334155!important;border-radius:10px!important}
.pb-card .form-control:focus,.pb-card .custom-select:focus{border-color:#f59e0b!important;box-shadow:0 0 0 3px rgba(245,158,11,.15)!important}
.pb-card .form-control::placeholder{color:#4b5563!important}
/* ── Product info strip ── */
.pb-info-strip{background:#111827;border:1px solid #334155;border-radius:12px;padding:14px 18px;display:flex;gap:32px;flex-wrap:wrap;margin-top:14px}
.pb-info-item small{color:#7890b5;text-transform:uppercase;font-size:10px;letter-spacing:.8px;display:block}
.pb-info-item b{color:#f59e0b;font-size:15px}
/* ── BOM table ── */
.bom-table{width:100%;border-collapse:collapse;margin-top:10px}
.bom-table thead tr{border-bottom:1px solid #2a3447}
.bom-table thead th{color:#64748b;text-transform:uppercase;font-size:10px;letter-spacing:.8px;padding:8px 10px;font-weight:600}
.bom-table tbody tr{border-bottom:1px solid #1a2233;transition:background .15s}
.bom-table tbody tr:hover{background:#1a2233}
.bom-table td{padding:10px 10px;font-size:13px;vertical-align:middle}
.bom-table td.num{font-variant-numeric:tabular-nums;font-weight:600;color:#f59e0b}
.unit-badge{display:inline-block;background:#1e3a5f;color:#60a5fa;border-radius:6px;padding:2px 8px;font-size:11px;font-weight:700}
.stock-ok{display:inline-block;background:#064e3b;color:#34d399;border-radius:8px;padding:3px 10px;font-size:12px;font-weight:700}
.stock-low{display:inline-block;background:#7f1d1d;color:#f87171;border-radius:8px;padding:3px 10px;font-size:12px;font-weight:700}
.stock-zero{display:inline-block;background:#450a0a;color:#ef4444;border-radius:8px;padding:3px 10px;font-size:12px;font-weight:700;animation:pulse 1.5s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.6}}
/* ── Metrics ── */
.pb-metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-top:16px}
@media(max-width:768px){.pb-metrics{grid-template-columns:repeat(2,1fr)}}
.pb-metric{background:#121826;border-radius:14px;padding:16px;border:1px solid #2a3447}
.pb-metric small{color:#7890b5;text-transform:uppercase;font-size:10px;letter-spacing:.8px;display:block;margin-bottom:4px}
.pb-metric b{font-size:22px;font-weight:800;color:#f59e0b;display:block}
.pb-metric b.green{color:#34d399}
.pb-metric b.red{color:#ef4444}
.pb-metric b.white{color:#e2e8f0}
/* ── Qty section ── */
.qty-control{display:flex;align-items:center;gap:0}
.qty-control button{width:42px;height:42px;background:#1f2937;border:1px solid #334155;color:#fff;font-size:18px;cursor:pointer;transition:.15s}
.qty-control button:hover{background:#374151}
.qty-control button:first-child{border-radius:10px 0 0 10px}
.qty-control button:last-child{border-radius:0 10px 10px 0}
.qty-control input{width:80px;height:42px;background:#111827;border:1px solid #334155;border-left:0;border-right:0;color:#fff;text-align:center;font-size:18px;font-weight:700}
.qty-control input:focus{outline:none;background:#1f2937}
/* ── Individual rows ── */
.unit-row{background:#111827;border:1px solid #2a3447;border-radius:12px;padding:14px;margin-bottom:10px}
.unit-row-head{display:flex;align-items:center;gap:10px;margin-bottom:12px}
.unit-num{width:28px;height:28px;background:#f59e0b;color:#111;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;flex-shrink:0}
.profit-badge{font-size:13px;font-weight:700;margin-left:auto}
/* ── Breakdown ── */
.pb-breakdown{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:18px}
@media(max-width:768px){.pb-breakdown{grid-template-columns:1fr}}
.breakdown-card{background:#121826;border:1px solid #2a3447;border-radius:14px;padding:18px}
.breakdown-card h4{font-size:14px;font-weight:700;margin:0 0 14px;display:flex;align-items:center;gap:6px}
.breakdown-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #1e2a3a;font-size:13px}
.breakdown-row:last-child{border-bottom:none;font-weight:700;font-size:15px;padding-top:12px}
.breakdown-row .lbl{color:#93a4bf}
.breakdown-row .val{color:#f59e0b;font-weight:600}
.breakdown-row .val.green{color:#34d399}
.breakdown-row .val.red{color:#ef4444}
.breakdown-row .val.white{color:#e2e8f0}
/* ── Produce button ── */
.btn-produce{background:linear-gradient(135deg,#f59e0b,#d97706)!important;color:#111827!important;border:0!important;font-weight:800!important;padding:14px 32px!important;border-radius:12px!important;font-size:15px!important}
.btn-produce:hover{opacity:.9}
.btn-produce:disabled{opacity:.5;cursor:not-allowed}
/* ── Pane visibility ── */
.pb-pane{display:none}.pb-pane.active{display:block}
/* ── Warehouse apply bar ── */
.apply-bar{background:#1a2233;border:1px solid #2a3447;border-radius:10px;padding:12px 16px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:16px}
.apply-bar label{color:#93a4bf;font-size:11px;text-transform:uppercase;letter-spacing:.8px;margin:0;white-space:nowrap}
</style>
@endpush

@section('content')
@php
    $itemsJson = $itemsData->toJson();
@endphp

<form method="POST" action="{{ route('admin.production-batches.store') }}" id="pbForm">
@csrf

<div class="pb-page">

    {{-- Header --}}
    <div class="pb-head">
        <div class="pb-icon"><i class="fas fa-cogs"></i></div>
        <div>
            <h1>New Production Batch</h1>
            <p>Convert finished goods templates → serialised items</p>
        </div>
    </div>

    {{-- Step indicators --}}
    <div class="pb-steps">
        <span class="pb-step active" data-step="1"><span class="pb-step-num">01</span> Select Product</span>
        <span class="pb-step" data-step="2"><span class="pb-step-num">02</span> Set Quantity</span>
        <span class="pb-step" data-step="3"><span class="pb-step-num">03</span> Goods Details</span>
        <span class="pb-step" data-step="4"><span class="pb-step-num">04</span> Confirm</span>
    </div>

    {{-- ══════════════ PANE 1: Select Product ══════════════ --}}
    <div class="pb-pane active" data-pane="1">
        <div class="pb-card">
            <div class="pb-card-title">🏷️ Finished Product Template <span class="pb-badge">Step 01</span></div>
            <div class="form-group">
                <label>Select Finished Goods Template</label>
                <select name="finished_item_id" id="finished_item_id" class="form-control select2" required>
                    <option value="">Choose a finished goods template…</option>
                    @foreach($finishedItems as $item)
                        <option value="{{ $item->id }}">{{ $item->name }} | {{ $item->item_code }} | HSN {{ $item->hsn_code }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Product info strip (shown after selection) --}}
            <div id="productInfo" style="display:none">
                <div class="pb-info-strip">
                    <div class="pb-info-item"><small>Product Name</small><b id="info_name">—</b></div>
                    <div class="pb-info-item"><small>Item Code</small><b id="info_code">—</b></div>
                    <div class="pb-info-item"><small>HSN Code</small><b id="info_hsn">—</b></div>
                    <div class="pb-info-item"><small>Base Sale Price</small><b id="info_sale">—</b></div>
                    <div class="pb-info-item"><small>GST %</small><b id="info_gst">—</b></div>
                    <div class="pb-info-item"><small>Raw Materials</small><b id="info_bom_count">—</b></div>
                </div>
            </div>
        </div>

        @if(count($parties))
        <div class="pb-card">
            <div class="pb-card-title">👤 Party / Buyer <span style="font-weight:400;color:#7890b5;font-size:12px;">(Optional)</span></div>
            <div class="form-group mb-0">
                <label>Party</label>
                <select name="party_id" class="form-control select2">
                    <option value="">— No specific party —</option>
                    @foreach($parties as $party)
                        <option value="{{ $party->id }}">{{ $party->display_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        @endif

        {{-- Raw Materials Composition --}}
        <div class="pb-card" id="bomSection" style="display:none">
            <div class="pb-card-title">🧪 Raw Materials Composition <span class="pb-badge pb-badge-blue">Per Unit</span></div>
            <div class="table-responsive">
                <table class="bom-table">
                    <thead>
                        <tr>
                            <th>Material Name</th>
                            <th>Unit</th>
                            <th>Qty / Unit</th>
                            <th>Purchase Price</th>
                            <th>Available Stock</th>
                            <th>Tax</th>
                            <th>Line Cost / Unit</th>
                        </tr>
                    </thead>
                    <tbody id="bomBody">
                        <tr><td colspan="7" class="text-center" style="color:#4b5563;padding:24px">Select a product above to see raw materials</td></tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6" style="text-align:right;padding:12px 10px;color:#93a4bf;font-size:12px;text-transform:uppercase;letter-spacing:.5px">Total Cost Per Unit</td>
                            <td class="num" id="totalCostPerUnit">₹ 0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- ══════════════ PANE 2: Quantity ══════════════ --}}
    <div class="pb-pane" data-pane="2">
        <div class="pb-card">
            <div class="pb-card-title">📦 How Many Units to Produce? <span class="pb-badge">Step 02</span></div>
            <div class="row align-items-end">
                <div class="col-md-4 form-group">
                    <label>Quantity</label>
                    <div class="qty-control">
                        <button type="button" id="qtyMinus">−</button>
                        <input type="number" step="1" min="1" name="quantity" id="prod_qty" value="1" required>
                        <button type="button" id="qtyPlus">+</button>
                    </div>
                    <small style="color:#7890b5;margin-top:6px;display:block" id="qtyHint">1 individual record will be created</small>
                </div>
                <div class="col-md-3 form-group">
                    <label>Batch No</label>
                    <input name="batch_no" class="form-control" value="{{ $batchNo }}">
                </div>
                <div class="col-md-3 form-group">
                    <label>Production Date</label>
                    <input type="date" name="production_date" class="form-control" value="{{ now()->toDateString() }}" required>
                </div>
            </div>
        </div>

        <div class="pb-metrics">
            <div class="pb-metric"><small>Total Raw Cost</small><b id="m_totalCost">₹ 0.00</b></div>
            <div class="pb-metric"><small>Cost Per Unit</small><b class="white" id="m_costPerUnit">₹ 0.00</b></div>
            <div class="pb-metric"><small>Total Input GST</small><b class="green" id="m_totalGst">₹ 0.00</b></div>
            <div class="pb-metric"><small>Stock Warnings</small><b id="m_warnings" class="green">All OK</b></div>
        </div>

        {{-- Live BOM with required qty --}}
        <div class="pb-card" id="qtyBomSection" style="display:none">
            <div class="pb-card-title">🔍 Raw Material Check <span class="pb-badge pb-badge-blue">For <span id="qtyLabel">1</span> Unit(s)</span></div>
            <div class="table-responsive">
                <table class="bom-table">
                    <thead>
                        <tr>
                            <th>Material</th>
                            <th>Need (Total)</th>
                            <th>Available</th>
                            <th>Status</th>
                            <th>Total Cost</th>
                        </tr>
                    </thead>
                    <tbody id="qtyBomBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ══════════════ PANE 3: Individual Goods Details ══════════════ --}}
    <div class="pb-pane" data-pane="3">
        <div class="pb-card">
            <div class="pb-card-title">📋 Individual Goods Details <span class="pb-badge">Step 03</span>
                <button type="button" class="btn btn-sm ml-auto" style="background:#f59e0b;color:#111;border-radius:8px;font-weight:700;padding:4px 14px" id="autoSerials">⚡ Auto-Gen All Serials</button>
                <button type="button" class="btn btn-sm ml-2" style="background:#374151;color:#e2e8f0;border-radius:8px;font-weight:700;padding:4px 14px" id="autoBatches">🏷️ Auto-Gen All Batch Nos</button>
            </div>

            {{-- Apply-all bar --}}
            <div class="apply-bar">
                <i class="fas fa-warehouse" style="color:#7890b5"></i>
                <label>Warehouse / Location</label>
                <input type="text" id="globalWarehouse" class="form-control" style="max-width:260px;height:36px" placeholder="Enter warehouse / storage location for all goods…">
                <span style="color:#7890b5;font-size:12px" id="applyHint">Applies to all <span id="applyCount">1</span> units</span>
                <label style="margin-left:auto">Sale Price ₹</label>
                <input type="number" step="0.01" id="globalSalePrice" class="form-control" style="max-width:120px;height:36px">
                <label>GST %</label>
                <input type="number" step="0.01" id="globalGst" class="form-control" style="max-width:80px;height:36px" value="0">
                <label>Sale Mode</label>
                <select id="globalSaleMode" class="form-control" style="max-width:150px;height:36px">
                    <option value="exclusive">Tax Exclusive</option>
                    <option value="inclusive">Tax Inclusive</option>
                </select>
                <button type="button" id="applyGlobal" class="btn" style="background:#f59e0b;color:#111;border-radius:8px;font-weight:700;height:36px;padding:0 18px">Apply</button>
            </div>

            {{-- Column headers --}}
            <div style="display:grid;grid-template-columns:40px 140px 160px 180px 130px 90px 140px 80px 80px;gap:8px;padding:0 0 6px;font-size:10px;text-transform:uppercase;letter-spacing:.6px;color:#64748b;font-weight:600">
                <span>#</span>
                <span>Buyer Code (Auto)</span>
                <span>Serial No.</span>
                <span>Batch No. (Purchase)</span>
                <span>Sale Price ₹ *</span>
                <span>GST %</span>
                <span>Sale Mode</span>
                <span>Warehouse</span>
                <span>Profit</span>
            </div>

            <div id="unitRows"></div>
        </div>

        <div class="pb-breakdown">
            <div class="breakdown-card">
                <h4>🌿 Cost Breakdown</h4>
                <div class="breakdown-row"><span class="lbl">Total Raw Material Cost</span><span class="val" id="bd_rawCost">₹ 0.00</span></div>
                <div class="breakdown-row"><span class="lbl">Total Input GST (ITC)</span><span class="val green" id="bd_itc">₹ 0.00</span></div>
                <div class="breakdown-row"><span class="lbl">Cost Per Unit</span><span class="val" id="bd_cpu">₹ 0.00</span></div>
                <div class="breakdown-row"><span class="lbl">Total Revenue (ex-tax)</span><span class="val green" id="bd_rev">₹ 0.00</span></div>
                <div class="breakdown-row"><span class="lbl">Profit Margin</span><span class="val" id="bd_margin">—</span></div>
                <div class="breakdown-row"><span class="lbl">Net Profit</span><span class="val" id="bd_profit">₹ 0.00</span></div>
            </div>
            <div class="breakdown-card">
                <h4>📊 GST Summary</h4>
                <div class="breakdown-row"><span class="lbl">Output GST (collected)</span><span class="val" id="gst_out">₹ 0.00</span></div>
                <div class="breakdown-row"><span class="lbl">CGST (50%)</span><span class="val white" id="gst_cgst">₹ 0.00</span></div>
                <div class="breakdown-row"><span class="lbl">SGST (50%)</span><span class="val white" id="gst_sgst">₹ 0.00</span></div>
                <div class="breakdown-row"><span class="lbl">Input ITC Credit</span><span class="val green" id="gst_itc">— ₹ 0.00</span></div>
                <div class="breakdown-row"><span class="lbl">GST Payable</span><span class="val" id="gst_pay">₹ 0.00</span></div>
                <div id="batchSummaryBlock" style="margin-top:14px;font-size:12px;color:#7890b5;display:none">
                    <div style="color:#93a4bf;font-weight:700;text-transform:uppercase;font-size:10px;letter-spacing:.8px;margin-bottom:8px">Batch Summary</div>
                    <div id="batchSummaryContent"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════ PANE 4: Confirm & Notes ══════════════ --}}
    <div class="pb-pane" data-pane="4">
        <div class="pb-card">
            <div class="pb-card-title">📝 Notes (Optional)</div>
            <textarea name="notes" class="form-control" rows="4" placeholder="Production notes, remarks…"></textarea>
        </div>

        <div class="pb-card" style="background:#0f2318;border-color:#166534">
            <div class="pb-card-title" style="color:#86efac">✅ Ready to Produce</div>
            <div id="confirmSummary" style="color:#93a4bf;font-size:13px;line-height:1.9"></div>
        </div>

        @include('admin.partials.entry-visibility', ['entry' => null])
    </div>

    {{-- Footer nav --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:24px">
        <a href="{{ route('admin.production-batches.index') }}" class="btn btn-link" style="color:#7890b5">Cancel</a>
        <div style="display:flex;gap:10px">
            <button type="button" id="pbPrev" class="btn" style="background:#1f2937;color:#e2e8f0;border-radius:10px;padding:10px 22px" disabled>← Back</button>
            <button type="button" id="pbNext" class="btn" style="background:#374151;color:#e2e8f0;border-radius:10px;padding:10px 22px">Next →</button>
            <button type="submit" id="pbSave" class="btn btn-produce d-none"><i class="fas fa-cog mr-2"></i>Produce Finished Goods</button>
        </div>
    </div>

</div>
</form>
@endsection

@push('scripts')
<script>
const ITEMS = @json($itemsData);

// ── State ──────────────────────────────────────────────────────
let selectedItem = null;
let step = 1;

// ── Helpers ───────────────────────────────────────────────────
function money(n){ return '₹ '+(Number(n)||0).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2}); }
function qty(){ return Math.max(1, parseInt($('#prod_qty').val()) || 1); }

// ── Step rendering ────────────────────────────────────────────
function renderStep(){
    $('.pb-step').each(function(){
        const s = +$(this).data('step');
        $(this).removeClass('active done').addClass(s===step?'active':s<step?'done':'');
    });
    $('.pb-pane').removeClass('active');
    $(`[data-pane="${step}"]`).addClass('active');
    $('#pbPrev').prop('disabled', step===1);
    $('#pbNext').toggleClass('d-none', step===4);
    $('#pbSave').toggleClass('d-none', step!==4);
    if(step===3) buildUnitRows();
    if(step===4) buildConfirm();
}

// ── Product selection ─────────────────────────────────────────
$('#finished_item_id').on('change', function(){
    const id = $(this).val();
    selectedItem = id ? ITEMS[id] : null;
    if(!selectedItem){ $('#productInfo,#bomSection,#qtyBomSection').hide(); return; }

    // Info strip
    $('#info_name').text(selectedItem.name);
    $('#info_code').text(selectedItem.item_code);
    $('#info_hsn').text(selectedItem.hsn_code||'—');
    $('#info_sale').text(money(selectedItem.sale_price));
    $('#info_gst').text((selectedItem.sale_gst_percent||0)+'%');
    $('#info_bom_count').text(selectedItem.bom.length+' items');
    $('#productInfo').show();

    // Global sale price default
    $('#globalSalePrice').val(selectedItem.sale_price||0);
    $('#globalGst').val(selectedItem.sale_gst_percent||0);

    renderBomTable();
    renderQtyBom();
});

// ── BOM table (per unit, step 1) ───────────────────────────────
function renderBomTable(){
    if(!selectedItem){ $('#bomSection').hide(); return; }
    const bom = selectedItem.bom;
    if(!bom.length){ $('#bomSection').hide(); return; }

    let html='', totalCpu=0;
    bom.forEach(m=>{
        const lineCost = m.qty_per_unit * m.purchase_price;
        totalCpu += lineCost;
        const stock = m.current_stock;
        const stockHtml = stock<=0
            ? `<span class="stock-zero">0 ${m.unit}</span>`
            : stock <= (m.low_stock_qty||0)
                ? `<span class="stock-low">${stock} ${m.unit}</span>`
                : `<span class="stock-ok">${stock} ${m.unit}</span>`;

        html+=`<tr>
            <td>${m.name}</td>
            <td><span class="unit-badge">${m.unit}</span></td>
            <td>${m.qty_per_unit}</td>
            <td>${money(m.purchase_price)}</td>
            <td>${stockHtml}</td>
            <td style="color:#94a3b8">${m.purchase_gst||0}%</td>
            <td class="num">${money(lineCost)}</td>
        </tr>`;
    });
    $('#bomBody').html(html);
    $('#totalCostPerUnit').text(money(totalCpu));
    $('#bomSection').show();
}

// ── Qty BOM (step 2) ──────────────────────────────────────────
function renderQtyBom(){
    if(!selectedItem){ $('#qtyBomSection').hide(); return; }
    const q = qty();
    $('#qtyLabel').text(q);

    const bom = selectedItem.bom;
    if(!bom.length){ $('#qtyBomSection').hide(); return; }

    let html='', totalCost=0, totalGst=0, warnings=0;
    bom.forEach(m=>{
        const need = m.qty_per_unit * q;
        const lineCost = need * m.purchase_price;
        const lineGst = lineCost * (m.purchase_gst||0)/100;
        totalCost += lineCost;
        totalGst  += lineGst;

        const ok = m.current_stock >= need;
        if(!ok) warnings++;
        const statusHtml = ok
            ? `<span class="stock-ok">✓ OK</span>`
            : `<span class="stock-zero">⚠ LOW (${m.current_stock} / ${need} needed)</span>`;

        html+=`<tr>
            <td>${m.name}</td>
            <td>${need} ${m.unit}</td>
            <td>${m.current_stock} ${m.unit}</td>
            <td>${statusHtml}</td>
            <td class="num">${money(lineCost)}</td>
        </tr>`;
    });

    $('#qtyBomBody').html(html);
    $('#qtyBomSection').show();

    // Update metrics
    const cpu = q>0 ? totalCost/q : 0;
    $('#m_totalCost').text(money(totalCost));
    $('#m_costPerUnit').text(money(cpu));
    $('#m_totalGst').text(money(totalGst));
    if(warnings>0){
        $('#m_warnings').text(warnings+' material(s) short!').removeClass('green').addClass('red');
    } else {
        $('#m_warnings').text('All OK ✓').removeClass('red').addClass('green');
    }
}

$('#prod_qty').on('input change', function(){
    const v = Math.max(1,parseInt($(this).val())||1);
    $(this).val(v);
    $('#qtyHint').text(v+' individual record'+(v>1?'s':'')+' will be created');
    $('#qtyLabel,#applyCount').text(v);
    renderQtyBom();
});
$('#qtyMinus').click(()=>{ $('#prod_qty').val(Math.max(1,(parseInt($('#prod_qty').val())||1)-1)).trigger('change'); });
$('#qtyPlus').click(()=>{ $('#prod_qty').val((parseInt($('#prod_qty').val())||1)+1).trigger('change'); });

// ── Unit rows (step 3) ────────────────────────────────────────
function batchPrefix(){
    const d = new Date();
    return d.toLocaleString('en-IN',{month:'short'}).toUpperCase()+d.getFullYear();
}
function randSuffix(){ return Math.random().toString(36).slice(2,6).toUpperCase(); }

function buildUnitRows(){
    const q = qty();
    let html='';
    for(let i=0;i<q;i++){
        const saleP = selectedItem ? (selectedItem.sale_price||0) : 0;
        const gstP  = selectedItem ? (selectedItem.sale_gst_percent||0) : 0;
        html+=`<div class="unit-row" data-index="${i}">
            <div class="unit-row-head">
                <div class="unit-num">${i+1}</div>
                <span style="color:#93a4bf;font-size:12px">Unit ${i+1} of ${q}</span>
                <span class="profit-badge" id="profit_${i}">—</span>
            </div>
            <div style="display:grid;grid-template-columns:140px 160px 180px 130px 90px 140px 1fr 80px;gap:8px;align-items:end">
                <div><small style="color:#64748b;font-size:10px;text-transform:uppercase;letter-spacing:.6px">Buyer Code</small>
                    <input class="form-control" style="height:36px;font-size:12px" value="BC-AUTO-${String(i+1).padStart(3,'0')}" readonly></div>
                <div><small style="color:#64748b;font-size:10px;text-transform:uppercase;letter-spacing:.6px">Serial No.</small>
                    <input type="text" name="unit_serial[${i}]" class="form-control unit-serial" style="height:36px;font-size:12px" placeholder="Auto or manual"></div>
                <div><small style="color:#64748b;font-size:10px;text-transform:uppercase;letter-spacing:.6px">Batch No. (Purchase)</small>
                    <input type="text" name="unit_batch[${i}]" class="form-control unit-batchno" style="height:36px;font-size:12px" value="${batchPrefix()+'-'+randSuffix()}"></div>
                <div><small style="color:#64748b;font-size:10px;text-transform:uppercase;letter-spacing:.6px">Sale Price ₹ *</small>
                    <input type="number" step="0.01" name="unit_sale_price[${i}]" class="form-control unit-sale-price" style="height:36px" value="${saleP}" data-index="${i}"></div>
                <div><small style="color:#64748b;font-size:10px;text-transform:uppercase;letter-spacing:.6px">GST %</small>
                    <input type="number" step="0.01" name="unit_gst[${i}]" class="form-control unit-gst" style="height:36px" value="${gstP}" data-index="${i}"></div>
                <div><small style="color:#64748b;font-size:10px;text-transform:uppercase;letter-spacing:.6px">Sale Mode</small>
                    <select name="unit_sale_mode[${i}]" class="form-control unit-sale-mode" style="height:36px;font-size:12px" data-index="${i}">
                        <option value="exclusive">Exclusive</option>
                        <option value="inclusive">Inclusive</option>
                    </select></div>
                <div><small style="color:#64748b;font-size:10px;text-transform:uppercase;letter-spacing:.6px">Warehouse</small>
                    <input type="text" name="unit_warehouse[${i}]" class="form-control unit-warehouse" style="height:36px;font-size:12px" placeholder="Location"></div>
                <div><small style="color:#64748b;font-size:10px;text-transform:uppercase;letter-spacing:.6px">Notes</small>
                    <input type="text" name="unit_notes[${i}]" class="form-control" style="height:36px;font-size:12px" placeholder="—"></div>
            </div>
        </div>`;
    }
    $('#unitRows').html(html);
    recalcBreakdown();
}

// ── Auto-gen buttons ──────────────────────────────────────────
$('#autoSerials').click(()=>{
    $('.unit-serial').each(function(i){ $(this).val('SN-'+batchPrefix()+'-'+String(i+1).padStart(4,'0')); });
});
$('#autoBatches').click(()=>{
    const prefix = batchPrefix();
    $('.unit-batchno').each(function(){ $(this).val(prefix+'-'+randSuffix()); });
});

// ── Apply-all bar ─────────────────────────────────────────────
$('#applyGlobal').click(()=>{
    const wh = $('#globalWarehouse').val();
    const sp = $('#globalSalePrice').val();
    const gst= $('#globalGst').val();
    const sm = $('#globalSaleMode').val();
    if(wh) $('.unit-warehouse').val(wh);
    if(sp) $('.unit-sale-price').val(sp);
    if(gst!=='') $('.unit-gst').val(gst);
    $('.unit-sale-mode').val(sm);
    recalcBreakdown();
});

// ── Per-unit profit + breakdown ────────────────────────────────
$(document).on('input change','.unit-sale-price,.unit-gst,.unit-sale-mode', function(){
    recalcBreakdown();
});

function recalcBreakdown(){
    if(!selectedItem) return;
    const q = qty();
    const rawCostTotal = calcTotalRawCost();
    const cpu = q>0 ? rawCostTotal/q : 0;
    let totalRev=0, totalOutGst=0, totalItc=0;

    for(let i=0;i<q;i++){
        const sp   = parseFloat($(`[name="unit_sale_price[${i}]"]`).val())||0;
        const gst  = parseFloat($(`[name="unit_gst[${i}]"]`).val())||0;
        const mode = $(`[name="unit_sale_mode[${i}]"]`).val()||'exclusive';
        const rev  = mode==='inclusive' ? sp/(1+gst/100) : sp;
        const gstAmt = mode==='inclusive' ? sp-rev : sp*gst/100;
        totalRev     += rev;
        totalOutGst  += gstAmt;
        // per-unit profit
        const profit = rev - cpu;
        const $badge = $(`#profit_${i}`);
        $badge.text(money(profit)).css('color', profit>=0?'#34d399':'#ef4444');
    }

    // Input ITC (from raw materials)
    selectedItem.bom.forEach(m=>{ totalItc += m.qty_per_unit*q*m.purchase_price*(m.purchase_gst||0)/100; });

    const netProfit = totalRev - rawCostTotal;
    const margin    = rawCostTotal>0 ? ((netProfit/rawCostTotal)*100).toFixed(1) : '0.0';

    $('#bd_rawCost').text(money(rawCostTotal));
    $('#bd_itc').text(money(totalItc));
    $('#bd_cpu').text(money(cpu));
    $('#bd_rev').text(money(totalRev));
    $('#bd_margin').text(margin+'%').css('color',netProfit>=0?'#34d399':'#ef4444');
    $('#bd_profit').text(money(netProfit)).css('color',netProfit>=0?'#34d399':'#ef4444');
    $('#gst_out').text(money(totalOutGst));
    $('#gst_cgst').text(money(totalOutGst/2));
    $('#gst_sgst').text(money(totalOutGst/2));
    $('#gst_itc').text('— '+money(totalItc));
    $('#gst_pay').text(money(Math.max(0,totalOutGst-totalItc)));

    $('#m_totalCost').text(money(rawCostTotal));
    $('#m_costPerUnit').text(money(cpu));
}

function calcTotalRawCost(){
    if(!selectedItem) return 0;
    const q = qty();
    return selectedItem.bom.reduce((sum,m)=>sum + m.qty_per_unit*q*m.purchase_price, 0);
}

// ── Confirm pane ───────────────────────────────────────────────
function buildConfirm(){
    if(!selectedItem){ $('#confirmSummary').html('<span style="color:#ef4444">No product selected.</span>'); return; }
    const q=qty();
    const rawCost=calcTotalRawCost();
    const cpu=q>0?rawCost/q:0;
    let warnings=0;
    selectedItem.bom.forEach(m=>{ if(m.current_stock<m.qty_per_unit*q) warnings++; });

    $('#confirmSummary').html(`
        <table style="width:100%;font-size:13px">
            <tr><td style="color:#7890b5;padding:4px 0;width:180px">Finished Product</td><td style="color:#e2e8f0;font-weight:600">${selectedItem.name} (${selectedItem.item_code})</td></tr>
            <tr><td style="color:#7890b5;padding:4px 0">Units to Produce</td><td style="color:#f59e0b;font-weight:700">${q} unit(s)</td></tr>
            <tr><td style="color:#7890b5;padding:4px 0">Total Raw Cost</td><td style="color:#e2e8f0">${money(rawCost)}</td></tr>
            <tr><td style="color:#7890b5;padding:4px 0">Cost Per Unit</td><td style="color:#e2e8f0">${money(cpu)}</td></tr>
            <tr><td style="color:#7890b5;padding:4px 0">Raw Materials Used</td><td style="color:#e2e8f0">${selectedItem.bom.length} type(s)</td></tr>
            <tr><td style="color:#7890b5;padding:4px 0">Stock Warnings</td><td style="${warnings>0?'color:#ef4444;font-weight:700':'color:#34d399'}">${warnings>0?warnings+' material(s) below required qty':'All materials sufficient ✓'}</td></tr>
            <tr><td style="color:#7890b5;padding:4px 0">After Production</td><td style="color:#34d399;font-weight:700">+${q} units added to finished goods stock</td></tr>
        </table>
    `);
}

// ── Navigation ─────────────────────────────────────────────────
$('#pbNext').click(()=>{
    if(step===1 && !selectedItem){ alert('Please select a finished product template first.'); return; }
    if(step<4){ step++; renderStep(); }
});
$('#pbPrev').click(()=>{ if(step>1){ step--; renderStep(); } });

// Init
renderStep();
</script>
@endpush
