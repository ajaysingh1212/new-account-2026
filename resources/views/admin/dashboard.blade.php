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
.cheque-preview-wrap{width:100%;max-width:720px;container-type:inline-size}.cheque-preview{background:#afece8;color:#16233c;border-radius:10px;position:relative;padding:clamp(14px,3.6cqi,26px) clamp(14px,4.4cqi,32px) clamp(10px,2.8cqi,20px);overflow:hidden;box-shadow:0 18px 40px rgba(15,23,42,.18)}.cheque-preview:before{content:"";position:absolute;inset:0;opacity:.10;background-image:repeating-radial-gradient(circle at 0% 50%,transparent 0 6px,#3c6f60 6px 7px,transparent 7px 13px),repeating-radial-gradient(circle at 100% 50%,transparent 0 6px,#3c6f60 6px 7px,transparent 7px 13px),repeating-radial-gradient(circle at 50% -20%,transparent 0 9px,#a4772c 9px 10px,transparent 10px 19px);background-size:120px 120px,120px 120px,240px 240px}.cheque-preview:after{content:"CHEQUE";position:absolute;top:42%;left:50%;transform:translate(-50%,-50%) rotate(-11deg);font-family:serif;font-size:clamp(26px,9cqi,64px);letter-spacing:.2em;color:#8a2f2f;opacity:.06;white-space:nowrap}.cheque-complete-stamp{position:absolute;right:28px;top:72px;z-index:2;transform:rotate(-12deg);border:3px solid #15803d;color:#15803d;background:rgba(255,255,255,.38);font-weight:900;letter-spacing:.12em;padding:8px 14px;border-radius:6px}.cheque-preview .bank-row{display:flex;justify-content:space-between;gap:16px;position:relative;z-index:1}.cheque-preview .bank-mark{display:flex;gap:12px;align-items:center}.cheque-preview .seal{width:46px;height:46px;border-radius:50%;border:1.6px solid #a4772c;display:flex;align-items:center;justify-content:center;font-family:serif;font-weight:700;color:#a4772c}.cheque-preview .bank-name{font-family:serif;font-weight:800;font-size:23px;line-height:1}.cheque-preview .bank-sub,.cheque-preview label,.cheque-preview .lbl,.cheque-preview .micr{font-family:monospace;text-transform:uppercase;color:#2b3b58;font-size:10px;letter-spacing:.08em}.cheque-preview .cheque-no{text-align:right;font-family:monospace}.cheque-preview .cheque-no .val{font-size:17px;font-weight:700;color:#8a2f2f}.cheque-preview .line{border-bottom:1px solid #16233c;min-height:28px;font-family:serif;font-style:italic;font-size:17px;color:#16233c;padding:3px 2px}.cheque-preview .row-date{display:flex;justify-content:flex-end;gap:10px;align-items:end;margin-top:6px;position:relative;z-index:1}.cheque-preview .row-pay,.cheque-preview .row-words{display:flex;align-items:end;gap:14px;margin-top:20px;position:relative;z-index:1}.cheque-preview .row-pay .line,.cheque-preview .row-words .line{flex:1}.cheque-preview .amount-box{border:1.4px solid #16233c;padding:6px 10px;background:rgba(255,255,255,.35);font-family:monospace;font-weight:700;color:#8a2f2f}.cheque-preview .row-bottom{display:flex;justify-content:space-between;gap:24px;margin-top:30px;position:relative;z-index:1}.cheque-preview .memo-block,.cheque-preview .sig-block{flex:1}.cheque-preview .micr{margin-top:20px;padding-top:12px;border-top:1px dashed rgba(22,35,60,.25);display:flex;justify-content:center;gap:10px;position:relative;z-index:1;flex-wrap:wrap}
</style>
@include('admin.partials.segment-viz-styles')
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
    if ($user->can('party_payments.view')) $cards[] = ['label'=>'Total Collection','value'=>'Rs '.number_format($stats['total_collection'] ?? 0,2),'icon'=>'fa-hand-holding-usd','accent'=>'#16a34a','modal'=>'collectionModal'];
    if ($user->can('banking.view')) $cards[] = ['label'=>'Cheque Clearing','html'=>'Rs '.number_format($stats['cheque_paid'] ?? 0,2).'<br><small style="color:#64748b;font-weight:800">Upcoming clear Rs '.number_format($stats['cheque_clearing_due'] ?? 0,2).'</small>','icon'=>'fa-money-check-alt','accent'=>'#16a34a','modal'=>'chequeClearingModal'];
    if ($user->can('banking.view')) $cards[] = ['label'=>'Completed Cheques','value'=>'Rs '.number_format($stats['cheque_completed'] ?? 0,2),'icon'=>'fa-check-double','accent'=>'#0f766e','modal'=>'completedChequeModal'];
    if ($user->can('stocks.view')) $cards[] = ['label'=>'Low Stock','value'=>$stats['low_stock'] ?? 0,'icon'=>'fa-exclamation-triangle','accent'=>'#ef4444'];
    if ($user->can('banking.view')) $cards[] = ['label'=>'Bank Balance','value'=>'Rs '.number_format($stats['bank_balance'] ?? 0,2),'icon'=>'fa-university','accent'=>'#06b6d4'];
    if ($user->can('estimates.view')) $cards[] = ['label'=>'Estimates','value'=>'Rs '.number_format($stats['estimate_amount'] ?? 0,2),'icon'=>'fa-file-contract','accent'=>'#4338ca','modal'=>'estimateSegmentModal'];
    if ($user->can('delivery_challans.view')) $cards[] = ['label'=>'Pending Sales','value'=>'Rs '.number_format($stats['pending_sales'] ?? 0,2),'icon'=>'fa-hourglass-half','accent'=>'#f97316','url'=>route('admin.pending-orders.index', ['from_date' => $from, 'to_date' => $to])];
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
                @foreach(['today'=>'Today','week'=>'This Week','month'=>'This Month','last_month'=>'Last Month','yesterday'=>'Yesterday','three_months'=>'3 Month','six_months'=>'6 Month','nine_months'=>'9 Month','year'=>'1 Year','all'=>'All','custom'=>'Custom Date'] as $value => $label)
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
            <div class="metric-card" style="--accent:{{ $card['accent'] }};cursor:{{ isset($card['modal']) || isset($card['url']) ? 'pointer' : 'default' }}" @if(isset($card['modal'])) data-toggle="modal" data-target="#{{ $card['modal'] }}" @endif @if(isset($card['url'])) onclick="window.location='{{ $card['url'] }}'" @endif>
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
                                        <strong>State:</strong> {{ $row['state'] }} - {{ $row['city'] }}
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
    'modalId' => 'estimateSegmentModal',
    'title' => 'Product Category Wise Estimate',
    'amountLabel' => 'Estimate',
    'segments' => $estimateSegments,
])

