@extends('layouts.admin')
@section('title','Delivery Challans')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h3 class="card-title m-0">Delivery Challans</h3>
        <a href="{{ route('admin.delivery-challans.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Add Challan</a>
    </div>
    <div class="card-body table-responsive">
        <table id="challansTable" class="table table-hover">
            <thead><tr><th>No</th><th>Date</th><th>Party</th><th>Created By</th><th>Vehicle</th><th>Dispatch</th><th>Serials</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            @foreach($challans as $challan)
                @php($canManage = app(\App\Services\EntryVisibilityService::class)->canManage(auth()->user(), $challan))
                @php($serialUnits = collect($challan->items ?? [])->flatMap(fn($line) => collect($line->selected_units ?? [])->filter(fn($unit) => is_array($unit)))->values())
                <tr>
                    <td><strong>{{ $challan->challan_no }}</strong></td>
                    <td>{{ $challan->challan_date?->format('d M Y') }}</td>
                    <td>{{ $challan->party?->display_name ?: 'Walk-in' }}</td>
                    <td>{{ $challan->creator?->name ?? 'System' }}<br><small class="text-muted">{{ $challan->creator?->rolesForCompany($challan->company_id)->pluck('name')->join(', ') }}</small></td>
                    <td>{{ $challan->vehicle_no ?: '-' }}</td>
                    <td>{{ $challan->dispatch_through ?: '-' }}</td>
                    <td>
                        @forelse($serialUnits->take(6) as $unit)
                            <span class="badge badge-info mr-1 mb-1">{{ $unit['serial_no'] ?? $unit['vts_sim'] ?? $unit['sku'] ?? $unit['key'] ?? 'Unit' }}</span>
                        @empty
                            <span class="text-muted">-</span>
                        @endforelse
                        @if($serialUnits->count() > 6)<small class="text-muted">+{{ $serialUnits->count() - 6 }} more</small>@endif
                    </td>
                    <td><span class="badge-active">{{ ucfirst($challan->status) }}</span>@if($challan->convertedInvoice)<div><span class="badge badge-success mt-1">Converted</span></div>@endif</td>
                    <td>
                        <a href="{{ route('admin.delivery-challans.show', $challan) }}" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
                        @if($canManage && $challan->status !== 'cancelled' && !$challan->convertedInvoice && auth()->user()->can('delivery_challans.edit'))
                            <form action="{{ route('admin.delivery-challans.convert', $challan) }}" method="POST" class="d-inline" onsubmit="return confirm('Convert this delivery challan to sale? Stock will not change again.');">@csrf <button class="btn btn-success btn-sm"><i class="fas fa-sync"></i></button></form>
                            <a href="{{ route('admin.delivery-challans.edit', $challan) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                        @endif
                        @can('delivery_challans.print')<a href="{{ route('admin.delivery-challans.print', $challan) }}" class="btn btn-secondary btn-sm" target="_blank"><i class="fas fa-print"></i></a>@endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
@push('scripts')<script>$('#challansTable').DataTable({pageLength:25, columnDefs:[{orderable:false, targets:8}]});</script>@endpush
