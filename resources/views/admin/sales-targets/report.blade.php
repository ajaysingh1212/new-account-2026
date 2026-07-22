@extends('layouts.admin')
@section('title','Sales Target Intelligence')
@section('content')
<style>
:root{
    --stx-violet:#7C3AED;
    --stx-indigo:#6366F1;
    --stx-teal:#0f766e;
    --stx-mint:#10b981;
    --stx-amber:#f59e0b;
    --stx-rose:#ef4444;
    --stx-ink:#0f172a;
    --stx-muted:#64748b;
    --stx-card-radius:20px;
}
#stx-wrap{font-family:'Inter','Outfit',sans-serif;color:var(--stx-ink)}
#stx-wrap *{box-sizing:border-box}

/* ---------- HERO ---------- */
.stx-hero{
    position:relative;overflow:hidden;border-radius:26px;padding:36px 32px;
    background:linear-gradient(135deg,var(--stx-violet) 0%,var(--stx-indigo) 55%,var(--stx-teal) 100%);
    color:#fff;box-shadow:0 24px 60px rgba(99,102,241,.28);margin-bottom:22px;
    animation:stxFadeUp .6s ease both;
}
.stx-hero .stx-blob{position:absolute;border-radius:50%;filter:blur(4px);opacity:.18;background:#fff;animation:stxFloat 9s ease-in-out infinite}
.stx-hero .stx-blob-1{width:220px;height:220px;top:-70px;right:-40px;animation-delay:0s}
.stx-hero .stx-blob-2{width:140px;height:140px;bottom:-50px;right:180px;animation-delay:2s}
.stx-hero .stx-blob-3{width:90px;height:90px;top:40px;right:260px;animation-delay:4s}
@keyframes stxFloat{0%,100%{transform:translateY(0) translateX(0)}50%{transform:translateY(-18px) translateX(10px)}}
.stx-hero-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.16);backdrop-filter:blur(6px);
    border:1px solid rgba(255,255,255,.28);padding:7px 16px;border-radius:999px;font-size:12px;font-weight:700;letter-spacing:.06em}
