    @extends('layouts.admin')
@section('title', 'Add Company')

@section('content')
<div class="row justify-content-center">
<div class="col-md-10">
<div class="card">
    <div class="card-header">
        <h3 class="card-title m-0"><i class="fas fa-building me-2 text-purple"></i> Add New Company + Admin</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.companies.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <h5 class="mb-3" style="color:#7C3AED;font-weight:700;border-bottom:2px solid #F0EAF8;padding-bottom:8px;">
                <i class="fas fa-building me-2"></i> Company Information
            </h5>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Company Name *</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="ABC Traders Pvt Ltd" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Business Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="info@company.com">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>GST Number</label>
                        <input type="text" name="gst_number" class="form-control" value="{{ old('gst_number') }}" placeholder="29ABCDE1234F1ZK">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>PAN Number</label>
                        <input type="text" name="pan_number" class="form-control" value="{{ old('pan_number') }}" placeholder="ABCDE1234F">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Currency</label>
                        <select name="currency" class="form-control">
                            <option value="INR">INR (₹)</option>
                            <option value="USD">USD ($)</option>
                            <option value="EUR">EUR (€)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Full business address">{{ old('address') }}</textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Company Logo</label>
                        <input type="file" name="logo" class="form-control" accept="image/*">
                    </div>
                </div>
            </div>

            <h5 class="mt-4 mb-3" style="color:#7C3AED;font-weight:700;border-bottom:2px solid #F0EAF8;padding-bottom:8px;">
                <i class="fas fa-user-shield me-2"></i> Company Admin Account
            </h5>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Admin Name *</label>
                        <input type="text" name="admin_name" class="form-control" value="{{ old('admin_name') }}" placeholder="Admin full name" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Admin Email *</label>
                        <input type="email" name="admin_email" class="form-control" value="{{ old('admin_email') }}" placeholder="admin@company.com" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Admin Password *</label>
                        <input type="password" name="admin_password" class="form-control" placeholder="Min 8 characters" required>
                    </div>
                </div>
            </div>

            @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Create Company & Admin</button>
                <a href="{{ route('admin.companies.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
@endsection
