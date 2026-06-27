@extends('layouts.admin')
@section('title', 'Purchase Returns')

@push('styles')
<style>
:root {
    --pr-accent:  #6C3FC5;
    --pr-accent2: #9B6FF5;
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
    background: linear-gradient(135deg, var(--pr-accent) 0%, #9055E8 100%);
    padding: 20px 28px;
    display: flex; align-items: center; gap: 14px;
}
.pr-header-icon {
    width: 44px; height: 44px;
    background: rgba(255,255,255,.18); border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; color: #fff;
}
.pr-header h4 { color: #fff; margin: 0; font-size: 1.1rem; font-weight: 600; }
.pr-header span { color: rgba(255,255,255,.7); font-size: .8rem; display: block; margin-top: 2px; }
.btn-pr-new {
    margin-left: auto;
    background: rgba(255,255,255,.2);
    color: #fff; border: 1.5px solid rgba(255,255,255,.4);
    border-radius: 8px; padding: 8px 18px;
    font-size: .85rem; font-weight: 600;
    text-decoration: none;
    display: inline-flex; align-items: center; gap: 7px;
    transition: background .2s;
}
.btn-pr-new:hover { background: rgba(255,255,255,.32); color: #fff; text-decoration: none; }

/* Stats strip */
.stats-strip {
    display: grid; grid-template-columns: repeat(4,1fr);
    border-bottom: 1px solid var(--pr-border);
}
.stat-cell {
    padding: 16px 20px;
    border-right: 1px solid var(--pr-border);
    text-align: center;
}
.stat-cell:last-child { border-right: none; }
.stat-cell .s-val { font-size: 1.35rem; font-weight: 800; color: var(--pr-accent); display: block; }
.stat-cell .s-lbl { font-size: .7rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--pr-muted); }

/* Table area */
.pr-body { padding: 20px 24px; }

/* DataTable overrides */
.pr-table { width: 100% !important; font-size: .84rem; }
.pr-table thead th {
    background: linear-gradient(135deg,#F0EBFF,#FAF8FF);
    color: var(--pr-accent);
    font-size: .7rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
    border-bottom: 2px solid var(--pr-border) !important;
    padding: 12px 14px; white-space: nowrap;
}
.pr-table tbody tr { transition: background .12s; }
.pr-table tbody tr:hover { background: #FAF8FF; }
.pr-table tbody td { padding: 12px 14px; vertical-align: middle; border-top: 1px solid #F3F0FA !important; color: var(--pr-text); }

/* Badges */
.type-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 20px; font-size: .75rem; font-weight: 700;
}
.type-credit { background: #EBF8FF; color: #2B6CB0; }
.type-cash   { background: #F0FFF4; color: #276749; }

.serial-pill-sm {
    display: inline-flex; align-items: center; gap: 4px;
    background: #F0EBFF; color: var(--pr-accent);
    border-radius: 999px; padding: 2px 8px;
    font-size: 10px; font-weight: 700; margin: 1px;
}

.total-pill {
    background: linear-gradient(135deg,var(--pr-accent),#9055E8);
    color: #fff; border-radius: 8px;
    padding: 4px 12px; font-size: .83rem; font-weight: 700;
    display: inline-block;
}

/* Action buttons */
.btn-view { background: #EBF8FF; color: #2B6CB0; border: none; border-radius: 7px; padding: 5px 11px; font-size: .78rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: background .15s; }
.btn-view:hover { background: #BEE3F8; color: #2B6CB0; text-decoration: none; }
.btn-edit { background: #FFFFF0; color: #744210; border: none; border-radius: 7px; padding: 5px 11px; font-size: .78rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: background .15s; }
.btn-edit:hover { background: #FEFCBF; color: #744210; text-decoration: none; }

@media(max-width:768px) {
    .pr-wrapper { padding: 12px; }
    .stats-strip { grid-template-columns: 1fr 1fr; }
    .pr-body { padding: 12px; }
}
</style>
@endpush

@section('content')
@php
    $totalReturns  = $returns->count();
    $totalAmount   = $returns->sum('grand_total');
    $creditReturns = $returns->filter(fn($r) => $r->bill?->purchase_type === 'credit')->count();
    $cashReturns   = $returns->filter(fn($r) => $r->bill?->purchase_type !== 'credit')->count();
@endphp

<div class="pr-wrapper">
<div class="pr-card">

    {{-- Header --}}
    <div class="pr-header">
        <div class="pr-header-icon"><i class="fas fa-undo-alt"></i></div>
        <div>
            <h4>Purchase Returns</h4>
            <span>Sabhi purchase return entries</span>
        </div>
        <a href="{{ route('admin.purchase-returns.create') }}" class="btn-pr-new">
            <i class="fas fa-plus"></i> New Return
        </a>
    </div>

    {{-- Stats --}}
    <div class="stats-strip">
        <div class="stat-cell">
            <span class="s-val">{{ $totalReturns }}</span>
            <span class="s-lbl">Total Returns</span>
        </div>
        <div class="stat-cell">
            <span class="s-val">&#8377;{{ number_format($totalAmount, 2) }}</span>
            <span class="s-lbl">Total Amount</span>
        </div>
        <div class="stat-cell">
            <span class="s-val">{{ $creditReturns }}</span>
            <span class="s-lbl">Credit Returns</span>
        </div>
        <div class="stat-cell">
            <span class="s-val">{{ $cashReturns }}</span>
            <span class="s-lbl">Cash Returns</span>
        </div>
    </div>

    {{-- Table --}}
    <div class="pr-body">
        <div class="table-responsive">
        <table id="returnsTable" class="pr-table table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Return No.</th>
                    <th>Date</th>
                    <th>Purchase Bill</th>
                    <th>Party</th>
                    <th>Type</th>
                    <th>Items</th>
                    <th>Serials</th>
                    <th>Reason</th>
                    <th>Subtotal</th>
                    <th>Tax</th>
                    <th>Grand Total</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            @foreach($returns as $i => $return)
            @php
                /* Collect all serial numbers from all return items */
                $allSerials = collect($return->items)->flatMap(function($item) {
                    return collect($item->selected_units ?? [])->map(function($u) {
                        return $u['serial_no'] ?? $u['vts_sim'] ?? $u['sku'] ?? $u['batch_no'] ?? $u['key'] ?? null;
                    })->filter();
                });
            @endphp
            <tr>
                <td style="color:var(--pr-muted);font-size:.78rem">{{ $i + 1 }}</td>
                <td>
                    <strong style="color:var(--pr-accent)">{{ $return->return_no }}</strong>
                </td>
                <td style="white-space:nowrap">
                    {{ $return->return_date?->format('d M Y') ?? '&mdash;' }}
                </td>
                <td>
                    <span style="font-weight:600">{{ $return->bill?->invoice_no ?? '&mdash;' }}</span>
                    <br><small style="color:var(--pr-muted)">&#8377;{{ number_format((float)$return->bill?->grand_total, 2) }}</small>
                </td>
                <td>
                    <span style="font-weight:600">{{ $return->party?->display_name ?: 'Cash' }}</span>
                </td>
                <td>
                    @if($return->bill?->purchase_type === 'credit')
                        <span class="type-badge type-credit"><i class="fas fa-credit-card"></i> Credit</span>
                    @else
                        <span class="type-badge type-cash"><i class="fas fa-money-bill-wave"></i> Cash</span>
                    @endif
                </td>
                <td>
                    <span style="font-weight:700;color:var(--pr-accent)">{{ $return->items->count() }}</span>
                    <small style="color:var(--pr-muted)"> item{{ $return->items->count() === 1 ? '' : 's' }}</small>
                </td>
                <td>
                    @if($allSerials->isNotEmpty())
                        @foreach($allSerials->take(3) as $sn)
                            <span class="serial-pill-sm"><i class="fas fa-barcode"></i>{{ $sn }}</span>
                        @endforeach
                        @if($allSerials->count() > 3)
                            <span class="serial-pill-sm">+{{ $allSerials->count() - 3 }} more</span>
                        @endif
                    @else
                        <span style="color:var(--pr-muted);font-size:.78rem">Track nahi</span>
                    @endif
                </td>
                <td style="max-width:160px">
                    @if($return->reason)
                        <span style="font-size:.8rem;color:var(--pr-muted)" title="{{ $return->reason }}">
                            {{ Str::limit($return->reason, 30) }}
                        </span>
                    @else
                        <span style="color:var(--pr-muted);font-size:.78rem">&mdash;</span>
                    @endif
                </td>
                <td style="white-space:nowrap">&#8377;{{ number_format((float)$return->subtotal, 2) }}</td>
                <td style="white-space:nowrap;color:var(--pr-warning)">&#8377;{{ number_format((float)$return->tax_amount, 2) }}</td>
                <td style="white-space:nowrap">
                    <span class="total-pill">&#8377;{{ number_format((float)$return->grand_total, 2) }}</span>
                </td>
                <td style="white-space:nowrap">
                    <a href="{{ route('admin.purchase-returns.show', $return) }}" class="btn-view">
                        <i class="fas fa-eye"></i> View
                    </a>
                    @can('purchase.edit')
                    <a href="{{ route('admin.purchase-returns.edit', $return) }}" class="btn-edit ml-1">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    @endcan
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>
    </div>

</div>
</div>
@endsection

@push('scripts')
<script>
$('#returnsTable').DataTable({
    pageLength: 25,
    order: [[2, 'desc']],
    columnDefs: [
        { orderable: false, targets: [7, 12] }
    ],
    language: {
        search:         'Search:',
        lengthMenu:     '_MENU_ per page',
        info:           'Showing _START_ to _END_ of _TOTAL_ returns',
        infoEmpty:      'Koi return nahi mila',
        zeroRecords:    'Koi record nahi mila',
        paginate: { previous: '&laquo;', next: '&raquo;' }
    }
});
</script>
@endpush
