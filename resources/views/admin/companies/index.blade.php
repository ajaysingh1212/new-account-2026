@extends('layouts.admin')
@section('title', 'Companies')

@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2 style="font-weight:700;color:#1A0A3D;"><i class="fas fa-building me-2 text-purple"></i> All Companies</h2>
    <a href="{{ route('admin.companies.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Add Company</a>
</div>

<div class="row">
@foreach($companies as $company)
<div class="col-md-4 mb-4">
    <div class="card h-100" style="transition:transform .3s;" onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform='translateY(0)'">
        <div class="card-body">
            <div class="d-flex align-items-start mb-3">
                @if($company->logo)
                    <img src="{{ $company->logo_url }}" width="48" height="48" style="border-radius:12px;object-fit:cover;" class="me-3">
                @else
                    <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#7C3AED,#06B6D4);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:18px;flex-shrink:0;" class="me-3">
                        {{ substr($company->name,0,1) }}
                    </div>
                @endif
                <div class="flex-grow-1">
                    <h5 style="font-weight:700;margin-bottom:2px;">{{ $company->name }}</h5>
                    <div style="font-size:12px;color:#9090B0;">{{ $company->email }}</div>
                </div>
                <span class="{{ $company->is_active ? 'badge-active' : 'badge-inactive' }}">{{ $company->is_active ? 'Active' : 'Inactive' }}</span>
            </div>
            <div class="mb-2">
                <span class="badge {{ $company->has_crm_access ? 'badge-success' : 'badge-secondary' }}">
                    <i class="fas fa-industry mr-1"></i>{{ $company->has_crm_access ? 'CRM Access' : 'No CRM Access' }}
                </span>
            </div>

            <div class="row text-center" style="border-top:1px solid #F0EAF8;border-bottom:1px solid #F0EAF8;padding:12px 0;margin:12px 0;">
                <div class="col-4">
                    <div style="font-size:20px;font-weight:700;color:#7C3AED;">{{ $company->users_count }}</div>
                    <div style="font-size:11px;color:#9090B0;">Users</div>
                </div>
                <div class="col-4">
                    <div style="font-size:20px;font-weight:700;color:#06B6D4;">{{ $company->roles_count }}</div>
                    <div style="font-size:11px;color:#9090B0;">Roles</div>
                </div>
                <div class="col-4">
                    <div style="font-size:20px;font-weight:700;color:#10B981;">₹</div>
                    <div style="font-size:11px;color:#9090B0;">{{ $company->currency }}</div>
                </div>
            </div>

            <div style="font-size:12px;color:#9090B0;margin-bottom:8px;">
                <i class="fas fa-user me-1"></i> Admin: {{ $company->createdBy?->name ?? '—' }}
                @if($company->gst_number) · GST: {{ $company->gst_number }} @endif
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('admin.companies.edit', $company) }}" class="btn btn-warning btn-sm flex-grow-1">
                    <i class="fas fa-edit me-1"></i> Edit
                </a>
                <form action="{{ route('admin.companies.destroy', $company) }}" method="POST" class="d-inline">
                    @csrf @method('DELETE')
                    <input type="hidden" name="super_admin_password" class="super-admin-password">
                    <button type="button" class="btn btn-danger btn-sm btn-delete"><i class="fas fa-trash"></i></button>
                </form>
            </div>
        </div>
    </div>
</div>
@endforeach
</div>

{{ $companies->links() }}
@endsection

@push('scripts')
<script>
$(document).on('click','.btn-delete',function(){
    if(!confirm('Company delete karne par is company ke saare books of account, users, entries aur records delete ho jayenge. Continue?')) return;
    const password = prompt('Confirm karne ke liye super admin password daaliye');
    if(!password) return;
    const form = $(this).closest('form');
    form.find('.super-admin-password').val(password);
    form.trigger('submit');
});
</script>
@endpush
