@extends('layouts.admin')
@section('title', 'Cost Centers')
@section('breadcrumb')<li class="breadcrumb-item active">Cost Centers</li>@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0"><i class="fas fa-sitemap mr-2 text-purple"></i> Main Cost Centers</h3>
        @can('cost_centers.manage')<a href="{{ route('admin.cost-centers.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Add Cost Center</a>@endcan
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="costCentersTable" class="table table-hover">
                <thead><tr><th>Code</th><th>Name</th><th>Department</th><th>Manager</th><th>Budget</th><th>Sub Centers</th><th>Created By</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                @foreach($costCenters as $costCenter)
                    @php($canManage = app(\App\Services\EntryVisibilityService::class)->canManage(auth()->user(), $costCenter))
                    <tr>
                        <td><strong>{{ $costCenter->code }}</strong></td>
                        <td>{{ $costCenter->name }}</td>
                        <td>{{ $costCenter->department ?: '-' }}</td>
                        <td>{{ $costCenter->manager_name ?: '-' }}</td>
                        <td>Rs {{ number_format((float) $costCenter->budget_amount, 2) }}</td>
                        <td><span class="badge-admin">{{ $costCenter->sub_cost_centers_count }}</span></td>
                        <td><strong>{{ $costCenter->creator?->name ?? 'System' }}</strong><br><small class="text-muted">{{ $costCenter->creator?->rolesForCompany($costCenter->company_id)->pluck('name')->join(', ') ?: 'No role' }}</small></td>
                        <td><span class="{{ $costCenter->status === 'active' ? 'badge-active' : 'badge-inactive' }}">{{ ucfirst($costCenter->status) }}</span></td>
                        <td>
                            @if($canManage)
                                @can('cost_centers.manage')<a href="{{ route('admin.cost-centers.edit', $costCenter) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>@endcan
                                @can('cost_centers.manage')<form action="{{ route('admin.cost-centers.destroy', $costCenter) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="button" class="btn btn-danger btn-sm btn-delete"><i class="fas fa-trash"></i></button></form>@endcan
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
<script>$('#costCentersTable').DataTable({ pageLength: 25, columnDefs: [{ orderable:false, targets:8 }] });</script>
@endpush
