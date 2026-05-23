@extends('layouts.admin')
@section('title','All Parties Report')
@section('content')
@include('admin.reports.partials.styles')
<div class="report-hero"><h1>All Parties</h1><div class="text-info mt-1">Complete party master with balances and ownership.</div></div>
<div class="report-card"><table id="partiesReport" class="table report-table"><thead><tr><th>Code</th><th>Party</th><th>GSTIN</th><th>Phone</th><th>Balance</th><th>Status</th><th>Created By</th></tr></thead><tbody>@foreach($parties as $p)<tr><td>{{ $p->party_code }}</td><td>{{ $p->display_name }}</td><td>{{ $p->gstin ?: '-' }}</td><td>{{ $p->phone ?: '-' }}</td><td>Rs {{ number_format((float)$p->current_balance,2) }}</td><td>{{ ucfirst($p->status) }}</td><td>{{ $p->creator?->name ?? 'System' }}</td></tr>@endforeach</tbody></table></div>
@endsection
@push('scripts')<script>$('#partiesReport').DataTable({pageLength:25});</script>@endpush
