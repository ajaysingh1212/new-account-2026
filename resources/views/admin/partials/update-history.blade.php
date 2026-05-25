@if(isset($auditLogs) && $auditLogs->count())
<div class="card mt-3">
    <div class="card-header"><h3 class="card-title m-0">Update History</h3></div>
    <div class="card-body table-responsive">
        <table class="table table-sm table-hover">
            <thead><tr><th>Date</th><th>User</th><th>Company</th><th>Role</th><th>Description</th></tr></thead>
            <tbody>
            @foreach($auditLogs as $log)
                <tr>
                    <td>{{ $log->created_at?->format('d M Y h:i A') }}</td>
                    <td>{{ $log->user?->name ?? 'System' }}</td>
                    <td>{{ $log->company?->name ?? '-' }}</td>
                    <td>{{ $log->user?->rolesForCompany($log->company_id)->pluck('name')->join(', ') ?: 'No role' }}</td>
                    <td>{{ $log->description }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
