@extends('layouts.admin')
@section('title', $title)
@section('content')
@include('admin.reports.partials.styles')
<div class="report-hero">
    <h1>{{ $title }}</h1>
    <form class="report-filter" method="GET">
        <div><label>Month</label><input type="month" name="month" class="form-control" value="{{ $filters['month'] }}"></div>
        <div><label>Party</label><select name="party_id" class="form-control"><option value="">All Parties</option>@foreach($parties as $party)<option value="{{ $party->id }}" @selected($filters['partyId']==$party->id)>{{ $party->display_name }}</option>@endforeach</select></div>
        <div></div><button class="btn btn-info report-btn">Apply</button>
    </form>
</div>
<div class="metric-strip">
    <div class="metric"><span>Total Bills</span><strong>{{ $bills->count() }}</strong></div>
    <div class="metric"><span>Tax</span><strong>Rs {{ number_format((float)$bills->sum('tax_amount'), 2) }}</strong></div>
    <div class="metric"><span>Grand Total</span><strong>Rs {{ number_format((float)$bills->sum('grand_total'), 2) }}</strong></div>
</div>
<div class="report-card">
    <table id="billTable" class="table report-table">
        <thead><tr><th>Date</th><th>Invoice</th><th>Party</th><th>Subtotal</th><th>Discount</th><th>Tax</th><th>Total</th><th>Status</th></tr></thead>
        <tbody>@foreach($bills as $bill)<tr><td>{{ $bill->billing_date?->format('d-m-Y') }}</td><td>{{ $bill->invoice_no }}</td><td>{{ $bill->party?->display_name ?: 'Cash / Walk-in' }}</td><td>Rs {{ number_format((float)$bill->subtotal,2) }}</td><td>Rs {{ number_format((float)$bill->discount_amount,2) }}</td><td>Rs {{ number_format((float)$bill->tax_amount,2) }}</td><td><strong>Rs {{ number_format((float)$bill->grand_total,2) }}</strong></td><td>{{ ucfirst($bill->status) }}</td></tr>@endforeach</tbody>
    </table>
</div>
@endsection
@push('scripts')<script>$('#billTable').DataTable({pageLength:25});</script>@endpush
