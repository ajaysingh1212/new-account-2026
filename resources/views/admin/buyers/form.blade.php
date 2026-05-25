@php($isEdit = isset($buyer))
<form method="POST" action="{{ $isEdit ? route('admin.buyers.update',$buyer) : route('admin.buyers.store') }}">
@csrf
@if($isEdit) @method('PUT') @endif
<div class="card">
    <div class="card-header"><h3 class="card-title m-0">{{ $isEdit ? 'Edit Buyer' : 'New Buyer' }}</h3></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 form-group"><label>Buyer Code</label><input name="buyer_code" class="form-control" value="{{ old('buyer_code',$buyer->buyer_code ?? $buyerCode ?? '') }}"></div>
            <div class="col-md-4 form-group"><label>Buyer Name</label><input name="name" class="form-control" value="{{ old('name',$buyer->name ?? '') }}" required></div>
            <div class="col-md-2 form-group"><label>Phone</label><input name="phone" class="form-control" value="{{ old('phone',$buyer->phone ?? '') }}"></div>
            <div class="col-md-3 form-group"><label>Email</label><input type="email" name="email" class="form-control" value="{{ old('email',$buyer->email ?? '') }}"></div>
            <div class="col-md-8 form-group"><label>Address</label><textarea name="address" class="form-control" rows="2">{{ old('address',$buyer->address ?? '') }}</textarea></div>
            <div class="col-md-2 form-group"><label>Status</label><select name="status" class="form-control"><option value="active" @selected(old('status',$buyer->status ?? 'active')==='active')>Active</option><option value="inactive" @selected(old('status',$buyer->status ?? '')==='inactive')>Inactive</option></select></div>
        </div>
        @include('admin.partials.entry-visibility', ['entry' => $buyer ?? null])
    </div>
    <div class="card-footer text-right"><button class="btn btn-primary"><i class="fas fa-save mr-1"></i>Save Buyer</button></div>
</div>
</form>
