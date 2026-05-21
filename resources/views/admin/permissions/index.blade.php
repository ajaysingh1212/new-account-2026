@extends('layouts.admin')
@section('title', 'Permissions')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2 style="font-weight:700;color:#1A0A3D;"><i class="fas fa-lock me-2 text-purple"></i> System Permissions</h2>
    <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Add Permission</a>
</div>

@foreach($permissions as $module => $perms)
<div class="card mb-3">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title m-0 text-capitalize"><i class="fas fa-cube me-2 text-purple"></i> {{ $module }}</h3>
        <span class="ml-auto badge" style="background:rgba(124,58,237,0.1);color:#7C3AED;border-radius:20px;padding:4px 10px;">{{ $perms->count() }} permissions</span>
    </div>
    <div class="card-body">
        <div class="row">
            @foreach($perms as $perm)
            <div class="col-6 col-md-3 mb-2">
                <div class="d-flex align-items-center justify-content-between p-2" style="background:#F8F6FF;border-radius:8px;">
                    <div>
                        <div style="font-size:13px;font-weight:600;">{{ $perm->name }}</div>
                        <div style="font-size:10px;color:#9090B0;font-family:monospace;">{{ $perm->slug }}</div>
                    </div>
                    <form action="{{ route('admin.permissions.destroy', $perm) }}" method="POST" class="d-inline">
                        @csrf @method('DELETE')
                        <button type="button" class="btn btn-sm btn-delete" style="background:none;border:none;color:#EF4444;padding:4px;">
                            <i class="fas fa-times"></i>
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endforeach
@endsection
