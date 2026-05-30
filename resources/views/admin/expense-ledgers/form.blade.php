@php $isEdit = $ledger->exists; @endphp
<form method="POST" action="{{ $isEdit ? route('admin.expense-ledgers.update',$ledger) : route('admin.expense-ledgers.store') }}" enctype="multipart/form-data">
@csrf @if($isEdit) @method('PUT') @endif
<div class="card">
    <div class="card-header"><h3 class="card-title m-0">Ledger Master</h3></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-2 form-group"><label>Code *</label><input name="ledger_code" class="form-control" value="{{ old('ledger_code',$ledger->ledger_code) }}" required></div>
            <div class="col-md-4 form-group"><label>Ledger Name *</label><input name="name" class="form-control" value="{{ old('name',$ledger->name) }}" required></div>
            <div class="col-md-3 form-group"><label>Category</label><input name="category" class="form-control" value="{{ old('category',$ledger->category) }}" placeholder="Travel, Office, Salary"></div>
            <div class="col-md-3 form-group"><label>Status</label><select name="status" class="form-control"><option value="active" @selected(old('status',$ledger->status)==='active')>Active</option><option value="inactive" @selected(old('status',$ledger->status)==='inactive')>Inactive</option></select></div>
        </div>
        <div class="row">
            <div class="col-md-3 form-group"><label>Opening Balance</label><input type="number" step="0.01" min="0" name="opening_balance" class="form-control" value="{{ old('opening_balance',$ledger->opening_balance ?? 0) }}"></div>
            <div class="col-md-3 form-group"><label>Opening Date</label><input type="date" name="opening_balance_date" class="form-control" value="{{ old('opening_balance_date',$ledger->opening_balance_date?->format('Y-m-d') ?? now()->toDateString()) }}"></div>
            <div class="col-md-3 form-group"><label>Attachment</label><input type="file" name="attachment" class="form-control">@if($isEdit && $ledger->attachment)<small><a target="_blank" href="{{ asset('storage/'.$ledger->attachment) }}">Current file</a></small>@endif</div>
        </div>
        <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3">{{ old('description',$ledger->description) }}</textarea></div>
        @include('admin.partials.entry-visibility', ['entry' => $ledger])
    </div>
    <div class="card-footer text-right"><a href="{{ route('admin.expense-ledgers.index') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-primary">Save Ledger</button></div>
</div>
</form>
