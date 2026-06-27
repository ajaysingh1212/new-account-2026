@extends('layouts.admin')
@section('title', 'New Purchase Return')

@push('styles')
<style>
/* ── Variables ─────────────────────────────────────────────────────────── */
:root {
    --pr-accent:   #6C3FC5;
    --pr-accent2:  #9B6FF5;
    --pr-danger:   #E53E3E;
    --pr-success:  #38A169;
    --pr-warning:  #D69E2E;
    --pr-bg:       #F7F6FC;
    --pr-card:     #FFFFFF;
    --pr-border:   #E2DCF7;
    --pr-text:     #2D2D3A;
    --pr-muted:    #7B7B9A;
    --pr-row-chk:  #F0EBFF;
    --pr-row-hover:#FAF8FF;
    --shadow-sm:   0 1px 4px rgba(108,63,197,.08);
    --shadow-md:   0 4px 18px rgba(108,63,197,.13);
    --radius:      12px;
}

/* ── Layout ──────────────────────────────────────────────────────────────── */
.pr-wrapper { background: var(--pr-bg); min-height: 100vh; padding: 24px; }

.pr-card {
    background: var(--pr-card);
    border-radius: var(--radius);
    border: 1px solid var(--pr-border);
    box-shadow: var(--shadow-md);
    overflow: hidden;
}

