@extends('layouts.admin')
@section('title', 'Bank Transactions')
@section('breadcrumb')<li class="breadcrumb-item active">Bank Transactions</li>@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0"><i class="fas fa-exchange-alt mr-2 text-purple"></i> Bank Transactions</h3>
        @can('banking.manage')<a href="{{ route('admin.bank-transactions.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> New Transaction</a>@endcan
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="bankTransactionsTable" class="table table-hover">
                <thead><tr><th>Date</th><th>Account</th><th>Type</th><th>Direction</th><th>Related</th><th>Party</th><th>Reference</th><th>Amount</th><th>Balance</th><th>Created By</th></tr></thead>
                <tbody>
                @foreach($transactions as $transaction)
                    <tr>
                        <td>{{ $transaction->transaction_date?->format('d M Y') }}</td>
                        <td>{{ $transaction->bankAccount?->account_name }}</td>
                        <td>{{ str_replace('_', ' ', ucfirst($transaction->transaction_type)) }}</td>
                        <td><span class="{{ $transaction->direction === 'in' ? 'badge-active' : 'badge-inactive' }}">{{ strtoupper($transaction->direction) }}</span></td>
                        <td>{{ $transaction->relatedBankAccount?->account_name ?: '-' }}</td>
                        <td>{{ $transaction->party?->display_name ?: '-' }}</td>
                        <td>{{ $transaction->reference_no ?: '-' }}</td>
                        <td>Rs {{ number_format((float) $transaction->amount, 2) }}</td>
                        <td>Rs {{ number_format((float) $transaction->balance_after, 2) }}</td>
                        <td><strong>{{ $transaction->creator?->name ?? 'System' }}</strong><br><small class="text-muted">{{ $transaction->creator?->rolesForCompany($transaction->company_id)->pluck('name')->join(', ') ?: 'No role' }}</small></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>$('#bankTransactionsTable').DataTable({ pageLength: 25, order:[[0,'desc']] });</script>
@endpush
