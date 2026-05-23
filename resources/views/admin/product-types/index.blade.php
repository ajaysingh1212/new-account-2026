@extends('layouts.admin')
@section('title','Product Types')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h3 class="card-title m-0">Product Types</h3>
        @can('product_types.manage')<a href="{{ route('admin.product-types.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add</a>@endcan
    </div>
    <div class="card-body table-responsive">
        <table id="typesTable" class="table table-hover">
            <thead><tr><th>Code</th><th>Name</th><th>Nature</th><th>Created By</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            @foreach($types as $type)
                @php($canManage = app(\App\Services\EntryVisibilityService::class)->canManage(auth()->user(), $type))
                <tr>
                    <td><b>{{ $type->code }}</b></td>
                    <td>{{ $type->name }}</td>
                    <td>{{ str_replace('_',' ',ucfirst($type->nature)) }}</td>
                    <td><strong>{{ $type->creator?->name ?? 'System' }}</strong><br><small class="text-muted">{{ $type->creator?->rolesForCompany($type->company_id)->pluck('name')->join(', ') ?: 'No role' }}</small></td>
                    <td><span class="{{ $type->status==='active'?'badge-active':'badge-inactive' }}">{{ ucfirst($type->status) }}</span></td>
                    <td>
                        @if($canManage)
                            @can('product_types.manage')<a class="btn btn-warning btn-sm" href="{{ route('admin.product-types.edit',$type) }}"><i class="fas fa-edit"></i></a>@endcan
                            @can('product_types.manage')<form class="d-inline" method="POST" action="{{ route('admin.product-types.destroy',$type) }}">@csrf @method('DELETE')<button type="button" class="btn btn-danger btn-sm btn-delete"><i class="fas fa-trash"></i></button></form>@endcan
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
@endsection
@push('scripts')<script>$('#typesTable').DataTable({columnDefs:[{orderable:false, targets:5}]});</script>@endpush
