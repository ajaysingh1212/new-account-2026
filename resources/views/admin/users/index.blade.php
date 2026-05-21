@extends('layouts.admin')
@section('title', 'Users')
@section('breadcrumb')
    <li class="breadcrumb-item active">Users</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0"><i class="fas fa-users me-2 text-purple"></i> All Users</h3>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i> Add User
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="usersTable" class="table table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Email</th>
                        <th>Type</th>
                        <th>Company</th>
                        <th>Roles</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($users as $user)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            @if($user->profile_pic)
                                <img src="{{ $user->profile_pic_url }}" class="user-avatar me-2">
                            @else
                                <div class="user-avatar me-2">{{ substr($user->name,0,1) }}</div>
                            @endif
                            <div>
                                <div style="font-weight:600;font-size:13px;">{{ $user->name }}</div>
                                <div style="font-size:11px;color:#9090B0;">{{ $user->phone }}</div>
                            </div>
                        </div>
                    </td>
                    <td>{{ $user->email }}</td>
                    <td>
                        @if($user->user_type === 'super_admin') <span class="badge-super">⭐ Super Admin</span>
                        @elseif($user->user_type === 'admin') <span class="badge-admin">🏢 Admin</span>
                        @else <span class="badge-user">👤 User</span>
                        @endif
                    </td>
                    <td>{{ $user->currentCompany?->name ?? '—' }}</td>
                    <td>
                        @foreach($user->userRoles->take(2) as $ur)
                            <span class="badge" style="background:rgba(124,58,237,0.1);color:#7C3AED;border-radius:6px;font-size:11px;">{{ $ur->role?->name }}</span>
                        @endforeach
                        @if($user->userRoles->count() > 2)
                            <span style="font-size:11px;color:#9090B0;">+{{ $user->userRoles->count()-2 }} more</span>
                        @endif
                    </td>
                    <td>
                        <span class="{{ $user->is_active ? 'badge-active' : 'badge-inactive' }}">
                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning btn-sm" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        @if(!$user->isSuperAdmin())
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline">
                            @csrf @method('DELETE')
                            <button type="button" class="btn btn-danger btn-sm btn-delete" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $users->links() }}</div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$('#usersTable').DataTable({ paging: false, searching: true, info: false, columnDefs: [{ orderable: false, targets: 7 }] });
</script>
@endpush
