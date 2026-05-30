@extends('layouts.admin')
@section('title','Expenses')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center"><h3 class="card-title m-0">Expense Approval Desk</h3><div>@can('expenses.create')<a href="{{ route('admin.expense-ledgers.index') }}" class="btn btn-outline-primary btn-sm">Ledgers</a> <a href="{{ route('admin.expenses.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> New Expense</a>@endcan</div></div>
    <div class="card-body table-responsive">
        <table id="expenseTable" class="table table-hover"><thead><tr><th>Date</th><th>No</th><th>Ledger</th><th>Vendor</th><th>Amount</th><th>Bank</th><th>Status</th><th>Created</th><th></th></tr></thead><tbody>
        @foreach($expenses as $expense)<tr><td>{{ $expense->expense_date?->format('d M Y') }}</td><td>{{ $expense->expense_no }}</td><td>{{ $expense->ledger?->name }}</td><td>{{ $expense->vendor_name ?: '-' }}</td><td><b>Rs {{ number_format((float)$expense->total_amount,2) }}</b></td><td>{{ $expense->bankAccount?->account_name }}</td><td><span class="{{ $expense->status === 'approved' ? 'badge-active' : ($expense->status === 'rejected' ? 'badge-inactive' : 'badge-admin') }}">{{ str_replace('_',' ',ucfirst($expense->status)) }}</span></td><td>{{ $expense->creator?->name ?? 'System' }}</td><td><a href="{{ route('admin.expenses.show',$expense) }}" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a> @if($expense->status !== 'approved')@can('expenses.edit')<a href="{{ route('admin.expenses.edit',$expense) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>@endcan @endif</td></tr>@endforeach
        </tbody></table>
    </div>
</div>
@endsection
@push('scripts')<script>$('#expenseTable').DataTable({pageLength:25, order:[[0,'desc']]});</script>@endpush
