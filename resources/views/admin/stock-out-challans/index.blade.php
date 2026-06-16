@extends('layouts.admin')
@section('title','Special Stock Out')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h3 class="card-title m-0">Special Stock Out</h3>
            <small class="text-muted">Stock out without party ledger, for bill/payment later cases.</small>
        </div>
        @can('stock_out_challans.create')<a href="{{ route('admin.stock-out-challans.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i>New Stock Out</a>@endcan
    </div>
    <div class="card-body table-responsive">
        <table id="stockOutTable" class="table table-hover">
            <thead><tr><th>No</th><th>Date</th><th>Party / Name</th><th>Qty</th><th>Value</th><th>Created By</th><th>IP</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            @foreach($challans as $challan)
                @php($canManage = app(\App\Services\EntryVisibilityService::class)->canManage(auth()->user(), $challan))
                <tr>
                    <td><b>{{ $challan->challan_no }}</b></td>
                    <td>{{ $challan->challan_date?->format('d M Y') }}<br><small>{{ $challan->created_at?->format('d M Y h:i A') }}</small></td>
                    <td>{{ $challan->display_party }}<br><small class="text-muted">{{ $challan->phone ?: '-' }}</small></td>
                    <td>{{ number_format($challan->items->sum(fn($line) => (float)$line->quantity),3) }}</td>
                    <td>Rs {{ number_format((float)$challan->grand_total,2) }}</td>
                    <td>{{ $challan->creator?->name ?? 'System' }}<br><small class="text-muted">{{ $challan->user_role ?: '-' }}</small></td>
                    <td>{{ $challan->ip_address ?: '-' }}</td>
                    <td><span class="badge badge-{{ $challan->status === 'issued' ? 'success' : 'secondary' }}">{{ ucfirst($challan->status) }}</span></td>
                    <td>
                        <a href="{{ route('admin.stock-out-challans.show', $challan) }}" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
                        @if($canManage && $challan->status !== 'cancelled') @can('stock_out_challans.edit')<a href="{{ route('admin.stock-out-challans.edit', $challan) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>@endcan @endif
                        @can('stock_out_challans.print')<a href="{{ route('admin.stock-out-challans.print', $challan) }}" class="btn btn-secondary btn-sm" target="_blank"><i class="fas fa-print"></i></a>@endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
@push('scripts')<script>$('#stockOutTable').DataTable({pageLength:25,order:[[1,'desc']],columnDefs:[{orderable:false,targets:8}]});</script>@endpush
