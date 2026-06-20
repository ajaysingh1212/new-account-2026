@extends('layouts.admin')
@section('title','Stock History')
@section('content')
<div class="card">
    <div class="card-body">
        <form method="GET" class="row">
            <div class="col-md-5"><select name="item_id" class="form-control select2"><option value="">All Items</option>@foreach($items as $item)<option value="{{ $item->id }}" @selected(request('item_id')==$item->id)>{{ $item->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><input name="q" class="form-control" value="{{ $serialSearch }}" placeholder="Serial / VTS / SKU / reference"></div>
            <div class="col-md-3"><button class="btn btn-primary">Filter</button><a href="{{ route('admin.stocks.history') }}" class="btn btn-light">Reset</a></div>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-body table-responsive">
        <table id="historyTable" class="table table-hover">
            <thead><tr><th>Date</th><th>Item</th><th>Type</th><th>Direction</th><th>Qty</th><th>Serial / VTS / SKU</th><th>Value</th><th>Party</th><th>Ref</th><th>Stock After</th><th>Created By</th></tr></thead>
            <tbody>@foreach($movements as $m)<tr><td>{{ $m->movement_date?->format('d M Y') }}</td><td>{{ $m->item?->name }}<br><small class="text-muted">{{ $m->item?->sku ?: $m->item?->item_code }}</small></td><td>{{ str_replace('_',' ',ucfirst($m->movement_type)) }}</td><td><span class="{{ $m->direction==='in'?'badge-active':'badge-inactive' }}">{{ strtoupper($m->direction) }}</span></td><td>{{ $m->quantity }}</td><td>@forelse(($m->movement_units ?? []) as $unit)<span class="badge badge-info mr-1 mb-1">{{ $unit['serial_no'] ?? $unit['vts_sim'] ?? $unit['sku'] ?? $unit['key'] ?? 'Unit' }}</span>@empty<span class="text-muted">-</span>@endforelse<span class="d-none">{{ collect($m->movement_units ?? [])->flatMap(fn($unit) => [$unit['serial_no'] ?? null, $unit['vts_sim'] ?? null, $unit['sku'] ?? null, $unit['batch_no'] ?? null, $unit['production_batch_no'] ?? null, $unit['key'] ?? null])->filter()->join(' ') }}</span></td><td>Rs {{ number_format((float)$m->total_value,2) }}</td><td>{{ $m->party?->display_name ?: '-' }}</td><td>{{ $m->reference_no }}</td><td>{{ $m->stock_after }}</td><td><strong>{{ $m->creator?->name ?? 'System' }}</strong><br><small class="text-muted">{{ $m->creator?->rolesForCompany($m->company_id)->pluck('name')->join(', ') ?: 'No role' }}</small></td></tr>@endforeach</tbody>
        </table>
    </div>
</div>
@endsection
@push('scripts')<script>$('#historyTable').DataTable({pageLength:25});</script>@endpush
