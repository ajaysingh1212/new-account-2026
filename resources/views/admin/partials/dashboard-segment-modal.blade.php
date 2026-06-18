@php
    $segments = collect($segments ?? []);
    $modalId = $modalId ?? 'segmentModal';
    $title = $title ?? 'Product Category Report';
    $amountLabel = $amountLabel ?? 'Amount';
    $total = (float) $segments->sum('amount');
    $absTotal = max(0.01, abs($total));
    $maxAmount = max(1, (float) $segments->max(fn($row) => abs((float) $row['amount'])));
    $parties = $segments->flatMap(fn($row) => collect($row['items'])->pluck('party'))->filter()->unique()->sort()->values();
    $states = $segments->flatMap(fn($row) => collect($row['items'])->pluck('state'))->filter()->unique()->sort()->values();
    $districts = $segments->flatMap(fn($row) => collect($row['items'])->pluck('district'))->filter()->unique()->sort()->values();
    $cities = $segments->flatMap(fn($row) => collect($row['items'])->pluck('city'))->filter()->unique()->sort()->values();
    $cursor = 0;
    $pieParts = [];
    foreach ($segments as $segment) {
        $share = (abs((float) $segment['amount']) / $absTotal) * 100;
        if ($share <= 0) continue;
        $pieParts[] = ($segment['color'] ?? '#64748b') . ' ' . $cursor . '% ' . min(100, $cursor + $share) . '%';
        $cursor += $share;
    }
    $pieGradient = count($pieParts) ? 'conic-gradient(' . implode(',', $pieParts) . ')' : 'conic-gradient(#e2e8f0 0 100%)';
    $labelCount = max(1, $segments->count() - 1);
    $wavePoints = $segments->values()->map(function ($segment, $index) use ($labelCount, $maxAmount) {
        $x = 35 + ($index * (690 / $labelCount));
        $y = 285 - ((abs((float) $segment['amount']) / $maxAmount) * 220);
        return $x . ',' . $y;
    })->implode(' ');
@endphp