@include('admin.partials.dashboard-segment-modal', [
    'modalId' => 'profitSegmentModal',
    'title' => 'Product Category Wise Profit',
    'amountLabel' => 'Profit',
    'segments' => $profitSegments,
])

<div class="modal fade pro-modal" id="chequeClearingModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div><h5 class="modal-title mb-0">Cheque Settlement Report</h5><small>Cheque age, validity, clearance date, bank details and bill settlement for {{ $from }} to {{ $to }}</small></div>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-3"><div class="modal-metric"><span>Cheque Amount</span><b>Rs {{ number_format($chequeRows->sum('amount'),2) }}</b></div></div>
                    <div class="col-md-3"><div class="modal-metric"><span>Settled</span><b>Rs {{ number_format($chequeRows->sum('settled'),2) }}</b></div></div>
                    <div class="col-md-3"><div class="modal-metric"><span>Due</span><b>Rs {{ number_format($chequeRows->sum('due'),2) }}</b></div></div>
                    <div class="col-md-3"><div class="modal-metric"><span>Cheques</span><b>{{ number_format($chequeRows->count()) }}</b></div></div>
                </div>
                <div class="row mb-3">
                    @foreach($chequeRows->groupBy(fn($row) => $row['days_left'] === null ? 'No Clearance' : ($row['days_left'] < 0 ? 'Overdue' : ($row['days_left'] <= 7 ? 'This Week' : ($row['days_left'] <= 31 ? 'This Month' : 'Later')))) as $label => $group)
                        @php $pct = $chequeRows->sum('amount') > 0 ? min(100, $group->sum('amount') / $chequeRows->sum('amount') * 100) : 0; @endphp
                        <div class="col-md-6 mb-2">
                            <div class="bg-white border rounded p-3">
                                <div class="d-flex justify-content-between"><b>{{ $label }}</b><strong>Rs {{ number_format($group->sum('amount'),2) }}</strong></div>
                                <div class="mt-2" style="height:10px;background:#e2e8f0;border-radius:999px;overflow:hidden"><div style="height:100%;width:{{ $pct }}%;background:linear-gradient(90deg,#16a34a,#0ea5e9)"></div></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="modal-table-wrap">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Cheque</th><th>Party / Bank</th><th>Amount</th><th>Settlement</th><th>Age / Validity</th><th>Clearance</th><th>Status</th><th>Bills</th><th>Action</th></tr></thead>
                        <tbody>
                        @forelse($chequeRows as $row)
                            <tr>
                                <td><b>{{ $row['cheque_no'] }}</b><br><small>{{ $row['book'] }}</small></td>
                                <td><b>{{ $row['party'] }}</b><br><small>{{ $row['bank_name'] ?: $row['bank'] }} | A/C {{ $row['account_number'] ?: '-' }} | IFSC {{ $row['ifsc_code'] ?: '-' }}</small></td>
                                <td>Rs {{ number_format($row['amount'],2) }}</td>
                                <td>Paid Rs {{ number_format($row['settled'],2) }}<br><b class="{{ $row['due'] > 0 ? 'text-danger' : 'text-success' }}">Due Rs {{ number_format($row['due'],2) }}</b></td>
                                <td>{{ $row['age'] }} days old<br><small>{{ $row['validity'] }} month validity</small></td>
                                <td>{{ $row['clearance_date'] ?: '-' }}<br><small>{{ $row['clearance_day'] ?: '-' }} | {{ $row['days_left'] === null ? '-' : $row['days_left'].' days left' }}</small></td>
                                <td><span class="badge badge-{{ $row['status_raw'] === 'completed' ? 'success' : ($row['status_raw'] === 'payment_posted' ? 'info' : 'warning') }}">{{ $row['status'] }}</span></td>
                                <td>@forelse($row['bills'] as $bill)<div>{{ $bill['bill'] }}: Rs {{ number_format($bill['amount'],2) }}</div>@empty<span class="text-muted">Not settled</span>@endforelse</td>
                                <td class="text-nowrap">
                                    @can('banking.manage')
                                    <form method="POST" action="{{ $row['status_url'] }}" class="d-inline-flex align-items-center mb-1">
                                        @csrf
                                        <select name="status" class="form-control  mr-1 p-2" style="width:140px">
                                            <option value="issued" @selected($row['status_raw'] === 'issued')>Issued</option>
                                            <option value="payment_posted" @selected($row['status_raw'] === 'payment_posted')>Payment Posted</option>
                                            <option value="completed" @selected($row['status_raw'] === 'completed')>Completed</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-success" title="Update status"><i class="fas fa-sync-alt"></i></button>
                                    </form>
                                    @endcan
                                    <button type="button" class="btn btn-sm btn-outline-primary cheque-detail-btn" data-url="{{ $row['details_url'] }}"><i class="fas fa-eye mr-1"></i>View Details</button>
                                    <a class="btn btn-sm btn-outline-danger mt-1" target="_blank" href="{{ $row['print_url'] }}"><i class="fas fa-file-pdf mr-1"></i>PDF</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">No cheque data for this filter.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="text-right mt-3"><a class="btn btn-outline-primary" href="{{ route('admin.cheques.report', ['from_date' => $from, 'to_date' => $to]) }}"><i class="fas fa-external-link-alt mr-1"></i>Full Cheque Report</a></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade pro-modal" id="completedChequeModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div><h5 class="modal-title mb-0">Completed Cheques</h5><small>Completed cheque list for {{ $from }} to {{ $to }}</small></div>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-4"><div class="modal-metric"><span>Completed Amount</span><b>Rs {{ number_format($completedChequeRows->sum('amount'),2) }}</b></div></div>
                    <div class="col-md-4"><div class="modal-metric"><span>Cheques</span><b>{{ number_format($completedChequeRows->count()) }}</b></div></div>
                    <div class="col-md-4"><div class="modal-metric"><span>Settled Bills</span><b>{{ number_format($completedChequeRows->sum(fn($row) => $row['bills']->count())) }}</b></div></div>
                </div>
                <div class="modal-table-wrap">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Cheque</th><th>Party / Bank</th><th>Amount</th><th>Clearance</th><th>Bills</th><th>Action</th></tr></thead>
                        <tbody>
                        @forelse($completedChequeRows as $row)
                            <tr>
                                <td><b>{{ $row['cheque_no'] }}</b><br><span class="badge badge-success">{{ $row['status'] }}</span></td>
                                <td><b>{{ $row['party'] }}</b><br><small>{{ $row['bank_name'] ?: $row['bank'] }} | A/C {{ $row['account_number'] ?: '-' }}</small></td>
                                <td>Rs {{ number_format($row['amount'],2) }}</td>
                                <td>{{ $row['clearance_date'] ?: '-' }}<br><small>{{ $row['clearance_day'] ?: '-' }}</small></td>
                                <td>@forelse($row['bills'] as $bill)<div>{{ $bill['bill'] }}: Rs {{ number_format($bill['amount'],2) }}</div>@empty<span class="text-muted">Not settled</span>@endforelse</td>
                                <td class="text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary cheque-detail-btn" data-url="{{ $row['details_url'] }}"><i class="fas fa-eye mr-1"></i>View Details</button>
                                    <a class="btn btn-sm btn-outline-danger mt-1" target="_blank" href="{{ $row['print_url'] }}"><i class="fas fa-file-pdf mr-1"></i>PDF</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No completed cheques for this filter.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade pro-modal" id="chequeDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div><h5 class="modal-title mb-0" id="chequeDetailTitle">Cheque Details</h5><small id="chequeDetailSub">Party, invoice, item and cheque image details</small></div>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="chequeDetailBody">
                <div class="text-muted p-4">Loading cheque details...</div>
            </div>
            <div class="modal-footer justify-content-between">
                <small class="text-muted">Printable PDF includes the cheque image and settlement disclaimer.</small>
                <a id="chequeDetailPdf" class="btn btn-danger" target="_blank" href="#"><i class="fas fa-file-pdf mr-1"></i>Download / Print PDF</a>
            </div>
        </div>
    </div>
