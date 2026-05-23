@extends('layouts.admin')
@section('title', 'Create Role')

@section('content')
<div class="row">
<div class="col-md-8">
<div class="card">
    <div class="card-header">
        <h3 class="card-title m-0"><i class="fas fa-plus me-2"></i> Create Role</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.roles.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label>Role Name *</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. Salesman, Accountant" required>
            </div>
            @if(auth()->user()->isSuperAdmin())
            <div class="form-group">
                <label>Company *</label>
                <select name="company_id" class="form-control" required>
                    <option value="">Select company</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" @selected((int) old('company_id') === (int) $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="2" placeholder="Brief description of this role">{{ old('description') }}</textarea>
            </div>

            <!-- Permissions -->
            <div class="form-group">
                <label class="d-flex align-items-center justify-content-between">
                    Assign Permissions
                    <a href="javascript:void(0)" onclick="toggleAll()" class="text-purple" style="font-size:12px;">Toggle All</a>
                </label>
                @foreach($permissions as $module => $perms)
                <div class="card mb-3" style="border:1px solid #F0EAF8!important;border-radius:12px!important;">
                    <div class="card-header p-3 d-flex align-items-center" style="background:#F8F6FF!important;border-radius:12px 12px 0 0!important;">
                        <label class="mb-0 d-flex align-items-center" style="cursor:pointer;gap:8px;font-weight:700;text-transform:capitalize;">
                            <input type="checkbox" class="module-check" onchange="toggleModule(this, '{{ $module }}')">
                            <i class="fas fa-folder-open text-purple me-1"></i> {{ ucfirst($module) }}
                        </label>
                        <span class="ml-auto badge" style="background:rgba(124,58,237,0.1);color:#7C3AED;border-radius:20px;">{{ $perms->count() }}</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row">
                            @foreach($perms as $perm)
                            <div class="col-6 col-md-3 mb-2">
                                <label class="d-flex align-items-center" style="cursor:pointer;gap:6px;font-size:13px;">
                                    <input type="checkbox" name="permission_ids[]" value="{{ $perm->id }}"
                                        class="perm-check-{{ $module }}"
                                        {{ in_array($perm->id, old('permission_ids', [])) ? 'checked' : '' }}>
                                    {{ $perm->name }}
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Create Role</button>
                <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
@endsection
@push('scripts')
<script>
function toggleModule(checkbox, module) {
    document.querySelectorAll('.perm-check-' + module).forEach(c => c.checked = checkbox.checked);
}
function toggleAll() {
    const allChecked = Array.from(document.querySelectorAll('input[name="permission_ids[]"]')).every(c => c.checked);
    document.querySelectorAll('input[name="permission_ids[]"], .module-check').forEach(c => c.checked = !allChecked);
}
</script>
@endpush
