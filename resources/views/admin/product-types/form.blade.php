@php $isEdit = $type->exists; @endphp
<form method="POST" action="{{ $isEdit ? route('admin.product-types.update', $type) : route('admin.product-types.store') }}">
    @csrf @if($isEdit) @method('PUT') @endif
    <div class="card">
        <div class="card-header"><h3 class="card-title m-0"><i class="fas fa-tags mr-2 text-purple"></i> Product Type</h3></div>
        <div class="card-body row">
            <div class="col-md-2 form-group"><label>Code</label><input name="code" class="form-control" value="{{ old('code', $type->code) }}" required></div>
            <div class="col-md-4 form-group"><label>Name</label><input name="name" class="form-control" value="{{ old('name', $type->name) }}" required></div>
            <div class="col-md-3 form-group"><label>Nature</label><select name="nature" class="form-control"><option value="finished_goods" @selected(old('nature', $type->nature)==='finished_goods')>Finished Goods</option><option value="raw_material" @selected(old('nature', $type->nature)==='raw_material')>Raw Material</option><option value="readymade" @selected(old('nature', $type->nature)==='readymade')>Readymade Product</option><option value="service" @selected(old('nature', $type->nature)==='service')>Service</option></select></div>
            <div class="col-md-3 form-group"><label>Status</label><select name="status" class="form-control"><option value="active" @selected(old('status', $type->status)==='active')>Active</option><option value="inactive" @selected(old('status', $type->status)==='inactive')>Inactive</option></select></div>
            <div class="col-12 form-group"><label>Description</label><textarea name="description" class="form-control">{{ old('description', $type->description) }}</textarea></div>
            <div class="col-12">@include('admin.partials.entry-visibility', ['entry' => $type])</div>
        </div>
        <div class="card-footer text-right"><a href="{{ route('admin.product-types.index') }}" class="btn btn-outline-secondary">Cancel</a> <button class="btn btn-primary">Save</button></div>
    </div>
</form>