/* ── Header ──────────────────────────────────────────────────────────────── */
.pr-header {
    background: linear-gradient(135deg, var(--pr-accent) 0%, #9055E8 100%);
    padding: 22px 28px;
    display: flex;
    align-items: center;
    gap: 14px;
}
.pr-header-icon {
    width: 44px; height: 44px;
    background: rgba(255,255,255,.18);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; color: #fff;
}
.pr-header h4 { color: #fff; margin: 0; font-size: 1.15rem; font-weight: 600; }
.pr-header span { color: rgba(255,255,255,.75); font-size: .82rem; margin-top: 2px; display: block; }

/* ── Form Sections ───────────────────────────────────────────────────────── */
.pr-section { padding: 24px 28px; }
.pr-section + .pr-section { border-top: 1px solid var(--pr-border); }
.pr-section-title {
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: var(--pr-accent);
    margin-bottom: 16px;
    display: flex; align-items: center; gap: 8px;
}
.pr-section-title::after {
    content: ''; flex: 1; height: 1px;
    background: linear-gradient(to right, var(--pr-border), transparent);
}

.pr-form-label {
    font-size: .78rem;
    font-weight: 600;
    color: var(--pr-text);
    margin-bottom: 5px;
    display: block;
}
.pr-form-control {
    width: 100%;
    padding: 9px 12px;
    border: 1.5px solid var(--pr-border);
    border-radius: 8px;
    font-size: .88rem;
    color: var(--pr-text);
    background: #fff;
    transition: border-color .2s, box-shadow .2s;
}
.pr-form-control:focus {
    outline: none;
    border-color: var(--pr-accent);
    box-shadow: 0 0 0 3px rgba(108,63,197,.12);
}

/* ── Bill Selector ───────────────────────────────────────────────────────── */
.bill-select-wrap { position: relative; }
.bill-select-wrap .select2-container .select2-selection--single {
    height: 42px !important;
    border: 1.5px solid var(--pr-border) !important;
    border-radius: 8px !important;
    display: flex; align-items: center;
}
.bill-select-wrap .select2-container--open .select2-selection--single {
    border-color: var(--pr-accent) !important;
    box-shadow: 0 0 0 3px rgba(108,63,197,.12) !important;
}

/* ── Party Info Banner ───────────────────────────────────────────────────── */
#partyBanner {
    display: none;
    background: linear-gradient(90deg,#F0EBFF,#F7F4FF);
    border: 1px solid var(--pr-border);
    border-radius: 10px;
    padding: 10px 16px;
    margin-top: 12px;
    font-size: .85rem;
    color: var(--pr-text);
    align-items: center;
    gap: 10px;
}
#partyBanner i { color: var(--pr-accent); font-size: 1rem; }

/* ── Lines Loader ────────────────────────────────────────────────────────── */
#linesLoader {
    display: none;
    text-align: center;
    padding: 40px;
    color: var(--pr-muted);
    font-size: .9rem;
}
.spin-icon { animation: spin .8s linear infinite; display: inline-block; margin-right: 8px; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Items Table ─────────────────────────────────────────────────────────── */
#linesTable { display: none; }

.items-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: .84rem;
}
.items-table thead tr {
    background: linear-gradient(135deg,#F0EBFF,#FAF8FF);
}
.items-table thead th {
    padding: 11px 14px;
    text-align: left;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--pr-accent);
    border-bottom: 2px solid var(--pr-border);
    white-space: nowrap;
}
.items-table thead th:first-child { border-radius: 10px 0 0 0; }
.items-table thead th:last-child  { border-radius: 0 10px 0 0; }

.items-table tbody tr {
    transition: background .15s;
    border-bottom: 1px solid #F3F0FA;
}
.items-table tbody tr:hover { background: var(--pr-row-hover); }
.items-table tbody tr.selected { background: var(--pr-row-chk); }
.items-table tbody tr.disabled-row { opacity: .5; pointer-events: none; }

.items-table td {
    padding: 12px 14px;
    vertical-align: middle;
    color: var(--pr-text);
}

/* Checkbox custom ──────────────────────────────────────────────────────── */
.pr-check-wrap { display: flex; align-items: center; justify-content: center; }
.pr-checkbox {
    width: 18px; height: 18px;
    accent-color: var(--pr-accent);
    cursor: pointer;
}

/* Stock badge ───────────────────────────────────────────────────────────── */
.stock-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: .78rem;
    font-weight: 600;
}
.stock-badge.ok   { background: #EBFAF2; color: var(--pr-success); }
.stock-badge.low  { background: #FFF8E1; color: var(--pr-warning); }
.stock-badge.zero { background: #FFEBEB; color: var(--pr-danger);  }

/* Quantity input ────────────────────────────────────────────────────────── */
.qty-input {
    width: 110px;
    padding: 7px 10px;
    border: 1.5px solid var(--pr-border);
    border-radius: 8px;
    font-size: .88rem;
    text-align: center;
    color: var(--pr-text);
    transition: border-color .2s, box-shadow .2s;
}
.qty-input:focus {
    outline: none;
    border-color: var(--pr-accent);
    box-shadow: 0 0 0 3px rgba(108,63,197,.12);
}
.qty-input:disabled { background: #F5F5F5; cursor: not-allowed; }

/* Value pill ─────────────────────────────────────────────────────────────── */
.val-pill {
    display: inline-block;
    background: #F0EBFF;
    color: var(--pr-accent);
    border-radius: 8px;
    padding: 5px 12px;
    font-size: .84rem;
    font-weight: 700;
    min-width: 90px;
    text-align: right;
    letter-spacing: .01em;
}

/* ── Summary Footer ──────────────────────────────────────────────────────── */
#summaryBox {
    display: none;
    margin-top: 16px;
    background: linear-gradient(135deg,#6C3FC5,#9055E8);
    border-radius: var(--radius);
    padding: 18px 24px;
    color: #fff;
}
.summary-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    text-align: center;
}
.summary-item label {
    font-size: .7rem;
    font-weight: 600;
    letter-spacing: .1em;
    text-transform: uppercase;
    opacity: .75;
    display: block;
    margin-bottom: 4px;
}
.summary-item span {
    font-size: 1.15rem;
    font-weight: 700;
    display: block;
}

/* ── No Items Empty State ────────────────────────────────────────────────── */
#emptyState {
    display: none;
    text-align: center;
    padding: 48px 24px;
    color: var(--pr-muted);
}
#emptyState i { font-size: 2.5rem; opacity: .35; margin-bottom: 10px; display: block; }

/* ── Footer ──────────────────────────────────────────────────────────────── */
.pr-footer {
    padding: 18px 28px;
    border-top: 1px solid var(--pr-border);
    background: #FAFBFF;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
.btn-pr-submit {
    background: linear-gradient(135deg,var(--pr-accent),#9055E8);
    color: #fff;
    border: none;
    border-radius: 9px;
    padding: 10px 28px;
    font-size: .9rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: transform .15s, box-shadow .15s;
    box-shadow: 0 4px 14px rgba(108,63,197,.35);
}
.btn-pr-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(108,63,197,.45); }
.btn-pr-submit:disabled { opacity: .6; cursor: not-allowed; transform: none; }
.btn-pr-cancel {
    background: #fff;
    color: var(--pr-muted);
    border: 1.5px solid var(--pr-border);
    border-radius: 9px;
    padding: 10px 22px;
    font-size: .9rem;
    font-weight: 600;
    cursor: pointer;
    transition: border-color .2s, color .2s;
    text-decoration: none;
    display: inline-flex; align-items: center; gap: 6px;
}
.btn-pr-cancel:hover { border-color: var(--pr-accent); color: var(--pr-accent); text-decoration: none; }

/* Serial styles */
.serial-btn{width:38px;height:38px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;position:relative}
.serial-count{position:absolute;right:-7px;top:-7px;min-width:19px;height:19px;border-radius:999px;background:var(--pr-accent);color:#fff;font-size:10px;font-weight:800}
.serial-pill{display:inline-flex;align-items:center;gap:5px;border:1px solid #99f6e4;background:#f0fdfa;color:#0f766e;border-radius:999px;padding:3px 8px;margin:2px;font-size:11px;font-weight:700}
.serial-summary{max-width:260px}
.serial-drawer{position:fixed;right:-460px;top:0;width:min(440px,100vw);height:100vh;background:#fff;z-index:2050;box-shadow:-18px 0 50px rgba(15,23,42,.22);transition:right .22s ease;display:flex;flex-direction:column}
.serial-drawer.open{right:0}
.serial-drawer-head{padding:18px 20px;background:#172033;color:#fff}
.serial-drawer-body{padding:16px;overflow:auto;flex:1}
.serial-card{border:1px solid #e2e8f0;border-radius:8px;padding:11px;display:grid;grid-template-columns:26px 1fr;gap:8px;margin-bottom:9px;cursor:pointer}
.serial-card.selected{border-color:var(--pr-accent);background:#faf5ff}
.serial-card.disabled{background:#f8fafc;color:#94a3b8;cursor:not-allowed}
.serial-meta{font-size:11px;color:#64748b}
.serial-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.35);z-index:2040;display:none}
.serial-backdrop.show{display:block}

/* ── Responsive ──────────────────────────────────────────────────────────── */
@media (max-width: 768px) {
    .pr-wrapper { padding: 12px; }
    .pr-section  { padding: 16px; }
    .summary-grid { grid-template-columns: 1fr 1fr; }
}
</style>
@endpush

@section('content')
<div class="pr-wrapper">
<form method="POST" action="{{ route('admin.purchase-returns.store') }}" id="prForm">
@csrf

<div class="pr-card">

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="pr-header">
        <div class="pr-header-icon"><i class="fas fa-undo-alt"></i></div>
        <div>
            <h4>New Purchase Return</h4>
            <span>Select a bill, pick items to return, and set quantities</span>
        </div>
    </div>

    {{-- ── Section 1: Bill & Meta ──────────────────────────────────────────── --}}
    <div class="pr-section">
        <div class="pr-section-title"><i class="fas fa-file-invoice"></i> Bill Details</div>

        <div class="row">
            <div class="col-md-5 form-group mb-3">
                <label class="pr-form-label">Purchase Bill <span style="color:var(--pr-danger)">*</span></label>
                <div class="bill-select-wrap">
                    <select name="purchase_bill_id" id="sourceBill" class="form-control select2" required style="width:100%">
                        <option value="">&mdash; Select a purchase bill &mdash;</option>
                        @foreach($bills as $bill)
                        <option value="{{ $bill->id }}">
                            {{ $bill->invoice_no }} &nbsp;|&nbsp;
                            {{ $bill->party?->display_name ?: 'Cash' }} &nbsp;|&nbsp;
                            &#8377;{{ number_format((float)$bill->grand_total,2) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div id="partyBanner">
                    <i class="fas fa-building"></i>
                    <span id="partyBannerText"></span>
                </div>
            </div>

            <div class="col-md-2 form-group mb-3">
                <label class="pr-form-label">Return No.</label>
                <input name="return_no" class="pr-form-control" value="{{ $returnNo }}" placeholder="{{ $returnNo }}">
            </div>

            <div class="col-md-2 form-group mb-3">
                <label class="pr-form-label">Date <span style="color:var(--pr-danger)">*</span></label>
                <input type="date" name="return_date" class="pr-form-control" value="{{ now()->toDateString() }}" required>
            </div>

            <div class="col-md-3 form-group mb-3">
                <label class="pr-form-label">Reason</label>
                <input name="reason" class="pr-form-control" placeholder="e.g. Damaged goods, Wrong item...">
            </div>
        </div>
    </div>

    {{-- ── Section 2: Items ────────────────────────────────────────────────── --}}
    <div class="pr-section">
        <div class="pr-section-title"><i class="fas fa-boxes"></i> Return Items</div>

        {{-- Loader --}}
        <div id="linesLoader">
            <i class="fas fa-circle-notch spin-icon"></i> Loading bill items&hellip;
        </div>

        {{-- Empty state --}}
        <div id="emptyState">
            <i class="fas fa-receipt"></i>
            <p class="mb-0">Select a purchase bill above to load items</p>
        </div>

        {{-- Table --}}
        <div id="linesTable">
            <div class="table-responsive" style="border-radius:10px;border:1px solid var(--pr-border);overflow:hidden;">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width:44px;text-align:center">
                                <input type="checkbox" id="checkAll" class="pr-checkbox" title="Select All">
                            </th>
                            <th>#</th>
                            <th>Item Name</th>
                            <th>Purchased Qty</th>
                            <th>Current Stock</th>
                            <th>Return Qty</th>
                            <th>Serials</th>
                            <th>Unit Price</th>
                            <th>Tax %</th>
                            <th>Return Value</th>
                        </tr>
                    </thead>
                    <tbody id="linesBody"></tbody>
                </table>
            </div>

            {{-- Summary --}}
            <div id="summaryBox">
                <div class="summary-grid">
                    <div class="summary-item">
                        <label>Items Selected</label>
                        <span id="sumItems">0</span>
                    </div>
                    <div class="summary-item">
                        <label>Subtotal</label>
                        <span>&#8377;<span id="sumSubtotal">0.00</span></span>
                    </div>
                    <div class="summary-item">
                        <label>Grand Total</label>
                        <span>&#8377;<span id="sumTotal">0.00</span></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Section 3: Visibility ───────────────────────────────────────────── --}}
    <div class="pr-section">
        <div class="pr-section-title"><i class="fas fa-eye"></i> Visibility</div>
        @include('admin.partials.entry-visibility')
    </div>

    {{-- ── Footer ──────────────────────────────────────────────────────────── --}}
    <div class="pr-footer">
        <a href="{{ route('admin.purchase-returns.index') }}" class="btn-pr-cancel">
            <i class="fas fa-times"></i> Cancel
        </a>
        <button type="submit" class="btn-pr-submit" id="submitBtn" disabled>
            <i class="fas fa-paper-plane"></i> Post Purchase Return
        </button>
    </div>

</div>{{-- .pr-card --}}
</form>
</div>

<div class="serial-backdrop" id="serialBackdrop"></div>
<aside class="serial-drawer" id="serialDrawer">
    <div class="serial-drawer-head d-flex justify-content-between align-items-center">
        <div>
            <div class="small text-uppercase" style="opacity:.7;font-weight:800">Purchase Return Serials</div>
            <h5 class="mb-0" id="serialDrawerTitle">Select serials</h5>
        </div>
        <button type="button" class="btn btn-sm btn-outline-light" id="closeSerialDrawer">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="serial-drawer-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <b id="serialRequirement">Select serials</b>
            <button type="button" class="btn btn-outline-primary btn-sm" id="autoSelectSerials">
                <i class="fas fa-magic mr-1"></i>Auto
            </button>
        </div>
        <div id="serialGrid"></div>
    </div>
    <div class="p-3 border-top text-right">
        <button type="button" class="btn btn-outline-secondary" id="cancelSerialDrawer">Cancel</button>
        <button type="button" class="btn btn-primary" id="saveSerialSelection">Use selected</button>
    </div>
</aside>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    // ── Unicode constants (Indian Rupee symbol) ──────────────────────────────
    const INR = '\u20B9';  // ₹

    const BILL_ITEMS_URL = "{{ route('admin.purchase-returns.bill-items') }}";
    const CSRF           = "{{ csrf_token() }}";

    let linesData = [];
    let activeSerialIndex = null, modalSelection = [];

    // ── DOM refs ─────────────────────────────────────────────────────────────
    const $billSel     = $('#sourceBill');
    const $loader      = $('#linesLoader');
    const $empty       = $('#emptyState');
    const $table       = $('#linesTable');
    const $tbody       = $('#linesBody');
    const $checkAll    = $('#checkAll');
    const $submitBtn   = $('#submitBtn');
    const $partyBanner = $('#partyBanner');
    const $partyText   = $('#partyBannerText');
    const $summaryBox  = $('#summaryBox');

    // ── Init empty state ─────────────────────────────────────────────────────
    $empty.show();
    $loader.hide();
    $table.hide();

    // ── Bill selection ───────────────────────────────────────────────────────
    $billSel.on('change', function () {
        const billId = $(this).val();
        if (!billId) { reset(); return; }

        $empty.hide();
        $table.hide();
        $loader.show();
        $partyBanner.hide();

        fetch(`${BILL_ITEMS_URL}?bill_id=${billId}`, {
            headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            $loader.hide();
            linesData = data.lines;

            $partyText.text(`Party: ${data.party}  |  Type: ${data.purchase_type}`);
            $partyBanner.css('display', 'flex');

            renderLines(linesData);
            $table.show();
            recalcSummary();
        })
        .catch(() => {
            $loader.hide();
            $empty.show();
            toastr.error('Bill items load nahi hue. Dobara try karein.');
        });
    });

    // ── Render table rows ────────────────────────────────────────────────────
    function renderLines(lines) {
        $tbody.empty();
        $checkAll.prop('checked', false);

        if (!lines.length) {
            $tbody.html(`<tr><td colspan="10" style="text-align:center;color:var(--pr-muted);padding:32px;">Is bill mein koi item nahi mila.</td></tr>`);
            return;
        }

        lines.forEach((l, i) => {
            const stockClass = l.current_stock > 0 ? (l.current_stock < l.purchased_qty ? 'low' : 'ok') : 'zero';
            const stockIcon  = l.current_stock > 0 ? 'fa-check-circle' : 'fa-times-circle';

            const serialsCell = hasSerials(l)
                ? `<button type="button" class="btn btn-outline-primary serial-btn open-serials" data-idx="${i}" disabled>
                       <i class="fas fa-barcode"></i>
                       <span class="serial-count" data-idx="${i}">0</span>
                   </button>
                   <div class="serial-summary mt-1" data-idx="${i}"></div>`
                : `<span class="text-muted small">Track nahi</span>`;

            const row = `
            <tr data-idx="${i}">
                <td style="text-align:center">
                    <div class="pr-check-wrap">
                        <input type="checkbox" class="pr-checkbox line-check" data-idx="${i}">
                        <input type="hidden" name="line_id[]" value="" class="line-id-inp" disabled>
                    </div>
                </td>
                <td style="color:var(--pr-muted);font-size:.8rem">${i + 1}</td>
                <td>
                    <strong style="color:var(--pr-text)">${escHtml(l.item_name)}</strong>
                    <br><small style="color:var(--pr-muted)">${escHtml(l.unit)}</small>
                </td>
                <td>
                    <span style="font-weight:600">${fmt(l.purchased_qty)}</span>
                    <small style="color:var(--pr-muted)"> ${escHtml(l.unit)}</small>
                </td>
                <td>
                    <span class="stock-badge ${stockClass}">
                        <i class="fas ${stockIcon}"></i>
                        ${fmt(l.current_stock)} ${escHtml(l.unit)}
                    </span>
                </td>
                <td>
                    <input type="number"
                           class="qty-input line-qty"
                           name="quantity[]"
                           data-idx="${i}"
                           step="0.001"
                           min="0.001"
                           max="${l.purchased_qty}"
                           value="${l.purchased_qty}"
                           disabled>
                </td>
                <td>
                    <input type="hidden" name="returned_units[]" class="returned-units" data-idx="${i}" value="[]" disabled>
                    ${serialsCell}
                </td>
                <td style="color:var(--pr-muted)">${INR}${fmt2(l.unit_price)}</td>
                <td>
                    <span style="color:var(--pr-muted)">${l.tax_percent}%</span>
                </td>
                <td>
                    <span class="val-pill line-val" data-idx="${i}">${INR}${calcLineVal(l, l.purchased_qty)}</span>
                </td>
            </tr>`;
            $tbody.append(row);
        });

        bindRowEvents();
    }

    // ── Row events ───────────────────────────────────────────────────────────
    function bindRowEvents() {

        // Checkbox toggle
        $tbody.find('.line-check').on('change', function () {
            const idx      = $(this).data('idx');
            const $row     = $tbody.find(`tr[data-idx="${idx}"]`);
            const $qtyInp  = $row.find('.line-qty');
            const $hiddenId= $row.find('.line-id-inp');
            const $unitsInp= $row.find('.returned-units');
            const checked  = $(this).is(':checked');

            $row.toggleClass('selected', checked);
            $qtyInp.prop('disabled', !checked);
            $hiddenId.prop('disabled', !checked);
            $hiddenId.val(checked ? linesData[idx].id : '');
            $unitsInp.prop('disabled', !checked);
            $row.find('.open-serials').prop('disabled', !checked);

            if (checked) reconcileSerialSelection(idx);
            else         setSelectedUnits(idx, []);

            recalcSummary();
        });

        // Quantity change
        $tbody.find('.line-qty').on('input', function () {
            const idx = $(this).data('idx');
            const l   = linesData[idx];
            let   qty = parseFloat($(this).val()) || 0;
            const max = l.purchased_qty;

            if (qty > max) { qty = max; $(this).val(max); }
            if (qty < 0)   { qty = 0;   $(this).val(0); }

            $tbody.find(`.line-val[data-idx="${idx}"]`).text(`${INR}${calcLineVal(l, qty)}`);
            reconcileSerialSelection(idx);
            recalcSummary();
        });

        // Check-all
        $checkAll.off('change').on('change', function () {
            const checked = $(this).is(':checked');
            $tbody.find('.line-check').each(function () {
                if ($(this).is(':checked') !== checked) {
                    $(this).prop('checked', checked).trigger('change');
                }
            });
        });

        // Open serial drawer
        $tbody.find('.open-serials').off('click').on('click', function () {
            activeSerialIndex = $(this).data('idx');
            modalSelection    = selectedUnits(activeSerialIndex);
            $('#serialDrawerTitle').text(linesData[activeSerialIndex].item_name);
            renderSerialDrawer();
            $('#serialDrawer').addClass('open');
            $('#serialBackdrop').addClass('show');
        });
    }

    // ── Serial drawer events ─────────────────────────────────────────────────
    function closeDrawer() {
        $('#serialDrawer').removeClass('open');
        $('#serialBackdrop').removeClass('show');
        activeSerialIndex = null;
    }

    $('#closeSerialDrawer, #cancelSerialDrawer').on('click', closeDrawer);
    $('#serialBackdrop').on('click', closeDrawer);

    $('#autoSelectSerials').on('click', function () {
        if (activeSerialIndex === null) return;
        const line      = linesData[activeSerialIndex];
        const required  = requiredQty(activeSerialIndex);
        const available = line.available_units || [];
        modalSelection  = available.slice(0, required);
        renderSerialDrawer();
    });

    $(document).on('change', '.drawer-serial-check', function () {
        if (activeSerialIndex === null) return;
        const line      = linesData[activeSerialIndex];
        const required  = requiredQty(activeSerialIndex);
        const key       = $(this).data('key');
        const allUnits  = line.purchased_units || [];

        if ($(this).is(':checked')) {
            if (modalSelection.length >= required) {
                $(this).prop('checked', false);
                toastr.warning(`Sirf ${required} serial select kar sakte hain.`);
                return;
            }
            const unit = allUnits.find(u => u.key === key);
            if (unit) modalSelection.push(unit);
        } else {
            modalSelection = modalSelection.filter(u => u.key !== key);
        }
        $('#serialRequirement').text(`Exactly ${required} serial select karein (${modalSelection.length} selected)`);
    });

    $('#saveSerialSelection').on('click', function () {
        if (activeSerialIndex === null) return;
        const required = requiredQty(activeSerialIndex);
        if (modalSelection.length !== required) {
            toastr.warning(`Exactly ${required} serial${required === 1 ? '' : 's'} select karein.`);
            return;
        }
        setSelectedUnits(activeSerialIndex, modalSelection);
        closeDrawer();
    });

    // ── Summary recalc ───────────────────────────────────────────────────────
    function recalcSummary() {
        let count = 0, subtotal = 0, total = 0;

        $tbody.find('.line-check:checked').each(function () {
            const idx = $(this).data('idx');
            const l   = linesData[idx];
            const qty = parseFloat($tbody.find(`.line-qty[data-idx="${idx}"]`).val()) || 0;

            if (qty > 0) {
                count++;
                const ratio     = l.purchased_qty > 0 ? qty / l.purchased_qty : 0;
                const taxAmt    = l.tax_amount  * ratio;
                const lineTotal = l.line_total  * ratio;
                subtotal += Math.max(0, lineTotal - taxAmt);
                total    += lineTotal;
            }
        });

        const hasLines = count > 0;
        $submitBtn.prop('disabled', !hasLines);
        $summaryBox.toggle(hasLines);

        if (hasLines) {
            $('#sumItems').text(count);
            $('#sumSubtotal').text(fmt2(subtotal));
            $('#sumTotal').text(fmt2(total));
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────────
    function calcLineVal(l, qty) {
        if (!qty || !l.purchased_qty) return '0.00';
        return fmt2(l.line_total * (qty / l.purchased_qty));
    }

    function hasSerials(line)  { return (line.purchased_units || []).length > 0; }
    function unitLabel(unit)   { return unit.serial_no || unit.vts_sim || unit.sku || unit.batch_no || unit.key || 'Serial'; }
    function requiredQty(idx)  { return Math.max(0, Math.floor(parseFloat($tbody.find(`.line-qty[data-idx="${idx}"]`).val()) || 0)); }

    function selectedUnits(idx) {
        try { return JSON.parse($tbody.find(`.returned-units[data-idx="${idx}"]`).val() || '[]'); }
        catch(e) { return []; }
    }

    function setSelectedUnits(idx, units) {
        $tbody.find(`.returned-units[data-idx="${idx}"]`).val(JSON.stringify(units));
        $tbody.find(`.serial-count[data-idx="${idx}"]`).text(units.length);
        $tbody.find(`.serial-summary[data-idx="${idx}"]`).html(
            units.length
                ? units.map(u => `<span class="serial-pill"><i class="fas fa-barcode"></i>${escHtml(unitLabel(u))}</span>`).join('')
                : '<span class="text-muted small">Koi serial select nahi</span>'
        );
    }

    function reconcileSerialSelection(idx) {
        const line = linesData[idx];
        if (!line || !hasSerials(line)) return;
        const required  = requiredQty(idx);
        const available = line.available_units || [];
        let chosen      = selectedUnits(idx).filter(sel => available.some(u => u.key === sel.key)).slice(0, required);
        const keys      = chosen.map(u => u.key);
        if (chosen.length < required) {
            chosen = chosen.concat(available.filter(u => !keys.includes(u.key)).slice(0, required - chosen.length));
        }
        setSelectedUnits(idx, chosen);
    }

    function renderSerialDrawer() {
        const line         = linesData[activeSerialIndex];
        const required     = requiredQty(activeSerialIndex);
        const selectedKeys = modalSelection.map(u => u.key);
        const availableKeys= (line.available_units || []).map(u => u.key);

        $('#serialRequirement').text(`Exactly ${required} serial${required === 1 ? '' : 's'} select karein (${modalSelection.length} selected)`);

        $('#serialGrid').html(
            (line.purchased_units || []).map(unit => {
                const available = availableKeys.includes(unit.key);
                const selected  = selectedKeys.includes(unit.key);
                return `
                <label class="serial-card ${available ? '' : 'disabled'} ${selected ? 'selected' : ''}">
                    <input type="checkbox" class="drawer-serial-check"
                           data-key="${escHtml(unit.key)}"
                           ${selected  ? 'checked'  : ''}
                           ${available ? '' : 'disabled'}>
                    <span>
                        <b>${escHtml(unitLabel(unit))}</b>
                        <div class="serial-meta">
                            Batch: ${escHtml(unit.batch_no || '-')} &nbsp;|&nbsp;
                            VTS/SIM: ${escHtml(unit.vts_sim || '-')} &nbsp;|&nbsp;
                            SKU: ${escHtml(unit.sku || '-')}
                        </div>
                        <div class="${available ? 'text-success' : 'text-muted'} small font-weight-bold">
                            ${available ? 'Stock mein available' : 'Stock mein nahi hai'}
                        </div>
                    </span>
                </label>`;
            }).join('')
        );
    }

    // ── Formatters ───────────────────────────────────────────────────────────
    function fmt(n)  { return parseFloat(n).toLocaleString('en-IN', { maximumFractionDigits: 3 }); }
    function fmt2(n) { return parseFloat(n).toFixed(2); }
    function escHtml(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    // ── Reset ────────────────────────────────────────────────────────────────
    function reset() {
        linesData = [];
        $tbody.empty();
        $table.hide();
        $loader.hide();
        $empty.show();
        $partyBanner.hide();
        $summaryBox.hide();
        $submitBtn.prop('disabled', true);
    }

    // ── Form validation ──────────────────────────────────────────────────────
    $('#prForm').on('submit', function (e) {
        if (!$tbody.find('.line-check:checked').length) {
            e.preventDefault();
            toastr.warning('Kam se kam ek item return ke liye select karein.');
        }
    });

})();
</script>
@endpush
