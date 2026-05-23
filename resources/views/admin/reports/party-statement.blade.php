@extends('layouts.admin')
@section('title','Party Statement')
@section('content')
@include('admin.reports.partials.styles')
<div class="report-hero"><h1>Party Statement</h1><form class="report-filter" method="GET"><div><label>From</label><input type="date" name="from_date" class="form-control" value="{{ $from }}"></div><div><label>To</label><input type="date" name="to_date" class="form-control" value="{{ $to }}"></div><div><label>Party</label><select name="party_id" class="form-control"><option value="">All Parties</option>@foreach($parties as $party)<option value="{{ $party->id }}" @selected($partyId==$party->id)>{{ $party->display_name }}</option>@endforeach</select></div><button class="btn btn-info report-btn">Apply</button></form></div>
<div class="report-card"><h3>Ledger Entries</h3><table id="ledgerTable" class="table report-table"><thead><tr><th>Date</th><th>Party</th><th>Type</th><th>Ref</th><th>Debit</th><th>Credit</th><th>Balance</th></tr></thead><tbody>@foreach($ledgers as $l)<tr><td>{{ $l->entry_date?->format('d-m-Y') }}</td><td>{{ $l->party?->display_name }}</td><td>{{ str_replace('_',' ',ucfirst($l->entry_type)) }}</td><td>{{ $l->reference_no }}</td><td>Rs {{ number_format((float)$l->debit,2) }}</td><td>Rs {{ number_format((float)$l->credit,2) }}</td><td>Rs {{ number_format((float)$l->balance_after,2) }}</td></tr>@endforeach</tbody></table></div>
@endsection
@push('scripts')<script>$('#ledgerTable').DataTable({pageLength:25});</script>@endpush
