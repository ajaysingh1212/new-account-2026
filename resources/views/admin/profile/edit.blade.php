@extends('layouts.admin')
@section('title', 'My Profile')

@section('content')
<form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data">
@csrf

<!-- Profile Cover -->
<div class="card mb-4" style="overflow:hidden;border-radius:20px!important;">
    <div id="profileCover" style="height:200px;background:{{ $user->background_pic ? 'url('.asset('storage/'.$user->background_pic).')' : 'linear-gradient(135deg,#7C3AED,#06B6D4)' }};background-size:cover;background-position:center;position:relative;">
        <label for="bgPicInput" style="position:absolute;bottom:12px;right:12px;cursor:pointer;background:rgba(0,0,0,0.4);color:#fff;padding:6px 14px;border-radius:20px;font-size:12px;backdrop-filter:blur(4px);">
            <i class="fas fa-camera me-1"></i> Change Cover
            <input type="file" id="bgPicInput" name="background_pic" accept="image/*" style="display:none" onchange="previewBg(this)">
        </label>
    </div>
    <div class="card-body" style="padding-top:0;">
        <div style="margin-top:-48px;padding-left:24px;display:flex;align-items:flex-end;gap:16px;flex-wrap:wrap;">
            <div style="position:relative;">
                @if($user->profile_pic)
                    <img src="{{ $user->profile_pic_url }}" id="profilePicPreview" width="96" height="96" style="border-radius:20px;object-fit:cover;border:4px solid #fff;box-shadow:0 4px 16px rgba(0,0,0,0.15);">
                @else
                    <div id="profilePicPreview" style="width:96px;height:96px;border-radius:20px;background:linear-gradient(135deg,#7C3AED,#06B6D4);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:32px;border:4px solid #fff;box-shadow:0 4px 16px rgba(0,0,0,0.15);">
                        {{ substr($user->name,0,1) }}
                    </div>
                @endif
                <label for="profilePicInput" style="position:absolute;bottom:-4px;right:-4px;width:28px;height:28px;background:#7C3AED;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#fff;font-size:11px;box-shadow:0 2px 8px rgba(124,58,237,0.4);">
                    <i class="fas fa-camera"></i>
                    <input type="file" id="profilePicInput" name="profile_pic" accept="image/*" style="display:none" onchange="previewPic(this)">
                </label>
            </div>
            <div style="padding-bottom:8px;">
                <h4 style="font-weight:700;margin-bottom:2px;">{{ $user->name }}</h4>
                <div style="color:#9090B0;font-size:13px;">{{ $user->email }} · {{ ucfirst(str_replace('_',' ',$user->user_type)) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Personal Info -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title m-0"><i class="fas fa-user me-2 text-purple"></i> Personal Information</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6"><div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="name" class="form-control" value="{{ $user->name }}" required>
                    </div></div>
                    <div class="col-md-6"><div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" class="form-control" value="{{ $user->email }}" required>
                    </div></div>
                    <div class="col-md-6"><div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" value="{{ $user->phone }}" placeholder="+91 9999999999">
                    </div></div>
                    <div class="col-12"><div class="form-group">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="3">{{ $user->address }}</textarea>
                    </div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Social Media -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title m-0"><i class="fas fa-share-alt me-2 text-purple"></i> Social Media</h3></div>
            <div class="card-body">
                @foreach([
                    ['name'=>'facebook','icon'=>'fab fa-facebook','placeholder'=>'https://facebook.com/you','color'=>'#1877F2'],
                    ['name'=>'twitter','icon'=>'fab fa-twitter','placeholder'=>'https://twitter.com/you','color'=>'#1DA1F2'],
                    ['name'=>'linkedin','icon'=>'fab fa-linkedin','placeholder'=>'https://linkedin.com/in/you','color'=>'#0A66C2'],
                    ['name'=>'instagram','icon'=>'fab fa-instagram','placeholder'=>'https://instagram.com/you','color'=>'#E4405F'],
                    ['name'=>'website','icon'=>'fas fa-globe','placeholder'=>'https://yourwebsite.com','color'=>'#10B981'],
                ] as $s)
                <div class="form-group mb-2">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" style="background:#F8F6FF;border-color:#E5E0F5;border-radius:10px 0 0 10px;">
                                <i class="{{ $s['icon'] }}" style="color:{{ $s['color'] }}"></i>
                            </span>
                        </div>
                        <input type="url" name="{{ $s['name'] }}" class="form-control" value="{{ $user->{$s['name']} }}" placeholder="{{ $s['placeholder'] }}" style="border-radius:0 10px 10px 0!important;">
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Change Password -->
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title m-0"><i class="fas fa-lock me-2 text-purple"></i> Change Password</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4"><div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" placeholder="Current password">
                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div></div>
                    <div class="col-md-4"><div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="password" class="form-control" placeholder="New password (min 8)">
                    </div></div>
                    <div class="col-md-4"><div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-control" placeholder="Repeat new password">
                    </div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i> Save Changes</button>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary btn-lg">Cancel</a>
</div>

</form>
@endsection

@push('scripts')
<script>
function previewPic(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const el = document.getElementById('profilePicPreview');
            if (el.tagName === 'IMG') el.src = e.target.result;
            else { el.style.backgroundImage = `url(${e.target.result})`; el.textContent = ''; el.style.backgroundSize='cover'; }
        };
        reader.readAsDataURL(input.files[0]);
    }
}
function previewBg(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { document.getElementById('profileCover').style.background = `url(${e.target.result}) center/cover`; };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endpush
