@extends('layouts.admin')
@section('title', 'Audit Logs')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title m-0"><i class="fas fa-history me-2 text-purple"></i> Audit Logs</h3>
    </div>

    <!-- Filters -->
    <div class="card-body pb-0">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <select name="user_id" class="form-control select2">
                    <option value="">All Users</option>
                    @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="action" class="form-control">
                    <option value="">All Actions</option>
                    @foreach(['created','updated','deleted','login','logout'] as $action)
                    <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>{{ ucfirst($action) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i> Filter</button>
                <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-secondary ms-2">Reset</a>
            </div>
        </form>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr><th>Time</th><th>User</th><th>Company</th><th>Action</th><th>Description</th><th>IP</th></tr>
                </thead>
                <tbody>
                @forelse($logs as $log)
                <tr>
                    <td style="font-size:12px;white-space:nowrap;color:#9090B0;">
                        {{ $log->created_at?->format('d M Y, H:i') }}<br>
                        <small>{{ $log->created_at?->diffForHumans() }}</small>
                    </td>
                    <td>{{ $log->user?->name ?? 'System' }}</td>
                    <td>{{ $log->company?->name ?? '—' }}</td>
                    <td>
                        @php $c=['created'=>'success','updated'=>'warning','deleted'=>'danger','login'=>'info','logout'=>'secondary']; @endphp
                        <span class="badge badge-{{ $c[$log->action] ?? 'secondary' }} badge-pill">{{ $log->action }}</span>
                    </td>
                    <td style="font-size:13px;">{{ $log->description }}</td>
                    <td style="font-size:11px;color:#9090B0;font-family:monospace;">{{ $log->ip_address }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-5 text-muted">No logs found</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $logs->links() }}
    </div>
</div>
@endsection
