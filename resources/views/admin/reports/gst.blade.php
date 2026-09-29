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
    @if($type === 'sales')
    <div class="metric"><span>Gross Sales GST</span><strong>Rs {{ number_format($grossTotals['gst'], 2) }}</strong><small class="d-block text-muted">Before Credit Notes</small></div>
    <div class="metric" style="cursor:pointer" data-toggle="modal" data-target="#creditNoteGstModal" title="View Credit Note GST details"><span>Less: Credit Note GST <i class="fas fa-search-plus ml-1"></i></span><strong class="text-danger">- Rs {{ number_format($creditNoteTotals['gst'], 2) }}</strong><small class="d-block text-info">Click for {{ $creditNoteRows->count() }} Credit Note(s)</small></div>
    <div class="metric"><span>Net Taxable Amount</span><strong>Rs {{ number_format($totals['taxable'], 2) }}</strong></div>
    <div class="metric"><span>Net GST Payable</span><strong class="text-purple">Rs {{ number_format($totals['gst'], 2) }}</strong><small class="d-block text-success">Credit Note GST deducted</small></div>
    <div class="metric"><span>Net Sales Value</span><strong>Rs {{ number_format($totals['total'], 2) }}</strong></div>
    @else
    <div class="metric"><span>Taxable Amount</span><strong>Rs {{ number_format($totals['taxable'], 2) }}</strong></div>
    <div class="metric"><span>GST Amount</span><strong class="text-purple">Rs {{ number_format($totals['gst'], 2) }}</strong></div>
    <div class="metric"><span>Total</span><strong>Rs {{ number_format($totals['total'], 2) }}</strong></div>
    @endif
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
            <tbody>@foreach($invoiceRows as $row)<tr class="{{ ($row['type'] ?? 'invoice') === 'credit_note' ? 'table-warning' : '' }}"><td>{{ $row['date'] }}</td><td>{{ $row['invoice'] }} @if(($row['type'] ?? '') === 'credit_note')<span class="badge badge-danger ml-1">GST Minus</span>@endif</td><td>{{ $row['party'] }}</td><td>{{ $row['gstin'] }}</td><td class="{{ $row['taxable'] < 0 ? 'text-danger' : '' }}">Rs {{ number_format($row['taxable'], 2) }}</td><td class="{{ $row['gst'] < 0 ? 'text-danger font-weight-bold' : '' }}">Rs {{ number_format($row['gst'], 2) }}</td><td><strong class="{{ $row['total'] < 0 ? 'text-danger' : '' }}">Rs {{ number_format($row['total'], 2) }}</strong></td></tr>@endforeach</tbody>
        </table>
    </div>
</div>

@if($filters['withoutGst'])
<div class="report-card">
    <h3>Without GST Bills</h3>
    <div class="table-responsive">
        <table id="withoutGstTable" class="table table-hover report-table">
            <thead><tr><th>Date</th><th>Invoice</th><th>Party</th><th>Total</th></tr></thead>
            <tbody>@foreach($withoutGst as $bill)<tr><td>{{ $bill->gst_date ?? $bill->billing_date?->format('d-m-Y') }}</td><td>{{ $bill->invoice_no }}</td><td>{{ $bill->party?->display_name ?: 'Cash / Walk-in' }}</td><td>Rs {{ number_format((float)$bill->grand_total, 2) }}</td></tr>@endforeach</tbody>
        </table>
    </div>
</div>
@endif
@if($type === 'sales')
<div class="modal fade" id="creditNoteGstModal" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable" role="document"><div class="modal-content" style="border:0;border-radius:8px;overflow:hidden">
    <div class="modal-header bg-dark text-white"><div><h5 class="modal-title mb-0">Credit Note GST Adjustments</h5><small>{{ $filters['from'] }} to {{ $filters['to'] }} | GST deducted from sales output</small></div><button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button></div>
    <div class="modal-body" style="background:#f8fafc">
        <div class="row mb-3"><div class="col-md-4"><div class="bg-white border p-3"><small class="text-muted">Taxable Value Reversed</small><div class="h5 mb-0 text-danger">- Rs {{ number_format($creditNoteTotals['taxable'],2) }}</div></div></div><div class="col-md-4"><div class="bg-white border p-3"><small class="text-muted">GST Reversed</small><div class="h5 mb-0 text-danger">- Rs {{ number_format($creditNoteTotals['gst'],2) }}</div></div></div><div class="col-md-4"><div class="bg-white border p-3"><small class="text-muted">Total Credit Notes</small><div class="h5 mb-0">Rs {{ number_format($creditNoteTotals['total'],2) }}</div></div></div></div>
        @forelse($creditNoteRows as $note)<div class="bg-white border p-3 mb-3"><div class="d-flex justify-content-between flex-wrap mb-2"><div><a href="{{ route('admin.credit-notes.show',$note['id']) }}"><b>{{ $note['credit_note_no'] }}</b></a> against Invoice <b>{{ $note['invoice'] }}</b><br><small class="text-muted">{{ $note['date'] }} | {{ $note['party'] }} | GSTIN {{ $note['gstin'] }} | {{ $note['reason'] }}</small></div><div class="text-right"><span class="badge badge-danger">GST Minus: Rs {{ number_format($note['gst'],2) }}</span><br><b>Credit: Rs {{ number_format($note['total'],2) }}</b></div></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Item / SKU</th><th>Qty</th><th>GST Rate</th><th>Taxable Minus</th><th>GST Minus</th><th>Total</th></tr></thead><tbody>@foreach($note['items'] as $item)<tr><td>{{ $item['name'] }}<br><small class="text-muted">{{ $item['sku'] }}</small></td><td>{{ number_format($item['quantity'],3) }}</td><td>{{ number_format($item['tax_percent'],2) }}%</td><td class="text-danger">- Rs {{ number_format($item['taxable'],2) }}</td><td class="text-danger">- Rs {{ number_format($item['gst'],2) }}</td><td>Rs {{ number_format($item['total'],2) }}</td></tr>@endforeach</tbody></table></div></div>@empty<div class="alert alert-light border mb-0">Selected period me GST wala koi Credit Note nahi hai.</div>@endforelse
    </div><div class="modal-footer"><button class="btn btn-secondary" data-dismiss="modal">Close</button></div>
</div></div></div>
@endif
@endsection

@push('scripts')
<script>
$('#partyGstTable,#invoiceGstTable,#withoutGstTable').DataTable({pageLength:10});
</script>
@endpush
