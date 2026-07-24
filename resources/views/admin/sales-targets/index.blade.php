@extends('layouts.admin')
@section('title','Sales Target')
@section('content')
<style>
:root{
    --sgi-violet:#7C3AED;
    --sgi-indigo:#6366F1;
    --sgi-mint:#10b981;
    --sgi-ink:#0f172a;
    --sgi-muted:#64748b;
}
#sgi-wrap{font-family:'Inter','Outfit',sans-serif;color:var(--sgi-ink)}
#sgi-wrap *{box-sizing:border-box}
.sgi-kpi{border:0;border-radius:18px;box-shadow:0 10px 26px rgba(15,23,42,.06);overflow:hidden;animation:sgiFadeUp .5s ease both}
.sgi-kpi:nth-child(1){animation-delay:.05s}.sgi-kpi:nth-child(2){animation-delay:.1s}.sgi-kpi:nth-child(3){animation-delay:.15s}
.sgi-kpi .card-body{padding:20px;display:flex;justify-content:space-between;align-items:flex-start}
.sgi-kpi-icon{width:48px;height:48px;border-radius:14px;display:grid;place-items:center;font-size:20px;flex-shrink:0}
.sgi-kpi-label{font-size:11px;font-weight:700;letter-spacing:.05em;color:var(--sgi-muted);text-transform:uppercase}
.sgi-kpi-value{font-family:'Outfit',sans-serif;font-weight:800;font-size:24px;margin-top:4px}
.sgi-kpi-sub{font-size:11px;color:var(--sgi-muted);margin-top:2px}
@keyframes sgiFadeUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.sgi-filterbar{border:0;border-radius:18px;box-shadow:0 10px 26px rgba(15,23,42,.06);margin-bottom:18px;animation:sgiFadeUp .5s ease .2s both}
.sgi-filterbar .form-control{border-radius:12px;border:1.5px solid #e5e7eb}
.sgi-filterbar .form-control:focus{border-color:var(--sgi-violet);box-shadow:0 0 0 3px rgba(124,58,237,.12)}
#sgi-wrap .card.shadow-sm{border-radius:18px}
</style>
<div id="sgi-wrap">

    <div class="row">
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card sgi-kpi">
                <div class="card-body">
                    <div>
                        <div class="sgi-kpi-label">Total Amount Target</div>
                        <div class="sgi-kpi-value">₹<span class="sgi-count" id="sgiTotalAmount" data-current="0">0</span></div>
                        <div class="sgi-kpi-sub" id="sgiAmountSub">Sabhi parties</div>
                    </div>
                    <span class="sgi-kpi-icon" style="background:#ede9fe;color:var(--sgi-violet)">💰</span>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card sgi-kpi">
                <div class="card-body">
                    <div>
                        <div class="sgi-kpi-label">Total % Target</div>
                        <div class="sgi-kpi-value"><span class="sgi-count" id="sgiTotalPercent" data-current="0">0</span>%</div>
                        <div class="sgi-kpi-sub" id="sgiPercentSub">Sabhi parties</div>
                    </div>
                    <span class="sgi-kpi-icon" style="background:#dbeafe;color:#2563eb">📈</span>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
            <div class="card sgi-kpi">
                <div class="card-body">
                    <div>
                        <div class="sgi-kpi-label">Total Quantity Target</div>
                        <div class="sgi-kpi-value"><span class="sgi-count" id="sgiTotalQty" data-current="0">0</span></div>
                        <div class="sgi-kpi-sub" id="sgiQtySub">Sabhi parties</div>
                    </div>
                    <span class="sgi-kpi-icon" style="background:#d1fae5;color:var(--sgi-mint)">📦</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card sgi-filterbar">
        <div class="card-body py-3 d-flex flex-wrap align-items-center justify-content-between">
            <div class="d-flex align-items-center flex-wrap" style="gap:10px">
                <label class="mb-0 mr-1 font-weight-bold" style="font-size:13px;color:var(--sgi-muted)">Party se filter karein:</label>
                <select id="sgiPartyFilter" class="form-control select2" style="min-width:240px">
                    <option value="">Sabhi Parties</option>
                    @foreach($targets->pluck('party')->filter()->unique('id') as $party)
                        <option value="{{ $party->display_name }}">{{ $party->display_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex" style="gap:8px">
                <a href="{{ route('admin.sales-targets.report') }}" class="btn btn-outline-primary"><i class="fas fa-chart-line mr-1"></i> Target Report</a>
                @can('sales_targets.create')
                <a href="{{ route('admin.sales-targets.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i> Set Target</a>
                @endcan
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="targetsTable">
                    <thead><tr><th>Party</th><th>Period</th><th>Dates</th><th>Product Categories & Goals</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @foreach($targets as $target)
                        @php
                            $amtSum = $target->items->where('target_type','amount')->sum('target_value');
                            $pctSum = $target->items->where('target_type','percent')->sum('target_value');
                            $qtySum = $target->items->where('target_type','quantity')->sum('target_value');
                        @endphp
                        <tr data-party="{{ $target->party?->display_name ?? 'Cash / Walk-in' }}" data-amount="{{ $amtSum }}" data-percent="{{ $pctSum }}" data-qty="{{ $qtySum }}">
                            <td><strong>{{ $target->party?->display_name ?? 'Cash / Walk-in' }}</strong></td>
                            <td>{{ ucfirst(str_replace('_',' ',$target->period_type)) }}</td>
                            <td>{{ $target->starts_on->format('d M Y') }}<br>to {{ $target->ends_on->format('d M Y') }}</td>
                            <td>@foreach($target->items as $item)<span class="badge badge-light mr-1 mb-1">{{ $item->productCategory?->name }}: {{ number_format($item->target_value,2) }} {{ $item->target_type }}</span>@endforeach</td>
                            <td><span class="badge badge-{{ $target->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($target->status) }}</span></td>
                            <td class="text-nowrap">
                                @can('sales_targets.edit')<a href="{{ route('admin.sales-targets.edit',$target) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>@endcan
                                @can('sales_targets.delete')<form class="d-inline" method="POST" action="{{ route('admin.sales-targets.destroy',$target) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger btn-delete"><i class="fas fa-trash"></i></button></form>@endcan
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
$(function () {
    $('#sgiPartyFilter').select2({ width: '100%', placeholder: 'Sabhi Parties' });

    const table = $('#targetsTable').DataTable({ pageLength: 25, order: [[2, 'desc']] });

    function animateCount($el, target) {
        const start = parseFloat($el.data('current')) || 0;
        const duration = 700, startTime = performance.now();
        function step(now) {
            const progress = Math.min((now - startTime) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            const val = start + (target - start) * eased;
            $el.text(Number(val).toLocaleString('en-IN', { maximumFractionDigits: 2 }));
            if (progress < 1) requestAnimationFrame(step); else $el.data('current', target);
        }
        requestAnimationFrame(step);
    }

    function recalcCards() {
        let amount = 0, percent = 0, qty = 0;
        table.rows({ search: 'applied' }).nodes().each(function (node) {
            const $row = $(node);
            amount += parseFloat($row.data('amount')) || 0;
            percent += parseFloat($row.data('percent')) || 0;
            qty += parseFloat($row.data('qty')) || 0;
        });
        animateCount($('#sgiTotalAmount'), amount);
        animateCount($('#sgiTotalPercent'), percent);
        animateCount($('#sgiTotalQty'), qty);
        const partyName = $('#sgiPartyFilter').val();
        const subText = partyName ? partyName : 'Sabhi parties';
        $('#sgiAmountSub, #sgiPercentSub, #sgiQtySub').text(subText);
    }

    table.on('draw', recalcCards);
    recalcCards();

    $('#sgiPartyFilter').on('change', function () {
        const val = $(this).val();
        table.column(0).search(val ? '^' + $.fn.dataTable.util.escapeRegex(val) + '$' : '', true, false).draw();
    });
});
</script>
@endpush
