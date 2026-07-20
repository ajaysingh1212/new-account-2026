@extends('layouts.admin')
@section('title', 'Bank Report')
@section('breadcrumb')<li class="breadcrumb-item active">Bank Report</li>@endsection

@section('content')
<div class="card">
    <div class="card-header"><h3 class="card-title m-0"><i class="fas fa-chart-line mr-2 text-purple"></i> Bank Statement Report</h3></div>
    <div class="card-body">
        <form method="GET" class="row align-items-end">
            <div class="col-md-4 form-group"><label>Bank / Cash Account</label><select name="bank_account_id" class="form-control select2" required><option value="">Select account</option>@foreach($accounts as $account)<option value="{{ $account->id }}" @selected(request('bank_account_id')==$account->id)>{{ $account->account_name }}</option>@endforeach</select></div>
            <div class="col-md-3 form-group"><label>From Date</label><input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}"></div>
            <div class="col-md-3 form-group"><label>To Date</label><input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}"></div>
            <div class="col-md-2 form-group"><button class="btn btn-primary btn-block"><i class="fas fa-filter mr-1"></i> View</button></div>
        </form>
    </div>
</div>

@if($selectedAccount)
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0">{{ $selectedAccount->account_name }}</h3>
        <strong>Current Balance: Rs {{ number_format((float) $selectedAccount->current_balance, 2) }}</strong>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="bankReportTable" class="table table-hover">
                <thead><tr><th>Date</th><th>Type</th><th>Party Details</th><th>Ledger</th><th>Related Account</th><th>Reference</th><th>Description</th><th>In</th><th>Out</th><th>Balance</th></tr></thead>
                <tbody>
                @foreach($transactions as $transaction)
                    <tr>
                        <td>{{ $transaction->transaction_date?->format('d M Y') }}</td>
                        <td>{{ str_replace('_', ' ', ucfirst($transaction->transaction_type)) }}</td>
                        <td>{{ $transaction->party?->display_name ?: '-' }}<br><small class="text-muted">{{ $transaction->party?->phone }}</small></td>
                        <td>{{ $transaction->ledger_name ?: '-' }}</td>
                        <td>{{ $transaction->relatedBankAccount?->account_name ?: '-' }}</td>
                        <td>{{ $transaction->reference_no ?: '-' }}</td>
                        <td>{{ $transaction->description }}</td>
                        <td class="text-success">{{ $transaction->direction === 'in' ? 'Rs '.number_format((float) $transaction->amount, 2) : '-' }}</td>
                        <td class="text-danger">{{ $transaction->direction === 'out' ? 'Rs '.number_format((float) $transaction->amount, 2) : '-' }}</td>
                        <td><strong>Rs {{ number_format((float) $transaction->balance_after, 2) }}</strong></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>$('#bankReportTable').DataTable({ pageLength: 25, order:[[0,'asc']] });</script>
@endpush
