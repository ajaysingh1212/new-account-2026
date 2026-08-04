@extends('layouts.admin')
@section('title','Day Book')
@section('content')
@include('admin.reports.partials.styles')
<style>
.daybook-metric-strip{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px;margin-top:18px}
.daybook-metric{border-radius:14px;padding:16px 18px;background:#fff;border:1px solid #e5e7eb;box-shadow:0 8px 20px rgba(15,23,42,.05);position:relative;overflow:hidden}
.daybook-metric:before{content:"";position:absolute;right:-18px;top:-18px;width:70px;height:70px;border-radius:999px;background:var(--dm-c);opacity:.14}
.daybook-metric .dm-icon{width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;background:var(--dm-c);margin-bottom:10px}
.daybook-metric span{color:#64748b;font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.4px}
.daybook-metric strong{display:block;font-size:21px;color:#111827;margin-top:4px}
.daybook-metric strong.negative{color:#dc2626}
.day-filter-note{color:#a5f3fc;font-size:12px;margin-top:8px}
.timeline{margin-top:22px}
.timeline-item{display:flex;gap:16px;position:relative;padding-bottom:22px}
.timeline-item:last-child{padding-bottom:0}
.timeline-item:before{content:"";position:absolute;left:19px;top:40px;bottom:0;width:2px;background:#e5e7eb}
.timeline-item:last-child:before{display:none}
.timeline-dot{flex:0 0 auto;width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;background:var(--tc);box-shadow:0 8px 18px color-mix(in srgb, var(--tc), transparent 60%);z-index:1}
.timeline-card{flex:1;background:#fff;border:1px solid #eef2f7;border-radius:14px;padding:14px 18px;box-shadow:0 6px 16px rgba(15,23,42,.05)}
.timeline-head{display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px}
.timeline-title{font-weight:850;color:var(--tc);font-size:14px}
.timeline-time{color:#94a3b8;font-size:12px;font-weight:700}
.timeline-detail{font-weight:800;color:#111827;margin-top:2px}
.timeline-meta{color:#64748b;font-size:12px;margin-top:2px}
.timeline-amount{font-weight:900;font-size:16px}
.timeline-amount.in{color:#16a34a}
.timeline-amount.out{color:#dc2626}
.timeline-who{display:inline-flex;align-items:center;gap:6px;margin-top:8px;font-size:12px;color:#334155;background:#f8fafc;border:1px solid #e2e8f0;border-radius:999px;padding:3px 10px;font-weight:700}
.timeline-ref{color:#94a3b8;font-size:11px;margin-top:2px}
.type-filter-bar{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px}
.type-chip{border:1px solid #e5e7eb;background:#fff;border-radius:999px;padding:6px 13px;font-size:12px;font-weight:800;color:#334155;cursor:pointer}
.type-chip.active{background:#0f172a;color:#fff;border-color:#0f172a}
</style>
<div data-export-title="Day Book - Daily Activity" data-export-file="day-book">@include('admin.reports.partials.branded-export')</div>
<div class="report-hero">
    <h1>Day Book - Daily Activity</h1>
    <form class="report-filter" style="grid-template-columns:1fr auto auto" method="GET">
        <div><label>Date</label><input type="date" name="date" class="form-control" value="{{ $date }}"></div>
        <div></div>
        <button class="btn btn-info report-btn">View Day</button>
    </form>
    <div class="day-filter-note">Sirf {{ \Carbon\Carbon::parse($date)->format('d M Y (l)') }} ka data — na pehle ka na baad ka.</div>
</div>

@php
    $cards = [
        ['label' => 'Sale / Income', 'amount' => $summary['sales'], 'color' => '#22c55e', 'icon' => 'fa-file-invoice-dollar'],
        ['label' => 'Purchase', 'amount' => $summary['purchase'], 'color' => '#f97316', 'icon' => 'fa-truck-loading'],
        ['label' => 'Payment In', 'amount' => $summary['payment_in'], 'color' => '#2563eb', 'icon' => 'fa-arrow-down'],
        ['label' => 'Payment Out', 'amount' => $summary['payment_out'], 'color' => '#7c3aed', 'icon' => 'fa-arrow-up'],
        ['label' => 'Expense', 'amount' => $summary['expense'], 'color' => '#ec4899', 'icon' => 'fa-receipt'],
        ['label' => 'Net Cash Flow', 'amount' => $summary['net_cash_flow'], 'color' => '#0f766e', 'icon' => 'fa-wallet'],
    ];
    $typeMeta = [
        'sale' => ['color' => '#22c55e', 'icon' => 'fa-file-invoice-dollar'],
        'purchase' => ['color' => '#f97316', 'icon' => 'fa-truck-loading'],
        'payment_in' => ['color' => '#2563eb', 'icon' => 'fa-arrow-down'],
        'payment_out' => ['color' => '#7c3aed', 'icon' => 'fa-arrow-up'],
        'expense' => ['color' => '#ec4899', 'icon' => 'fa-receipt'],
        'production' => ['color' => '#6366f1', 'icon' => 'fa-industry'],
        'stock_in' => ['color' => '#0ea5e9', 'icon' => 'fa-dolly'],
        'stock_out' => ['color' => '#f43f5e', 'icon' => 'fa-dolly-flatbed'],
        'new_item' => ['color' => '#64748b', 'icon' => 'fa-box'],
        'new_party' => ['color' => '#06b6d4', 'icon' => 'fa-user-plus'],
        'bank' => ['color' => '#a855f7', 'icon' => 'fa-university'],
    ];
    $typeLabels = ['sale'=>'Sale','purchase'=>'Purchase','payment_in'=>'Payment In','payment_out'=>'Payment Out','expense'=>'Expense','production'=>'Production','stock_in'=>'Stock In','stock_out'=>'Stock Out','new_item'=>'New Item','new_party'=>'New Party','bank'=>'Bank'];
    $presentTypes = $entries->pluck('type')->unique();
@endphp

<div class="daybook-metric-strip">
    @foreach($cards as $card)
        <div class="daybook-metric" style="--dm-c:{{ $card['color'] }}">
            <div class="dm-icon"><i class="fas {{ $card['icon'] }}"></i></div>
            <span>{{ $card['label'] }}</span>
            <strong class="{{ $card['amount'] < 0 ? 'negative' : '' }}">Rs {{ number_format($card['amount'], 2) }}</strong>
        </div>
    @endforeach
</div>

<div class="report-card">
    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:10px">
        <h3 class="m-0">Everything that happened on {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</h3>
        <span class="text-muted small">{{ $entries->count() }} activities</span>
    </div>
    <div class="type-filter-bar">
        <div class="type-chip active" data-type-filter="all">All ({{ $entries->count() }})</div>
        @foreach($presentTypes as $type)
            <div class="type-chip" data-type-filter="{{ $type }}"><i class="fas {{ $typeMeta[$type]['icon'] }} mr-1"></i>{{ $typeLabels[$type] }} ({{ $entries->where('type',$type)->count() }})</div>
        @endforeach
    </div>

    <div class="timeline">
        @forelse($entries as $entry)
            @php($meta = $typeMeta[$entry['type']])
            <div class="timeline-item" data-entry-type="{{ $entry['type'] }}" style="--tc:{{ $meta['color'] }}">
                <div class="timeline-dot"><i class="fas {{ $meta['icon'] }}"></i></div>
                <div class="timeline-card">
                    <div class="timeline-head">
                        <div>
                            <div class="timeline-title">{{ $entry['label'] }}</div>
                            <div class="timeline-detail">{{ $entry['detail'] }}</div>
                            <div class="timeline-meta">{{ $entry['meta'] }}</div>
                            @if($entry['ref'])<div class="timeline-ref">Ref: {{ $entry['ref'] }}</div>@endif
                        </div>
                        <div class="text-right">
                            <div class="timeline-time">{{ $entry['time']?->format('h:i A') }}</div>
                            @if($entry['direction'] !== 'neutral')
                                <div class="timeline-amount {{ $entry['direction'] }}">{{ $entry['direction'] === 'in' ? '+' : '-' }} Rs {{ number_format($entry['amount'],2) }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="timeline-who"><i class="fas fa-user-circle"></i> {{ $entry['who'] }}</div>
                </div>
            </div>
        @empty
            <p class="text-muted text-center py-4 mb-0">Is din koi bhi activity nahi hui.</p>
        @endforelse
    </div>
</div>
@endsection
@push('scripts')<script>
$(document).on('click','.type-chip',function(){
    const type = $(this).data('type-filter');
    $('.type-chip').removeClass('active');
    $(this).addClass('active');
    if (type === 'all') { $('.timeline-item').show(); return; }
    $('.timeline-item').hide().filter(`[data-entry-type="${type}"]`).show();
});
</script>@endpush
