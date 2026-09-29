@extends('layouts.admin')
@section('title','Credit Notes')
@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center"><div><h3 class="card-title m-0">Credit Notes</h3><small class="text-muted">Invoice-wise credit documents and stock-return status</small></div>@can('credit_notes.create')<a href="{{ route('admin.credit-notes.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i>New Credit Note</a>@endcan</div>
    <div class="card-body table-responsive">
        <table id="creditNotesTable" class="table table-hover">
            <thead><tr><th>Credit Note</th><th>Date</th><th>Invoice</th><th>Party</th><th>Items</th><th>Amount</th><th>Sales Return</th><th>Created By</th><th>Actions</th></tr></thead>
            <tbody>@foreach($creditNotes as $note)<tr>
                <td><b>{{ $note->credit_note_no }}</b></td><td>{{ $note->credit_note_date?->format('d M Y') }}</td>
                <td><a href="{{ route('admin.sales.show',$note->invoice) }}">{{ $note->invoice?->invoice_no }}</a></td><td>{{ $note->party?->display_name ?: 'Cash' }}</td>
                <td>{{ $note->items->count() }} line(s)</td><td><b>Rs {{ number_format((float)$note->grand_total,2) }}</b></td>
                <td>@if($note->hasCompleteSalesReturn())<span class="badge badge-success">Return received</span>@else<span class="badge badge-warning">Credit passed, return pending</span>@endif</td>
                <td>{{ $note->creator?->name ?: 'System' }}</td>
                <td class="text-nowrap"><a href="{{ route('admin.credit-notes.show',$note) }}" class="btn btn-info btn-sm" title="View"><i class="fas fa-eye"></i></a> @can('credit_notes.edit')<a href="{{ route('admin.credit-notes.edit',$note) }}" class="btn btn-warning btn-sm" title="Edit"><i class="fas fa-edit"></i></a>@endcan @can('credit_notes.print')<a href="{{ route('admin.credit-notes.print',$note) }}" target="_blank" class="btn btn-secondary btn-sm" title="Print"><i class="fas fa-print"></i></a>@endcan</td>
            </tr>@endforeach</tbody>
        </table>
    </div>
</div>
@endsection
@push('scripts')<script>$('#creditNotesTable').DataTable({pageLength:25,columnDefs:[{orderable:false,targets:8}]});</script>@endpush
