@extends('layouts.admin')
@section('title','Day Book')
@section('content')
@include('admin.reports.partials.styles')
<div data-export-title="Day Book / All Transactions" data-export-file="day-book">@include('admin.reports.partials.branded-export')</div>
<div class="report-hero"><h1>Day Book / All Transactions</h1><form class="report-filter" method="GET"><div><label>From</label><input type="date" name="from_date" class="form-control" value="{{ $filters['from'] }}"></div><div><label>To</label><input type="date" name="to_date" class="form-control" value="{{ $filters['to'] }}"></div><div></div><button class="btn btn-info report-btn">Apply</button></form></div>
<div class="metric-strip"><div class="metric"><span>Sales</span><strong>Rs {{ number_format((float)$sales->sum('grand_total'),2) }}</strong></div><div class="metric"><span>Purchase</span><strong>Rs {{ number_format((float)$purchases->sum('grand_total'),2) }}</strong></div><div class="metric"><span>Bank Flow</span><strong>Rs {{ number_format((float)$bank->sum('amount'),2) }}</strong></div></div>
<div class="report-card"><h3>Bank Transactions</h3><table id="dayBook" class="table report-table"><thead><tr><th>Date</th><th>Account</th><th>Type</th><th>Direction</th><th>Amount</th><th>Reference</th></tr></thead><tbody>@foreach($bank as $b)<tr><td>{{ $b->transaction_date?->format('d-m-Y') }}</td><td>{{ $b->bankAccount?->account_name }}</td><td>{{ str_replace('_',' ',ucfirst($b->transaction_type)) }}</td><td>{{ strtoupper($b->direction) }}</td><td>Rs {{ number_format((float)$b->amount,2) }}</td><td>{{ $b->reference_no }}</td></tr>@endforeach</tbody></table></div>
@endsection
@push('scripts')<script>$('#dayBook').DataTable({pageLength:25});</script>@endpush
