@extends('layouts.admin')
@section('title','Estimates')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h3 class="card-title m-0">Estimate / Quotation</h3>
        <a href="{{ route('admin.estimates.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Add Estimate</a>
    </div>
    <div class="card-body table-responsive">
        <table id="estimatesTable" class="table table-hover">
            <thead><tr><th>No</th><th>Date</th><th>Valid Until</th><th>Party</th><th>Created By</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            @foreach($estimates as $estimate)
                @php($canManage = app(\App\Services\EntryVisibilityService::class)->canManage(auth()->user(), $estimate))
                <tr>
                    <td><strong>{{ $estimate->estimate_no }}</strong></td>
                    <td>{{ $estimate->estimate_date?->format('d M Y') }}</td>
                    <td>{{ $estimate->valid_until?->format('d M Y') ?: '-' }}</td>
                    <td>{{ $estimate->party?->display_name ?: 'Walk-in' }}</td>
                    <td>{{ $estimate->creator?->name ?? 'System' }}<br><small class="text-muted">{{ $estimate->creator?->rolesForCompany($estimate->company_id)->pluck('name')->join(', ') }}</small></td>
                    <td>Rs {{ number_format((float) $estimate->grand_total, 2) }}</td>
                    <td>
                        <span class="badge-active">{{ ucfirst($estimate->status) }}</span>
                        @if($estimate->convertedInvoice)
                            <div><span class="badge badge-success mt-1">Converted</span></div>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.estimates.show', $estimate) }}" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
                        @if($canManage && $estimate->status !== 'converted' && $estimate->status !== 'cancelled' && auth()->user()->can('estimates.convert'))
                            <a href="{{ route('admin.estimates.convert-form', $estimate) }}" class="btn btn-success btn-sm"><i class="fas fa-sync mr-1"></i>Convert</a>
                        @endif
                        @if($canManage && $estimate->status !== 'converted' && auth()->user()->can('estimates.edit'))<a href="{{ route('admin.estimates.edit', $estimate) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>@endif
                        @can('estimates.print')<a href="{{ route('admin.estimates.print', $estimate) }}" class="btn btn-secondary btn-sm" target="_blank"><i class="fas fa-print"></i></a>@endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
@push('scripts')<script>$('#estimatesTable').DataTable({pageLength:25, columnDefs:[{orderable:false, targets:7}]});</script>@endpush
