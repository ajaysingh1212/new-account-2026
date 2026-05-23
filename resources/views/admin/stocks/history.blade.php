@extends('layouts.admin')
@section('title','Stock History')
@section('content')
<div class="card">
    <div class="card-body">
        <form method="GET" class="row">
            <div class="col-md-8"><select name="item_id" class="form-control select2"><option value="">All Items</option>@foreach($items as $item)<option value="{{ $item->id }}" @selected(request('item_id')==$item->id)>{{ $item->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><button class="btn btn-primary">Filter</button></div>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-body table-responsive">
        <table id="historyTable" class="table table-hover">
            <thead><tr><th>Date</th><th>Item</th><th>Type</th><th>Direction</th><th>Qty</th><th>Value</th><th>Party</th><th>Ref</th><th>Stock After</th><th>Created By</th></tr></thead>
            <tbody>@foreach($movements as $m)<tr><td>{{ $m->movement_date?->format('d M Y') }}</td><td>{{ $m->item?->name }}</td><td>{{ str_replace('_',' ',ucfirst($m->movement_type)) }}</td><td><span class="{{ $m->direction==='in'?'badge-active':'badge-inactive' }}">{{ strtoupper($m->direction) }}</span></td><td>{{ $m->quantity }}</td><td>Rs {{ number_format((float)$m->total_value,2) }}</td><td>{{ $m->party?->display_name ?: '-' }}</td><td>{{ $m->reference_no }}</td><td>{{ $m->stock_after }}</td><td><strong>{{ $m->creator?->name ?? 'System' }}</strong><br><small class="text-muted">{{ $m->creator?->rolesForCompany($m->company_id)->pluck('name')->join(', ') ?: 'No role' }}</small></td></tr>@endforeach</tbody>
        </table>
    </div>
</div>
@endsection
@push('scripts')<script>$('#historyTable').DataTable({pageLength:25});</script>@endpush
