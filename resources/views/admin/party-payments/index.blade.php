@extends('layouts.admin')
@section('title', $type === 'payment_out' ? 'Payment Out' : ($type === 'payment_in' ? 'Payment In' : 'Party Payments'))

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0"><i class="fas fa-money-check-alt mr-2 text-purple"></i> Party Payments</h3>
        <div>
            @can('party_payments.create')<a href="{{ route('admin.party-payments.create', ['type' => 'payment_in']) }}" class="btn btn-success btn-sm"><i class="fas fa-arrow-down mr-1"></i> Payment In</a>@endcan
            @can('party_payments.create')<a href="{{ route('admin.party-payments.create', ['type' => 'payment_out']) }}" class="btn btn-danger btn-sm"><i class="fas fa-arrow-up mr-1"></i> Payment Out</a>@endcan
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="paymentsTable" class="table table-hover">
                <thead><tr><th>Date</th><th>Type</th><th>Party</th><th>Bank/Cash</th><th>Bills</th><th>Reference</th><th>Amount</th><th>Discount</th><th>Total</th><th>Mode</th><th>Created By</th></tr></thead>
                <tbody>
                @foreach($payments as $payment)
                    <tr>
                        <td>{{ $payment->payment_date?->format('d M Y') }}</td>
                        <td>
                            <span class="{{ $payment->payment_type === 'payment_in' ? 'badge-active' : 'badge-inactive' }}">{{ str_replace('_', ' ', ucfirst($payment->payment_type)) }}</span>
                            @if($payment->advance)
                                <div class="mt-1"><span class="badge badge-info">Advance</span></div>
                            @endif
                        </td>
                        <td>{{ $payment->party?->display_name }}</td>
                        <td>{{ $payment->bankAccount?->account_name }}</td>
                        <td>
                            @forelse($payment->allocations as $allocation)
                                <div><b>{{ $allocation->bill_type === 'opening_balance' ? 'Opening Balance' : $allocation->bill_no }}</b>: Rs {{ number_format((float) $allocation->amount, 2) }}</div>
                            @empty
                                -
                            @endforelse
                        </td>
                        <td>{{ $payment->reference_no ?: '-' }}</td>
                        <td>Rs {{ number_format((float) $payment->amount, 2) }}</td>
                        <td>Rs {{ number_format((float) $payment->discount_amount, 2) }}</td>
                        <td><strong>Rs {{ number_format((float) $payment->total_amount, 2) }}</strong></td>
                        <td>{{ $payment->payment_mode ?: '-' }}</td>
                        <td><strong>{{ $payment->creator?->name ?? 'System' }}</strong><br><small class="text-muted">{{ $payment->creator?->rolesForCompany($payment->company_id)->pluck('name')->join(', ') ?: 'No role' }}</small></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>$('#paymentsTable').DataTable({pageLength:25, order:[[0,'desc']]});</script>
@endpush
