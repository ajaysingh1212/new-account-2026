@extends('layouts.admin')
@section('title','Party Report By Item')
@section('content')
@include('admin.reports.partials.styles')
<div class="report-hero"><h1>Party Report By Item</h1><form class="report-filter" method="GET"><div><label>Month</label><input type="month" name="month" class="form-control" value="{{ $filters['month'] }}"></div><div><label>Party</label><select name="party_id" class="form-control"><option value="">All Parties</option>@foreach($parties as $party)<option value="{{ $party->id }}" @selected($filters['partyId']==$party->id)>{{ $party->display_name }}</option>@endforeach</select></div><div></div><button class="btn btn-info report-btn">Apply</button></form></div>
<div class="report-card"><table id="partyItem" class="table report-table"><thead><tr><th>Date</th><th>Party</th><th>Item</th><th>Type</th><th>Qty</th><th>Amount</th></tr></thead><tbody>@foreach($sales as $row)<tr><td>{{ $row['date']?->format('d-m-Y') }}</td><td>{{ $row['party'] }}</td><td>{{ $row['item'] }}</td><td>{{ $row['type'] }}</td><td>{{ $row['qty'] }}</td><td>Rs {{ number_format($row['amount'],2) }}</td></tr>@endforeach</tbody></table></div>
@endsection
@push('scripts')<script>$('#partyItem').DataTable({pageLength:25});</script>@endpush
