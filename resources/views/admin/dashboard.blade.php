@extends('layouts.admin')
@section('title', 'Dashboard')

@push('styles')
<style>
.dash-hero{position:relative;overflow:hidden;border-radius:18px;background:#0f172a;color:#fff;padding:26px 28px;margin-bottom:22px;box-shadow:0 18px 42px rgba(15,23,42,.18)}.dash-hero:after{content:"";position:absolute;inset:auto -10% -62% -10%;height:170px;background:linear-gradient(90deg,#22d3ee,#2563eb,#22c55e);opacity:.45;border-radius:50%;animation:wave 7s ease-in-out infinite}.dash-hero>*{position:relative;z-index:1}.dash-title{font-size:28px;font-weight:850;margin:0}.dash-sub{color:#cbd5e1;margin-top:6px}.filter-panel{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:14px;margin-bottom:22px}.metric-card{background:#fff;border:1px solid #eef2f7;border-radius:14px;padding:18px;min-height:132px;box-shadow:0 10px 26px rgba(2,6,23,.06);position:relative;overflow:hidden}.metric-card:before{content:"";position:absolute;right:-24px;top:-24px;width:86px;height:86px;border-radius:999px;background:var(--accent);opacity:.12}.metric-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;background:var(--accent);margin-bottom:12px}.metric-value{font-size:24px;font-weight:850;color:#0f172a}.metric-label{color:#64748b;font-size:12px;text-transform:uppercase;font-weight:800;letter-spacing:.5px}.chart-card{background:#fff;border:1px solid #eef2f7;border-radius:16px;padding:18px;box-shadow:0 10px 26px rgba(2,6,23,.06);height:100%}.wave-chart{height:220px;width:100%}.wave-line{fill:none;stroke-width:4;stroke-linecap:round;stroke-dasharray:800;stroke-dashoffset:800;animation:draw 2.1s ease forwards}.pie{width:180px;height:180px;border-radius:50%;margin:auto;background:conic-gradient(#2563eb 0 var(--sales),#ec4899 var(--sales) var(--purchase),#14b8a6 var(--purchase) var(--bank),#f59e0b var(--bank) 100%);animation:pop .8s ease}.quick-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px}.quick-action{display:flex;align-items:center;gap:10px;border:1px solid #e5e7eb;border-radius:12px;padding:14px;background:#fff;color:#0f172a;font-weight:750}.quick-action i{color:#2563eb}.activity-row{display:flex;gap:12px;padding:12px 0;border-bottom:1px solid #eef2f7}.activity-dot{width:34px;height:34px;border-radius:10px;background:#eff6ff;color:#2563eb;display:flex;align-items:center;justify-content:center;flex:0 0 auto}@keyframes wave{0%,100%{transform:translateY(0)}50%{transform:translateY(-16px)}}@keyframes draw{to{stroke-dashoffset:0}}@keyframes pop{from{transform:scale(.86);opacity:.4}to{transform:scale(1);opacity:1}}
</style>
@endpush

@section('content')
@php
    $user = auth()->user();
    $roleLabel = $user->isSuperAdmin() ? 'Super Admin Control Center' : ($user->isAdmin() ? 'Company Admin Dashboard' : 'My Role Dashboard');
    $cards = [];
    if ($user->isSuperAdmin()) {
        $cards[] = ['label'=>'Companies','value'=>$stats['companies'] ?? 0,'icon'=>'fa-building','accent'=>'#2563eb'];
        $cards[] = ['label'=>'Users','value'=>$stats['users'] ?? 0,'icon'=>'fa-users','accent'=>'#14b8a6'];
        $cards[] = ['label'=>'Company Admins','value'=>$stats['admins'] ?? 0,'icon'=>'fa-user-shield','accent'=>'#ec4899'];
        $cards[] = ['label'=>'Active Companies','value'=>$stats['active_companies'] ?? 0,'icon'=>'fa-check-circle','accent'=>'#22c55e'];
    }
    if ($user->can('sales.view')) $cards[] = ['label'=>'Sales','value'=>'Rs '.number_format($stats['sales'] ?? 0,2),'icon'=>'fa-file-invoice-dollar','accent'=>'#2563eb'];
    if ($user->can('purchase.view')) $cards[] = ['label'=>'Purchase','value'=>'Rs '.number_format($stats['purchases'] ?? 0,2),'icon'=>'fa-shopping-cart','accent'=>'#ec4899'];
    if ($user->can('parties.view')) $cards[] = ['label'=>'Parties','value'=>$stats['parties'] ?? 0,'icon'=>'fa-users','accent'=>'#8b5cf6'];
    if ($user->can('items.view')) $cards[] = ['label'=>'Items','value'=>$stats['items'] ?? 0,'icon'=>'fa-box','accent'=>'#f59e0b'];
    if ($user->can('stocks.view')) $cards[] = ['label'=>'Low Stock','value'=>$stats['low_stock'] ?? 0,'icon'=>'fa-exclamation-triangle','accent'=>'#ef4444'];
    if ($user->can('banking.view')) $cards[] = ['label'=>'Bank Balance','value'=>'Rs '.number_format($stats['bank_balance'] ?? 0,2),'icon'=>'fa-university','accent'=>'#06b6d4'];
    if ($user->can('estimates.view')) $cards[] = ['label'=>'Estimates','value'=>$stats['estimates'] ?? 0,'icon'=>'fa-file-contract','accent'=>'#4338ca'];
    if ($user->can('delivery_challans.view')) $cards[] = ['label'=>'Challans','value'=>$stats['challans'] ?? 0,'icon'=>'fa-truck','accent'=>'#0f766e'];
    $sales = max(0, (float)($mix['Sales'] ?? 0)); $purchase = max(0, (float)($mix['Purchase'] ?? 0)); $bank = max(0, (float)($mix['Bank'] ?? 0)); $cash = max(0, (float)($mix['Cash'] ?? 0));
    $totalMix = max(1, $sales + $purchase + $bank + $cash);
    $salesEnd = round($sales / $totalMix * 100, 2);
    $purchaseEnd = round(($sales + $purchase) / $totalMix * 100, 2);
    $bankEnd = round(($sales + $purchase + $bank) / $totalMix * 100, 2);
@endphp

<div class="dash-hero">
    <div class="d-flex justify-content-between flex-wrap">
        <div>
            <div class="dash-title">{{ $roleLabel }}</div>
            <div class="dash-sub">Filtered business intelligence for {{ $from }} to {{ $to }}.</div>
        </div>
        <div class="text-right mt-2 mt-md-0">
            <div style="font-size:12px;color:#cbd5e1;text-transform:uppercase;font-weight:800;">Signed in as</div>
            <div style="font-weight:800;font-size:18px;">{{ $user->name }}</div>
        </div>
    </div>
</div>

<form class="filter-panel" method="GET">
    <div class="row align-items-end">
        @if($user->isSuperAdmin())
            <div class="col-md-4 form-group mb-md-0"><label>Company</label><select name="company_id" class="form-control"><option value="">All Companies</option>@foreach($companiesFilter as $company)<option value="{{ $company->id }}" @selected((int)$companyId === (int)$company->id)>{{ $company->name }}</option>@endforeach</select></div>
        @endif
        <div class="col-md-3 form-group mb-md-0"><label>From Date</label><input type="date" name="from_date" value="{{ $from }}" class="form-control"></div>
        <div class="col-md-3 form-group mb-md-0"><label>To Date</label><input type="date" name="to_date" value="{{ $to }}" class="form-control"></div>
        <div class="col-md-2"><button class="btn btn-primary btn-block"><i class="fas fa-filter mr-1"></i> Filter</button></div>
    </div>
</form>

<div class="row">
    @forelse($cards as $card)
        <div class="col-6 col-xl-3 mb-4">
            <div class="metric-card" style="--accent:{{ $card['accent'] }}">
                <div class="metric-icon"><i class="fas {{ $card['icon'] }}"></i></div>
                <div class="metric-value">{{ $card['value'] }}</div>
                <div class="metric-label">{{ $card['label'] }}</div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="alert alert-info">No dashboard widgets are available for this role yet.</div></div>
    @endforelse
</div>
<div class="row">
    <div class="col-lg-7 mb-4"><div class="chart-card"><h5>Quick Actions</h5><div class="quick-grid mt-3">@forelse($quickActions as $action)<a class="quick-action" href="{{ route($action['route']) }}"><i class="fas {{ $action['icon'] }}"></i>{{ $action['label'] }}</a>@empty <span class="text-muted">No actions available for this role.</span>@endforelse</div></div></div>
    <div class="col-lg-5 mb-4"><div class="chart-card"><h5>Recent Activity</h5>@forelse($recentLogs as $log)<div class="activity-row"><div class="activity-dot"><i class="fas fa-bolt"></i></div><div><b>{{ $log->user?->name ?? 'System' }}</b> {{ $log->action }}<br><span class="text-muted small">{{ \Illuminate\Support\Str::limit($log->description, 54) }} · {{ $log->created_at?->diffForHumans() }}</span></div></div>@empty <div class="text-muted">No activity yet.</div>@endforelse</div></div>
</div>
<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="chart-card">
            <div class="d-flex justify-content-between mb-3"><h5 class="m-0">Animated Wave Trend</h5><span class="text-muted">Last 6 months</span></div>
            <svg class="wave-chart" viewBox="0 0 760 220" preserveAspectRatio="none">
                <defs><linearGradient id="salesGrad" x1="0" x2="1"><stop offset="0" stop-color="#22d3ee"/><stop offset="1" stop-color="#2563eb"/></linearGradient><linearGradient id="purchaseGrad" x1="0" x2="1"><stop offset="0" stop-color="#f472b6"/><stop offset="1" stop-color="#ec4899"/></linearGradient></defs>
                @php
                    $maxVal = max(1, collect($monthly['sales'])->merge($monthly['purchases'])->max());
                    $points = function($series) use ($maxVal) { return collect($series)->values()->map(fn($v,$i) => (($i * 140) + 30).','. (190 - ((float)$v / $maxVal * 150)))->implode(' '); };
                @endphp
                <polyline class="wave-line" points="{{ $points($monthly['sales']) }}" stroke="url(#salesGrad)"/>
                <polyline class="wave-line" points="{{ $points($monthly['purchases']) }}" stroke="url(#purchaseGrad)" style="animation-delay:.25s"/>
                @foreach($monthly['labels'] as $i => $label)<text x="{{ ($i * 140) + 24 }}" y="214" font-size="12" fill="#64748b">{{ $label }}</text>@endforeach
            </svg>
        </div>
    </div>
    <div class="col-lg-4 mb-4">
        <div class="chart-card">
            <div class="d-flex justify-content-between mb-3"><h5 class="m-0">Animated Mix</h5><span class="text-muted">Current filter</span></div>
            <div class="pie" style="--sales:{{ $salesEnd }}%;--purchase:{{ $purchaseEnd }}%;--bank:{{ $bankEnd }}%"></div>
            <div class="mt-3 small">
                <div><span style="color:#2563eb">■</span> Sales</div><div><span style="color:#ec4899">■</span> Purchase</div><div><span style="color:#14b8a6">■</span> Bank</div><div><span style="color:#f59e0b">■</span> Cash</div>
            </div>
        </div>
    </div>
</div>



@if($user->isSuperAdmin())
<div class="chart-card mb-4">
    <h5>Company Pulse</h5>
    <div class="table-responsive mt-3"><table class="table table-hover"><thead><tr><th>Company</th><th>Users</th><th>Roles</th><th>Status</th></tr></thead><tbody>@foreach($companies as $company)<tr><td><b>{{ $company->name }}</b></td><td>{{ $company->users_count }}</td><td>{{ $company->roles_count }}</td><td>{{ $company->is_active ? 'Active' : 'Inactive' }}</td></tr>@endforeach</tbody></table></div>
</div>
@endif
@endsection
