@extends('layouts.admin')
@section('title','Current Stocks')

@push('styles')
<style>
.stock-head{background:#111827;color:#fff;border-radius:8px;padding:22px;margin-bottom:16px;display:flex;justify-content:space-between;gap:16px;align-items:center}.stock-head h2{margin:0;font-weight:800}.metric-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px}.metric{background:#fff;border:1px solid #e8edf5;border-radius:8px;padding:16px;box-shadow:0 10px 24px rgba(15,23,42,.06)}.metric small{display:block;text-transform:uppercase;color:#667085;font-weight:800;font-size:11px}.metric b{font-size:22px;color:#111827}.filter-card{background:#fff;border:1px solid #e8edf5;border-radius:8px;padding:14px;margin-bottom:16px}.stock-table-card{background:#fff;border:1px solid #e8edf5;border-radius:8px;padding:16px}.low-row{background:#fff7ed}@media(max-width:992px){.metric-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:576px){.metric-grid{grid-template-columns:1fr}.stock-head{display:block}}
</style>
@endpush

@section('content')
<div class="stock-head">
    <div>
        <h2><i class="fas fa-boxes mr-2"></i>Current Stock Valuation</h2>
        <small>Overall value, monthly stock movement value and item-level balance.</small>
    </div>
    <a href="{{ route('admin.stocks.history') }}" class="btn btn-outline-light btn-sm"><i class="fas fa-history mr-1"></i>Movement History</a>
</div>

<div class="metric-grid">
    <div class="metric"><small>Overall Stock Value</small><b>Rs {{ number_format($overallValue,2) }}</b></div>
    <div class="metric"><small>Overall Quantity</small><b>{{ number_format($overallQty,3) }}</b></div>
    <div class="metric"><small>{{ \Carbon\Carbon::parse($month.'-01')->format('M Y') }} Stock In</small><b class="text-success">Rs {{ number_format($monthIn,2) }}</b></div>
    <div class="metric"><small>{{ \Carbon\Carbon::parse($month.'-01')->format('M Y') }} Stock Out</small><b class="text-danger">Rs {{ number_format($monthOut,2) }}</b></div>
</div>

<form method="GET" class="filter-card">
    <div class="row align-items-end">
        <div class="col-md-3 form-group mb-md-0"><label>Month Wise Valuation</label><input type="month" name="month" value="{{ $month }}" class="form-control"></div>
        <div class="col-md-3 form-group mb-md-0"><label>Product Nature</label><select name="nature" class="form-control"><option value="">All</option><option value="finished_goods" @selected($nature==='finished_goods')>Finished Goods</option><option value="raw_material" @selected($nature==='raw_material')>Raw Material</option><option value="traded_goods" @selected($nature==='traded_goods')>Traded Goods</option><option value="packing_material" @selected($nature==='packing_material')>Packing Material</option></select></div>
        <div class="col-md-3"><button class="btn btn-primary"><i class="fas fa-filter mr-1"></i>Apply</button><a href="{{ route('admin.stocks.index') }}" class="btn btn-light">Reset</a></div>
    </div>
</form>

<div class="stock-table-card table-responsive">
    <table id="stockTable" class="table table-hover">
        <thead><tr><th>Item</th><th>Nature</th><th>Stock</th><th>Value</th><th>Avg Rate</th><th>Low Warning</th><th>Status</th></tr></thead>
        <tbody>
        @foreach($items as $item)
            @php $isLow = $item->low_stock_qty && $item->current_stock <= $item->low_stock_qty; @endphp
            <tr class="{{ $isLow ? 'low-row' : '' }}">
                <td><b>{{ $item->name }}</b><br><small class="text-muted">{{ $item->item_code }}</small></td>
                <td>{{ $item->productType?->name }}<br><small>{{ str_replace('_',' ', $item->productType?->nature ?? '-') }}</small></td>
                <td><b>{{ number_format((float)$item->current_stock,3) }}</b> {{ $item->unit }}</td>
                <td>Rs {{ number_format((float)$item->stock_value,2) }}</td>
                <td>Rs {{ (float)$item->current_stock > 0 ? number_format((float)$item->stock_value / (float)$item->current_stock,2) : '0.00' }}</td>
                <td>@if($isLow)<span class="badge badge-warning">Low Stock</span>@else <span class="badge badge-success">OK</span>@endif</td>
                <td>{{ ucfirst($item->status) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection

@push('scripts')<script>$('#stockTable').DataTable({pageLength:25,order:[[0,'asc']]});</script>@endpush
