@extends('layouts.admin')
@section('title', 'Ageing Report')
@section('content')
@include('admin.reports.partials.styles')
<div class="report-card" data-export-title="Ageing Report" data-export-file="ageing-report">
@include('admin.reports.partials.branded-export')
<div class="report-hero">
    <h1>Party-wise Ageing Report</h1>
    <form class="report-filter" method="GET">
        <div><label>Party</label><select name="party_id" class="form-control"><option value="">All Parties</option>@foreach($parties as $party)<option value="{{ $party->id }}" @selected($partyId==$party->id)>{{ $party->display_name }}</option>@endforeach</select></div>
        <div><label>Balance Type</label><select name="kind" class="form-control"><option value="both" @selected($kind==='both')>Both</option><option value="receivable" @selected($kind==='receivable')>Receivable</option><option value="payable" @selected($kind==='payable')>Payable</option></select></div>
        <div><label>As On Date</label><input type="date" name="to_date" class="form-control" value="{{ $to }}"></div>
        <button class="btn btn-info report-btn">Apply</button>
    </form>
</div>
<div class="metric-strip">
    <div class="metric"><span>Receivable</span><strong>Rs {{ number_format($billRows->where('kind','receivable')->sum('due'),2) }}</strong></div>
    <div class="metric"><span>Payable</span><strong>Rs {{ number_format($billRows->where('kind','payable')->sum('due'),2) }}</strong></div>
    <div class="metric"><span>Open Bills</span><strong>{{ $billRows->count() }}</strong></div>
    <div class="metric"><span>Parties</span><strong>{{ $rows->count() }}</strong></div>
</div>
<div class="table-responsive">
    <table id="ageingTable" class="table report-table">
        <thead><tr><th>Party</th><th>Receivable</th><th>Payable</th>@foreach($slabs as $label)<th>{{ $label }}</th>@endforeach<th>Total Due</th></tr></thead>
        <tbody>
        @foreach($rows as $row)<tr>
            <td><strong>{{ $row['party'] }}</strong><br><small>{{ $row['bill_count'] }} open bill(s)</small></td>
            <td>Rs {{ number_format($row['receivable'],2) }}</td><td>Rs {{ number_format($row['payable'],2) }}</td>
            @foreach($slabs as $key => $label) @php $cell = $row['slabs'][$key]; @endphp
                <td title="{{ $cell['invoices'] }}">@if($cell['bills'])<strong>Rs {{ number_format($cell['due'],2) }}</strong><br><small>{{ $cell['bills'] }} bill(s)</small>@else<span class="text-muted">—</span>@endif</td>
            @endforeach
            <td><strong>Rs {{ number_format($row['total_due'],2) }}</strong></td>
        </tr>@endforeach
        </tbody>
        <tfoot><tr><th>Total</th><th>Rs {{ number_format($rows->sum('receivable'),2) }}</th><th>Rs {{ number_format($rows->sum('payable'),2) }}</th>@foreach($slabs as $key => $label)<th>Rs {{ number_format($rows->sum(fn($row) => $row['slabs'][$key]['due']),2) }}</th>@endforeach<th>Rs {{ number_format($rows->sum('total_due'),2) }}</th></tr></tfoot>
    </table>
</div>
</div>
@endsection
@push('scripts')<script>$('#ageingTable').DataTable({pageLength:25, order:[[9,'desc']]});</script>@endpush
