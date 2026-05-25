@extends('layouts.admin')
@section('title','Purchases')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h3 class="card-title m-0">Purchase Bills</h3>
        <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary btn-sm">Add Purchase</a>
    </div>
    <div class="card-body table-responsive">
        <table id="purchasesTable" class="table table-hover">
            <thead><tr><th>No</th><th>Date</th><th>Party</th><th>Created By</th><th>Type</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            @foreach($bills as $bill)
                <tr>
                    <td>{{ $bill->invoice_no }}</td>
                    <td>{{ $bill->billing_date?->format('d M Y') }}</td>
                    <td>{{ $bill->party?->display_name ?: 'Cash' }}</td>
                    <td>{{ $bill->creator?->name ?? 'System' }}<br><small class="text-muted">{{ $bill->creator?->rolesForCompany($bill->company_id)->pluck('name')->join(', ') }}</small></td>
                    <td>{{ ucfirst($bill->purchase_type) }}</td>
                    <td>Rs {{ number_format((float)$bill->grand_total,2) }}</td>
                    <td><span class="badge-active">{{ ucfirst($bill->status) }}</span></td>
                    <td><a href="{{ route('admin.purchases.show',$bill) }}" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a> @can('purchase.edit')<a href="{{ route('admin.purchases.edit',$bill) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>@endcan @can('purchase.print')<a href="{{ route('admin.purchases.print',$bill) }}" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-print"></i></a>@endcan</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
@push('scripts')<script>$('#purchasesTable').DataTable({pageLength:25, columnDefs:[{orderable:false, targets:7}]});</script>@endpush
