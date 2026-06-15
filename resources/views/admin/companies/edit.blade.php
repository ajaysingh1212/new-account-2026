@extends('layouts.admin')
@section('title', 'Edit Company')

@section('content')
<div class="row justify-content-center">
<div class="col-md-10">
<div class="card">

    <div class="card-header">
        <h3 class="card-title m-0">
            <i class="fas fa-edit me-2 text-purple"></i>
            Edit Company
        </h3>
    </div>

    <div class="card-body">

        <form action="{{ route('admin.companies.update', $company->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <h5 class="mb-3" style="color:#7C3AED;font-weight:700;border-bottom:2px solid #F0EAF8;padding-bottom:8px;">
                <i class="fas fa-building me-2"></i> Company Information
            </h5>

            <div class="row">

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Company Name *</label>
                        <input type="text" name="name" class="form-control"
                               value="{{ old('name', $company->name) }}" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Business Email</label>
                        <input type="email" name="email" class="form-control"
                               value="{{ old('email', $company->email) }}">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label>GST Number</label>
                        <input type="text" name="gst_number" class="form-control"
                               value="{{ old('gst_number', $company->gst_number) }}">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label>PAN Number</label>
                        <input type="text" name="pan_number" class="form-control"
                               value="{{ old('pan_number', $company->pan_number) }}">
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label>Currency</label>
                        <select name="currency" class="form-control">
                            <option value="INR" {{ $company->currency == 'INR' ? 'selected' : '' }}>INR (₹)</option>
                            <option value="USD" {{ $company->currency == 'USD' ? 'selected' : '' }}>USD ($)</option>
                            <option value="EUR" {{ $company->currency == 'EUR' ? 'selected' : '' }}>EUR (€)</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="2">{{ old('address', $company->address) }}</textarea>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label>Company Logo</label>

                        @if($company->logo)
                            <div class="mb-2">
                                <img src="{{ Storage::url($company->logo) }}"
                                     alt="Logo"
                                     style="height:70px;width:auto;border-radius:8px;">
                            </div>
                        @endif

                        <input type="file" name="logo" class="form-control" accept="image/*">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>CRM Access</label>
                        <div class="custom-control custom-switch mt-2">
                            <input type="checkbox" class="custom-control-input" id="hasCrmAccess" name="has_crm_access" value="1" @checked(old('has_crm_access', $company->has_crm_access))>
                            <label class="custom-control-label" for="hasCrmAccess">Allow CRM Assembly</label>
                        </div>
                    </div>
                </div>

            </div>

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Update Company
                </button>

                <a href="{{ route('admin.companies.index') }}" class="btn btn-secondary">
                    Cancel
                </a>
            </div>

        </form>

    </div>
</div>
</div>
</div>
@endsection
