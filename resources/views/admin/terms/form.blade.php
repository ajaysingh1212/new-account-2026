@php $isEdit = $template->exists; @endphp
<form method="POST" action="{{ $isEdit ? route('admin.terms.update',$template) : route('admin.terms.store') }}" enctype="multipart/form-data">
@csrf @if($isEdit) @method('PUT') @endif
<div class="card">
    <div class="card-header"><h3 class="card-title m-0">Terms Template</h3></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 form-group"><label>Title *</label><input name="title" class="form-control" value="{{ old('title',$template->title) }}" required></div>
            <div class="col-md-3 form-group"><label>Document Type</label><select name="document_type" class="form-control"><option value="all" @selected(old('document_type',$template->document_type)==='all')>All</option><option value="sales" @selected(old('document_type',$template->document_type)==='sales')>Sales Invoice</option><option value="purchase" @selected(old('document_type',$template->document_type)==='purchase')>Purchase Bill</option></select></div>
            <div class="col-md-2 form-group"><label>Status</label><select name="status" class="form-control"><option value="active" @selected(old('status',$template->status)==='active')>Active</option><option value="inactive" @selected(old('status',$template->status)==='inactive')>Inactive</option></select></div>
            <div class="col-md-3 form-group"><label>Attachment</label><input type="file" name="attachment" class="form-control">@if($isEdit && $template->attachment)<small><a target="_blank" href="{{ asset('storage/'.$template->attachment) }}">Current file</a></small>@endif</div>
        </div>
        <div class="custom-control custom-switch mb-3"><input type="checkbox" class="custom-control-input" id="is_default" name="is_default" value="1" @checked(old('is_default',$template->is_default))><label class="custom-control-label" for="is_default">Default for this document type</label></div>
        <div class="form-group"><label>Terms Content *</label><textarea name="content" class="form-control" rows="8" required>{{ old('content',$template->content) }}</textarea></div>
    </div>
    <div class="card-footer text-right"><a href="{{ route('admin.terms.index') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-primary">Save Terms</button></div>
</div>
</form>
