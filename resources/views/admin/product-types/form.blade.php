@php $isEdit = $type->exists; @endphp
<form method="POST" action="{{ $isEdit ? route('admin.product-types.update', $type) : route('admin.product-types.store') }}">
    @csrf @if($isEdit) @method('PUT') @endif
    <div class="card">
        <div class="card-header"><h3 class="card-title m-0"><i class="fas fa-tags mr-2 text-purple"></i> Product Type</h3></div>
        <div class="card-body row">
            <div class="col-md-2 form-group"><label>Code</label><input name="code" class="form-control" value="{{ old('code', $type->code) }}" required></div>
            <div class="col-md-4 form-group"><label>Name</label><input name="name" class="form-control" value="{{ old('name', $type->name) }}" required></div>
            <div class="col-md-3 form-group"><label>Nature</label><select name="nature" id="productNature" class="form-control"><option value="finished_goods" @selected(old('nature', $type->nature)==='finished_goods')>Finished Goods</option><option value="raw_material" @selected(old('nature', $type->nature)==='raw_material')>Raw Material</option><option value="readymade" @selected(old('nature', $type->nature)==='readymade')>Readymade Product</option><option value="service" @selected(old('nature', $type->nature)==='service')>Service</option></select></div>
            <div class="col-md-3 form-group" id="productCategoryBox">
                <label class="d-flex justify-content-between align-items-center">
                    <span>Product Category</span>
                    <button type="button" class="btn btn-xs btn-primary" style="border-radius:8px;padding:2px 8px" data-toggle="modal" data-target="#productCategoryModal" title="Add category"><i class="fas fa-plus"></i></button>
                </label>
                <select name="product_category_id" id="productCategorySelect" class="form-control select2" data-selected="{{ old('product_category_id', $type->product_category_id) }}">
                    <option value="">Select Product Category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('product_category_id', $type->product_category_id)==$category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('product_category_id')<small class="text-danger">{{ $message }}</small>@enderror
            </div>
            <div class="col-md-3 form-group"><label>Status</label><select name="status" class="form-control"><option value="active" @selected(old('status', $type->status)==='active')>Active</option><option value="inactive" @selected(old('status', $type->status)==='inactive')>Inactive</option></select></div>
            <div class="col-12 form-group"><label>Description</label><textarea name="description" class="form-control">{{ old('description', $type->description) }}</textarea></div>
            <div class="col-12">@include('admin.partials.entry-visibility', ['entry' => $type])</div>
        </div>
        <div class="card-footer text-right"><a href="{{ route('admin.product-types.index') }}" class="btn btn-outline-secondary">Cancel</a> <button class="btn btn-primary">Save</button></div>
    </div>
</form>

<div class="modal fade" id="productCategoryModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <form class="modal-content" id="productCategoryForm">
            <div class="modal-header">
                <h5 class="modal-title">Add Product Category</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <label>Category Name</label>
                <input name="name" class="form-control" placeholder="e.g. GPS" required>
                <div class="text-danger small mt-2" id="productCategoryError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                <button class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function toggleProductCategoryBox(){
    const isFinished = $('#productNature').val() === 'finished_goods';
    $('#productCategoryBox').toggle(isFinished);
    $('#productCategorySelect').prop('required', isFinished);
    if(!isFinished) $('#productCategorySelect').val('').trigger('change');
}

$('#productNature').on('change', toggleProductCategoryBox);
toggleProductCategoryBox();

$('#productCategoryForm').on('submit', async function(e){
    e.preventDefault();
    $('#productCategoryError').text('');
    const formData = new FormData(this);
    const res = await fetch('{{ route('admin.product-types.categories.store') }}', {
        method: 'POST',
        body: formData,
        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json'}
    });
    if(!res.ok){
        $('#productCategoryError').text('Category save nahi ho payi. Name check karein.');
        return;
    }
    const category = await res.json();
    const option = new Option(category.name, category.id, true, true);
    $('#productCategorySelect').append(option).trigger('change');
    this.reset();
    $('#productCategoryModal').modal('hide');
});
</script>
@endpush
