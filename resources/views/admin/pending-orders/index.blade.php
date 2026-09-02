@extends('layouts.admin')
@section('title','Pending Orders')
@push('styles')
@include('admin.partials.segment-viz-styles')
@endpush
@section('content')
<div class="card">
    <div class="card-header"><h3 class="card-title m-0">Pending Orders</h3></div>
    <div class="card-body">
        <form class="row mb-3">
            <div class="col-md-2"><input type="date" name="from_date" class="form-control" value="{{ $filters['from_date'] }}"></div>
            <div class="col-md-2"><input type="date" name="to_date" class="form-control" value="{{ $filters['to_date'] }}"></div>
            <div class="col-md-2"><input type="month" name="month" class="form-control" value="{{ $filters['month'] ?? '' }}"></div>
            <div class="col-md-2"><select name="party_id" class="form-control"><option value="">All Parties</option>@foreach($parties as $party)<option value="{{ $party->id }}" @selected(($filters['party_id'] ?? '') == $party->id)>{{ $party->display_name }}</option>@endforeach</select></div>
            <div class="col-md-2"><select name="product_category_id" class="form-control"><option value="">All Categories</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(($filters['product_category_id'] ?? '') == $category->id)>{{ $category->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><button class="btn btn-primary btn-block"><i class="fas fa-filter mr-1"></i>Filter</button></div>
        </form>
        <div class="row">
            <div class="col-md-3"><div class="small-box bg-info"><div class="inner"><h3>Rs {{ number_format($summary['sales'],2) }}</h3><p>Pending Sales Amount</p></div></div></div>
            <div class="col-md-3"><div class="small-box bg-secondary"><div class="inner"><h3>Rs {{ number_format($summary['cost'],2) }}</h3><p>Pending Cost</p></div></div></div>
            <div class="col-md-3"><div class="small-box bg-success"><div class="inner"><h3>Rs {{ number_format($summary['profit'],2) }}</h3><p>Possible Profit</p></div></div></div>
            <div class="col-md-3"><div class="small-box bg-warning"><div class="inner"><h3>{{ number_format($summary['profit_percent'],2) }}%</h3><p>Profit on Cost</p></div></div></div>
        </div>

        <div class="row">
            <div class="col-lg-6 mb-3">
                <div class="segment-report-modal segment-report-inline">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="font-weight-bold m-0">Pending by Category</h5>
                    </div>
                    <div class="segment-filter-grid">
                        <select class="form-control segment-filter" data-filter="category" data-placeholder="All Categories"></select>
                        <select class="form-control segment-filter" data-filter="party" data-placeholder="All Parties"></select>
                        <select class="form-control segment-filter" data-filter="state" data-placeholder="All States"></select>
                    </div>
                    @include('admin.partials.segment-viz-body', ['segments' => $categorySegments, 'amountLabel' => 'Pending Sales'])
                </div>
            </div>
            <div class="col-lg-6 mb-3">
                <div class="segment-report-modal segment-report-inline">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="font-weight-bold m-0">Pending by Party</h5>
                    </div>
                    <div class="segment-filter-grid">
                        <select class="form-control segment-filter" data-filter="category" data-placeholder="All Categories"></select>
                        <select class="form-control segment-filter" data-filter="party" data-placeholder="All Parties"></select>
                        <select class="form-control segment-filter" data-filter="state" data-placeholder="All States"></select>
                    </div>
                    @include('admin.partials.segment-viz-body', ['segments' => $partySegments, 'amountLabel' => 'Pending Sales'])
                </div>
            </div>
        </div>

        <div class="table-responsive mt-3">
            <table id="pendingTable" class="table table-hover">
                <thead><tr><th>Date</th><th>Party</th><th>Challan</th><th>Invoice</th><th>Status</th><th>Category</th><th>Item</th><th>Pending Qty</th><th>Stock Now</th><th>Sales</th><th>Cost</th><th>Profit</th><th>%</th><th></th></tr></thead>
                <tbody>@foreach($orders as $order)<tr>
                    <td>{{ $order->pending_date?->format('d M Y') }}</td>
                    <td>{{ $order->party?->display_name ?: 'Walk-in' }}</td>
                    <td>{{ $order->deliveryChallan?->challan_no }}</td>
                    <td>
                        @if($order->convertedInvoice)
                            <a href="{{ route('admin.sales.show', $order->convertedInvoice) }}">{{ $order->convertedInvoice->invoice_no }}</a>
                        @else
                            -
                        @endif
                    </td>
                    <td><span class="badge {{ $order->status === 'converted' ? 'badge-primary' : 'badge-warning' }}">{{ ucfirst($order->status) }}</span></td>
                    <td>{{ $order->item?->productCategory?->name ?: '-' }}</td>
                    <td>{{ $order->item?->name }}</td>
                    <td>{{ number_format((float)$order->quantity,2) }} {{ $order->unit }}</td>
                    @php $stockNow = (float)($stockByItem[$order->item_id] ?? 0); @endphp
                    <td><span class="badge {{ $stockNow >= (float)$order->quantity ? 'badge-success' : 'badge-danger' }}">{{ number_format($stockNow,2) }}</span></td>
                    <td>Rs {{ number_format((float)$order->line_total,2) }}</td>
                    <td>Rs {{ number_format((float)$order->cost_amount,2) }}</td>
                    <td>Rs {{ number_format((float)$order->profit_amount,2) }}</td>
                    <td>{{ number_format((float)$order->profit_percent,2) }}%</td>
                    <td class="text-nowrap">
                        <a class="btn btn-sm {{ $order->status === 'pending' && $stockNow >= (float)$order->quantity ? 'btn-success' : 'btn-secondary disabled' }}" href="{{ $order->status === 'pending' && $stockNow >= (float)$order->quantity ? route('admin.pending-orders.convert-form', $order) : '#' }}"><i class="fas fa-exchange-alt"></i></a>
                        <button class="btn btn-sm btn-info detail-btn" data-detail='@json($order->raw_materials ?? [])' data-title="{{ $order->item?->name }}"><i class="fas fa-eye"></i></button>
                    </td>
                </tr>@endforeach</tbody>
            </table>
        </div>
    </div>
</div>
<div class="modal fade" id="detailModal"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Item Details</h5><button class="close" data-dismiss="modal">&times;</button></div><div class="modal-body"><table class="table"><thead><tr><th>Raw Material</th><th>Qty</th><th>Rate</th><th>Amount</th></tr></thead><tbody id="rawRows"></tbody></table></div></div></div></div>
@endsection
@push('scripts')
@include('admin.partials.segment-viz-scripts')
<script>
$('#pendingTable').DataTable({pageLength:25, columnDefs:[{orderable:false, targets:13}]});
$(document).on('click','.detail-btn',function(){let rows=$(this).data('detail')||[];$('.modal-title').text($(this).data('title'));$('#rawRows').html(rows.length?rows.map(r=>`<tr><td>${r.name}</td><td>${Number(r.qty||0).toFixed(2)} ${r.unit||''}</td><td>Rs ${Number(r.unit_price||0).toFixed(2)}</td><td>Rs ${Number(r.amount||0).toFixed(2)}</td></tr>`).join(''):'<tr><td colspan="4">No raw material details.</td></tr>');$('#detailModal').modal('show')});
</script>
@endpush
