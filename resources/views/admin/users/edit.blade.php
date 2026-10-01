@extends('layouts.admin')
@section('title', 'Edit User')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
    <li class="breadcrumb-item active">Edit User</li>
@endsection

@php
    $selectedCompanyId = old('company_id', $user->current_company_id);
    $selectedRoleIds = collect(old('role_ids', $userRoleIds))->map(fn($id) => (int) $id)->all();
@endphp

@section('content')
<div class="row justify-content-center">
<div class="col-md-8">
<div class="card">
    <div class="card-header">
        <h3 class="card-title m-0"><i class="fas fa-user-edit me-2 text-purple"></i> Edit User</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $user->name) }}" placeholder="Enter full name" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email', $user->email) }}" placeholder="user@example.com" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                            placeholder="Leave blank to keep current password">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-control" placeholder="Repeat new password">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>User Type *</label>
                        <select name="user_type" class="form-control @error('user_type') is-invalid @enderror" required>
                            <option value="user" @selected(old('user_type', $user->user_type) === 'user')>User</option>
                            @if(auth()->user()->isSuperAdmin())
                            <option value="admin" @selected(old('user_type', $user->user_type) === 'admin')>Admin</option>
                            @endif
                        </select>
                        @error('user_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Company *</label>
                        <select name="company_id" class="form-control select2 @error('company_id') is-invalid @enderror" required>
                            <option value="">Select Company</option>
                            @foreach($companies as $company)
                            <option value="{{ $company->id }}" @selected((int) $selectedCompanyId === (int) $company->id)>{{ $company->name }}</option>
                            @endforeach
                        </select>
                        @error('company_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                            value="{{ old('phone', $user->phone) }}" placeholder="+91 9999999999">
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Assign Roles</label>
                        <select name="role_ids[]" class="form-control select2 @error('role_ids') is-invalid @enderror" multiple>
                            @foreach($roles as $role)
                            <option value="{{ $role->id }}" @selected(in_array((int) $role->id, $selectedRoleIds, true))>
                                {{ $role->name }} @if($role->company)({{ $role->company->name }})@endif
                            </option>
                            @endforeach
                        </select>
                        @error('role_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        @error('role_ids.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <div class="custom-control custom-switch mt-4">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="custom-control-input" id="is_active"
                                @checked((bool) old('is_active', $user->is_active))>
                            <label class="custom-control-label" for="is_active">Active User</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Update User</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
@endsection