<div class="modal fade pro-modal segment-report-modal" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header segment-light-head">
                <div><h5 class="modal-title mb-0">{{ $title }}</h5><small>Filter: {{ $from }} to {{ $to }}</small></div>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="segment-filter-grid">
                    <select class="form-control segment-filter" data-filter="party"><option value="">All Parties</option>@foreach($parties as $party)<option value="{{ $party }}">{{ $party }}</option>@endforeach</select>
                    <select class="form-control segment-filter" data-filter="state"><option value="">All States</option>@foreach($states as $state)<option value="{{ $state }}">{{ $state }}</option>@endforeach</select>
                    <select class="form-control segment-filter" data-filter="district"><option value="">All Districts</option>@foreach($districts as $district)<option value="{{ $district }}">{{ $district }}</option>@endforeach</select>
                    <select class="form-control segment-filter" data-filter="city"><option value="">All Cities</option>@foreach($cities as $city)<option value="{{ $city }}">{{ $city }}</option>@endforeach</select>
                </div>

                <div class="sales-viz-shell mb-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:10px">
                        <div>
                            <div class="segment-total-label">Total {{ $amountLabel }}</div>
                            <div class="segment-total-value">Rs {{ number_format($total,2) }}</div>
                        </div>
                        <div class="sales-viz-tabs">
                            <button type="button" class="sales-viz-tab active text-primary" data-sales-viz="pie"><i class="fas fa-chart-pie mr-1"></i>Pie</button>
                            <button type="button" class="sales-viz-tab text-primary" data-sales-viz="candle"><i class="fas fa-chart-simple mr-1"></i>Candle</button>
                            <button type="button" class="sales-viz-tab text-primary" data-sales-viz="wave"><i class="fas fa-water mr-1"></i>Wave</button>
                            <button type="button" class="sales-viz-tab text-primary" data-sales-viz="bar"><i class="fas fa-chart-bar mr-1"></i>Bar</button>
                            <button type="button" class="sales-viz-tab text-primary" data-sales-viz="content"><i class="fas fa-list mr-1"></i>Content</button>
                        </div>
                    </div>

                    <div class="sales-viz-pane active" data-sales-pane="pie">
                        <div class="category-pie-wrap">
                            <div class="category-pie segment-pie" style="--pie-gradient:{{ $pieGradient }}"><div class="category-pie-center text-white">100%<br><span style="font-size:11px;color:#64748b">{{ $amountLabel }}</span></div></div>
                            <div class="category-legend">
                                @foreach($segments as $segment)
                                    <div class="category-legend-row segment-legend" data-segment='@json($segment)' style="--c:{{ $segment['color'] }};--w:{{ min(100, $segment['percent']) }}%">
                                        <span class="category-dot"></span>
                                        <div><b>{{ $segment['label'] }}</b><div class="category-meter"><span></span></div></div>
                                        <div class="text-right segment-legend-amount"><b>{{ number_format($segment['percent'],2) }}%</b><br><small>Rs {{ number_format($segment['amount'],2) }}</small></div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="sales-viz-pane" data-sales-pane="candle">
                        <div class="candle-stage">
                            @foreach($segments as $segment)
                                @php $h = max(8, (abs((float)$segment['amount']) / $maxAmount) * 210); @endphp
                                <div class="candle-stick segment-candle" data-segment='@json($segment)' style="--c:{{ $segment['color'] }};--h:{{ $h }}px;--wick:{{ min(260, $h + 46) }}px">
                                    <div class="candle-line"></div><div class="candle-body"></div><div class="chart-label">{{ $segment['label'] }}<br>{{ number_format($segment['percent'],1) }}%</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="sales-viz-pane" data-sales-pane="wave">
                        <svg class="wave-pro" viewBox="0 0 760 330" preserveAspectRatio="none">
                            @foreach(range(0,4) as $line)<line x1="25" x2="735" y1="{{ 55 + ($line * 52) }}" y2="{{ 55 + ($line * 52) }}" stroke="#dbeafe"/>@endforeach
                            <path class="segment-wave-path" d="M {{ $wavePoints }}" stroke="#38bdf8"/>
                            @foreach($segments->values() as $index => $segment)
                                @php $x = 35 + ($index * (690 / $labelCount)); $y = 285 - ((abs((float)$segment['amount']) / $maxAmount) * 220); @endphp
                                <g class="segment-wave-point" data-segment='@json($segment)'><circle cx="{{ $x }}" cy="{{ $y }}" r="5" fill="{{ $segment['color'] }}"/><text x="{{ $x }}" y="315" text-anchor="middle" font-size="11" fill="#475569">{{ \Illuminate\Support\Str::limit($segment['label'], 10) }}</text></g>
                            @endforeach
                        </svg>
                    </div>

                    <div class="sales-viz-pane" data-sales-pane="bar">
                        <div class="bar-stage segment-bars">
                            @foreach($segments as $segment)
                                @php $h = max(8, (abs((float)$segment['amount']) / $maxAmount) * 230); @endphp
                                <div class="bar-col segment-bar" data-segment='@json($segment)' style="--c:{{ $segment['color'] }};--h:{{ $h }}px">
                                    <div class="bar-fill"></div>
                                    <div class="chart-label">{{ $segment['label'] }}<br>Rs {{ number_format((float)$segment['amount'],0) }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="sales-viz-pane" data-sales-pane="content">
                        @foreach($segments as $segment)
                            <div class="content-sales-row segment-content-row" data-segment='@json($segment)' style="--c:{{ $segment['color'] }};--soft:{{ $segment['color'] }}22">
                                <div class="segment-icon"><i class="fas {{ $segment['icon'] }}"></i></div>
                                <div><b>{{ $segment['label'] }}</b><br><small>{{ number_format($segment['qty'],2) }} qty | {{ number_format($segment['percent'],2) }}% of total {{ strtolower($amountLabel) }}</small></div>
                                <strong>Rs {{ number_format($segment['amount'],2) }}</strong>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="segment-grid segment-cards">
                    @foreach($segments as $segment)
                        <div class="segment-card segment-card-filterable" data-segment='@json($segment)'>
                            <div class="segment-top">
                                <div class="d-flex align-items-center" style="gap:10px"><div class="segment-icon" style="background:{{ $segment['color'] }}22;color:{{ $segment['color'] }}"><i class="fas {{ $segment['icon'] }}"></i></div><div><b>{{ $segment['label'] }}</b><br><small class="segment-card-meta">{{ number_format($segment['qty'],2) }} qty | {{ number_format($segment['percent'],2) }}%</small></div></div>
                                <strong>Rs {{ number_format((float)$segment['amount'],2) }}</strong>
                            </div>
                            <div class="modal-table-wrap" style="max-height:260px"><table class="table table-sm mb-0"><thead><tr><th>Item</th><th>Party / Place</th><th>{{ $amountLabel }}</th></tr></thead><tbody>
                                @forelse($segment['items'] as $item)<tr data-item='@json($item)'><td>{{ $item['name'] }}<br><small>{{ $item['invoice'] }} | {{ $item['date'] }} | {{ $item['product_type'] }}</small></td><td>{{ $item['party'] }}<br><small>{{ $item['city'] }}, {{ $item['district'] }}, {{ $item['state'] }}</small></td><td>Rs {{ number_format((float)$item['amount'],2) }}</td></tr>@empty
                                <tr><td colspan="3" class="text-muted text-center">No data.</td></tr>@endforelse
                            </tbody></table></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
