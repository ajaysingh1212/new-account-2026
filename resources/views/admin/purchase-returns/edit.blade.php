@extends('layouts.admin')
@section('title', 'Edit Purchase Return — ' . $return->return_no)

@push('styles')
{{-- Same styles as create.blade.php — copy the full <style> block from create.blade.php here --}}
<style>
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
.pr-wrapper { background: var(--pr-bg); min-height: 100vh; padding: 24px; }
.pr-card {
    background: var(--pr-card);
    border-radius: var(--radius);
    border: 1px solid var(--pr-border);
    box-shadow: var(--shadow-md);
    overflow: hidden;
}
.pr-header {
    background: linear-gradient(135deg, #3B5998 0%, #6C3FC5 100%);
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
.pr-edit-badge {
    margin-left: auto;
    background: rgba(255,255,255,.2);
    color: #fff;
    border-radius: 8px;
    padding: 5px 14px;
    font-size: .78rem;
    font-weight: 600;
    letter-spacing: .06em;
}
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
    font-size: .78rem; font-weight: 600; color: var(--pr-text);
    margin-bottom: 5px; display: block;
}
.pr-form-control {
    width: 100%; padding: 9px 12px;
    border: 1.5px solid var(--pr-border);
    border-radius: 8px; font-size: .88rem;
    color: var(--pr-text); background: #fff;
    transition: border-color .2s, box-shadow .2s;
}
.pr-form-control:focus {
    outline: none; border-color: var(--pr-accent);
    box-shadow: 0 0 0 3px rgba(108,63,197,.12);
}
.pr-form-control[readonly] { background: #F5F3FF; cursor: default; color: var(--pr-muted); }
#partyBanner {
    display: flex;
    background: linear-gradient(90deg,#F0EBFF,#F7F4FF);
    border: 1px solid var(--pr-border);
    border-radius: 10px;
    padding: 10px 16px;
    margin-top: 12px;
    font-size: .85rem; color: var(--pr-text);
    align-items: center; gap: 10px;
}
#partyBanner i { color: var(--pr-accent); font-size: 1rem; }
.items-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: .84rem;
}
.items-table thead tr { background: linear-gradient(135deg,#F0EBFF,#FAF8FF); }
.items-table thead th {
    padding: 11px 14px; text-align: left;
    font-size: .72rem; font-weight: 700;
    letter-spacing: .08em; text-transform: uppercase;
    color: var(--pr-accent); border-bottom: 2px solid var(--pr-border);
    white-space: nowrap;
}
.items-table thead th:first-child { border-radius: 10px 0 0 0; }
.items-table thead th:last-child  { border-radius: 0 10px 0 0; }
.items-table tbody tr { transition: background .15s; border-bottom: 1px solid #F3F0FA; }
.items-table tbody tr:hover  { background: var(--pr-row-hover); }
.items-table tbody tr.selected { background: var(--pr-row-chk); }
.items-table td { padding: 12px 14px; vertical-align: middle; color: var(--pr-text); }
.pr-check-wrap { display: flex; align-items: center; justify-content: center; }
.pr-checkbox { width: 18px; height: 18px; accent-color: var(--pr-accent); cursor: pointer; }
.stock-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 20px;
    font-size: .78rem; font-weight: 600;
}
.stock-badge.ok   { background: #EBFAF2; color: var(--pr-success); }
.stock-badge.low  { background: #FFF8E1; color: var(--pr-warning); }
.stock-badge.zero { background: #FFEBEB; color: var(--pr-danger);  }
.qty-input {
    width: 110px; padding: 7px 10px;
    border: 1.5px solid var(--pr-border);
    border-radius: 8px; font-size: .88rem;
    text-align: center; color: var(--pr-text);
    transition: border-color .2s, box-shadow .2s;
}
.qty-input:focus {
    outline: none; border-color: var(--pr-accent);
    box-shadow: 0 0 0 3px rgba(108,63,197,.12);
}
.qty-input:disabled { background: #F5F5F5; cursor: not-allowed; }
.val-pill {
    display: inline-block; background: #F0EBFF;
    color: var(--pr-accent); border-radius: 8px;
    padding: 5px 12px; font-size: .84rem; font-weight: 700;
    min-width: 90px; text-align: right; letter-spacing: .01em;
}
#summaryBox {
    margin-top: 16px;
    background: linear-gradient(135deg,#3B5998,#6C3FC5);
    border-radius: var(--radius);
    padding: 18px 24px; color: #fff;
}
.summary-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 12px; text-align: center; }
.summary-item label {
    font-size: .7rem; font-weight: 600;
    letter-spacing: .1em; text-transform: uppercase;
    opacity: .75; display: block; margin-bottom: 4px;
}
.summary-item span { font-size: 1.15rem; font-weight: 700; display: block; }
.pr-footer {
    padding: 18px 28px;
    border-top: 1px solid var(--pr-border);
    background: #FAFBFF;
    display: flex; justify-content: flex-end; gap: 10px;
}
.btn-pr-submit {
    background: linear-gradient(135deg,#3B5998,var(--pr-accent));
    color: #fff; border: none; border-radius: 9px;
    padding: 10px 28px; font-size: .9rem; font-weight: 600;
    cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
    transition: transform .15s, box-shadow .15s;
    box-shadow: 0 4px 14px rgba(59,89,152,.35);
}
.btn-pr-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(59,89,152,.45); }
.btn-pr-submit:disabled { opacity: .6; cursor: not-allowed; transform: none; }
.btn-pr-cancel {
    background: #fff; color: var(--pr-muted);
    border: 1.5px solid var(--pr-border);
    border-radius: 9px; padding: 10px 22px;
    font-size: .9rem; font-weight: 600;
    cursor: pointer; transition: border-color .2s, color .2s;
    text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
}
.btn-pr-cancel:hover { border-color: var(--pr-accent); color: var(--pr-accent); text-decoration: none; }
.warn-alert {
    background: #FFF8E1; border: 1px solid #F6C90E;
    border-radius: 10px; padding: 12px 16px;
    font-size: .84rem; color: #7A5C00;
    display: flex; align-items: flex-start; gap: 10px; margin-bottom: 16px;
}
.warn-alert i { color: var(--pr-warning); margin-top: 1px; }
@media (max-width: 768px) {
    .pr-wrapper { padding: 12px; }
    .pr-section  { padding: 16px; }
    .summary-grid { grid-template-columns: 1fr 1fr; }
}
</style>
@endpush

@section('content')
{{-- Pass existing quantities to JS --}}
@php
$existingQtyJson = $existingQty->toJson();
@endphp

<div class="pr-wrapper">
<form method="POST" action="{{ route('admin.purchase-returns.update', $return) }}" id="prForm">
@csrf
@method('PUT')

<div class="pr-card">

    {{-- ── Header ──────────────────────────────────────────────── --}}
    <div class="pr-header">
        <div class="pr-header-icon"><i class="fas fa-edit"></i></div>
        <div>
            <h4>Edit Purchase Return</h4>
            <span>Modify quantities — old entries will be reversed and re-posted</span>
        </div>
        <div class="pr-edit-badge">{{ $return->return_no }}</div>
    </div>

    {{-- ── Warning Banner ──────────────────────────────────────── --}}
    <div class="pr-section" style="padding-bottom:0">
        <div class="warn-alert">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>Editing will reverse existing stock movements and ledger entries</strong>, then re-post them with the new quantities. Make sure all changes are correct before saving.
            </div>
        </div>
    </div>

    {{-- ── Section 1: Bill & Meta ──────────────────────────────── --}}
    <div class="pr-section">
        <div class="pr-section-title"><i class="fas fa-file-invoice"></i> Bill Details</div>

        <div class="row">
            <div class="col-md-5 form-group mb-3">
                <label class="pr-form-label">Purchase Bill</label>
                {{-- Bill is locked on edit --}}
                <input class="pr-form-control" readonly
                       value="{{ $return->bill->invoice_no }} | {{ $return->party?->display_name ?: 'Cash' }} | ₹{{ number_format((float)$return->bill->grand_total,2) }}">
                <input type="hidden" name="purchase_bill_id" value="{{ $return->purchase_bill_id }}">

                <div id="partyBanner">
                    <i class="fas fa-building"></i>
                    <span>
                        Party: {{ $return->party?->display_name ?? 'Cash' }}
                        &nbsp;|&nbsp;
                        Type: {{ $return->bill->purchase_type }}
                    </span>
                </div>
            </div>

            <div class="col-md-2 form-group mb-3">
                <label class="pr-form-label">Return No.</label>
                <input name="return_no" class="pr-form-control" value="{{ $return->return_no }}">
            </div>

            <div class="col-md-2 form-group mb-3">
                <label class="pr-form-label">Date <span style="color:var(--pr-danger)">*</span></label>
                <input type="date" name="return_date" class="pr-form-control"
                       value="{{ $return->return_date }}" required>
            </div>

            <div class="col-md-3 form-group mb-3">
                <label class="pr-form-label">Reason</label>
                <input name="reason" class="pr-form-control"
                       value="{{ $return->reason }}" placeholder="Reason for return…">
            </div>
        </div>
    </div>

    {{-- ── Section 2: Items ────────────────────────────────────── --}}
    <div class="pr-section">
        <div class="pr-section-title"><i class="fas fa-boxes"></i> Return Items</div>

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
                        <th>Unit Price</th>
                        <th>Tax %</th>
                        <th>Return Value</th>
                    </tr>
                </thead>
                <tbody id="linesBody">
                @php
                    $existingQtyArr = json_decode($existingQtyJson, true);
                @endphp
                @foreach($return->bill->items as $i => $line)
                @php
                    $prevQty    = $existingQtyArr[$line->id] ?? null;
                    $isSelected = $prevQty !== null;

                    // Current stock from StockMovement
                    $currentStock = \App\Models\StockMovement::where('item_id', $line->item_id)
                        ->where('company_id', auth()->user()->current_company_id)
                        ->selectRaw("SUM(CASE WHEN direction='in' THEN quantity ELSE -quantity END) as net")
                        ->value('net') ?? 0;

                    $stockClass = $currentStock > 0 ? ($currentStock < $line->quantity ? 'low' : 'ok') : 'zero';
                    $stockIcon  = $currentStock > 0 ? 'fa-check-circle' : 'fa-times-circle';

                    $returnQty  = $prevQty ?? $line->quantity;
                    $ratio      = $line->quantity > 0 ? $returnQty / $line->quantity : 0;
                    $lineVal    = $line->line_total * $ratio;
                @endphp
                <tr data-idx="{{ $i }}"
                    data-line-total="{{ $line->line_total }}"
                    data-tax-amount="{{ $line->tax_amount }}"
                    data-purchased="{{ $line->quantity }}"
                    class="{{ $isSelected ? 'selected' : '' }}">
                    <td style="text-align:center">
                        <div class="pr-check-wrap">
                            <input type="checkbox"
                                   class="pr-checkbox line-check"
                                   data-idx="{{ $i }}"
                                   {{ $isSelected ? 'checked' : '' }}>
                            <input type="hidden"
                                   name="line_id[]"
                                   value="{{ $isSelected ? $line->id : '' }}"
                                   class="line-id-inp"
                                   {{ $isSelected ? '' : 'disabled' }}>
                        </div>
                    </td>
                    <td style="color:var(--pr-muted);font-size:.8rem">{{ $i + 1 }}</td>
                    <td>
                        <strong>{{ $line->item?->name ?? '—' }}</strong>
                        <br><small style="color:var(--pr-muted)">{{ $line->unit }}</small>
                    </td>
                    <td>
                        <span style="font-weight:600">{{ number_format($line->quantity,3) }}</span>
                        <small style="color:var(--pr-muted)"> {{ $line->unit }}</small>
                    </td>
                    <td>
                        <span class="stock-badge {{ $stockClass }}">
                            <i class="fas {{ $stockIcon }}"></i>
                            {{ number_format($currentStock,3) }} {{ $line->unit }}
                        </span>
                    </td>
                    <td>
                        <input type="number"
                               class="qty-input line-qty"
                               data-idx="{{ $i }}"
                               data-line-id="{{ $line->id }}"
                               step="0.001"
                               min="0.001"
                               max="{{ $line->quantity }}"
                               value="{{ $returnQty }}"
                               {{ $isSelected ? '' : 'disabled' }}>
                    </td>
                    <td style="color:var(--pr-muted)">₹{{ number_format($line->unit_price,2) }}</td>
                    <td><span style="color:var(--pr-muted)">{{ $line->tax_percent }}%</span></td>
                    <td>
                        <span class="val-pill line-val" data-idx="{{ $i }}">
                            ₹{{ number_format($lineVal,2) }}
                        </span>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        {{-- Summary --}}
        <div id="summaryBox" style="{{ $existingQty->count() ? '' : 'display:none' }}">
            <div class="summary-grid">
                <div class="summary-item">
                    <label>Items Selected</label>
                    <span id="sumItems">{{ $existingQty->count() }}</span>
                </div>
                <div class="summary-item">
                    <label>Subtotal</label>
                    <span>₹<span id="sumSubtotal">{{ number_format($return->subtotal,2) }}</span></span>
                </div>
                <div class="summary-item">
                    <label>Grand Total</label>
                    <span>₹<span id="sumTotal">{{ number_format($return->grand_total,2) }}</span></span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Section 3: Visibility ───────────────────────────────── --}}
    <div class="pr-section">
        <div class="pr-section-title"><i class="fas fa-eye"></i> Visibility</div>
        @include('admin.partials.entry-visibility')
    </div>

    {{-- ── Footer ──────────────────────────────────────────────── --}}
    <div class="pr-footer">
        <a href="{{ route('admin.purchase-returns.show', $return) }}" class="btn-pr-cancel">
            <i class="fas fa-times"></i> Cancel
        </a>
        <button type="submit" class="btn-pr-submit" id="submitBtn">
            <i class="fas fa-save"></i> Save Changes
        </button>
    </div>

</div>{{-- .pr-card --}}
</form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const $tbody     = $('#linesBody');
    const $checkAll  = $('#checkAll');
    const $submitBtn = $('#submitBtn');

    // ── Checkbox toggle ──────────────────────────────────────────
    $tbody.on('change', '.line-check', function () {
        const idx      = $(this).data('idx');
        const $row     = $tbody.find(`tr[data-idx="${idx}"]`);
        const $qtyInp  = $row.find('.line-qty');
        const $hiddenId= $row.find('.line-id-inp');
        const checked  = $(this).is(':checked');
        const lineId   = $qtyInp.data('line-id');

        $row.toggleClass('selected', checked);
        $qtyInp.prop('disabled', !checked);
        $hiddenId.prop('disabled', !checked).val(checked ? lineId : '');

        recalcSummary();
    });

    // ── Quantity change ──────────────────────────────────────────
    $tbody.on('input', '.line-qty', function () {
        const idx  = $(this).data('idx');
        const $row = $tbody.find(`tr[data-idx="${idx}"]`);
        const max  = parseFloat($row.data('purchased')) || 0;
        let   qty  = parseFloat($(this).val()) || 0;

        if (qty > max) { qty = max; $(this).val(max); }
        if (qty < 0)   { qty = 0;   $(this).val(0); }

        const lineTotal = parseFloat($row.data('line-total')) || 0;
        const ratio     = max > 0 ? qty / max : 0;
        $row.find('.line-val').text(`₹${(lineTotal * ratio).toFixed(2)}`);

        recalcSummary();
    });

    // ── Check-all ────────────────────────────────────────────────
    $checkAll.on('change', function () {
        const checked = $(this).is(':checked');
        $tbody.find('.line-check').each(function () {
            if ($(this).is(':checked') !== checked) {
                $(this).prop('checked', checked).trigger('change');
            }
        });
    });

    // ── Summary ──────────────────────────────────────────────────
    function recalcSummary() {
        let count = 0, subtotal = 0, total = 0;

        $tbody.find('.line-check:checked').each(function () {
            const idx       = $(this).data('idx');
            const $row      = $tbody.find(`tr[data-idx="${idx}"]`);
            const purchased = parseFloat($row.data('purchased'))  || 0;
            const lineTotal = parseFloat($row.data('line-total')) || 0;
            const taxAmount = parseFloat($row.data('tax-amount')) || 0;
            const qty       = parseFloat($row.find('.line-qty').val()) || 0;

            if (qty > 0 && purchased > 0) {
                count++;
                const ratio  = qty / purchased;
                total    += lineTotal * ratio;
                subtotal += Math.max(0, (lineTotal - taxAmount) * ratio);
            }
        });

        const hasLines = count > 0;
        $submitBtn.prop('disabled', !hasLines);
        $('#summaryBox').toggle(hasLines);
        if (hasLines) {
            $('#sumItems').text(count);
            $('#sumSubtotal').text(subtotal.toFixed(2));
            $('#sumTotal').text(total.toFixed(2));
        }
    }

    // ── Init summary ─────────────────────────────────────────────
    recalcSummary();

    // ── Form guard ───────────────────────────────────────────────
    $('#prForm').on('submit', function (e) {
        if (!$tbody.find('.line-check:checked').length) {
            e.preventDefault();
            toastr.warning('Please select at least one item to return.');
        }
    });

})();
</script>
@endpush
