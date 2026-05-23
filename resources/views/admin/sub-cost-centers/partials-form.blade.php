@php $isEdit = $subCostCenter->exists; @endphp
<form method="POST" action="{{ $isEdit ? route('admin.sub-cost-centers.update', $subCostCenter) : route('admin.sub-cost-centers.store') }}">
    @csrf
    @if($isEdit) @method('PUT') @endif
    <div class="card">
        <div class="card-header"><h3 class="card-title m-0"><i class="fas fa-project-diagram mr-2 text-purple"></i> Sub Cost Center Details</h3></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 form-group"><label>Main Cost Center *</label><select name="cost_center_id" class="form-control select2" required><option value="">Select cost center</option>@foreach($costCenters as $costCenter)<option value="{{ $costCenter->id }}" @selected(old('cost_center_id', $subCostCenter->cost_center_id)==$costCenter->id)>{{ $costCenter->code }} - {{ $costCenter->name }}</option>@endforeach</select>@error('cost_center_id')<small class="text-danger">{{ $message }}</small>@enderror</div>
                <div class="col-md-2 form-group"><label>Code *</label><input name="code" class="form-control" value="{{ old('code', $subCostCenter->code) }}" required>@error('code')<small class="text-danger">{{ $message }}</small>@enderror</div>
                <div class="col-md-4 form-group"><label>Name *</label><input name="name" class="form-control" value="{{ old('name', $subCostCenter->name) }}" required></div>
                <div class="col-md-2 form-group"><label>Status</label><select name="status" class="form-control"><option value="active" @selected(old('status', $subCostCenter->status)==='active')>Active</option><option value="inactive" @selected(old('status', $subCostCenter->status)==='inactive')>Inactive</option></select></div>
            </div>
            <div class="row">
                <div class="col-md-4 form-group"><label>Owner</label><input name="owner_name" class="form-control" value="{{ old('owner_name', $subCostCenter->owner_name) }}"></div>
                <div class="col-md-4 form-group"><label>Budget Amount</label><input type="number" step="0.01" min="0" name="budget_amount" class="form-control" value="{{ old('budget_amount', $subCostCenter->budget_amount) }}"></div>
            </div>
            <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="4">{{ old('description', $subCostCenter->description) }}</textarea></div>
            @include('admin.partials.entry-visibility', ['entry' => $subCostCenter])
        </div>
        <div class="card-footer text-right">
            <a href="{{ route('admin.sub-cost-centers.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Sub Cost Center</button>
        </div>
    </div>
</form>
