@extends('layouts.admin')
@section('title', 'Other Income / Expense')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0">Other Income / Expense Approval Desk</h3>
        @can('other_transactions.create')
            <a href="{{ route('admin.other-transactions.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> New Entry</a>
        @endcan
    </div>
    <div class="card-body table-responsive">
        <table id="otherTxnTable" class="table table-hover">
            <thead>
                <tr>
                    <th>Date</th><th>No</th><th>Type</th><th>Ledger</th><th>Party</th><th>Amount</th><th>Bank</th><th>Status</th><th>Created</th><th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($transactions as $transaction)
                <tr>
                    <td>{{ $transaction->transaction_date?->format('d M Y') }}</td>
                    <td>{{ $transaction->transaction_no }}</td>
                    <td>{{ ucfirst($transaction->transaction_kind) }}</td>
                    <td>{{ $transaction->ledger?->name ?: '-' }}</td>
                    <td>{{ $transaction->party_name ?: '-' }}</td>
                    <td><b>Rs {{ number_format((float) $transaction->total_amount, 2) }}</b></td>
                    <td>{{ $transaction->bankAccount?->account_name ?: '-' }}</td>
                    <td><span class="{{ $transaction->status === 'approved' ? 'badge-active' : ($transaction->status === 'rejected' ? 'badge-inactive' : 'badge-admin') }}">{{ str_replace('_', ' ', ucfirst($transaction->status)) }}</span></td>
                    <td>{{ $transaction->creator?->name ?? 'System' }}</td>
                    <td>
                        <a href="{{ route('admin.other-transactions.show', $transaction) }}" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
                        @if($transaction->status !== 'approved')
                            @can('other_transactions.edit')
                                <a href="{{ route('admin.other-transactions.edit', $transaction) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                            @endcan
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
@push('scripts')
<script>$('#otherTxnTable').DataTable({pageLength:25, order:[[0,'desc']]});</script>
@endpush
