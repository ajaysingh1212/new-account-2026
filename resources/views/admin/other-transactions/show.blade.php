@extends('layouts.admin')
@section('title', 'Other Income / Expense Details')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h3 class="card-title m-0">{{ $otherTransaction->transaction_no }}</h3>
        <div>
            <a href="{{ route('admin.other-transactions.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
            @if($otherTransaction->status !== 'approved' && auth()->user()->can('other_transactions.edit'))
                <a href="{{ route('admin.other-transactions.edit', $otherTransaction) }}" class="btn btn-warning btn-sm">Edit</a>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <table class="table">
                    <tr><th>Type</th><td>{{ ucfirst($otherTransaction->transaction_kind) }}</td></tr>
                    <tr><th>Ledger</th><td>{{ $otherTransaction->ledger?->name }}</td></tr>
                    <tr><th>Date</th><td>{{ $otherTransaction->transaction_date?->format('d M Y') }}</td></tr>
                    <tr><th>Party / Narration</th><td>{{ $otherTransaction->party_name ?: '-' }}</td></tr>
                    <tr><th>Bank</th><td>{{ $otherTransaction->bankAccount?->account_name ?: '-' }}</td></tr>
                    <tr><th>Reference</th><td>{{ $otherTransaction->reference_no ?: '-' }}</td></tr>
                    <tr><th>Amount</th><td>Rs {{ number_format((float) $otherTransaction->amount, 2) }}</td></tr>
                    <tr><th>Tax</th><td>Rs {{ number_format((float) $otherTransaction->tax_amount, 2) }}</td></tr>
                    <tr><th>Total</th><td><b>Rs {{ number_format((float) $otherTransaction->total_amount, 2) }}</b></td></tr>
                    <tr><th>Description</th><td>{!! nl2br(e($otherTransaction->description)) !!}</td></tr>
                </table>
            </div>
            <div class="col-md-4">
                <div class="p-3 rounded bg-light">
                    <small>Status</small>
                    <h4>{{ str_replace('_', ' ', ucfirst($otherTransaction->status)) }}</h4>
                    <p>Created by {{ $otherTransaction->creator?->name ?? 'System' }}</p>
                    @if($otherTransaction->attachment)
                        <a href="{{ asset('storage/'.$otherTransaction->attachment) }}" target="_blank" class="btn btn-outline-primary btn-sm">View Attachment</a>
                    @endif
                </div>

                @can('other_transactions.approve')
                    @if($otherTransaction->status === 'pending_approval')
                        <form method="POST" action="{{ route('admin.other-transactions.approve', $otherTransaction) }}" class="mt-3">
                            @csrf
                            <textarea name="approval_note" class="form-control mb-2" placeholder="Approval note"></textarea>
                            <button class="btn btn-success btn-block">Approve & Post Bank</button>
                        </form>
                        <form method="POST" action="{{ route('admin.other-transactions.reject', $otherTransaction) }}" class="mt-2">
                            @csrf
                            <textarea name="rejection_reason" class="form-control mb-2" placeholder="Reason" required></textarea>
                            <button class="btn btn-danger btn-block">Reject</button>
                        </form>
                    @endif
                @endcan

                @if($otherTransaction->status === 'approved')
                    <div class="alert alert-success mt-3">
                        Approved by {{ $otherTransaction->approver?->name }} on {{ $otherTransaction->approved_at?->format('d M Y h:i A') }}
                        <hr>
                        Ledger balance after: Rs {{ number_format((float) $otherTransaction->ledger_balance_after, 2) }}<br>
                        Bank balance after: Rs {{ number_format((float) $otherTransaction->bank_balance_after, 2) }}
                    </div>
                @endif
                @if($otherTransaction->status === 'rejected')
                    <div class="alert alert-danger mt-3">{{ $otherTransaction->rejection_reason }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header"><h3 class="card-title m-0">Ledger Statement</h3></div>
    <div class="card-body table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Date</th><th>No</th><th>Type</th><th>In</th><th>Out</th><th>Balance After</th><th>Bank</th>
                </tr>
            </thead>
            <tbody>
            @foreach($ledgerHistory as $row)
                <tr>
                    <td>{{ $row->transaction_date?->format('d M Y') }}</td>
                    <td>{{ $row->transaction_no }}</td>
                    <td>{{ ucfirst($row->transaction_kind) }}</td>
                    <td class="text-success">{{ $row->transaction_kind === 'income' ? 'Rs '.number_format((float) $row->total_amount, 2) : '-' }}</td>
                    <td class="text-danger">{{ $row->transaction_kind === 'expense' ? 'Rs '.number_format((float) $row->total_amount, 2) : '-' }}</td>
                    <td>Rs {{ number_format((float) $row->ledger_balance_after, 2) }}</td>
                    <td>{{ $row->bankAccount?->account_name ?: '-' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
