@extends('layouts.admin')
@section('title', $mode === 'profit' ? 'Party Wise Profit And Loss' : 'Sale Purchase By Party')
@section('content')
@include('admin.reports.partials.styles')
<div class="report-hero"><h1>{{ $mode === 'profit' ? 'Party Wise Profit And Loss' : 'Sale Purchase By Party' }}</h1><form class="report-filter" method="GET"><div><label>Month</label><input type="month" name="month" class="form-control" value="{{ $filters['month'] }}"></div><div><label>Party</label><select name="party_id" class="form-control"><option value="">All Parties</option>@foreach($parties as $party)<option value="{{ $party->id }}" @selected($filters['partyId']==$party->id)>{{ $party->display_name }}</option>@endforeach</select></div><div></div><button class="btn btn-info report-btn">Apply</button></form></div>
<div class="report-card"><table id="spParty" class="table report-table"><thead><tr><th>Party</th><th>Sales</th><th>Purchase</th><th>Net</th></tr></thead><tbody>@foreach($rows as $row)<tr><td>{{ $row['party']->display_name }}</td><td>Rs {{ number_format($row['sale'],2) }}</td><td>Rs {{ number_format($row['purchase'],2) }}</td><td><strong>Rs {{ number_format($row['net'],2) }}</strong></td></tr>@endforeach</tbody></table></div>
@endsection
@push('scripts')<script>$('#spParty').DataTable({pageLength:25});</script>@endpush
