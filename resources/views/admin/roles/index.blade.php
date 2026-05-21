@extends('layouts.admin')
@section('title', 'Roles')
@section('breadcrumb')
    <li class="breadcrumb-item active">Roles</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h3 class="card-title m-0"><i class="fas fa-briefcase me-2 text-purple"></i> All Roles</h3>
        <a href="{{ route('admin.roles.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i> Add Role
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="rolesTable" class="table table-hover">
                <thead>
                    <tr><th>#</th><th>Role Name</th><th>Company</th><th>Permissions</th><th>Users</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                @foreach($roles as $role)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        <div style="font-weight:600;">{{ $role->name }}</div>
                        <div style="font-size:11px;color:#9090B0;">{{ $role->description }}</div>
                    </td>
                    <td>{{ $role->company?->name ?? '—' }}</td>
                    <td>
                        @foreach($role->permissions->take(3) as $perm)
                            <span class="badge" style="background:rgba(6,182,212,0.1);color:#0891B2;border-radius:6px;font-size:10px;margin:1px;">{{ $perm->slug }}</span>
                        @endforeach
                        @if($role->permissions->count() > 3)
                            <span style="font-size:11px;color:#9090B0;">+{{ $role->permissions->count()-3 }}</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge" style="background:rgba(124,58,237,0.1);color:#7C3AED;border-radius:20px;padding:4px 10px;">
                            {{ $role->user_roles_count ?? 0 }} users
                        </span>
                    </td>
                    <td><span class="{{ $role->is_active ? 'badge-active' : 'badge-inactive' }}">{{ $role->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td>
                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                        <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="d-inline">
                            @csrf @method('DELETE')
                            <button type="button" class="btn btn-danger btn-sm btn-delete"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $roles->links() }}
    </div>
</div>
@endsection
@push('scripts')
<script>$('#rolesTable').DataTable({paging:false,searching:true,info:false,columnDefs:[{orderable:false,targets:6}]});</script>
@endpush
