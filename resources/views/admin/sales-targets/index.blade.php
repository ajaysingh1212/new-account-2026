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

/* ===== Redesigned Product Categories & Goals column ===== */
.sgi-goal-block{display:flex;gap:14px;align-items:flex-start;min-width:270px}
.sgi-goal-visual{flex:0 0 64px;display:flex;align-items:center;justify-content:center;position:relative}
.sgi-goal-visual canvas{filter:drop-shadow(0 6px 10px rgba(124,58,237,.22))}
.sgi-goal-details{flex:1;min-width:0}
.sgi-goal-chips{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px}
.sgi-goal-chip{display:inline-flex;align-items:center;gap:6px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:999px;
    padding:5px 10px 5px 8px;font-size:11px;font-weight:600;color:var(--sgi-muted);transition:.2s}
.sgi-goal-chip:hover{border-color:var(--sgi-violet);transform:translateY(-1px)}
.sgi-goal-chip b{color:var(--sgi-ink);font-size:13px;font-weight:800}
.sgi-goal-chip small{color:var(--sgi-muted);text-transform:uppercase;font-size:9px;background:#e5e7eb;padding:1px 5px;border-radius:3px}
.sgi-goal-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.sgi-goal-totals{display:flex;gap:10px;flex-wrap:wrap;padding-top:9px;border-top:1px dashed #e5e7eb}
.sgi-total-pill{display:inline-flex;align-items:baseline;gap:3px;border-radius:10px;padding:6px 14px;font-weight:700;font-size:12px}
.sgi-total-pill span{font-family:'Outfit',sans-serif;font-weight:800;font-size:20px}
.sgi-total-amt{background:#ede9fe;color:var(--sgi-violet)}
.sgi-total-pct{background:#dbeafe;color:#2563eb}
.sgi-total-qty{background:#d1fae5;color:var(--sgi-mint)}
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
                            $colors = ['#7C3AED', '#0ea5e9', '#f59e0b', '#10b981', '#ef4444', '#ec4899', '#2563eb', '#06b6d4'];
                            $rowChartData = $target->items->values()->map(function ($item, $idx) use ($colors) {
                                return [
                                    'label' => $item->productCategory?->name ?? '-',
                                    'value' => (float) $item->target_value,
                                    'type' => $item->target_type,
                                    'color' => $colors[$idx % count($colors)],
                                ];
                            });
                        @endphp
                        <tr data-party="{{ $target->party?->display_name ?? 'Cash / Walk-in' }}" data-amount="{{ $amtSum }}" data-percent="{{ $pctSum }}" data-qty="{{ $qtySum }}">
                            <td><strong>{{ $target->party?->display_name ?? 'Cash / Walk-in' }}</strong></td>
                            <td>{{ ucfirst(str_replace('_',' ',$target->period_type)) }}</td>
                            <td>{{ $target->starts_on->format('d M Y') }}<br>to {{ $target->ends_on->format('d M Y') }}</td>
                            <td>
                                <div class="sgi-goal-block">
                                    <div class="sgi-goal-visual">
                                        <canvas class="sgi-row-pie" id="sgiPie{{ $target->id }}" width="64" height="64"></canvas>
                                    </div>
                                    <div class="sgi-goal-details">
                                        <div class="sgi-goal-chips">
                                            @foreach($rowChartData as $c)
                                            <span class="sgi-goal-chip">
                                                <i class="sgi-goal-dot" style="background:{{ $c['color'] }}"></i>
                                                {{ $c['label'] }}
                                                <b>{{ number_format($c['value'],0) }}</b>
                                                <small>{{ $c['type'] }}</small>
                                            </span>
                                            @endforeach
                                        </div>
                                        <div class="sgi-goal-totals">
                                            @if($amtSum > 0)<div class="sgi-total-pill sgi-total-amt">₹<span>{{ number_format($amtSum,0) }}</span></div>@endif
                                            @if($pctSum > 0)<div class="sgi-total-pill sgi-total-pct"><span>{{ number_format($pctSum,0) }}</span>%</div>@endif
                                            @if($qtySum > 0)<div class="sgi-total-pill sgi-total-qty"><span>{{ number_format($qtySum,0) }}</span> qty</div>@endif
                                        </div>
                                    </div>
                                </div>
                                <script type="application/json" class="sgi-row-data">{!! $rowChartData->toJson() !!}</script>
                            </td>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
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

    // ===== Per-row creative pie chart for Product Categories & Goals =====
    document.querySelectorAll('.sgi-row-pie').forEach(function (canvas) {
        const dataEl = canvas.closest('td').querySelector('.sgi-row-data');
        if (!dataEl) return;
        let items = [];
        try { items = JSON.parse(dataEl.textContent || '[]'); } catch (e) { items = []; }
        if (!items.length) return;
        new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: items.map(i => i.label),
                datasets: [{
                    data: items.map(i => i.value),
                    backgroundColor: items.map(i => i.color),
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: false,
                maintainAspectRatio: false,
                cutout: '55%',
                animation: { duration: 900, easing: 'easeOutQuart' },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                const it = items[ctx.dataIndex];
                                return it.label + ': ' + Number(it.value).toLocaleString('en-IN') + ' (' + it.type + ')';
                            }
                        }
                    }
                }
            }
        });
    });
});
</script>
@endpush
