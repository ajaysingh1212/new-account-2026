@extends('layouts.admin')
@section('title','Expense Ledgers')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center"><h3 class="card-title m-0">Expense Ledgers</h3>@can('expenses.create')<a href="{{ route('admin.expense-ledgers.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> New Ledger</a>@endcan</div>
    <div class="card-body table-responsive">
        <table id="ledgerTable" class="table table-hover"><thead><tr><th>Code</th><th>Name</th><th>Category</th><th>Opening</th><th>Current</th><th>Status</th><th>Created By</th><th></th></tr></thead><tbody>
        @foreach($ledgers as $ledger)<tr><td>{{ $ledger->ledger_code }}</td><td><b>{{ $ledger->name }}</b></td><td>{{ $ledger->category ?: '-' }}</td><td>Rs {{ number_format((float)$ledger->opening_balance,2) }}<br><small>{{ $ledger->opening_balance_date?->format('d M Y') }}</small></td><td><b>Rs {{ number_format((float)$ledger->current_balance,2) }}</b></td><td>{{ ucfirst($ledger->status) }}</td><td>{{ $ledger->creator?->name ?? 'System' }}</td><td>@can('expenses.create')<a class="btn btn-info btn-sm" href="{{ route('admin.expense-ledgers.edit',$ledger) }}"><i class="fas fa-edit"></i></a>@endcan</td></tr>@endforeach
        </tbody></table>
    </div>
</div>
@endsection
@push('scripts')<script>$('#ledgerTable').DataTable({pageLength:25});</script>@endpush
