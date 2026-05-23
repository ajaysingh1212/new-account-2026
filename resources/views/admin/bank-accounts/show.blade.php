@extends('layouts.admin')
@section('title', 'Bank Account Statement')
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('admin.bank-accounts.index') }}">Bank Accounts</a></li><li class="breadcrumb-item active">{{ $bankAccount->account_name }}</li>@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0"><i class="fas fa-university mr-2 text-purple"></i> {{ $bankAccount->account_name }}</h3>
        <a href="{{ route('admin.bank-reports.statement', ['bank_account_id' => $bankAccount->id]) }}" class="btn btn-primary btn-sm"><i class="fas fa-chart-line mr-1"></i> Full Report</a>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3"><strong>Current Balance</strong><br><span style="font-size:24px;font-weight:800;">₹ {{ number_format((float) $bankAccount->current_balance, 2) }}</span></div>
            <div class="col-md-3"><strong>Bank</strong><br>{{ $bankAccount->bank_name ?: '—' }}</div>
            <div class="col-md-3"><strong>Account No.</strong><br>{{ $bankAccount->masked_account_number }}</div>
            <div class="col-md-3"><strong>IFSC / UPI</strong><br>{{ $bankAccount->ifsc_code ?: $bankAccount->upi_id ?: '—' }}</div>
        </div>
        <div class="table-responsive">
            <table id="accountLedgerTable" class="table table-hover">
                <thead><tr><th>Date</th><th>Type</th><th>Direction</th><th>Related</th><th>Party</th><th>Reference</th><th>Amount</th><th>Balance</th></tr></thead>
                <tbody>
                @foreach($bankAccount->transactions as $transaction)
                    <tr>
                        <td>{{ $transaction->transaction_date?->format('d M Y') }}</td>
                        <td>{{ str_replace('_', ' ', ucfirst($transaction->transaction_type)) }}</td>
                        <td><span class="{{ $transaction->direction === 'in' ? 'badge-active' : 'badge-inactive' }}">{{ strtoupper($transaction->direction) }}</span></td>
                        <td>{{ $transaction->relatedBankAccount?->account_name ?: '—' }}</td>
                        <td>{{ $transaction->party?->display_name ?: '—' }}</td>
                        <td>{{ $transaction->reference_no ?: '—' }}</td>
                        <td>₹ {{ number_format((float) $transaction->amount, 2) }}</td>
                        <td>₹ {{ number_format((float) $transaction->balance_after, 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>$('#accountLedgerTable').DataTable({ pageLength: 25 });</script>
@endpush
