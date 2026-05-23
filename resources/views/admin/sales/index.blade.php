@extends('layouts.admin')
@section('title','Sales')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h3 class="card-title m-0">Sales Invoices</h3>
        <a href="{{ route('admin.sales.create') }}" class="btn btn-primary btn-sm">Add Sale</a>
    </div>
    <div class="card-body table-responsive">
        <table id="salesTable" class="table table-hover">
            <thead><tr><th>No</th><th>Date</th><th>Party</th><th>Created By</th><th>Type</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            @foreach($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->invoice_no }}</td>
                    <td>{{ $invoice->billing_date?->format('d M Y') }}</td>
                    <td>{{ $invoice->party?->display_name ?: 'Cash' }}</td>
                    <td>{{ $invoice->creator?->name ?? 'System' }}<br><small class="text-muted">{{ $invoice->creator?->rolesForCompany($invoice->company_id)->pluck('name')->join(', ') }}</small></td>
                    <td>{{ ucfirst($invoice->sale_type) }}</td>
                    <td>Rs {{ number_format((float)$invoice->grand_total,2) }}</td>
                    <td><span class="badge-active">{{ ucfirst($invoice->status) }}</span></td>
                    <td><a href="{{ route('admin.sales.show',$invoice) }}" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a> @can('sales.print')<a href="{{ route('admin.sales.print',$invoice) }}" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-print"></i></a>@endcan</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
@push('scripts')<script>$('#salesTable').DataTable({pageLength:25, columnDefs:[{orderable:false, targets:7}]});</script>@endpush
