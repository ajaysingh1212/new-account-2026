@extends('layouts.admin')
@section('title', 'Dashboard')

@push('styles')
<style>
.activity-footer .pagination{
    display:flex;
    flex-wrap:wrap;
    gap:4px;
    justify-content:center;
    margin:0;
}

.activity-footer .page-link{
    padding:8px 14px;
    font-size:14px;
}

.activity-footer svg{
    width:14px !important;
    height:14px !important;
}

.activity-footer nav{
    display:flex;
    justify-content:center;
}
.dash-hero{position:relative;overflow:hidden;border-radius:18px;background:#0f172a;color:#fff;padding:26px 28px;margin-bottom:22px;box-shadow:0 18px 42px rgba(15,23,42,.18)}.dash-hero:after{content:"";position:absolute;inset:auto -10% -62% -10%;height:170px;background:linear-gradient(90deg,#22d3ee,#2563eb,#22c55e);opacity:.45;border-radius:50%;animation:wave 7s ease-in-out infinite}.dash-hero>*{position:relative;z-index:1}.dash-title{font-size:28px;font-weight:850;margin:0}.dash-sub{color:#cbd5e1;margin-top:6px}.filter-panel{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:14px;margin-bottom:22px}.metric-card{background:#fff;border:1px solid #eef2f7;border-radius:14px;padding:18px;min-height:132px;box-shadow:0 10px 26px rgba(2,6,23,.06);position:relative;overflow:hidden}.metric-card:before{content:"";position:absolute;right:-24px;top:-24px;width:86px;height:86px;border-radius:999px;background:var(--accent);opacity:.12}.metric-icon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;background:var(--accent);margin-bottom:12px}.metric-value{font-size:24px;font-weight:850;color:#0f172a}.metric-label{color:#64748b;font-size:12px;text-transform:uppercase;font-weight:800;letter-spacing:.5px}.chart-card{background:#fff;border:1px solid #eef2f7;border-radius:16px;padding:18px;box-shadow:0 10px 26px rgba(2,6,23,.06);height:100%}.wave-chart{height:220px;width:100%}.wave-line{fill:none;stroke-width:4;stroke-linecap:round;stroke-dasharray:800;stroke-dashoffset:800;animation:draw 2.1s ease forwards}.pie{width:180px;height:180px;border-radius:50%;margin:auto;background:conic-gradient(#2563eb 0 var(--sales),#ec4899 var(--sales) var(--purchase),#14b8a6 var(--purchase) var(--bank),#f59e0b var(--bank) 100%);animation:pop .8s ease}.quick-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px}.quick-action{display:flex;align-items:center;gap:10px;border:1px solid #e5e7eb;border-radius:12px;padding:14px;background:#fff;color:#0f172a;font-weight:750}.quick-action i{color:#2563eb}.activity-row{display:flex;gap:12px;padding:12px 0;border-bottom:1px solid #eef2f7}.activity-dot{width:34px;height:34px;border-radius:10px;background:#eff6ff;color:#2563eb;display:flex;align-items:center;justify-content:center;flex:0 0 auto}@keyframes wave{0%,100%{transform:translateY(0)}50%{transform:translateY(-16px)}}@keyframes draw{to{stroke-dashoffset:0}}@keyframes pop{from{transform:scale(.86);opacity:.4}to{transform:scale(1);opacity:1}}
.period-tabs{display:flex;flex-wrap:wrap;gap:8px}.period-tab{border:1px solid #dbe4f0;background:#fff;color:#334155;border-radius:999px;padding:8px 12px;font-weight:750}.period-tab.active{background:#0f766e;color:#fff;border-color:#0f766e}.wave-chart{background:linear-gradient(180deg,#f8fafc,#fff);border-radius:12px}.wave-line{filter:drop-shadow(0 8px 12px rgba(37,99,235,.18))}.wave-grid{stroke:#e2e8f0;stroke-width:1}.activity-footer .pagination{margin-bottom:0;justify-content:flex-end}.chart-card h5{font-weight:800;color:#0f172a}
.ops-card{background:#fff;border:1px solid #e7edf5;border-radius:14px;padding:16px;height:100%;box-shadow:0 10px 26px rgba(2,6,23,.06)}.ops-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:12px}.ops-kicker{font-size:11px;text-transform:uppercase;font-weight:850;color:#64748b;letter-spacing:.6px}.ops-amount{font-size:24px;font-weight:900;color:#0f172a}.product-row{display:flex;justify-content:space-between;gap:10px;border-top:1px solid #eef2f7;padding:10px 0}.product-row:first-child{border-top:0}.product-name{font-weight:800;color:#172033}.tag-hot{display:inline-flex;align-items:center;gap:5px;background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;border-radius:999px;padding:2px 8px;font-size:10px;font-weight:900;text-transform:uppercase}.tag-low{display:inline-flex;align-items:center;gap:5px;background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;border-radius:999px;padding:2px 8px;font-size:10px;font-weight:900;text-transform:uppercase}.blink-alert{animation:blinkAlert 1s ease-in-out infinite}@keyframes blinkAlert{0%,100%{box-shadow:0 0 0 rgba(220,38,38,0)}50%{box-shadow:0 0 0 4px rgba(220,38,38,.16)}}.ageing-table th{font-size:11px;text-transform:uppercase;color:#64748b;border-top:0}.ageing-table td{vertical-align:middle}.view-detail-btn{border-radius:999px;font-weight:800}.due-action{white-space:nowrap}.modal-metric{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px}.modal-metric span{font-size:11px;text-transform:uppercase;color:#64748b;font-weight:800}.modal-metric b{display:block;font-size:16px;color:#0f172a}
.dash-card-button{border:0;text-align:left;width:100%;height:100%}.pro-modal .modal-content{border:0;border-radius:18px;overflow:hidden;box-shadow:0 26px 80px rgba(15,23,42,.28)}.pro-modal .modal-header{background:linear-gradient(135deg,#101827,#0f766e);color:#fff;border:0;padding:20px 24px}.pro-modal .modal-body{background:#f8fafc}.segment-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px}.segment-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px;box-shadow:0 10px 24px rgba(15,23,42,.07)}.segment-top{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px}.segment-icon{width:40px;height:40px;border-radius:12px;background:#e0f2fe;color:#0369a1;display:flex;align-items:center;justify-content:center}.modal-table-wrap{max-height:340px;overflow:auto;border:1px solid #e2e8f0;border-radius:12px;background:#fff}.quick-drawer-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.36);z-index:1040;display:none}.quick-drawer{position:fixed;top:0;right:-420px;width:min(420px,100vw);height:100vh;background:#fff;z-index:1041;box-shadow:-24px 0 60px rgba(15,23,42,.22);transition:right .24s ease;display:flex;flex-direction:column}.quick-drawer.open{right:0}.quick-drawer-backdrop.open{display:block}.quick-drawer-head{padding:20px 22px;background:#0f172a;color:#fff;display:flex;justify-content:space-between;align-items:center}.quick-drawer-body{padding:18px;overflow:auto}.quick-section-title{font-size:11px;text-transform:uppercase;color:#64748b;font-weight:900;letter-spacing:.6px;margin:16px 0 8px}.quick-side-link{display:flex;align-items:center;gap:12px;padding:12px;border:1px solid #e5e7eb;border-radius:10px;color:#0f172a;font-weight:800;margin-bottom:8px}.quick-side-link i{width:24px;color:#0f766e}.quick-open-btn{border:1px solid rgba(255,255,255,.35);background:rgba(255,255,255,.12);color:#fff;border-radius:10px;padding:10px 14px;font-weight:800}
.sales-viz-shell{background:#08111f;border-radius:16px;padding:18px;color:#fff;box-shadow:0 18px 42px rgba(8,17,31,.2)}.sales-viz-tabs{display:flex;gap:8px;flex-wrap:wrap}.sales-viz-tab{border:1px solid rgba(255,255,255,.18);background:rgba(255,255,255,.08);color:#dbeafe;border-radius:10px;padding:8px 11px;font-size:12px;font-weight:900}.sales-viz-tab.active{background:#fff;color:#0f172a}.sales-viz-pane{display:none;min-height:360px}.sales-viz-pane.active{display:block}.category-pie-wrap{display:grid;grid-template-columns:minmax(240px,360px) 1fr;gap:22px;align-items:center}.category-pie{width:min(340px,72vw);aspect-ratio:1;border-radius:50%;background:var(--pie-gradient);position:relative;margin:auto;animation:categorySpin 1s cubic-bezier(.2,.9,.2,1);box-shadow:inset 0 0 0 18px rgba(255,255,255,.08),0 26px 70px rgba(0,0,0,.3)}.category-pie:after{content:"";position:absolute;inset:27%;border-radius:50%;background:#08111f;box-shadow:inset 0 0 22px rgba(255,255,255,.08)}.category-pie-center{position:absolute;inset:34%;display:flex;align-items:center;justify-content:center;text-align:center;z-index:2;font-weight:900;font-size:22px}.category-legend{display:grid;gap:9px}.category-legend-row{display:grid;grid-template-columns:14px 1fr auto;gap:9px;align-items:center;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.08);border-radius:10px;padding:9px 11px}.category-dot{width:12px;height:12px;border-radius:50%;background:var(--c)}.category-meter{height:8px;background:rgba(255,255,255,.1);border-radius:999px;overflow:hidden;margin-top:4px}.category-meter span{display:block;height:100%;width:var(--w);background:var(--c);animation:growBar 1.1s ease}.candle-stage,.bar-stage{display:flex;align-items:flex-end;gap:14px;height:300px;padding:20px;background:rgba(255,255,255,.04);border-radius:14px}.candle-stick{flex:1;min-width:36px;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;gap:8px}.candle-line{width:3px;height:var(--wick);background:rgba(255,255,255,.35);border-radius:99px}.candle-body{width:34px;height:var(--h);background:var(--c);border-radius:7px;box-shadow:0 8px 22px color-mix(in srgb,var(--c),transparent 55%);animation:riseBar 1s ease}.bar-col{flex:1;display:flex;flex-direction:column;justify-content:flex-end;gap:8px;align-items:center;min-width:44px}.bar-fill{width:100%;max-width:64px;height:var(--h);background:linear-gradient(180deg,var(--c),rgba(255,255,255,.18));border-radius:10px 10px 4px 4px;animation:riseBar 1s ease}.chart-label{font-size:11px;color:#cbd5e1;text-align:center;max-width:92px}.wave-pro{width:100%;height:330px;background:rgba(255,255,255,.04);border-radius:14px}.wave-pro path{fill:none;stroke-width:5;stroke-linecap:round;stroke-linejoin:round;stroke-dasharray:1100;stroke-dashoffset:1100;animation:draw 1.8s ease forwards}.content-sales-row{display:grid;grid-template-columns:42px 1fr auto;gap:12px;align-items:center;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:12px;margin-bottom:10px;color:#0f172a}.content-sales-row .segment-icon{background:var(--soft);color:var(--c)}@keyframes categorySpin{from{transform:scale(.86) rotate(-26deg);opacity:.45}to{transform:scale(1) rotate(0);opacity:1}}@keyframes growBar{from{width:0}to{width:var(--w)}}@keyframes riseBar{from{height:0;opacity:.3}to{height:var(--h);opacity:1}}@media(max-width:768px){.category-pie-wrap{grid-template-columns:1fr}.sales-viz-pane{min-height:auto}.candle-stage,.bar-stage{overflow-x:auto;align-items:flex-end}}
.segment-light-head{background:linear-gradient(135deg,#e0f2fe,#d1fae5)!important;color:#0f172a!important}.segment-filter-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin-bottom:14px}.segment-total-label{font-size:12px;color:#0f766e;text-transform:uppercase;font-weight:900;letter-spacing:.6px}.segment-total-value{font-size:26px;font-weight:950;color:#0f172a}.segment-report-modal .sales-viz-shell{background:#f8fafc;border:1px solid #dbeafe;color:#0f172a}.segment-report-modal .bar-stage{background:#fff;border:1px solid #e2e8f0}.segment-report-modal .chart-label{color:#475569}.segment-report-modal .close{color:#0f172a;opacity:.8}
</style>
@endpush

@section('content')
@php
    $user = auth()->user();
    $roleLabel = $user->isSuperAdmin() ? 'Super Admin Control Center' : ($user->isAdmin() ? 'Company Admin Dashboard' : 'My Role Dashboard');
    $serviceNameOptions = collect($serviceRows ?? [])->pluck('service')->filter()->unique()->sort()->values();
    $cards = [];
    if ($user->isSuperAdmin()) {
        $cards[] = ['label'=>'Companies','value'=>$stats['companies'] ?? 0,'icon'=>'fa-building','accent'=>'#2563eb'];
        $cards[] = ['label'=>'Users','value'=>$stats['users'] ?? 0,'icon'=>'fa-users','accent'=>'#14b8a6'];
        $cards[] = ['label'=>'Company Admins','value'=>$stats['admins'] ?? 0,'icon'=>'fa-user-shield','accent'=>'#ec4899'];
        $cards[] = ['label'=>'Active Companies','value'=>$stats['active_companies'] ?? 0,'icon'=>'fa-check-circle','accent'=>'#22c55e'];
    }
    if ($user->can('sales.view')) $cards[] = ['label'=>'Sales','value'=>'Rs '.number_format($stats['sales'] ?? 0,2),'icon'=>'fa-file-invoice-dollar','accent'=>'#2563eb','modal'=>'salesSegmentModal'];
    if ($user->can('sales.view')) $cards[] = ['label'=>'Sales Due','value'=>'Rs '.number_format($stats['sales_due'] ?? 0,2),'icon'=>'fa-hand-holding-dollar','accent'=>'#dc2626','modal'=>'salesDueModal'];
    if ($user->can('purchase.view')) $cards[] = ['label'=>'Purchase','value'=>'Rs '.number_format($stats['purchases'] ?? 0,2),'icon'=>'fa-shopping-cart','accent'=>'#ec4899','modal'=>'purchaseSegmentModal'];
    if ($user->can('purchase.view')) $cards[] = ['label'=>'Purchase Due','value'=>'Rs '.number_format($stats['purchase_due'] ?? 0,2),'icon'=>'fa-file-circle-exclamation','accent'=>'#f59e0b','target'=>'purchaseDueBox'];
    if ($user->can('reports.transaction')) $cards[] = ['label'=>'Service Amount','value'=>'Rs '.number_format($stats['service_amount'] ?? 0,2),'icon'=>'fa-concierge-bell','accent'=>'#0ea5e9','modal'=>'serviceModal'];
    if ($user->can('items.view')) $cards[] = ['label'=>'Items','value'=>$stats['items'] ?? 0,'icon'=>'fa-box','accent'=>'#f59e0b'];
    if ($user->can('stocks.view')) $cards[] = ['label'=>'Low Stock','value'=>$stats['low_stock'] ?? 0,'icon'=>'fa-exclamation-triangle','accent'=>'#ef4444'];
    if ($user->can('banking.view')) $cards[] = ['label'=>'Bank Balance','value'=>'Rs '.number_format($stats['bank_balance'] ?? 0,2),'icon'=>'fa-university','accent'=>'#06b6d4'];
    if ($user->can('estimates.view')) $cards[] = ['label'=>'Estimates','value'=>$stats['estimates'] ?? 0,'icon'=>'fa-file-contract','accent'=>'#4338ca'];
    if ($user->can('delivery_challans.view')) $cards[] = ['label'=>'Challans','value'=>$stats['challans'] ?? 0,'icon'=>'fa-truck','accent'=>'#0f766e'];
    if ($user->can('expenses.view')) $cards[] = ['label'=>'Pending Expenses','value'=>$stats['pending_expenses'] ?? 0,'icon'=>'fa-clipboard-check','accent'=>'#10b981'];
    if ($user->can('reports.transaction')) $cards[] = ['label'=>'Total Profit (on Cost)','html'=>'Rs '.number_format($stats['total_profit'] ?? 0,2).'<br><small style="color:#64748b;font-weight:800">On Sale '.number_format($stats['total_profit_percent_on_sale'] ?? 0,2).'% | On Cost '.number_format($stats['total_profit_percent'] ?? 0,2).'%</small>','icon'=>'fa-chart-line','accent'=>'#0f766e','modal'=>'profitSegmentModal'];
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
            <button type="button" class="quick-open-btn mt-3" id="openQuickDrawer"><i class="fas fa-bolt mr-1"></i> Quick Links</button>
        </div>
    </div>
</div>

<form class="filter-panel" method="GET" id="dashboardFilterForm">
    <input type="hidden" name="period" id="dashboardPeriod" value="{{ $period }}">
    <div class="row align-items-end">
        @if($user->isSuperAdmin())
            <div class="col-md-4 form-group mb-md-0"><label>Company</label><select name="company_id" class="form-control"><option value="">All Companies</option>@foreach($companiesFilter as $company)<option value="{{ $company->id }}" @selected((int)$companyId === (int)$company->id)>{{ $company->name }}</option>@endforeach</select></div>
        @endif
        <div class="col-md-{{ $user->isSuperAdmin() ? '8' : '10' }} form-group mb-md-0">
            <label>Date Filter</label>
            <div class="period-tabs">
                @foreach(['today'=>'Today','yesterday'=>'Yesterday','week'=>'Week','month'=>'Month','three_months'=>'3 Month','six_months'=>'6 Month','nine_months'=>'9 Month','year'=>'1 Year','all'=>'All','custom'=>'Custom Date'] as $value => $label)
                    <button type="button" data-period="{{ $value }}" class="period-tab {{ $period === $value ? 'active' : '' }}">{{ $label }}</button>
                @endforeach
            </div>
        </div>
        <div class="col-md-3 form-group mb-md-0 custom-date-box" style="{{ $period === 'custom' ? '' : 'display:none' }}"><label>From Date</label><input type="date" name="from_date" value="{{ $from }}" class="form-control" @required($period === 'custom')></div>
        <div class="col-md-3 form-group mb-md-0 custom-date-box" style="{{ $period === 'custom' ? '' : 'display:none' }}"><label>To Date</label><input type="date" name="to_date" value="{{ $to }}" class="form-control" @required($period === 'custom')></div>
        <div class="col-md-2"><button class="btn btn-primary btn-block"><i class="fas fa-filter mr-1"></i> Filter</button></div>
    </div>
</form>

<div class="row">
    @forelse($cards as $card)
        <div class="col-6 col-xl-3 mb-4">
            <div class="metric-card" style="--accent:{{ $card['accent'] }};cursor:{{ isset($card['modal']) ? 'pointer' : 'default' }}" @if(isset($card['modal'])) data-toggle="modal" data-target="#{{ $card['modal'] }}" @endif>
                <div class="metric-icon"><i class="fas {{ $card['icon'] }}"></i></div>
                <div class="metric-value">@if(isset($card['html'])) {!! $card['html'] !!} @else {{ $card['value'] }} @endif</div>
                <div class="metric-label">{{ $card['label'] }}</div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="alert alert-info">No dashboard widgets are available for this role yet.</div></div>
    @endforelse
</div>

<div class="row">
    @can('reports.transaction')
    <div class="col-12 mb-4">
        <div class="ops-card report-card" data-export-title="Dashboard Ageing Report" data-export-file="dashboard-ageing-report">
            @include('admin.reports.partials.branded-export')
            <div class="ops-head">
                <div><div class="ops-kicker">Ageing Report</div><div class="ops-amount">Rs {{ number_format(($stats['sales_due'] ?? 0) + ($stats['purchase_due'] ?? 0),2) }}</div></div>
                <span class="badge badge-light">Party-wise slab summary</span>
            </div>
            <form method="GET" class="row mb-3">
                @foreach(request()->except(['ageing_kind']) as $key => $value) @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif @endforeach
                <div class="col-10"><select name="ageing_kind" class="form-control form-control-sm"><option value="both" @selected($ageingKind==='both')>Both</option><option value="receivable" @selected($ageingKind==='receivable')>Receivable</option><option value="payable" @selected($ageingKind==='payable')>Payable</option></select></div>
                <div class="col-2"><button class="btn btn-sm btn-primary btn-block"><i class="fas fa-filter"></i></button></div>
            </form>
            <div class="table-responsive">
                <table class="table ageing-table mb-0">
                    <thead><tr><th>Party</th><th>Receivable</th><th>Payable</th>@foreach($ageingSlabLabels as $label)<th>{{ $label }}</th>@endforeach<th>Total Due</th></tr></thead>
                    <tbody>
                    @forelse($ageingMatrix as $row)
                        <tr>
                            <td>
                                <strong>{{ $row['party'] }}</strong><br>

                                @if(!empty($row['state']))
                                    <small class="text-muted">
                                        <strong>State:</strong> {{ $row['state'] }} - {{ $row['district'] }}
                                    </small><br>
                                @endif

                                @if(!empty($row['district']))
                                    <small class="text-muted">
                                        <strong>District:</strong> {{ $row['district'] }}
                                    </small><br>
                                @endif

                                <small>{{ $row['bill_count'] }} open bill(s)</small>
                            </td>
                            <td>Rs {{ number_format($row['receivable'],2) }}</td><td>Rs {{ number_format($row['payable'],2) }}</td>
                            @foreach($ageingSlabLabels as $key => $label) @php $cell=$row['slabs'][$key]; @endphp<td title="{{ $cell['invoices'] }}">@if($cell['bills'])<b>Rs {{ number_format($cell['due'],2) }}</b><br><small>{{ $cell['bills'] }} bill(s)</small>@else<span class="text-muted">—</span>@endif</td>@endforeach
                            <td><b>Rs {{ number_format($row['total_due'],2) }}</b></td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-muted text-center py-4">No ageing due found for this filter.</td></tr>
                    @endforelse
                    </tbody>
                    <tfoot><tr><th>Total</th><th>Rs {{ number_format($ageingMatrix->sum('receivable'),2) }}</th><th>Rs {{ number_format($ageingMatrix->sum('payable'),2) }}</th>@foreach($ageingSlabLabels as $key => $label)<th>Rs {{ number_format($ageingMatrix->sum(fn($row) => $row['slabs'][$key]['due']),2) }}</th>@endforeach<th>Rs {{ number_format($ageingMatrix->sum('total_due'),2) }}</th></tr></tfoot>
                </table>
            </div>
        </div>
    </div>
    @endcan
    @can('sales.view')
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="ops-card">
            <div class="ops-head"><div><div class="ops-kicker">Sales Products</div><div class="ops-amount">Rs {{ number_format($stats['sales'] ?? 0,2) }}</div></div><i class="fas fa-chart-line text-primary"></i></div>
            @forelse($salesProducts->take(5) as $index => $product)
                <div class="product-row">
                    <div><div class="product-name">{{ $product['name'] }}</div><small>{{ number_format($product['qty'],2) }} {{ $product['unit'] }} | Rs {{ number_format($product['amount'],2) }}</small></div>
                    @if($index === 0)<span class="tag-hot"><i class="fas fa-fire"></i>Most Selling</span>@endif
                </div>
            @empty
                <div class="text-muted">No sales products in this filter.</div>
            @endforelse
        </div>
    </div>
    @endcan
    @can('purchase.view')
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="ops-card">
            <div class="ops-head"><div><div class="ops-kicker">Purchase Products</div><div class="ops-amount">Rs {{ number_format($stats['purchases'] ?? 0,2) }}</div></div><i class="fas fa-cart-shopping text-danger"></i></div>
            @forelse($purchaseProducts->take(5) as $product)
                <div class="product-row">
                    <div><div class="product-name">{{ $product['name'] }}</div><small>{{ number_format($product['qty'],2) }} {{ $product['unit'] }} | Rs {{ number_format($product['amount'],2) }}</small></div>
                </div>
            @empty
                <div class="text-muted">No purchase products in this filter.</div>
            @endforelse
        </div>
    </div>
    @endcan
</div>

@can('stocks.view')
<div class="ops-card mb-4">
    <div class="ops-head"><div><div class="ops-kicker">Low Stock Watch</div><div class="ops-amount">{{ $lowStockProducts->count() }} Items</div></div><i class="fas fa-triangle-exclamation text-danger"></i></div>
    <div class="row">
        @forelse($lowStockProducts as $product)
            <div class="col-md-6 col-xl-4 mb-3">
                <div class="product-row {{ $product['most_selling'] ? 'blink-alert' : '' }}" style="border:1px solid #eef2f7;border-radius:10px;padding:12px;">
                    <div><div class="product-name">{{ $product['name'] }}</div><small>Stock {{ number_format($product['stock'],2) }} {{ $product['unit'] }} | Alert {{ number_format($product['low'],2) }}</small></div>
                    <div>@if($product['most_selling'])<span class="tag-hot">Most Selling</span>@endif <span class="tag-low">Low</span></div>
                </div>
            </div>
        @empty
            <div class="col-12 text-muted">No low stock products right now.</div>
        @endforelse
    </div>
</div>
@endcan
<div class="row">
    <div class="col-lg-7 mb-4"><div class="chart-card"><h5>Quick Actions</h5><div class="quick-grid mt-3">@forelse($quickActions as $action)<a class="quick-action" href="{{ route($action['route']) }}"><i class="fas {{ $action['icon'] }}"></i>{{ $action['label'] }}</a>@empty <span class="text-muted">No actions available for this role.</span>@endforelse</div></div></div>
    <div class="col-lg-5 mb-4"><div class="chart-card"><h5>Recent Activity</h5>@forelse($recentLogs as $log)<div class="activity-row"><div class="activity-dot"><i class="fas fa-bolt"></i></div><div><b>{{ $log->user?->name ?? 'System' }}</b> {{ $log->action }}<br><span class="text-muted small">{{ \Illuminate\Support\Str::limit($log->description, 54) }} - {{ $log->created_at?->diffForHumans() }}</span></div></div>@empty <div class="text-muted">No activity yet.</div>@endforelse <div class="activity-footer mt-3">{{ $recentLogs->appends(request()->except('activity_page'))->links('pagination::bootstrap-5') }}</div></div></div>
</div>
<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="chart-card">
            <div class="d-flex justify-content-between mb-3"><h5 class="m-0">Animated Wave Trend</h5><span class="text-muted">{{ $from }} to {{ $to }}</span></div>
            <svg class="wave-chart" viewBox="0 0 760 220" preserveAspectRatio="none">
                <defs><linearGradient id="salesGrad" x1="0" x2="1"><stop offset="0" stop-color="#22d3ee"/><stop offset="1" stop-color="#2563eb"/></linearGradient><linearGradient id="purchaseGrad" x1="0" x2="1"><stop offset="0" stop-color="#f472b6"/><stop offset="1" stop-color="#ec4899"/></linearGradient></defs>
                @php
                    $maxVal = max(1, collect($monthly['sales'])->merge($monthly['purchases'])->max());
                    $labelCount = max(1, count($monthly['labels']) - 1);
                    $points = function($series) use ($maxVal, $labelCount) { return collect($series)->values()->map(fn($v,$i) => (30 + ($i * (700 / $labelCount))).','. (190 - ((float)$v / $maxVal * 150)))->implode(' '); };
                @endphp
                @foreach(range(0,4) as $line)<line class="wave-grid" x1="20" x2="740" y1="{{ 40 + ($line * 38) }}" y2="{{ 40 + ($line * 38) }}"/>@endforeach
                <polyline class="wave-line" points="{{ $points($monthly['sales']) }}" stroke="url(#salesGrad)"/>
                <polyline class="wave-line" points="{{ $points($monthly['purchases']) }}" stroke="url(#purchaseGrad)" style="animation-delay:.25s"/>
                @foreach($monthly['labels'] as $i => $label)<text x="{{ 24 + ($i * (700 / $labelCount)) }}" y="214" font-size="12" fill="#64748b">{{ $label }}</text>@endforeach
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

<div class="quick-drawer-backdrop" id="quickDrawerBackdrop"></div>
<aside class="quick-drawer" id="quickDrawer" aria-hidden="true">
    <div class="quick-drawer-head">
        <div>
            <div style="font-size:12px;color:#cbd5e1;text-transform:uppercase;font-weight:900">Command Center</div>
            <h5 class="m-0 font-weight-bold">Quick Links</h5>
        </div>
        <button type="button" class="close text-white" id="closeQuickDrawer" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
    <div class="quick-drawer-body">
        <div class="quick-section-title">Sales</div>
        @can('sales.create')<a class="quick-side-link" href="{{ route('admin.sales.create') }}"><i class="fas fa-file-invoice-dollar"></i>New Sale</a>@endcan
        @can('party_payments.view')<a class="quick-side-link" href="{{ route('admin.party-payments.create') }}?type=payment_in"><i class="fas fa-hand-holding-usd"></i>Payment In</a>@endcan
        @can('estimates.create')<a class="quick-side-link" href="{{ route('admin.estimates.create') }}"><i class="fas fa-file-contract"></i>Estimate</a>@endcan
        @can('delivery_challans.create')<a class="quick-side-link" href="{{ route('admin.delivery-challans.create') }}"><i class="fas fa-truck"></i>Delivery Challan</a>@endcan
        <div class="quick-section-title">Purchase</div>
        @can('purchase.create')<a class="quick-side-link" href="{{ route('admin.purchases.create') }}"><i class="fas fa-shopping-cart"></i>New Purchase</a>@endcan
        @can('party_payments.view')<a class="quick-side-link" href="{{ route('admin.party-payments.create') }}?type=payment_out"><i class="fas fa-money-check-alt"></i>Payment Out</a>@endcan
        <div class="quick-section-title">Inventory</div>
        @can('items.create')<a class="quick-side-link" href="{{ route('admin.items.create') }}"><i class="fas fa-box"></i>Add Item</a>@endcan
        @can('stocks.view')<a class="quick-side-link" href="{{ route('admin.stocks.index') }}"><i class="fas fa-warehouse"></i>Stock Dashboard</a>@endcan
        @can('stocks.view')<a class="quick-side-link" href="{{ route('admin.stock-transfers.create') }}"><i class="fas fa-random"></i>Stock Transfer</a>@endcan
        <div class="quick-section-title">Important Reports</div>
        @can('reports.transaction')<a class="quick-side-link" href="{{ route('admin.reports.bill-wise-profit') }}"><i class="fas fa-chart-line"></i>Bill Wise Profit</a>@endcan
        @can('reports.transaction')<a class="quick-side-link" href="{{ route('admin.reports.ageing') }}"><i class="fas fa-hourglass-half"></i>Ageing Report</a>@endcan
        @can('reports.transaction')<a class="quick-side-link" href="{{ route('admin.reports.profit-loss') }}"><i class="fas fa-balance-scale"></i>Profit / Loss</a>@endcan
        @can('reports.party')<a class="quick-side-link" href="{{ route('admin.reports.party-statement') }}"><i class="fas fa-address-book"></i>Party Statement</a>@endcan
    </div>
</aside>

<div class="modal fade pro-modal" id="profitModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div><h5 class="modal-title mb-0">Invoice Wise Profit</h5><small>Filter: {{ $from }} to {{ $to }}</small></div>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-3"><div class="modal-metric"><span>Total Sale</span><b>Rs {{ number_format($profitRows->sum('sale'),2) }}</b></div></div>
                    <div class="col-md-3"><div class="modal-metric"><span>Total Cost</span><b>Rs {{ number_format($profitRows->sum('cost'),2) }}</b></div></div>
                    <div class="col-md-3"><div class="modal-metric"><span>Total Profit</span><b>Rs {{ number_format($profitRows->sum('profit'),2) }}</b></div></div>
                    <div class="col-md-3"><div class="modal-metric"><span>Profit % on Cost</span><b>{{ number_format($stats['total_profit_percent'] ?? 0,2) }}%</b></div></div>
                </div>
                <div class="modal-table-wrap"><table class="table mb-0"><thead><tr><th>Date</th><th>Invoice</th><th>Party</th><th>Sale</th><th>Cost</th><th>Profit</th><th>Profit %</th></tr></thead><tbody>
                    @forelse($profitRows as $row)<tr><td>{{ $row['date'] }}</td><td>{{ $row['invoice'] }}</td><td>{{ $row['party'] }}</td><td>Rs {{ number_format($row['sale'],2) }}</td><td>Rs {{ number_format($row['cost'],2) }}</td><td><b class="{{ $row['profit'] >= 0 ? 'text-success' : 'text-danger' }}">Rs {{ number_format($row['profit'],2) }}</b></td><td><b class="{{ $row['profit_percent'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($row['profit_percent'],2) }}%</b></td></tr>@empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No invoice profit found for this filter.</td></tr>@endforelse
                </tbody></table></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade pro-modal" id="serviceModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div><h5 class="modal-title mb-0">Service Cost Intelligence</h5><small>Finished-goods BOM services sold from {{ $from }} to {{ $to }}</small></div>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-3"><div class="modal-metric"><span>Total Service Cost</span><b>Rs {{ number_format($serviceTotals['amount'] ?? 0,2) }}</b></div></div>
                    <div class="col-md-3"><div class="modal-metric"><span>Service Lines</span><b>{{ number_format($serviceTotals['count'] ?? 0,0) }}</b></div></div>
                    <div class="col-md-3"><div class="modal-metric"><span>Invoices Touched</span><b>{{ number_format($serviceTotals['invoices'] ?? 0,0) }}</b></div></div>
                    <div class="col-md-3"><div class="modal-metric"><span>Service Names</span><b>{{ $serviceNameOptions->count() }}</b></div></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="font-weight-bold">Filter by Service</label>
                        <select id="serviceFilter" class="form-control">
                            <option value="">All Services</option>
                            @foreach($serviceNameOptions as $serviceName)
                                <option value="{{ $serviceName }}">{{ $serviceName }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row" id="serviceChartRows">
                    @foreach($serviceRows->groupBy('service')->sortByDesc(fn($rows) => $rows->sum('amount')) as $service => $rows)
                        @php
                            $serviceAmount = (float) $rows->sum('amount');
                            $serviceQty = (float) $rows->sum('qty');
                            $servicePct = ($serviceTotals['amount'] ?? 0) > 0 ? round($serviceAmount / $serviceTotals['amount'] * 100, 2) : 0;
                        @endphp
                        <div class="col-md-6 mb-3 service-chart-row" data-service="{{ $service }}">
                            <div class="p-3 bg-white rounded border h-100">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div><b>{{ $service }}</b><br><small class="text-muted">{{ number_format($serviceQty,2) }} qty | {{ number_format($servicePct,2) }}%</small></div>
                                    <strong>Rs {{ number_format($serviceAmount,2) }}</strong>
                                </div>
                                <div style="height:10px;background:#e2e8f0;border-radius:999px;overflow:hidden">
                                    <div style="height:100%;width:{{ min(100, max(3, $servicePct)) }}%;background:linear-gradient(90deg,#0ea5e9,#14b8a6)"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="table-responsive mt-2">
                    <table class="table table-sm mb-0" id="serviceDetailTable">
                        <thead><tr><th>Date</th><th>Invoice</th><th>Party</th><th>Item</th><th>Service</th><th>Qty</th><th>Unit Cost</th><th>Amount</th></tr></thead>
                        <tbody>
                        @forelse($serviceRows as $row)
                            <tr class="service-detail-row" data-service="{{ $row['service'] }}">
                                <td>{{ $row['invoice_date']?->format('d M Y') }}</td>
                                <td>{{ $row['invoice'] }}</td>
                                <td>{{ $row['party'] }}</td>
                                <td>{{ $row['item'] }}</td>
                                <td><b>{{ $row['service'] }}</b></td>
                                <td>{{ number_format((float) $row['qty'],2) }}</td>
                                <td>Rs {{ number_format((float) $row['unit_price'],2) }}</td>
                                <td><strong>Rs {{ number_format((float) $row['amount'],2) }}</strong></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">No service cost found for this range.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.partials.dashboard-segment-modal', [
    'modalId' => 'salesSegmentModal',
    'title' => 'Product Category Wise Sales',
    'amountLabel' => 'Sales',
    'segments' => $salesSegments,
])

<div class="modal fade pro-modal" id="salesDueModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div><h5 class="modal-title mb-0">Sales Due Details</h5><small>Party, invoice age, due amount and payment history for {{ $from }} to {{ $to }}</small></div>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="modal-table-wrap"><table class="table mb-0"><thead><tr><th>Party</th><th>Invoice</th><th>Age</th><th>Total</th><th>Paid</th><th>Due</th><th>Payment History</th></tr></thead><tbody>
                    @forelse($salesDueRows as $row)
                        <tr>
                            <td><b>{{ $row['party'] }}</b></td>
                            <td>{{ $row['invoice'] }}<br><small>{{ $row['date']?->format('d M Y') }}</small></td>
                            <td>{{ $row['age'] }} days</td>
                            <td>Rs {{ number_format($row['total'],2) }}</td>
                            <td>Rs {{ number_format($row['paid'],2) }}</td>
                            <td><b class="text-danger">Rs {{ number_format($row['due'],2) }}</b></td>
                            <td>
                                @forelse($row['payments'] as $payment)
                                    <div><b>Rs {{ number_format($payment['amount'],2) }}</b> on {{ $payment['date'] }} <small>({{ $payment['mode'] }} / {{ $payment['reference'] }})</small></div>
                                @empty
                                    <span class="text-muted">No payment yet.</span>
                                @endforelse
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No sales due for this filter.</td></tr>
                    @endforelse
                </tbody></table></div>
            </div>
        </div>
    </div>
</div>

@include('admin.partials.dashboard-segment-modal', [
    'modalId' => 'purchaseSegmentModal',
    'title' => 'Product Category Wise Purchase',
    'amountLabel' => 'Purchase',
    'segments' => $purchaseSegments,
])

@include('admin.partials.dashboard-segment-modal', [
    'modalId' => 'profitSegmentModal',
    'title' => 'Product Category Wise Profit',
    'amountLabel' => 'Profit',
    'segments' => $profitSegments,
])

<div class="modal fade" id="invoiceDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content" style="border-radius:14px;border:0;overflow:hidden">
            <div class="modal-header" style="background:#0f172a;color:#fff">
                <div>
                    <h5 class="modal-title mb-0" id="detailTitle">Invoice Details</h5>
                    <small id="detailSub" style="color:#cbd5e1"></small>
                </div>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-3"><div class="modal-metric"><span>Total</span><b id="detailTotal">Rs 0.00</b></div></div>
                    <div class="col-md-3"><div class="modal-metric"><span>Received / Paid</span><b id="detailPaid">Rs 0.00</b></div></div>
                    <div class="col-md-3"><div class="modal-metric"><span>Due</span><b id="detailDue">Rs 0.00</b></div></div>
                    <div class="col-md-3"><div class="modal-metric"><span>Age</span><b id="detailAge">0 days</b></div></div>
                </div>
                <div class="row">
                    <div class="col-lg-7">
                        <h6 class="font-weight-bold">Invoice Items</h6>
                        <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Item</th><th>Qty</th><th>Rate</th><th>Amount</th></tr></thead><tbody id="detailItems"></tbody></table></div>
                    </div>
                    <div class="col-lg-5">
                        <h6 class="font-weight-bold">Payment Details</h6>
                        <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>Bank</th><th>Mode</th><th>Amount</th></tr></thead><tbody id="detailPayments"></tbody></table></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <small class="text-muted">Payment In/Out button opens the existing payment screen with bill context.</small>
                <a id="detailPaymentAction" href="#" class="btn btn-primary"><i class="fas fa-money-bill-wave mr-1"></i>Payment</a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$('.period-tab').on('click', function(){
    const period = $(this).data('period');
    $('#dashboardPeriod').val(period);
    $('.period-tab').removeClass('active');
    $(this).addClass('active');
    const isCustom = period === 'custom';
    $('.custom-date-box').toggle(isCustom).find('input').prop('required', isCustom);
    if (!isCustom) $('#dashboardFilterForm').trigger('submit');
});
$('#dashboardFilterForm input[type="date"]').on('change', function(){
    $('#dashboardPeriod').val('custom');
    $('.period-tab').removeClass('active');
    $('.period-tab[data-period="custom"]').addClass('active');
    $('.custom-date-box').show().find('input').prop('required', true);
});
function dashMoney(value){return 'Rs '+(Number(value)||0).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2});}
$('#openQuickDrawer').on('click',function(){
    $('#quickDrawer,#quickDrawerBackdrop').addClass('open');
    $('#quickDrawer').attr('aria-hidden','false');
});
$('#closeQuickDrawer,#quickDrawerBackdrop').on('click',function(){
    $('#quickDrawer,#quickDrawerBackdrop').removeClass('open');
    $('#quickDrawer').attr('aria-hidden','true');
});
$(document).on('click','.sales-viz-tab',function(){
    const pane = $(this).data('sales-viz');
    const $shell = $(this).closest('.sales-viz-shell');
    $shell.find('.sales-viz-tab').removeClass('active');
    $(this).addClass('active');
    $shell.find('.sales-viz-pane').removeClass('active');
    $shell.find(`[data-sales-pane="${pane}"]`).addClass('active');
    replaySegmentAnimations($(this).closest('.segment-report-modal'));
});
$(document).on('click','.view-detail-btn',function(){
    const row = $(this).data('invoice');
    $('#detailTitle').text((row.kind === 'receivable' ? 'Sales Invoice ' : 'Purchase Bill ') + row.invoice);
    $('#detailSub').text(`${row.party} | ${row.date || '-'} | ${row.kind === 'receivable' ? 'Amount to receive' : 'Amount to pay'}`);
    $('#detailTotal').text(dashMoney(row.total));
    $('#detailPaid').text(dashMoney(row.paid));
    $('#detailDue').text(dashMoney(row.due));
    $('#detailAge').text((row.age || 0) + ' days');
    $('#detailItems').html((row.items || []).map(item => `<tr><td>${item.name}</td><td>${Number(item.qty||0).toFixed(2)} ${item.unit||''}</td><td>${dashMoney(item.rate)}</td><td>${dashMoney(item.amount)}</td></tr>`).join('') || '<tr><td colspan="4" class="text-muted">No item lines.</td></tr>');
    $('#detailPayments').html((row.payments || []).map(payment => `<tr><td>${payment.date}</td><td>${payment.bank}<br><small>${payment.reference}</small></td><td>${payment.mode}</td><td>${dashMoney(payment.amount)}</td></tr>`).join('') || '<tr><td colspan="4" class="text-muted">No payment received yet.</td></tr>');
    const type = row.kind === 'receivable' ? 'payment_in' : 'payment_out';
    $('#detailPaymentAction').attr('href', `{{ route('admin.party-payments.create') }}?type=${type}&party_id=${row.party_id || ''}&bill_id=${row.bill_id || ''}`);
    $('#detailPaymentAction').html(row.kind === 'receivable' ? '<i class="fas fa-money-bill-wave mr-1"></i>Payment In' : '<i class="fas fa-hand-holding-usd mr-1"></i>Payment Out');
    $('#invoiceDetailModal').modal('show');
});
$('#serviceFilter').on('change', function(){
    const value = $(this).val();
    $('.service-chart-row,.service-detail-row').each(function(){
        const service = $(this).data('service');
        $(this).toggle(!value || service === value);
    });
});
function segmentModalItems($modal) {
    return $modal.find('.segment-card-filterable').map(function(){
        return ($(this).data('segment') || {}).items || [];
    }).get().flat();
}

function segmentUniqueValues(items, key) {
    return [...new Set((items || []).map(item => item[key]).filter(Boolean))].sort((a, b) => String(a).localeCompare(String(b)));
}

function segmentSetOptions($select, values, selected) {
    const placeholder = $select.data('placeholder') || 'All';
    const validSelected = selected && values.includes(selected) ? selected : '';
    $select.html(`<option value="">${placeholder}</option>` + values.map(value => `<option value="${String(value).replace(/"/g,'&quot;')}">${value}</option>`).join(''));
    $select.val(validSelected);
}

function refreshSegmentFilterOptions($modal, changedFilter) {
    const allItems = segmentModalItems($modal);
    const categorySelect = $modal.find('[data-filter="category"]');
    const productTypeSelect = $modal.find('[data-filter="product_type"]');
    const party = $modal.find('[data-filter="party"]').val();
    const stateSelect = $modal.find('[data-filter="state"]');
    const districtSelect = $modal.find('[data-filter="district"]');
    const citySelect = $modal.find('[data-filter="city"]');
    const currentCategory = categorySelect.val();
    const currentProductType = productTypeSelect.val();
    const currentState = stateSelect.val();
    const currentDistrict = districtSelect.val();
    const currentCity = citySelect.val();

    segmentSetOptions(categorySelect, segmentUniqueValues(allItems, 'category'), currentCategory);
    const category = categorySelect.val();
    const categoryItems = category ? allItems.filter(item => item.category === category) : allItems;
    segmentSetOptions(productTypeSelect, segmentUniqueValues(categoryItems, 'product_type').filter(value => value !== '-'), changedFilter === 'category' ? '' : currentProductType);
    const productType = productTypeSelect.val();
    const productItems = productType ? categoryItems.filter(item => item.product_type === productType) : categoryItems;

    const partyItems = party ? productItems.filter(item => item.party === party) : productItems;
    segmentSetOptions(stateSelect, segmentUniqueValues(partyItems, 'state'), changedFilter === 'party' ? '' : currentState);

    const state = stateSelect.val();
    const stateItems = state ? partyItems.filter(item => item.state === state) : partyItems;
    segmentSetOptions(districtSelect, segmentUniqueValues(stateItems, 'district'), ['party','state'].includes(changedFilter) ? '' : currentDistrict);

    const district = districtSelect.val();
    const districtItems = district ? stateItems.filter(item => item.district === district) : stateItems;
    segmentSetOptions(citySelect, segmentUniqueValues(districtItems, 'city'), ['party','state','district'].includes(changedFilter) ? '' : currentCity);
}

function replaySegmentAnimations($modal) {
    const $animated = $modal.find('.category-pie,.category-meter span,.candle-body,.bar-fill,.segment-wave-path');
    $animated.each(function(){
        this.style.animation = 'none';
        void this.offsetHeight;
        this.style.animation = '';
    });
}

function applySegmentFilters() {
    const $modal = $(this).closest('.segment-report-modal');
    const filters = {};
    $modal.find('.segment-filter').each(function(){ filters[$(this).data('filter')] = this.value; });
    let modalTotal = 0;
    let modalAbsTotal = 0;
    const matches = item => Object.keys(filters).every(key => !filters[key] || item[key] === filters[key]);
    const segmentAmount = segment => (segment.items || []).filter(matches).reduce((sum,item) => sum + (Number(item.amount)||0), 0);
    const segmentQty = segment => (segment.items || []).filter(matches).reduce((sum,item) => sum + (Number(item.qty)||0), 0);
    const chartRows = [];
    $modal.find('.segment-card-filterable').each(function(){
        const segment = $(this).data('segment') || {};
        const items = segment.items || [];
        const matching = items.filter(matches);
        const amount = matching.reduce((sum,item) => sum + (Number(item.amount)||0), 0);
        const qty = matching.reduce((sum,item) => sum + (Number(item.qty)||0), 0);
        modalTotal += amount;
        modalAbsTotal += Math.abs(amount);
        chartRows.push({segment, amount, qty});
        $(this).toggle(matching.length > 0 || amount !== 0);
        $(this).find('.segment-top strong').text(dashMoney(amount));
        $(this).find('.segment-card-meta').text(`${qty.toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2})} qty`);
        $(this).find('tbody tr[data-item]').each(function(){
            const item = $(this).data('item') || {};
            $(this).toggle(matches(item));
        });
    });
    const maxAmount = Math.max(1, ...$modal.find('.segment-bar').map(function(){ return Math.abs(segmentAmount($(this).data('segment') || {})); }).get());
    $modal.find('.segment-bar,.segment-candle,.segment-content-row,.segment-legend,.segment-wave-point').each(function(){
        const segment = $(this).data('segment') || {};
        const amount = segmentAmount(segment);
        const qty = segmentQty(segment);
        const pct = modalAbsTotal > 0 ? (Math.abs(amount) / modalAbsTotal * 100) : 0;
        const height = Math.max(8, Math.abs(amount) / maxAmount * 230);
        $(this).toggle(amount !== 0);
        $(this).css({'--h': `${height}px`, '--wick': `${Math.min(260, height + 46)}px`, '--w': `${Math.min(100, pct)}%`});
        $(this).find('.chart-label').html(`${segment.label}<br>${$(this).hasClass('segment-candle') ? pct.toFixed(1) + '%' : dashMoney(amount)}`);
        $(this).find('.segment-legend-amount').html(`<b>${pct.toFixed(2)}%</b><br><small>${dashMoney(amount)}</small>`);
        $(this).find('strong').last().text(dashMoney(amount));
        $(this).find('small').first().text(`${qty.toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2})} qty | ${pct.toFixed(2)}%`);
    });
    let cursor = 0;
    const pieParts = chartRows.filter(row => row.amount !== 0).map(row => {
        const pct = modalAbsTotal > 0 ? Math.abs(row.amount) / modalAbsTotal * 100 : 0;
        const part = `${row.segment.color || '#64748b'} ${cursor}% ${Math.min(100, cursor + pct)}%`;
        cursor += pct;
        return part;
    });
    $modal.find('.segment-pie').css('--pie-gradient', pieParts.length ? `conic-gradient(${pieParts.join(',')})` : 'conic-gradient(#e2e8f0 0 100%)');
    $modal.find('.category-pie-center').html(`${modalAbsTotal > 0 ? '100%' : '0%'}<br><span style="font-size:11px;color:#64748b">${$modal.find('.segment-total-label').text().replace('Total ', '')}</span>`);
    const visibleRows = chartRows.filter(row => row.amount !== 0);
    const pointDenominator = Math.max(1, visibleRows.length - 1);
    const wavePoints = visibleRows.map((row, index) => {
        const x = 35 + (index * (690 / pointDenominator));
        const y = 285 - ((Math.abs(row.amount) / maxAmount) * 220);
        return `${x},${y}`;
    }).join(' ');
    $modal.find('.segment-wave-path').attr('d', wavePoints ? `M ${wavePoints}` : 'M 35,285');
    let waveIndex = 0;
    $modal.find('.segment-wave-point').each(function(){
        const segment = $(this).data('segment') || {};
        const amount = segmentAmount(segment);
        if (amount === 0) return;
        const row = visibleRows[waveIndex];
        if (!row) return;
        const index = waveIndex++;
        const x = 35 + (index * (690 / pointDenominator));
        const y = 285 - ((Math.abs(row.amount) / maxAmount) * 220);
        $(this).find('circle').attr({cx:x, cy:y, fill:row.segment.color || '#64748b'});
        $(this).find('text').attr({x:x}).text(String(row.segment.label || '').slice(0, 10));
    });
    $modal.find('.segment-total-value').text(dashMoney(modalTotal));
    replaySegmentAnimations($modal);
}

$(document).on('change','.segment-report-modal .segment-filter',function(){
    const $modal = $(this).closest('.segment-report-modal');
    refreshSegmentFilterOptions($modal, $(this).data('filter'));
    applySegmentFilters.call(this);
});
$('.segment-report-modal').on('shown.bs.modal', function(){
    const $modal = $(this);
    refreshSegmentFilterOptions($modal, null);
    applySegmentFilters.call($modal.find('.segment-filter').first()[0] || this);
});
</script>
@endpush
@endsection
