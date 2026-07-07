@extends('layouts.admin')
@section('title', 'Ageing Report')
@section('content')
@include('admin.reports.partials.styles')
<style>#ageingTable tbody td:not(:first-child) .btn-xs{display:none}</style>
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
            <td>
                <strong>{{ $row['party'] }}</strong><br>
                <small>{{ $row['bill_count'] }} open bill(s)</small>
                <div class="mt-2">
                    @php $partyRouteKey = $row['party_id'] ?: 'cash'; @endphp
                    <a href="{{ route('admin.reports.ageing.party-print', ['party' => $partyRouteKey, 'kind' => $kind, 'to_date' => $to]) }}" class="btn btn-outline-info btn-xs mr-1" target="_blank"><i class="fas fa-file-pdf mr-1"></i>PDF</a>
                    <a href="{{ route('admin.reports.ageing.party-diagnosis', ['party' => $partyRouteKey, 'kind' => $kind, 'to_date' => $to]) }}" class="btn btn-outline-secondary btn-xs"><i class="fas fa-stethoscope mr-1"></i>Diagnosis</a>
                </div>
            </td>
            <td>Rs {{ number_format($row['receivable'],2) }}</td><td>Rs {{ number_format($row['payable'],2) }}</td>
            @foreach($slabs as $key => $label) @php $cell = $row['slabs'][$key]; @endphp
                <td title="{{ $cell['invoices'] }}">@if($cell['bills'])<strong>Rs {{ number_format($cell['due'],2) }}</strong><br><small>{{ $cell['bills'] }} bill(s)</small>@if(!empty($cell['bills']))<div class="mt-2">@foreach($cell['invoices'] ? explode(',', $cell['invoices']) : [] as $invoiceId) @php $invoiceId = trim($invoiceId); @endphp @if($invoiceId)<a href="{{ route('admin.reports.ageing.print', ['kind' => $row['receivable'] > 0 ? 'receivable' : 'payable', 'bill' => $invoiceId]) }}" class="btn btn-outline-info btn-xs mr-1" target="_blank">PDF</a><a href="{{ route('admin.reports.ageing.diagnosis', ['kind' => $row['receivable'] > 0 ? 'receivable' : 'payable', 'bill' => $invoiceId]) }}" class="btn btn-outline-secondary btn-xs">Diagnosis</a>@endif @endforeach</div>@endif@else<span class="text-muted">—</span>@endif</td>
            @endforeach
            <td><strong>Rs {{ number_format($row['total_due'],2) }}</strong></td>
        </tr>@endforeach
        </tbody>
        <tfoot><tr><th>Total</th><th>Rs {{ number_format($rows->sum('receivable'),2) }}</th><th>Rs {{ number_format($rows->sum('payable'),2) }}</th>@foreach($slabs as $key => $label)<th>Rs {{ number_format($rows->sum(fn($row) => $row['slabs'][$key]['due']),2) }}</th>@endforeach<th>Rs {{ number_format($rows->sum('total_due'),2) }}</th></tr></tfoot>
    </table>
</div>
<div class="mt-4 d-none">
    <h3>Invoice Ageing Detail</h3>
    <div class="table-responsive">
        <table id="ageingBillTable" class="table report-table">
            <thead><tr><th>Invoice</th><th>Type</th><th>Party</th><th>Bill Date</th><th>Age</th><th>Total</th><th>Paid</th><th>Due</th><th>Actions</th></tr></thead>
            <tbody>
            @foreach($billRows as $bill)
                <tr>
                    <td><strong>{{ $bill['invoice'] }}</strong></td>
                    <td><span class="badge badge-{{ $bill['kind'] === 'receivable' ? 'success' : 'warning' }}">{{ ucfirst($bill['kind']) }}</span></td>
                    <td>{{ $bill['party'] }}</td>
                    <td>{{ $bill['date']?->format('d M Y') }}</td>
                    <td>{{ $bill['age'] }} days</td>
                    <td>Rs {{ number_format($bill['total'],2) }}</td>
                    <td>Rs {{ number_format($bill['paid'],2) }}</td>
                    <td><strong>Rs {{ number_format($bill['due'],2) }}</strong></td>
                    <td>
                        <a href="{{ route('admin.reports.ageing.print', ['kind' => $bill['kind'], 'bill' => $bill['bill_id']]) }}" class="btn btn-outline-info btn-sm" target="_blank"><i class="fas fa-file-pdf mr-1"></i>PDF</a>
                        <a href="{{ route('admin.reports.ageing.diagnosis', ['kind' => $bill['kind'], 'bill' => $bill['bill_id']]) }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-stethoscope mr-1"></i>Diagnosis</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection
@push('scripts')<script>$('#ageingTable').DataTable({pageLength:25, order:[[9,'desc']]});$('#ageingBillTable').DataTable({pageLength:25, order:[[3,'desc']]});</script>@endpush
