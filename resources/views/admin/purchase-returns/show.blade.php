@extends('layouts.admin')
@section('title', 'Purchase Return &mdash; ' . $return->return_no)

@push('styles')
<style>
:root {
    --pr-accent:  #6C3FC5;
    --pr-danger:  #E53E3E;
    --pr-success: #38A169;
    --pr-warning: #D69E2E;
    --pr-bg:      #F7F6FC;
    --pr-card:    #FFFFFF;
    --pr-border:  #E2DCF7;
    --pr-text:    #2D2D3A;
    --pr-muted:   #7B7B9A;
    --shadow-md:  0 4px 18px rgba(108,63,197,.13);
    --radius:     12px;
}

.pr-wrapper { background: var(--pr-bg); min-height: 100vh; padding: 24px; }

.pr-card {
    background: var(--pr-card);
    border-radius: var(--radius);
    border: 1px solid var(--pr-border);
    box-shadow: var(--shadow-md);
    overflow: hidden;
}

/* Header */
.pr-header {
    background: linear-gradient(135deg, #3B5998 0%, #6C3FC5 100%);
    padding: 22px 28px;
    display: flex; align-items: center; gap: 14px;
}
.pr-header-icon {
    width: 48px; height: 48px;
    background: rgba(255,255,255,.18); border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; color: #fff; flex-shrink: 0;
}
.pr-header h4 { color: #fff; margin: 0; font-size: 1.15rem; font-weight: 700; }
.pr-header span { color: rgba(255,255,255,.72); font-size: .82rem; display: block; margin-top: 2px; }
.pr-header-actions { margin-left: auto; display: flex; gap: 8px; }
.btn-hdr {
    border-radius: 8px; padding: 7px 16px;
    font-size: .82rem; font-weight: 600;
    display: inline-flex; align-items: center; gap: 6px;
    text-decoration: none; transition: background .15s, transform .12s;
    border: none; cursor: pointer;
}
.btn-hdr:hover { transform: translateY(-1px); text-decoration: none; }
.btn-hdr-back   { background: rgba(255,255,255,.18); color: #fff; border: 1.5px solid rgba(255,255,255,.3); }
.btn-hdr-back:hover { background: rgba(255,255,255,.28); color: #fff; }
.btn-hdr-edit   { background: #fff; color: var(--pr-accent); }
.btn-hdr-edit:hover { background: #F0EBFF; color: var(--pr-accent); }
.btn-hdr-print  { background: rgba(255,255,255,.1); color: #fff; border: 1.5px solid rgba(255,255,255,.25); }
.btn-hdr-print:hover { background: rgba(255,255,255,.2); color: #fff; }

/* Status badge in header */
.pr-return-badge {
    background: rgba(255,255,255,.2); color: #fff;
    border-radius: 8px; padding: 5px 14px;
    font-size: .78rem; font-weight: 700; letter-spacing: .06em;
    border: 1px solid rgba(255,255,255,.3);
}

/* Section */
.pr-section { padding: 24px 28px; }
.pr-section + .pr-section { border-top: 1px solid var(--pr-border); }
.pr-section-title {
    font-size: .68rem; font-weight: 700; letter-spacing: .12em;
    text-transform: uppercase; color: var(--pr-accent);
    margin-bottom: 18px; display: flex; align-items: center; gap: 8px;
}
.pr-section-title::after {
    content: ''; flex: 1; height: 1px;
    background: linear-gradient(to right, var(--pr-border), transparent);
}

/* Info grid */
.info-grid {
    display: grid; grid-template-columns: repeat(4,1fr); gap: 16px;
}
.info-cell {
    background: #F7F6FC; border: 1px solid var(--pr-border);
    border-radius: 10px; padding: 14px 16px;
}
.info-cell .ic-label {
    font-size: .68rem; font-weight: 700; letter-spacing: .1em;
    text-transform: uppercase; color: var(--pr-muted); margin-bottom: 5px;
}
.info-cell .ic-val {
    font-size: .95rem; font-weight: 700; color: var(--pr-text);
}
.info-cell .ic-sub {
    font-size: .75rem; color: var(--pr-muted); margin-top: 2px;
}

/* Party banner */
.party-banner {
    background: linear-gradient(90deg,#F0EBFF,#F7F4FF);
    border: 1px solid var(--pr-border);
    border-radius: 10px; padding: 12px 18px;
    display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
}
.party-banner i { color: var(--pr-accent); font-size: 1.1rem; }
.party-info-item { font-size: .85rem; color: var(--pr-text); }
.party-info-item b { color: var(--pr-accent); }

/* Items table */
.items-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .84rem; }
.items-table thead tr { background: linear-gradient(135deg,#F0EBFF,#FAF8FF); }
.items-table thead th {
    padding: 11px 14px; text-align: left;
    font-size: .7rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
    color: var(--pr-accent); border-bottom: 2px solid var(--pr-border); white-space: nowrap;
}
.items-table thead th:first-child { border-radius: 10px 0 0 0; }
.items-table thead th:last-child  { border-radius: 0 10px 0 0; }
.items-table tbody tr { border-bottom: 1px solid #F3F0FA; transition: background .12s; }
.items-table tbody tr:hover { background: #FAF8FF; }
.items-table td { padding: 13px 14px; vertical-align: middle; color: var(--pr-text); }

/* Serial pills */
.serial-pill {
    display: inline-flex; align-items: center; gap: 4px;
    border: 1px solid #99f6e4; background: #f0fdfa; color: #0f766e;
    border-radius: 999px; padding: 3px 9px; margin: 2px;
    font-size: 11px; font-weight: 700;
}
.no-serial { color: var(--pr-muted); font-size: .78rem; }

/* Value pills */
.val-pill {
    background: #F0EBFF; color: var(--pr-accent);
    border-radius: 8px; padding: 5px 12px;
    font-size: .84rem; font-weight: 700;
    display: inline-block; min-width: 90px; text-align: right;
}
.tax-pill {
    background: #FFF8E1; color: var(--pr-warning);
    border-radius: 8px; padding: 5px 12px;
    font-size: .84rem; font-weight: 700;
    display: inline-block;
}

/* Summary box */
.summary-box {
    background: linear-gradient(135deg, #3B5998, #6C3FC5);
    border-radius: var(--radius); padding: 20px 28px; color: #fff;
    margin-top: 16px;
}
.summary-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 6px 0;
    border-bottom: 1px solid rgba(255,255,255,.15);
}
.summary-row:last-child { border-bottom: none; padding-top: 12px; margin-top: 4px; }
.summary-row .s-label { font-size: .8rem; opacity: .8; }
.summary-row .s-val   { font-size: .95rem; font-weight: 700; }
.summary-row.grand .s-label { font-size: .95rem; font-weight: 700; opacity: 1; }
.summary-row.grand .s-val   { font-size: 1.3rem; font-weight: 800; }

/* Reason box */
.reason-box {
    background: #FFF8E1; border: 1px solid #F6C90E;
    border-radius: 10px; padding: 14px 18px;
    font-size: .88rem; color: #7A5C00;
    display: flex; align-items: flex-start; gap: 10px;
}
.reason-box i { color: var(--pr-warning); margin-top: 2px; flex-shrink: 0; }

/* Meta info */
.meta-row {
    display: flex; flex-wrap: wrap; gap: 20px;
    font-size: .8rem; color: var(--pr-muted);
}
.meta-row span b { color: var(--pr-text); }

/* Footer */
.pr-footer {
    padding: 16px 28px; border-top: 1px solid var(--pr-border);
    background: #FAFBFF; display: flex; gap: 10px; align-items: center;
}

@media(max-width:768px) {
    .pr-wrapper { padding: 12px; }
    .pr-section { padding: 16px; }
    .info-grid { grid-template-columns: 1fr 1fr; }
    .pr-header-actions { flex-wrap: wrap; }
}

@media print {
    .pr-wrapper { padding: 0; background: #fff; }
    .pr-header-actions, .pr-footer { display: none !important; }
    .pr-card { box-shadow: none; border: none; }
}
</style>
@endpush

@section('content')
<div class="pr-wrapper">
<div class="pr-card">

    {{-- ── Header ──────────────────────────────────────────────────────── --}}
    <div class="pr-header">
        <div class="pr-header-icon"><i class="fas fa-undo-alt"></i></div>
        <div>
            <h4>Purchase Return</h4>
            <span>{{ $return->return_date?->format('d M Y') ?? '&mdash;' }} &nbsp;&bull;&nbsp; {{ $return->party?->display_name ?: 'Cash' }}</span>
        </div>
        <div class="pr-return-badge">{{ $return->return_no }}</div>
        <div class="pr-header-actions">
            <a href="{{ route('admin.purchase-returns.index') }}" class="btn-hdr btn-hdr-back">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <button onclick="window.print()" class="btn-hdr btn-hdr-print">
                <i class="fas fa-print"></i> Print
            </button>
            @can('purchase.edit')
            <a href="{{ route('admin.purchase-returns.edit', $return) }}" class="btn-hdr btn-hdr-edit">
                <i class="fas fa-edit"></i> Edit
            </a>
            @endcan
        </div>
    </div>

    {{-- ── Bill & Party Info ────────────────────────────────────────────── --}}
    <div class="pr-section">
        <div class="pr-section-title"><i class="fas fa-file-invoice"></i> Bill &amp; Party Details</div>

        {{-- Info grid --}}
        <div class="info-grid mb-4">
            <div class="info-cell">
                <div class="ic-label">Return No.</div>
                <div class="ic-val" style="color:var(--pr-accent)">{{ $return->return_no }}</div>
            </div>
            <div class="info-cell">
                <div class="ic-label">Return Date</div>
                <div class="ic-val">{{ $return->return_date?->format('d M Y') ?? '&mdash;' }}</div>
            </div>
            <div class="info-cell">
                <div class="ic-label">Purchase Bill</div>
                <div class="ic-val">{{ $return->bill?->invoice_no ?? '&mdash;' }}</div>
                <div class="ic-sub">&#8377;{{ number_format((float)$return->bill?->grand_total, 2) }}</div>
            </div>
            <div class="info-cell">
                <div class="ic-label">Purchase Type</div>
                <div class="ic-val">
                    @if($return->bill?->purchase_type === 'credit')
                        <span style="color:#2B6CB0"><i class="fas fa-credit-card mr-1"></i>Credit</span>
                    @else
                        <span style="color:var(--pr-success)"><i class="fas fa-money-bill-wave mr-1"></i>Cash</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Party banner --}}
        <div class="party-banner">
            <i class="fas fa-building"></i>
            <div class="party-info-item"><b>Party:</b> {{ $return->party?->display_name ?? 'Cash' }}</div>
            @if($return->party?->phone)
            <div class="party-info-item"><b>Phone:</b> {{ $return->party->phone }}</div>
            @endif
            @if($return->party?->email)
            <div class="party-info-item"><b>Email:</b> {{ $return->party->email }}</div>
            @endif
            @if($return->party?->address)
            <div class="party-info-item"><b>Address:</b> {{ Str::limit($return->party->address, 60) }}</div>
            @endif
        </div>

        {{-- Reason --}}
        @if($return->reason)
        <div class="reason-box mt-3">
            <i class="fas fa-info-circle"></i>
            <div><strong>Return Reason:</strong> {{ $return->reason }}</div>
        </div>
        @endif
    </div>

    {{-- ── Return Items ─────────────────────────────────────────────────── --}}
    <div class="pr-section">
        <div class="pr-section-title"><i class="fas fa-boxes"></i> Return Items ({{ $return->items->count() }})</div>

        <div class="table-responsive" style="border-radius:10px;border:1px solid var(--pr-border);overflow:hidden;">
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width:36px">#</th>
                        <th>Item Name</th>
                        <th>Unit</th>
                        <th>Return Qty</th>
                        <th>Unit Price</th>
                        <th>Tax %</th>
                        <th>Tax Amt</th>
                        <th>Serial / Batch Numbers</th>
                        <th>Line Total</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($return->items as $i => $line)
                @php
                    $serials = collect($line->selected_units ?? [])->filter(function($u) {
                        return !empty($u['key']);
                    });
                @endphp
                <tr>
                    <td style="color:var(--pr-muted);font-size:.78rem">{{ $i + 1 }}</td>
                    <td>
                        <strong>{{ $line->item?->name ?? '&mdash;' }}</strong>
                        @if($line->item?->item_code)
                            <br><small style="color:var(--pr-muted)">{{ $line->item->item_code }}</small>
                        @endif
                    </td>
                    <td style="color:var(--pr-muted)">{{ $line->unit ?? '&mdash;' }}</td>
                    <td>
                        <span style="font-weight:700;font-size:.95rem">{{ number_format((float)$line->quantity, 3) }}</span>
                    </td>
                    <td style="color:var(--pr-muted)">&#8377;{{ number_format((float)$line->unit_price, 2) }}</td>
                    <td>
                        <span style="color:var(--pr-muted)">{{ $line->tax_percent }}%</span>
                    </td>
                    <td>
                        <span class="tax-pill">&#8377;{{ number_format((float)$line->tax_amount, 2) }}</span>
                    </td>
                    <td style="max-width:280px">
                        @if($serials->isNotEmpty())
                            <div style="display:flex;flex-wrap:wrap;gap:2px;">
                                @foreach($serials as $su)
                                <span class="serial-pill">
                                    <i class="fas fa-barcode"></i>
                                    {{ $su['serial_no'] ?? $su['vts_sim'] ?? $su['sku'] ?? $su['batch_no'] ?? $su['key'] ?? 'Serial' }}
                                </span>
                                @endforeach
                            </div>
                            @if($serials->count() > 0)
                            <div style="margin-top:4px;">
                                @foreach($serials as $su)
                                @if(!empty($su['batch_no']) || !empty($su['vts_sim']) || !empty($su['sku']))
                                <div style="font-size:10px;color:var(--pr-muted);margin-top:2px;">
                                    <span>Key: {{ $su['key'] ?? '-' }}</span>
                                    @if(!empty($su['batch_no'])) &nbsp;| Batch: {{ $su['batch_no'] }} @endif
                                    @if(!empty($su['vts_sim'])) &nbsp;| VTS/SIM: {{ $su['vts_sim'] }} @endif
                                    @if(!empty($su['sku'])) &nbsp;| SKU: {{ $su['sku'] }} @endif
                                </div>
                                @endif
                                @endforeach
                            </div>
                            @endif
                        @else
                            <span class="no-serial"><i class="fas fa-minus-circle mr-1"></i>Track nahi</span>
                        @endif
                    </td>
                    <td>
                        <span class="val-pill">&#8377;{{ number_format((float)$line->line_total, 2) }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center;padding:40px;color:var(--pr-muted);">
                        <i class="fas fa-box-open" style="font-size:2rem;opacity:.3;display:block;margin-bottom:8px;"></i>
                        Koi item nahi mila
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- Summary --}}
        <div class="row justify-content-end mt-3">
            <div class="col-md-4">
                <div class="summary-box">
                    <div class="summary-row">
                        <span class="s-label">Items Returned</span>
                        <span class="s-val">{{ $return->items->count() }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="s-label">Subtotal</span>
                        <span class="s-val">&#8377;{{ number_format((float)$return->subtotal, 2) }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="s-label">Tax Amount</span>
                        <span class="s-val">&#8377;{{ number_format((float)$return->tax_amount, 2) }}</span>
                    </div>
                    <div class="summary-row grand">
                        <span class="s-label">Grand Total</span>
                        <span class="s-val">&#8377;{{ number_format((float)$return->grand_total, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Meta / Audit Info ────────────────────────────────────────────── --}}
    <div class="pr-section" style="padding-top:16px;padding-bottom:16px;">
        <div class="meta-row">
            <span><b>Created By:</b> {{ $return->creator?->name ?? '&mdash;' }}</span>
            <span><b>Created At:</b> {{ $return->created_at?->format('d M Y, h:i A') ?? '&mdash;' }}</span>
            <span><b>Updated At:</b> {{ $return->updated_at?->format('d M Y, h:i A') ?? '&mdash;' }}</span>
            @if($return->return_no)
            <span><b>Return No:</b> {{ $return->return_no }}</span>
            @endif
        </div>
    </div>

    {{-- ── Footer ───────────────────────────────────────────────────────── --}}
    <div class="pr-footer">
        <a href="{{ route('admin.purchase-returns.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to List
        </a>
        <button onclick="window.print()" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-print mr-1"></i> Print
        </button>
        @can('purchase.edit')
        <a href="{{ route('admin.purchase-returns.edit', $return) }}" class="btn btn-warning btn-sm">
            <i class="fas fa-edit mr-1"></i> Edit Return
        </a>
        @endcan
    </div>

</div>
</div>
@endsection
