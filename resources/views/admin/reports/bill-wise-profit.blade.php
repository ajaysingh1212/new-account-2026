@extends('layouts.admin')
@section('title', 'Bill Wise Profit')
@section('content')
@include('admin.reports.partials.styles')
<div class="report-hero">
    <h1>Bill Wise Profit</h1>
    <form class="report-filter" method="GET">
        <div><label>Month</label><input type="month" name="month" class="form-control" value="{{ $filters['month'] }}"></div>
        <div><label>Party</label><select name="party_id" class="form-control"><option value="">All Parties</option>@foreach($parties as $party)<option value="{{ $party->id }}" @selected($filters['partyId']==$party->id)>{{ $party->display_name }}</option>@endforeach</select></div>
        <div></div><button class="btn btn-info report-btn">Apply</button>
    </form>
</div>
<div class="metric-strip">
    <div class="metric"><span>Total Sale</span><strong>Rs {{ number_format($bills->sum('sale'),2) }}</strong></div>
    <div class="metric"><span>Total Cost</span><strong>Rs {{ number_format($bills->sum('cost'),2) }}</strong></div>
    <div class="metric"><span>Profit / Loss</span><strong>Rs {{ number_format($bills->sum('profit'),2) }}</strong></div>
</div>
<div class="report-card">
    <table id="profitTable" class="table report-table">
        <thead><tr><th>Date</th><th>Invoice</th><th>Party</th><th>Cost</th><th>Sale</th><th>Profit / Loss</th></tr></thead>
        <tbody>@foreach($bills as $row)<tr><td>{{ $row['bill']->billing_date?->format('d-m-Y') }}</td><td>{{ $row['bill']->invoice_no }}</td><td>{{ $row['bill']->party?->display_name ?: 'Cash / Walk-in' }}</td><td>Rs {{ number_format($row['cost'],2) }}</td><td>Rs {{ number_format($row['sale'],2) }}</td><td><strong class="{{ $row['profit'] >= 0 ? 'text-success' : 'text-danger' }}">Rs {{ number_format($row['profit'],2) }}</strong></td></tr>@endforeach</tbody>
    </table>
</div>
@endsection
@push('scripts')<script>$('#profitTable').DataTable({pageLength:25});</script>@endpush
