@php $isEdit = $costCenter->exists; @endphp
<form method="POST" action="{{ $isEdit ? route('admin.cost-centers.update', $costCenter) : route('admin.cost-centers.store') }}">
    @csrf
    @if($isEdit) @method('PUT') @endif
    <div class="card">
        <div class="card-header"><h3 class="card-title m-0"><i class="fas fa-sitemap mr-2 text-purple"></i> Cost Center Details</h3></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 form-group"><label>Code *</label><input name="code" class="form-control" value="{{ old('code', $costCenter->code) }}" required>@error('code')<small class="text-danger">{{ $message }}</small>@enderror</div>
                <div class="col-md-5 form-group"><label>Name *</label><input name="name" class="form-control" value="{{ old('name', $costCenter->name) }}" required>@error('name')<small class="text-danger">{{ $message }}</small>@enderror</div>
                <div class="col-md-2 form-group"><label>Department</label><input name="department" class="form-control" value="{{ old('department', $costCenter->department) }}"></div>
                <div class="col-md-2 form-group"><label>Status</label><select name="status" class="form-control"><option value="active" @selected(old('status', $costCenter->status)==='active')>Active</option><option value="inactive" @selected(old('status', $costCenter->status)==='inactive')>Inactive</option></select></div>
            </div>
            <div class="row">
                <div class="col-md-3 form-group"><label>Manager</label><input name="manager_name" class="form-control" value="{{ old('manager_name', $costCenter->manager_name) }}"></div>
                <div class="col-md-3 form-group"><label>Budget Amount</label><input type="number" step="0.01" min="0" name="budget_amount" class="form-control" value="{{ old('budget_amount', $costCenter->budget_amount) }}"></div>
                <div class="col-md-3 form-group"><label>Budget Start</label><input type="date" name="budget_start_date" class="form-control" value="{{ old('budget_start_date', optional($costCenter->budget_start_date)->format('Y-m-d')) }}"></div>
                <div class="col-md-3 form-group"><label>Budget End</label><input type="date" name="budget_end_date" class="form-control" value="{{ old('budget_end_date', optional($costCenter->budget_end_date)->format('Y-m-d')) }}"></div>
            </div>
            <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="4">{{ old('description', $costCenter->description) }}</textarea></div>
            @include('admin.partials.entry-visibility', ['entry' => $costCenter])
        </div>
        <div class="card-footer text-right">
            <a href="{{ route('admin.cost-centers.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Cost Center</button>
        </div>
    </div>
</form>
