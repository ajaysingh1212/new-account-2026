@extends('layouts.admin')
@section('title', 'Sub Cost Centers')
@section('breadcrumb')<li class="breadcrumb-item active">Sub Cost Centers</li>@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0"><i class="fas fa-project-diagram mr-2 text-purple"></i> Sub Cost Centers</h3>
        @can('cost_centers.manage')<a href="{{ route('admin.sub-cost-centers.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Add Sub Cost Center</a>@endcan
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="subCostCentersTable" class="table table-hover">
                <thead><tr><th>Code</th><th>Name</th><th>Main Cost Center</th><th>Owner</th><th>Budget</th><th>Created By</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                @foreach($subCostCenters as $subCostCenter)
                    @php($canManage = app(\App\Services\EntryVisibilityService::class)->canManage(auth()->user(), $subCostCenter))
                    <tr>
                        <td><strong>{{ $subCostCenter->code }}</strong></td>
                        <td>{{ $subCostCenter->name }}</td>
                        <td>{{ $subCostCenter->costCenter?->name }}</td>
                        <td>{{ $subCostCenter->owner_name ?: '-' }}</td>
                        <td>Rs {{ number_format((float) $subCostCenter->budget_amount, 2) }}</td>
                        <td><strong>{{ $subCostCenter->creator?->name ?? 'System' }}</strong><br><small class="text-muted">{{ $subCostCenter->creator?->rolesForCompany($subCostCenter->company_id)->pluck('name')->join(', ') ?: 'No role' }}</small></td>
                        <td><span class="{{ $subCostCenter->status === 'active' ? 'badge-active' : 'badge-inactive' }}">{{ ucfirst($subCostCenter->status) }}</span></td>
                        <td>
                            @if($canManage)
                                @can('cost_centers.manage')<a href="{{ route('admin.sub-cost-centers.edit', $subCostCenter) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>@endcan
                                @can('cost_centers.manage')<form action="{{ route('admin.sub-cost-centers.destroy', $subCostCenter) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="button" class="btn btn-danger btn-sm btn-delete"><i class="fas fa-trash"></i></button></form>@endcan
                            @else
                                <span class="badge-admin">Read only</span>
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
<script>$('#subCostCentersTable').DataTable({ pageLength: 25, columnDefs: [{ orderable:false, targets:7 }] });</script>
@endpush