</div>

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

<div class="modal fade pro-modal" id="collectionModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div><h5 class="modal-title mb-0">Total Collection</h5><small>Payment In collection for {{ $from }} to {{ $to }}</small></div>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-3"><select id="collectionParty" class="form-control"><option value="">All Parties</option></select></div>
                    <div class="col-md-2"><select id="collectionState" class="form-control"><option value="">All States</option></select></div>
                    <div class="col-md-2"><select id="collectionDistrict" class="form-control"><option value="">All Districts</option></select></div>
                    <div class="col-md-2"><select id="collectionCity" class="form-control"><option value="">All Cities</option></select></div>
                    <div class="col-md-3 d-flex" style="gap:8px"><input type="date" id="collectionFrom" class="form-control" value="{{ $from }}"><input type="date" id="collectionTo" class="form-control" value="{{ $to }}"></div>
                </div>
                <div class="modal-metric mb-3"><span>Filtered Collection</span><b id="collectionFilteredTotal">Rs 0.00</b></div>
                <div class="modal-table-wrap">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Party</th><th>Payment Date</th><th>Reference</th><th>Amount</th><th>State</th><th>District</th><th>City</th></tr></thead>
                        <tbody id="collectionRows"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const collectionData = @json($collectionRows ?? []);
function dashMoney(n){return 'Rs '+(Number(n)||0).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2})}
function fillCollectionOptions(){
    const fields = [['collectionParty','party'],['collectionState','state'],['collectionDistrict','district'],['collectionCity','city']];
    fields.forEach(([id,key]) => {
        const select = document.getElementById(id);
        if(!select || select.options.length > 1) return;
        [...new Set(collectionData.map(row => row[key]).filter(Boolean))].sort().forEach(value => {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = value;
            select.appendChild(option);
        });
    });
}
function renderCollectionRows(){
    const party = $('#collectionParty').val(), state = $('#collectionState').val(), district = $('#collectionDistrict').val(), city = $('#collectionCity').val();
    const from = $('#collectionFrom').val(), to = $('#collectionTo').val();
    const rows = collectionData.filter(row =>
        (!party || row.party === party) &&
        (!state || row.state === state) &&
        (!district || row.district === district) &&
        (!city || row.city === city) &&
        (!from || row.date >= from) &&
        (!to || row.date <= to)
    );
    $('#collectionFilteredTotal').text(dashMoney(rows.reduce((sum,row)=>sum + Number(row.amount || 0), 0)));
    $('#collectionRows').html(rows.length ? rows.map(row => `<tr><td>${row.party || '-'}</td><td>${row.date_label || '-'}</td><td>${row.reference_no || '-'}</td><td>${dashMoney(row.amount)}</td><td>${row.state || '-'}</td><td>${row.district || '-'}</td><td>${row.city || '-'}</td></tr>`).join('') : '<tr><td colspan="7" class="text-center text-muted py-4">No Payment In records for selected filters.</td></tr>');
}
$('#collectionModal').on('shown.bs.modal', function(){fillCollectionOptions();renderCollectionRows();});
$('#collectionParty,#collectionState,#collectionDistrict,#collectionCity,#collectionFrom,#collectionTo').on('change input', renderCollectionRows);
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
$('#openQuickDrawer').on('click',function(){
    $('#quickDrawer,#quickDrawerBackdrop').addClass('open');
    $('#quickDrawer').attr('aria-hidden','false');
});
$('#closeQuickDrawer,#quickDrawerBackdrop').on('click',function(){
    $('#quickDrawer,#quickDrawerBackdrop').removeClass('open');
    $('#quickDrawer').attr('aria-hidden','true');
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
function chequeMoney(n){return dashMoney(n)}
function chequeText(v){return v || '-'}
$(document).on('click','.cheque-detail-btn',async function(){
    const url = $(this).data('url');
    $('#chequeDetailBody').html('<div class="text-muted p-4">Loading cheque details...</div>');
    $('#chequeDetailModal').modal('show');
    const res = await fetch(url,{headers:{Accept:'application/json'}});
    const data = res.ok ? await res.json() : null;
    if(!data){
        $('#chequeDetailBody').html('<div class="alert alert-danger">Cheque details load nahi ho paya.</div>');
        return;
    }
    $('#chequeDetailTitle').text('Cheque '+data.cheque.cheque_no);
    $('#chequeDetailSub').text(`${chequeText(data.party.name)} | Settlement ${chequeText(data.cheque.settlement_date)} | Clearance ${chequeText(data.cheque.clearance_date)}`);
    $('#chequeDetailPdf').attr('href', data.print_url);
    const bills = (data.bills || []).map(bill => `
        <div class="bg-white border rounded p-3 mb-3">
            <div class="d-flex justify-content-between flex-wrap mb-2">
                <div><b>${chequeText(bill.bill_no)}</b><br><small>${chequeText(bill.bill_type)} | ${chequeText(bill.bill_date)}</small></div>
                <div class="text-right"><b>${chequeMoney(bill.settled_amount)}</b><br><small>Bill total ${chequeMoney(bill.bill_total)}</small></div>
            </div>
            <div class="row mb-2">
                <div class="col-md-3"><div class="modal-metric"><span>Invoice Total</span><b>${chequeMoney(bill.invoice?.grand_total)}</b></div></div>
                <div class="col-md-3"><div class="modal-metric"><span>Discount</span><b>${chequeMoney(bill.invoice?.discount_amount)}</b></div></div>
                <div class="col-md-3"><div class="modal-metric"><span>Tax</span><b>${chequeMoney(bill.invoice?.tax_amount)}</b></div></div>
                <div class="col-md-3"><div class="modal-metric"><span>Reference</span><b>${chequeText(bill.invoice?.reference_no)}</b></div></div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Item</th><th>Qty</th><th>Rate</th><th>Discount</th><th>Tax</th><th>Total</th></tr></thead>
                    <tbody>${(bill.items || []).map(item => `<tr><td><b>${chequeText(item.name)}</b><br><small>${chequeText(item.description)}</small></td><td>${Number(item.quantity||0).toFixed(2)} ${chequeText(item.unit)}</td><td>${chequeMoney(item.rate)}</td><td>${chequeMoney(item.discount_amount)}<br><small>${chequeText(item.discount_type)} ${Number(item.discount_value||0).toFixed(2)}</small></td><td>${Number(item.tax_percent||0).toFixed(2)}%<br><small>${chequeMoney(item.tax_amount)}</small></td><td><b>${chequeMoney(item.line_total)}</b></td></tr>`).join('') || '<tr><td colspan="6" class="text-muted">No item details found.</td></tr>'}</tbody>
                </table>
            </div>
        </div>`).join('') || '<div class="text-muted">No bill settlement found for this cheque.</div>';
    $('#chequeDetailBody').html(`
        <div class="row mb-3">
            <div class="col-md-3"><div class="modal-metric"><span>Cheque Amount</span><b>${chequeMoney(data.cheque.amount)}</b></div></div>
            <div class="col-md-3"><div class="modal-metric"><span>Settled</span><b>${chequeMoney(data.totals.settled)}</b></div></div>
            <div class="col-md-3"><div class="modal-metric"><span>Due</span><b>${chequeMoney(data.totals.due)}</b></div></div>
            <div class="col-md-3"><div class="modal-metric"><span>Clearance</span><b>${chequeText(data.cheque.clearance_day)}</b></div></div>
        </div>
        <div class="row mb-3">
            <div class="col-lg-5 mb-3">
                <div class="bg-white border rounded p-3">
                    <h6 class="font-weight-bold">Cheque Image</h6>
                    <div class="cheque-preview-wrap"><div class="cheque-preview">${data.cheque.is_completed ? '<div class="cheque-complete-stamp">COMPLETED</div>' : ''}<div class="bank-row"><div class="bank-mark"><div class="seal">${chequeText(data.bank.name).slice(0,2).toUpperCase()}</div><div><div class="bank-name">${chequeText(data.bank.name)}</div><div class="bank-sub">A/C ${chequeText(data.bank.account_number)} | IFSC ${chequeText(data.bank.ifsc_code)}</div></div></div><div class="cheque-no"><div class="lbl">Cheque</div><div class="val">${data.cheque.cheque_no}</div></div></div><div class="row-date"><label>Date</label><div class="line" style="width:150px;text-align:right">${chequeText(data.cheque.issue_date)}</div></div><div class="row-pay"><label>Pay to the order of</label><div class="line">${chequeText(data.cheque.payee_name || data.party.name)}</div><div class="amount-box">${chequeMoney(data.cheque.amount)}</div></div><div class="row-words"><label>Rupees</label><div class="line">${chequeText(data.cheque.amount_words)}</div><span class="bank-sub">only</span></div><div class="row-bottom"><div class="memo-block"><div class="line">${chequeText(data.cheque.memo)}</div><label>Memo</label></div><div class="sig-block"><div class="line">&nbsp;</div><label>Authorised signature</label></div></div><div class="micr"><span>${data.cheque.cheque_no}</span><span>${chequeText(data.bank.ifsc_code)}</span><span>${chequeText(data.bank.account_number)}</span></div></div></div>
                </div>
            </div>
            <div class="col-lg-7 mb-3">
                <div class="bg-white border rounded p-3 h-100">
                    <h6 class="font-weight-bold">Party and Bank Details</h6>
                    <div class="row">
                        <div class="col-md-6"><b>${chequeText(data.party.name)}</b><br><small>${chequeText(data.party.legal_name)}<br>${chequeText(data.party.phone)} | ${chequeText(data.party.email)}<br>GSTIN ${chequeText(data.party.gstin)} | PAN ${chequeText(data.party.pan)}<br>${chequeText(data.party.billing_address)}</small></div>
                        <div class="col-md-6"><b>${chequeText(data.bank.name)}</b><br><small>${chequeText(data.bank.account_name)}<br>A/C ${chequeText(data.bank.account_number)}<br>IFSC ${chequeText(data.bank.ifsc_code)}<br>Branch ${chequeText(data.bank.branch_name)}</small></div>
                    </div>
                    <hr>
                    <div>Age: <b>${data.cheque.age} days</b> | Validity: <b>${data.cheque.validity_months} month</b> | Clearance: <b>${chequeText(data.cheque.clearance_date)} (${chequeText(data.cheque.clearance_day)})</b> | Days left: <b>${data.cheque.days_left ?? '-'}</b></div>
                </div>
            </div>
        </div>
        <h6 class="font-weight-bold">Invoice / Bill Details</h6>
        ${bills}
    `);
});
</script>
@include('admin.partials.segment-viz-scripts')
@endpush
@endsection
