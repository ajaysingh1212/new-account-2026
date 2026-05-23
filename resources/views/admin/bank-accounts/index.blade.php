@extends('layouts.admin')
@section('title', 'Bank - Cash & Assets')
@section('breadcrumb')<li class="breadcrumb-item active">Bank Accounts</li>@endsection

@section('content')
<div class="row">
    <div class="col-6 col-lg-3 mb-4"><div class="stat-card stat-purple"><div class="stat-icon"><i class="fas fa-wallet"></i></div><div class="stat-value">{{ $summary['total'] }}</div><div class="stat-label">Accounts</div></div></div>
    <div class="col-6 col-lg-3 mb-4"><div class="stat-card stat-cyan"><div class="stat-icon"><i class="fas fa-university"></i></div><div class="stat-value">₹ {{ number_format($summary['bank'], 2) }}</div><div class="stat-label">Bank Balance</div></div></div>
    <div class="col-6 col-lg-3 mb-4"><div class="stat-card stat-green"><div class="stat-icon"><i class="fas fa-money-bill"></i></div><div class="stat-value">₹ {{ number_format($summary['cash'], 2) }}</div><div class="stat-label">Cash Balance</div></div></div>
    <div class="col-6 col-lg-3 mb-4"><div class="stat-card stat-orange"><div class="stat-icon"><i class="fas fa-print"></i></div><div class="stat-value">{{ $summary['print'] }}</div><div class="stat-label">Print on Bill</div></div></div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0"><i class="fas fa-piggy-bank mr-2 text-purple"></i> Bank & Cash Accounts</h3>
        @can('banking.manage')<a href="{{ route('admin.bank-accounts.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Add Account</a>@endcan
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="bankAccountsTable" class="table table-hover">
                <thead><tr><th>Code</th><th>Account</th><th>Type</th><th>Bank</th><th>Account No.</th><th>Balance</th><th>Flags</th><th>Created By</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                @foreach($accounts as $account)
                    <tr>
                        <td><strong>{{ $account->account_code }}</strong></td>
                        <td><div style="font-weight:700;color:#1A0A3D;">{{ $account->account_name }}</div><div style="font-size:12px;color:#9090B0;">{{ $account->upi_id }}</div></td>
                        <td><span class="badge-admin">{{ ucfirst($account->account_type) }}</span></td>
                        <td>{{ $account->bank_name ?: '—' }}</td>
                        <td>{{ $account->masked_account_number }}</td>
                        <td><strong>₹ {{ number_format((float) $account->current_balance, 2) }}</strong></td>
                        <td>
                            @if($account->is_primary)<span class="badge-active">Main</span>@endif
                            @if($account->print_on_invoice)<span class="badge-admin">Bill Print</span>@endif
                        </td>
                        <td><strong>{{ $account->creator?->name ?? 'System' }}</strong><div style="font-size:12px;color:#9090B0;">{{ $account->creator?->rolesForCompany($account->company_id)->pluck('name')->join(', ') ?: 'No role' }}</div></td>
                        <td><span class="{{ $account->status === 'active' ? 'badge-active' : 'badge-inactive' }}">{{ ucfirst($account->status) }}</span></td>
                        <td>
                            @php($canManage = app(\App\Services\EntryVisibilityService::class)->canManage(auth()->user(), $account))
                            <a href="{{ route('admin.bank-accounts.show', $account) }}" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
                            @if($canManage)
                            <a href="{{ route('admin.bank-accounts.edit', $account) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('admin.bank-accounts.destroy', $account) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="button" class="btn btn-danger btn-sm btn-delete"><i class="fas fa-trash"></i></button></form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>$('#bankAccountsTable').DataTable({ pageLength: 25, columnDefs: [{ orderable:false, targets:9 }] });</script>
@endpush
