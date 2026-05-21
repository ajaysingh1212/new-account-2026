@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')

<!-- ── Stats Cards ──────────────────────────── -->
<div class="row">
    @if(auth()->user()->isSuperAdmin())
    <div class="col-6 col-lg-3 mb-4">
        <div class="stat-card stat-purple">
            <div class="stat-icon"><i class="fas fa-building"></i></div>
            <div class="stat-value" data-target="{{ $stats['companies'] ?? 0 }}">0</div>
            <div class="stat-label">Total Companies</div>
            <div class="stat-change">🟢 {{ $stats['active_companies'] ?? 0 }} Active</div>
        </div>
    </div>
    @endif

    <div class="col-6 col-lg-3 mb-4">
        <div class="stat-card stat-cyan">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-value" data-target="{{ $stats['users'] ?? 0 }}">0</div>
            <div class="stat-label">Total Users</div>
        </div>
    </div>

    <div class="col-6 col-lg-3 mb-4">
        <div class="stat-card stat-pink">
            <div class="stat-icon"><i class="fas fa-briefcase"></i></div>
            <div class="stat-value" data-target="{{ $stats['roles'] ?? 0 }}">0</div>
            <div class="stat-label">Total Roles</div>
        </div>
    </div>

    @if(auth()->user()->isSuperAdmin())
    <div class="col-6 col-lg-3 mb-4">
        <div class="stat-card stat-green">
            <div class="stat-icon"><i class="fas fa-user-shield"></i></div>
            <div class="stat-value" data-target="{{ $stats['admins'] ?? 0 }}">0</div>
            <div class="stat-label">Company Admins</div>
        </div>
    </div>
    @endif
</div>

<!-- ── Quick Actions ───────────────────────── -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title m-0"><i class="fas fa-bolt text-warning me-2"></i> Quick Actions</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    @if(auth()->user()->isSuperAdmin())
                    <div class="col-6 col-md-3 mb-3">
                        <a href="{{ route('admin.companies.create') }}" class="btn btn-primary btn-block py-3">
                            <i class="fas fa-plus-circle d-block mb-1" style="font-size:20px"></i>
                            Add Company
                        </a>
                    </div>
                    @endif
                    <div class="col-6 col-md-3 mb-3">
                        <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-block py-3">
                            <i class="fas fa-user-plus d-block mb-1" style="font-size:20px"></i>
                            Add User
                        </a>
                    </div>
                    <div class="col-6 col-md-3 mb-3">
                        <a href="{{ route('admin.roles.create') }}" class="btn btn-primary btn-block py-3">
                            <i class="fas fa-briefcase d-block mb-1" style="font-size:20px"></i>
                            Add Role
                        </a>
                    </div>
                    <div class="col-6 col-md-3 mb-3">
                        <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-primary btn-block py-3">
                            <i class="fas fa-file-alt d-block mb-1" style="font-size:20px"></i>
                            View Logs
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Recent Activity ─────────────────────── -->
<div class="row">
    <div class="{{ auth()->user()->isSuperAdmin() ? 'col-md-8' : 'col-12' }} mb-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title m-0"><i class="fas fa-history me-2 text-purple"></i> Recent Activity</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr>
                            <th>User</th><th>Action</th><th>Description</th><th>IP</th><th>Time</th>
                        </tr></thead>
                        <tbody>
                        @forelse($recentLogs as $log)
                        <tr>
                            <td>
                                <div class="user-avatar d-inline-flex me-2" style="width:28px;height:28px;font-size:11px;">
                                    {{ $log->user ? substr($log->user->name,0,1) : '?' }}
                                </div>
                                {{ $log->user?->name ?? 'System' }}
                            </td>
                            <td>
                                @php $actionColors = ['created'=>'success','updated'=>'warning','deleted'=>'danger','login'=>'info','logout'=>'secondary']; @endphp
                                <span class="badge badge-{{ $actionColors[$log->action] ?? 'secondary' }} badge-pill">{{ $log->action }}</span>
                            </td>
                            <td>{{ Str::limit($log->description, 50) }}</td>
                            <td style="font-size:11px;color:#9090B0;">{{ $log->ip_address }}</td>
                            <td style="font-size:12px;color:#9090B0;">{{ $log->created_at?->diffForHumans() }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No activity yet</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if(auth()->user()->isSuperAdmin())
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title m-0"><i class="fas fa-building me-2 text-purple"></i> Companies</h3>
            </div>
            <div class="card-body p-0">
                @foreach($companies as $company)
                <div class="d-flex align-items-center p-3" style="border-bottom:1px solid #F8F6FF;">
                    @if($company->logo)
                        <img src="{{ $company->logo_url }}" width="36" height="36" style="border-radius:8px;object-fit:cover;" class="me-3">
                    @else
                        <div style="width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,#7C3AED,#06B6D4);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:14px;flex-shrink:0;" class="me-3">
                            {{ substr($company->name,0,1) }}
                        </div>
                    @endif
                    <div>
                        <div style="font-weight:600;font-size:13px;">{{ $company->name }}</div>
                        <div style="font-size:11px;color:#9090B0;">{{ $company->users_count }} users · {{ $company->roles_count }} roles</div>
                    </div>
                    <span class="ml-auto badge-{{ $company->is_active ? 'active' : 'inactive' }}">
                        {{ $company->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
// Animated counter
document.querySelectorAll('.stat-value[data-target]').forEach(el => {
    const target = parseInt(el.dataset.target);
    let current = 0;
    const step = Math.ceil(target / 40);
    const timer = setInterval(() => {
        current = Math.min(current + step, target);
        el.textContent = current.toLocaleString();
        if (current >= target) clearInterval(timer);
    }, 30);
});
</script>
@endpush
