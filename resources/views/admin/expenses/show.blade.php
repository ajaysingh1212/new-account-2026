@extends('layouts.admin')
@section('title','Expense Details')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between"><h3 class="card-title m-0">{{ $expense->expense_no }}</h3><a href="{{ route('admin.expenses.index') }}" class="btn btn-outline-secondary btn-sm">Back</a></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <table class="table"><tr><th>Ledger</th><td>{{ $expense->ledger?->name }}</td></tr><tr><th>Date</th><td>{{ $expense->expense_date?->format('d M Y') }}</td></tr><tr><th>Vendor</th><td>{{ $expense->vendor_name ?: '-' }}</td></tr><tr><th>Bank</th><td>{{ $expense->bankAccount?->account_name }}</td></tr><tr><th>Amount</th><td>Rs {{ number_format((float)$expense->amount,2) }}</td></tr><tr><th>Tax</th><td>Rs {{ number_format((float)$expense->tax_amount,2) }}</td></tr><tr><th>Total</th><td><b>Rs {{ number_format((float)$expense->total_amount,2) }}</b></td></tr><tr><th>Description</th><td>{!! nl2br(e($expense->description)) !!}</td></tr></table>
            </div>
            <div class="col-md-4">
                <div class="p-3 rounded bg-light"><small>Status</small><h4>{{ str_replace('_',' ',ucfirst($expense->status)) }}</h4><p>Created by {{ $expense->creator?->name ?? 'System' }}</p>@if($expense->attachment)<a target="_blank" href="{{ asset('storage/'.$expense->attachment) }}" class="btn btn-outline-primary btn-sm">View Attachment</a>@endif</div>
                @can('expenses.approve')
                    @if($expense->status === 'pending_approval')
                    <form method="POST" action="{{ route('admin.expenses.approve',$expense) }}" class="mt-3">@csrf<textarea name="approval_note" class="form-control mb-2" placeholder="Approval note"></textarea><button class="btn btn-success btn-block">Approve & Post Bank</button></form>
                    <form method="POST" action="{{ route('admin.expenses.reject',$expense) }}" class="mt-2">@csrf<textarea name="rejection_reason" class="form-control mb-2" placeholder="Reason" required></textarea><button class="btn btn-danger btn-block">Reject</button></form>
                    @endif
                @endcan
                @if($expense->status === 'approved')<div class="alert alert-success mt-3">Approved by {{ $expense->approver?->name }} on {{ $expense->approved_at?->format('d M Y h:i A') }}</div>@endif
                @if($expense->status === 'rejected')<div class="alert alert-danger mt-3">{{ $expense->rejection_reason }}</div>@endif
            </div>
        </div>
    </div>
</div>
@endsection
