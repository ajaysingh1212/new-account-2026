@extends('layouts.admin')
@section('title', $reportTitle)
@section('content')
@include('admin.reports.partials.styles')

<div class="report-hero">
    <div class="d-flex justify-content-between align-items-start flex-wrap">
        <div>
            <h1>{{ $reportTitle }}</h1>
            <div class="text-info mt-1">{{ $reportSubTitle }} | Party wise and invoice wise GST summary</div>
        </div>
        <div>
            <a class="btn btn-info report-btn" href="{{ route($routeName, request()->query() + ['export' => 'pdf']) }}" target="_blank"><i class="fas fa-file-pdf mr-1"></i> Download PDF</a>
            <a class="btn btn-success report-btn ml-2" href="{{ route($routeName, request()->query() + ['export' => 'excel']) }}"><i class="fas fa-file-excel mr-1"></i> Excel</a>
        </div>
    </div>
    <form class="report-filter" method="GET">
        <div><label>Month</label><input type="month" name="month" class="form-control" value="{{ $filters['month'] }}"></div>
        <div><label>Party</label><select name="party_id" class="form-control"><option value="">All Parties</option>@foreach($parties as $party)<option value="{{ $party->id }}" @selected($filters['partyId']==$party->id)>{{ $party->display_name }}</option>@endforeach</select></div>
        <div class="custom-control custom-checkbox mb-2"><input type="checkbox" class="custom-control-input" id="withoutGst" name="without_gst" value="1" @checked($filters['withoutGst'])><label class="custom-control-label text-info" for="withoutGst">Without GST</label></div>
        <button class="btn btn-info report-btn">Apply</button>
    </form>
</div>

<div class="metric-strip">
    <div class="metric"><span>Taxable Amount</span><strong>Rs {{ number_format($totals['taxable'], 2) }}</strong></div>
    <div class="metric"><span>GST Amount</span><strong class="text-purple">Rs {{ number_format($totals['gst'], 2) }}</strong></div>
    <div class="metric"><span>Total</span><strong>Rs {{ number_format($totals['total'], 2) }}</strong></div>
</div>

<div class="report-card">
    <h3>Party Wise GST Summary</h3>
    <div class="table-responsive">
        <table id="partyGstTable" class="table table-hover report-table">
            <thead><tr><th>Party</th><th>GSTIN</th><th>State</th><th>Taxable Amount</th><th>GST Amount</th><th>Total</th></tr></thead>
            <tbody>@foreach($summary as $row)<tr><td>{{ $row['party'] }}</td><td>{{ $row['gstin'] }}</td><td>{{ $row['state'] }}</td><td>Rs {{ number_format($row['taxable'], 2) }}</td><td class="text-purple font-weight-bold">Rs {{ number_format($row['gst'], 2) }}</td><td><strong>Rs {{ number_format($row['total'], 2) }}</strong></td></tr>@endforeach</tbody>
            <tfoot><tr><th colspan="3">Grand Total</th><th>Rs {{ number_format($totals['taxable'], 2) }}</th><th>Rs {{ number_format($totals['gst'], 2) }}</th><th>Rs {{ number_format($totals['total'], 2) }}</th></tr></tfoot>
        </table>
    </div>
</div>

<div class="report-card">
    <h3>Invoice Level GST Details</h3>
    <div class="table-responsive">
        <table id="invoiceGstTable" class="table table-hover report-table">
            <thead><tr><th>Date</th><th>Invoice</th><th>Party</th><th>GSTIN</th><th>Taxable</th><th>GST</th><th>Total</th></tr></thead>
            <tbody>@foreach($invoiceRows as $row)<tr><td>{{ $row['date'] }}</td><td>{{ $row['invoice'] }}</td><td>{{ $row['party'] }}</td><td>{{ $row['gstin'] }}</td><td>Rs {{ number_format($row['taxable'], 2) }}</td><td>Rs {{ number_format($row['gst'], 2) }}</td><td><strong>Rs {{ number_format($row['total'], 2) }}</strong></td></tr>@endforeach</tbody>
        </table>
    </div>
</div>

@if($filters['withoutGst'])
<div class="report-card">
    <h3>Without GST Bills</h3>
    <div class="table-responsive">
        <table id="withoutGstTable" class="table table-hover report-table">
            <thead><tr><th>Date</th><th>Invoice</th><th>Party</th><th>Total</th></tr></thead>
            <tbody>@foreach($withoutGst as $bill)<tr><td>{{ $bill->billing_date?->format('d-m-Y') }}</td><td>{{ $bill->invoice_no }}</td><td>{{ $bill->party?->display_name ?: 'Cash / Walk-in' }}</td><td>Rs {{ number_format((float)$bill->grand_total, 2) }}</td></tr>@endforeach</tbody>
        </table>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
$('#partyGstTable,#invoiceGstTable,#withoutGstTable').DataTable({pageLength:10});
</script>
@endpush