.stx-hero-badge .stx-dot{width:7px;height:7px;border-radius:50%;background:#5eead4;animation:stxPulse 1.6s infinite}
@keyframes stxPulse{0%{box-shadow:0 0 0 0 rgba(94,234,212,.6)}70%{box-shadow:0 0 0 8px rgba(94,234,212,0)}100%{box-shadow:0 0 0 0 rgba(94,234,212,0)}}
.stx-hero h1{font-family:'Outfit',sans-serif;font-weight:800;font-size:32px;margin:14px 0 6px}
.stx-target-orb{width:78px;height:78px;border-radius:22px;background:rgba(255,255,255,.14);backdrop-filter:blur(6px);
    border:1px solid rgba(255,255,255,.25);display:grid;place-items:center;font-size:32px;position:relative;z-index:2;
    animation:stxSpinSlow 12s linear infinite}
@keyframes stxSpinSlow{from{transform:rotate(0)}to{transform:rotate(360deg)}}

/* ---------- FILTER PANEL ---------- */
.stx-filter-card{border:0;border-radius:var(--stx-card-radius);box-shadow:0 10px 30px rgba(15,23,42,.06);margin-bottom:22px;
    animation:stxFadeUp .6s ease .08s both}
.stx-pill{border:1.5px solid #e5e7eb;border-radius:999px;padding:8px 16px;margin:3px;color:var(--stx-muted);background:#fff;
    text-decoration:none;display:inline-block;font-weight:600;font-size:13px;transition:all .25s ease}
.stx-pill:hover{border-color:var(--stx-violet);color:var(--stx-violet);transform:translateY(-1px)}
.stx-pill.active{background:linear-gradient(135deg,var(--stx-violet),var(--stx-indigo));color:#fff;border-color:transparent;
    box-shadow:0 8px 18px rgba(124,58,237,.28)}
.stx-input-label{font-size:11px;font-weight:700;letter-spacing:.05em;color:var(--stx-muted);text-transform:uppercase;margin-bottom:6px}
#stx-wrap .form-control{border-radius:12px;border:1.5px solid #e5e7eb;padding:10px 14px;transition:.2s}
#stx-wrap .form-control:focus{border-color:var(--stx-violet);box-shadow:0 0 0 3px rgba(124,58,237,.12)}
.stx-btn-grad{background:linear-gradient(135deg,var(--stx-violet),var(--stx-indigo));border:none;color:#fff;border-radius:12px;
    padding:10px 22px;font-weight:700;box-shadow:0 10px 22px rgba(124,58,237,.25);transition:transform .2s}
.stx-btn-grad:hover{transform:translateY(-2px);color:#fff}
.stx-btn-soft{border-radius:12px;font-weight:700;padding:10px 18px;transition:transform .2s}
.stx-btn-soft:hover{transform:translateY(-2px)}

/* ---------- KPI CARDS ---------- */
.stx-kpi{border:0;border-radius:var(--stx-card-radius);box-shadow:0 10px 26px rgba(15,23,42,.06);overflow:hidden;position:relative;
    animation:stxFadeUp .6s ease both}
.stx-kpi:nth-child(1){animation-delay:.12s}.stx-kpi:nth-child(2){animation-delay:.18s}
.stx-kpi:nth-child(3){animation-delay:.24s}.stx-kpi:nth-child(4){animation-delay:.30s}
.stx-kpi .card-body{padding:22px}
.stx-kpi-icon{width:50px;height:50px;border-radius:16px;display:grid;place-items:center;font-size:20px;flex-shrink:0}
.stx-kpi-label{font-size:11px;font-weight:700;letter-spacing:.06em;color:var(--stx-muted);text-transform:uppercase}
.stx-kpi-value{font-family:'Outfit',sans-serif;font-weight:800;font-size:26px;margin-top:4px}
.stx-kpi-bar{height:4px;width:100%;background:#f1f5f9;border-radius:4px;margin-top:14px;overflow:hidden}
.stx-kpi-bar span{display:block;height:100%;border-radius:4px;width:0;transition:width 1.1s cubic-bezier(.2,.8,.2,1)}

/* achievement ring */
.stx-ring-wrap{position:relative;width:56px;height:56px}
.stx-ring-wrap svg{transform:rotate(-90deg)}
.stx-ring-bg{stroke:#f1f5f9}
.stx-ring-fg{stroke:var(--stx-violet);stroke-linecap:round;transition:stroke-dashoffset 1.2s cubic-bezier(.2,.8,.2,1)}
.stx-ring-txt{position:absolute;inset:0;display:grid;place-items:center;font-size:11px;font-weight:800}

/* ---------- CHART SEGMENT TABS ---------- */
.stx-chart-card{border:0;border-radius:var(--stx-card-radius);box-shadow:0 10px 30px rgba(15,23,42,.06);
    animation:stxFadeUp .6s ease .34s both}
.stx-tabbar{display:inline-flex;background:#f1f5f9;border-radius:14px;padding:5px;gap:2px;flex-wrap:wrap}
.stx-tab{border:0;background:transparent;color:var(--stx-muted);padding:9px 16px;border-radius:11px;font-weight:700;
    font-size:13px;display:inline-flex;align-items:center;gap:7px;transition:all .25s ease}
.stx-tab:hover{color:var(--stx-violet)}
.stx-tab.active{background:#fff;color:var(--stx-violet);box-shadow:0 4px 12px rgba(15,23,42,.08)}
.stx-pane{display:none;animation:stxFadeIn .35s ease both}
.stx-pane.active{display:block}
@keyframes stxFadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
@keyframes stxFadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}

/* ---------- TABLE ---------- */
.stx-table thead th{border:0;background:#f8fafc;color:var(--stx-muted);font-size:11px;text-transform:uppercase;
    letter-spacing:.05em;font-weight:700;padding:14px 16px}
.stx-table tbody tr{transition:background .2s}
.stx-table tbody tr:hover{background:#f5f3ff}
.stx-table td{padding:14px 16px;vertical-align:middle;border-top:1px solid #f1f5f9}
.stx-cat-chip{display:inline-flex;align-items:center;gap:8px;font-weight:700}
.stx-cat-dot{width:9px;height:9px;border-radius:50%}
.stx-badge-ach{border-radius:999px;padding:5px 12px;font-weight:800;font-size:12px}
.stx-badge-ach.good{background:#dcfce7;color:#15803d}
.stx-badge-ach.warn{background:#fef3c7;color:#b45309}
.stx-mini-bar{height:6px;width:70px;background:#f1f5f9;border-radius:4px;display:inline-block;overflow:hidden;vertical-align:middle;margin-left:8px}
.stx-mini-bar span{display:block;height:100%;border-radius:4px}

/* empty state */
.stx-empty{text-align:center;padding:60px 20px;color:var(--stx-muted)}
.stx-empty .stx-empty-emoji{font-size:48px;margin-bottom:12px;display:block;animation:stxFloat 3s ease-in-out infinite}

@media(max-width:768px){.stx-hero h1{font-size:24px}.stx-target-orb{display:none}}

/* ---------- CHART SIZE FIX + 3D PIE ---------- */
.stx-chart-box{position:relative;height:300px;width:100%}
.stx-pie-wrap{display:flex;gap:28px;align-items:center;flex-wrap:wrap}
.stx-pie-canvas-col{position:relative;height:250px;width:250px;flex:0 0 250px;
    filter:drop-shadow(0 16px 22px rgba(124,58,237,.30)) drop-shadow(0 2px 6px rgba(15,23,42,.15))}
.stx-pie-legend{flex:1;min-width:230px;max-height:270px;overflow-y:auto;padding-right:6px}
.stx-pie-legend-item{display:flex;align-items:center;justify-content:space-between;padding:9px 2px;
    border-bottom:1px solid #f1f5f9;font-size:13px}
.stx-pie-legend-item:last-child{border-bottom:0}
.stx-pie-legend-left{display:flex;align-items:center;gap:10px;font-weight:700}
.stx-pie-dot{width:11px;height:11px;border-radius:50%;flex-shrink:0;box-shadow:0 0 0 3px rgba(0,0,0,.03)}
.stx-pie-legend-pct{font-weight:800;color:var(--stx-violet)}
.stx-pie-center-label{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none}
.stx-pie-center-label b{font-family:'Outfit',sans-serif;font-size:22px;color:var(--stx-ink);line-height:1.1}
.stx-pie-center-label span{font-size:10px;color:var(--stx-muted);text-transform:uppercase;letter-spacing:.05em;text-align:center;max-width:110px}
@media(max-width:576px){.stx-pie-canvas-col{width:210px;height:210px;flex:0 0 210px}}
</style>

<div id="stx-wrap">

    {{-- ===================== HERO ===================== --}}
    <div class="stx-hero">
        <div class="stx-blob stx-blob-1"></div>
        <div class="stx-blob stx-blob-2"></div>
        <div class="stx-blob stx-blob-3"></div>
        <div class="d-flex justify-content-between align-items-start" style="position:relative;z-index:2">
            <div>
                <span class="stx-hero-badge"><span class="stx-dot"></span> SALES TARGET INTELLIGENCE</span>
                <h1>🎯 Target vs Actual — Ek nazar mein sab kuch</h1>
                <p class="mb-0" style="opacity:.85">
                    {{ date('d M Y', strtotime($filters['from'])) }} &mdash; {{ date('d M Y', strtotime($filters['to'])) }}
                    &nbsp;·&nbsp; Category performance ka poora overview
                </p>
            </div>
            <div class="stx-target-orb">🎯</div>
        </div>
    </div>

    {{-- ===================== FILTERS ===================== --}}
    <div class="card stx-filter-card">
        <div class="card-body p-4">
            <div class="mb-3">
                <strong class="mr-2" style="color:var(--stx-muted);font-size:13px">⚡ Quick view:</strong>
                @foreach(['last_month'=>'Last Month','this_month'=>'This Month','last_3_months'=>'Last 3 Months','last_6_months'=>'Last 6 Months','last_9_months'=>'Last 9 Months','this_year'=>'This Year'] as $key=>$label)
                    <a class="stx-pill {{ $filters['quick_period']===$key?'active':'' }}"
                       href="{{ route('admin.sales-targets.report',array_merge(request()->query(),['quick_period'=>$key])) }}">{{ $label }}</a>
                @endforeach
                <a class="stx-pill" href="{{ route('admin.sales-targets.report') }}">🗓 Custom</a>
            </div>
            <form id="stxFilterForm">
                <div class="row align-items-end">
                    <div class="col-md-3 form-group mb-md-0">
                        <div class="stx-input-label">From</div>
                        <input type="date" name="from_date" class="form-control" value="{{ $filters['from'] }}">
                    </div>
                    <div class="col-md-3 form-group mb-md-0">
                        <div class="stx-input-label">To</div>
                        <input type="date" name="to_date" class="form-control" value="{{ $filters['to'] }}">
                    </div>
                    <div class="col-md-3 form-group mb-md-0">
                        <div class="stx-input-label">Party</div>
                        <select name="party_id" class="form-control select2">
                            <option value="">All Parties</option>
                            @foreach($parties as $party)
                                <option value="{{ $party->id }}" @selected($filters['party_id']==$party->id)>{{ $party->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group mb-md-0">
                        <div class="stx-input-label">Product Category</div>
                        <select name="product_category_id" class="form-control select2">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected($filters['product_category_id']==$category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="stx-btn-grad"><i class="fas fa-filter mr-1"></i> Filter Apply Karein</button>
                    <a class="btn btn-outline-success stx-btn-soft ml-2" id="stxExportExcel"
                       href="{{ route('admin.sales-targets.report.export',request()->query()) }}">
                        <i class="fas fa-file-excel mr-1"></i> Excel</a>
                    <a class="btn btn-outline-secondary stx-btn-soft ml-2" id="stxExportPdf" target="_blank"
                       href="{{ route('admin.sales-targets.report.print',request()->query()) }}">
                        <i class="fas fa-file-pdf mr-1"></i> PDF</a>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== KPI CARDS ===================== --}}
    <div class="row">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stx-kpi">
                <div class="card-body d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stx-kpi-label">Total Target</div>
                        <div class="stx-kpi-value stx-count" data-target="{{ $summary['target'] }}">0</div>
                        <div class="stx-kpi-bar"><span style="background:linear-gradient(90deg,var(--stx-violet),var(--stx-indigo));width:100%"></span></div>
                    </div>
                    <span class="stx-kpi-icon" style="background:#ede9fe;color:var(--stx-violet)">🎯</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stx-kpi">
                <div class="card-body d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stx-kpi-label">Actual Amount</div>
                        <div class="stx-kpi-value">₹<span class="stx-count" data-target="{{ $summary['amount'] }}">0</span></div>
                        <div class="stx-kpi-bar"><span style="background:linear-gradient(90deg,#10b981,#5eead4);width:100%"></span></div>
                    </div>
                    <span class="stx-kpi-icon" style="background:#d1fae5;color:var(--stx-mint)">💰</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stx-kpi">
                <div class="card-body d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stx-kpi-label">Actual Quantity</div>
                        <div class="stx-kpi-value stx-count" data-target="{{ $summary['quantity'] }}">0</div>
                        <div class="stx-kpi-bar"><span style="background:linear-gradient(90deg,#0ea5e9,#7dd3fc);width:100%"></span></div>
                    </div>
                    <span class="stx-kpi-icon" style="background:#e0f2fe;color:#0284c7">📦</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stx-kpi">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stx-kpi-label">Avg Achievement</div>
                        <div class="stx-kpi-value">{{ number_format($rows->avg('achievement'),1) }}%</div>
                        <small style="color:var(--stx-muted)">Poore records ka average</small>
                    </div>
                    <div class="stx-ring-wrap">
                        <svg width="56" height="56" viewBox="0 0 56 56">
                            <circle class="stx-ring-bg" cx="28" cy="28" r="24" fill="none" stroke-width="6"></circle>
                            <circle class="stx-ring-fg" id="stxAchRing" cx="28" cy="28" r="24" fill="none" stroke-width="6"
                                    stroke-dasharray="150.8" stroke-dashoffset="150.8"
                                    data-pct="{{ number_format($rows->avg('achievement'),1) }}"></circle>
                        </svg>
                        <div class="stx-ring-txt">🔥</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== CHARTS + TABLE ===================== --}}
    <div class="card stx-chart-card">
        <div class="card-header bg-white border-0 pt-3 pb-2">
            <div class="stx-tabbar">
                <button class="stx-tab active" data-pane="stxPiePane">🍩 Pie</button>
                <button class="stx-tab" data-pane="stxCandlePane">📊 Bars</button>
                <button class="stx-tab" data-pane="stxWavePane">🌊 Wave</button>
                <button class="stx-tab" data-pane="stxRadarPane">🕸 Radar</button>
                <button class="stx-tab" data-pane="stxContentPane">📋 Table</button>
            </div>
        </div>
        <div class="card-body">
            <div id="stxPiePane" class="stx-pane active">
                <div class="stx-pie-wrap">
                    <div class="stx-pie-canvas-col">
                        <canvas id="stxPieChart"></canvas>
                        <div class="stx-pie-center-label"><b id="stxPieCenterVal">0%</b><span id="stxPieCenterLabel">Top Category</span></div>
                    </div>
                    <div class="stx-pie-legend" id="stxPieLegend"></div>
                </div>
            </div>
            <div id="stxCandlePane" class="stx-pane"><div class="stx-chart-box"><canvas id="stxCandleChart"></canvas></div></div>
            <div id="stxWavePane" class="stx-pane"><div class="stx-chart-box"><canvas id="stxWaveChart"></canvas></div></div>
            <div id="stxRadarPane" class="stx-pane"><div class="stx-chart-box"><canvas id="stxRadarChart"></canvas></div></div>
            <div id="stxContentPane" class="stx-pane">
                @if($rows->count())
                <div class="table-responsive">
                    <table class="table stx-table" id="stxReportTable">
                        <thead>
                            <tr>
                                <th>Party</th><th>Category</th><th>Period</th><th>Target</th>
                                <th>Actual</th><th>Achievement</th><th>Amount</th><th>Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $stxPalette=['#7C3AED','#0ea5e9','#f59e0b','#10b981','#ef4444','#ec4899','#2563eb']; @endphp
                            @foreach($rows as $i => $row)
                            <tr>
                                <td><strong>{{ $row['party'] }}</strong></td>
                                <td>
                                    <span class="stx-cat-chip">
                                        <span class="stx-cat-dot" style="background:{{ $stxPalette[$i % count($stxPalette)] }}"></span>
                                        {{ $row['category'] }}
                                    </span>
                                </td>
                                <td>{{ $row['period'] }}<br><small class="text-muted">{{ $row['starts_on'] }} - {{ $row['ends_on'] }}</small></td>
                                <td>{{ number_format($row['target'],2) }} {{ $row['target_type'] }}</td>
                                <td>{{ number_format($row['actual'],2) }}</td>
                                <td>
                                    <span class="stx-badge-ach {{ $row['achievement'] >= 100 ? 'good':'warn' }}">
                                        {{ number_format($row['achievement'],1) }}%
                                    </span>
                                    <span class="stx-mini-bar">
                                        <span style="width:{{ min($row['achievement'],100) }}%;background:{{ $row['achievement']>=100?'#10b981':'#f59e0b' }}"></span>
                                    </span>
                                </td>
                                <td>₹ {{ number_format($row['actual_amount'],2) }}</td>
                                <td>{{ number_format($row['actual_quantity'],2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="stx-empty">
                    <span class="stx-empty-emoji">🔍</span>
                    <h5>Koi target nahi mila</h5>
                    <p class="mb-0">Apne filters change karke phir try karein.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
$(function () {
    const c = @json($charts);
    const palette = ['#7C3AED','#0ea5e9','#f59e0b','#10b981','#ef4444','#ec4899','#2563eb'];
    const paletteLight = ['#a78bfa','#7dd3fc','#fcd34d','#6ee7b7','#fca5a5','#f9a8d4','#93c5fd'];
    const paletteDark  = ['#5b21b6','#0369a1','#b45309','#047857','#b91c1c','#be185d','#1d4ed8'];
    const common = { responsive:true, maintainAspectRatio:false, animation:{ duration:1400, easing:'easeOutQuart' },
        plugins:{ tooltip:{ enabled:true, displayColors:true } } };

    // ---- 3D-style Pie (doughnut with gradient slices + drop shadow via CSS) ----
    new Chart($('#stxPieChart'), { type:'doughnut',
        data:{ labels:c.labels, datasets:[{
            data:c.actual,
            backgroundColor: function (context) {
                const { chart, dataIndex } = context;
                const { ctx, chartArea } = chart;
                if (!chartArea) return palette[dataIndex % palette.length];
                const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                gradient.addColorStop(0, paletteLight[dataIndex % paletteLight.length]);
                gradient.addColorStop(1, paletteDark[dataIndex % paletteDark.length]);
                return gradient;
            },
            borderWidth: 3, borderColor: '#fff', hoverOffset: 16, borderRadius: 6, spacing: 3
        }] },
        options: { ...common, cutout: '66%', rotation: -15,
            plugins: { tooltip: { enabled: true, displayColors: true }, legend: { display: false } } } });

    // ---- Custom legend beside the pie: category-wise target share % ----
    (function () {
        const totalTarget = c.target.reduce((sum, v) => sum + (parseFloat(v) || 0), 0) || 1;
        let topIdx = 0, topShare = -1, legendHtml = '';
        c.labels.forEach((label, i) => {
            const share = ((parseFloat(c.target[i]) || 0) / totalTarget) * 100;
            if (share > topShare) { topShare = share; topIdx = i; }
            legendHtml += `<div class="stx-pie-legend-item">
                <span class="stx-pie-legend-left">
                    <span class="stx-pie-dot" style="background:${paletteDark[i % paletteDark.length]}"></span>${label}
                </span>
                <span class="stx-pie-legend-pct">${share.toFixed(1)}%</span>
            </div>`;
        });
        $('#stxPieLegend').html(legendHtml || '<div class="text-muted">Koi data nahi mila</div>');
        $('#stxPieCenterVal').text(topShare >= 0 ? topShare.toFixed(0) + '%' : '0%');
        $('#stxPieCenterLabel').text(c.labels[topIdx] ? (c.labels[topIdx] + ' target share') : 'Top Category');
    })();

    new Chart($('#stxCandleChart'), { type:'bar',
        data:{ labels:c.labels, datasets:[
            { label:'Target', data:c.target, backgroundColor:'#ddd6fe', borderColor:'#7C3AED', borderWidth:2, borderRadius:8 },
            { label:'Actual', data:c.actual, backgroundColor:'#5eead4', borderColor:'#0f766e', borderWidth:2, borderRadius:8 }
        ] }, options:{ ...common, scales:{ y:{ beginAtZero:true } } } });

    new Chart($('#stxWaveChart'), { type:'line',
        data:{ labels:c.labels, datasets:[
            { label:'Target wave', data:c.target, borderColor:'#7C3AED', backgroundColor:'rgba(124,58,237,.12)', fill:true, tension:.45 },
            { label:'Actual wave', data:c.actual, borderColor:'#0f766e', backgroundColor:'rgba(15,118,110,.12)', fill:true, tension:.45 }
        ] }, options:{ ...common, scales:{ y:{ beginAtZero:true } } } });

    new Chart($('#stxRadarChart'), { type:'radar',
        data:{ labels:c.labels, datasets:[{ label:'Achievement %', data:c.achievement,
            backgroundColor:'rgba(124,58,237,.2)', borderColor:'#7C3AED', pointBackgroundColor:palette }] },
        options:{ ...common, scales:{ r:{ beginAtZero:true, suggestedMax:100 } } } });

    // tab switching
    $('.stx-tab').on('click', function () {
        $('.stx-tab').removeClass('active');
        $('.stx-pane').removeClass('active');
        $(this).addClass('active');
        $('#' + $(this).data('pane')).addClass('active');
    });

    // DataTable
    if ($('#stxReportTable').length) {
        $('#stxReportTable').DataTable({ pageLength:25, order:[[5,'desc']] });
    }

    // KPI count-up animation
    $('.stx-count').each(function () {
        const $el = $(this), target = parseFloat($el.data('target')) || 0;
        let start = 0; const duration = 1100, startTime = performance.now();
        function step(now) {
            const progress = Math.min((now - startTime) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            $el.text(Number((target * eased)).toLocaleString('en-IN', { maximumFractionDigits:2 }));
            if (progress < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    });

    // Achievement ring fill
    const $ring = $('#stxAchRing');
    if ($ring.length) {
        const pct = Math.min(parseFloat($ring.data('pct')) || 0, 100);
        const circumference = 150.8;
        const offset = circumference - (circumference * pct / 100);
        setTimeout(() => $ring.css('stroke-dashoffset', offset), 200);
    }

    // Hinglish toast feedback (SweetAlert2)
    $('#stxFilterForm').on('submit', function () {
        if (window.Swal) {
            Swal.fire({ toast:true, position:'top-end', showConfirmButton:false, timer:1400,
                icon:'success', title:'Filters apply ho rahe hain...' });
        }
    });
    $('#stxExportExcel').on('click', function () {
        if (window.Swal) {
            Swal.fire({ toast:true, position:'top-end', showConfirmButton:false, timer:1600,
                icon:'success', title:'Excel file taiyar ho rahi hai...' });
        }
    });
    $('#stxExportPdf').on('click', function () {
        if (window.Swal) {
            Swal.fire({ toast:true, position:'top-end', showConfirmButton:false, timer:1600,
                icon:'info', title:'PDF naye tab mein khul rahi hai...' });
        }
    });
});
</script>
@endpush
