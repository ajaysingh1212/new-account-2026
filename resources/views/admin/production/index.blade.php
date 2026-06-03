@extends('layouts.admin')
@section('title','Production Batches')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h3 class="card-title m-0">Production / CRM Assembly</h3>
        @can('production.create')
            <a href="{{ route('admin.production-batches.create') }}" class="btn btn-primary btn-sm">New Batch</a>
        @endcan
    </div>
    <div class="card-body table-responsive">
        <table id="prodTable" class="table table-hover">
            <thead>
                <tr>
                    <th>Batch</th>
                    <th>Date</th>
                    <th>Finished Item</th>
                    <th>Qty</th>
                    <th>VTS/SIM No.</th>
                    <th>Raw Cost</th>
                    <th>Cost/Unit</th>
                    <th>Status</th>
                    <th>Created By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @foreach($batches as $batch)
                <tr>
                    <td>{{ $batch->batch_no }}</td>
                    <td>{{ $batch->production_date?->format('d M Y') }}</td>
                    <td>{{ $batch->finishedItem?->name }}</td>
                    <td>{{ $batch->quantity }}</td>
                    <td>{{ collect($batch->units_data ?? [])->pluck('vts_sim')->filter()->join(', ') ?: '-' }}</td>
                    <td>Rs {{ number_format((float) $batch->raw_material_cost, 2) }}</td>
                    <td>Rs {{ number_format((float) $batch->cost_per_unit, 2) }}</td>
                    <td>
                        <span class="badge badge-{{ $batch->status === 'reverted' ? 'secondary' : 'success' }}">
                            {{ ucfirst($batch->status ?? 'posted') }}
                        </span>
                    </td>
                    <td>
                        <strong>{{ $batch->creator?->name ?? 'System' }}</strong><br>
                        <small class="text-muted">{{ $batch->creator?->rolesForCompany($batch->company_id)->pluck('name')->join(', ') ?: 'No role' }}</small>
                    </td>
                    <td class="text-nowrap">
                        <a href="{{ route('admin.production-batches.show', $batch) }}" class="btn btn-info btn-sm" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        @can('production.create')
                            @if(($batch->status ?? 'posted') !== 'reverted')
                                <a href="{{ route('admin.production-batches.edit', $batch) }}" class="btn btn-warning btn-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.production-batches.revert', $batch) }}" method="POST" class="d-inline" onsubmit="return confirm('Revert this production batch? Finished goods stock will be removed and raw material stock will be restored.');">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-sm" title="Revert Production">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                </form>
                            @endif
                        @endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
@push('scripts')
<script>$('#prodTable').DataTable();</script>
@endpush
