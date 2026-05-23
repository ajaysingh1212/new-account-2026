@extends('layouts.admin')
@section('title','GST-3 Report')
@section('content')
@include('admin.reports.partials.styles')
<div class="report-hero">
    <h1>GST-3 Report</h1>
    <div class="text-info mt-1">Output GST minus Input GST for selected month and party.</div>
    <form class="report-filter" method="GET">
        <div><label>Month</label><input type="month" name="month" class="form-control" value="{{ $filters['month'] }}"></div>
        <div><label>Party</label><select name="party_id" class="form-control"><option value="">All Parties</option>@foreach($parties as $party)<option value="{{ $party->id }}" @selected($filters['partyId']==$party->id)>{{ $party->display_name }}</option>@endforeach</select></div>
        <div></div><button class="btn btn-info report-btn">Apply</button>
    </form>
</div>
<div class="metric-strip">
    <div class="metric"><span>Output GST From Sales</span><strong>Rs {{ number_format($salesTotals['gst'], 2) }}</strong></div>
    <div class="metric"><span>Input GST From Purchase</span><strong>Rs {{ number_format($purchaseTotals['gst'], 2) }}</strong></div>
    <div class="metric"><span>{{ $netGst >= 0 ? 'GST Payable' : 'GST Credit' }}</span><strong class="{{ $netGst >= 0 ? 'text-danger' : 'text-success' }}">Rs {{ number_format(abs($netGst), 2) }}</strong></div>
</div>
<div class="report-card">
    <h3>GST-3 Summary</h3>
    <table class="table report-table">
        <thead><tr><th>Particular</th><th>Taxable</th><th>GST</th><th>Total</th></tr></thead>
        <tbody>
            <tr><td>Sales GST Output</td><td>Rs {{ number_format($salesTotals['taxable'], 2) }}</td><td>Rs {{ number_format($salesTotals['gst'], 2) }}</td><td>Rs {{ number_format($salesTotals['total'], 2) }}</td></tr>
            <tr><td>Purchase GST Input</td><td>Rs {{ number_format($purchaseTotals['taxable'], 2) }}</td><td>Rs {{ number_format($purchaseTotals['gst'], 2) }}</td><td>Rs {{ number_format($purchaseTotals['total'], 2) }}</td></tr>
        </tbody>
    </table>
</div>
@endsection
