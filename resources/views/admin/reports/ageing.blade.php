@extends('layouts.admin')
@section('title', 'Ageing Report')
@section('content')
@include('admin.reports.partials.styles')
<div data-export-title="Ageing Report" data-export-file="ageing-report">@include('admin.reports.partials.branded-export')</div>
<div class="report-hero">
    <h1>Ageing Report</h1>
    <form class="report-filter" method="GET">
        <div><label>Party</label><select name="party_id" class="form-control"><option value="">All Parties</option>@foreach($parties as $party)<option value="{{ $party->id }}" @selected($partyId==$party->id)>{{ $party->display_name }}</option>@endforeach</select></div>
        <div><label>Balance Type</label><select name="kind" class="form-control"><option value="both" @selected($kind==='both')>Both</option><option value="receivable" @selected($kind==='receivable')>Receivable</option><option value="payable" @selected($kind==='payable')>Payable</option></select></div>
        <div><label>As On Date</label><input type="date" name="to_date" class="form-control" value="{{ $to }}"></div>
        <button class="btn btn-info report-btn">Apply</button>
    </form>
</div>
<div class="mb-3 d-flex flex-wrap" style="gap:8px">
@foreach(['0-15','15-30','30-45','30-60','60-75','75-90','all'] as $ageSlab)
    <a class="btn {{ $slab === $ageSlab ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('admin.reports.ageing', request()->except('slab') + ['slab' => $ageSlab]) }}">{{ $ageSlab === 'all' ? 'All Days' : $ageSlab.' Days' }}</a>
@endforeach
</div>
<div class="metric-strip">
    <div class="metric"><span>Receivable</span><strong>Rs {{ number_format($rows->where('kind','receivable')->sum('due'),2) }}</strong></div>
    <div class="metric"><span>Payable</span><strong>Rs {{ number_format($rows->where('kind','payable')->sum('due'),2) }}</strong></div>
    <div class="metric"><span>Open Bills</span><strong>{{ $rows->count() }}</strong></div>
</div>
<div class="report-card">
    <table id="ageingTable" class="table report-table">
        <thead><tr><th>Type</th><th>Party</th><th>Invoice</th><th>Date</th><th>Age</th><th>Total</th><th>Paid</th><th>Due</th></tr></thead>
        <tbody>@foreach($rows as $row)<tr><td>{{ ucfirst($row['kind']) }}</td><td>{{ $row['party'] }}</td><td>{{ $row['invoice'] }}</td><td>{{ $row['date']?->format('d-m-Y') }}</td><td>{{ $row['age'] }} days</td><td>Rs {{ number_format($row['total'],2) }}</td><td>Rs {{ number_format($row['paid'],2) }}</td><td><strong>Rs {{ number_format($row['due'],2) }}</strong></td></tr>@endforeach</tbody>
    </table>
</div>
@endsection
@push('scripts')<script>$('#ageingTable').DataTable({pageLength:25, order:[[3,'desc']]});</script>@endpush
