@extends('layouts.admin')
@section('title','Current Stocks')

@push('styles')
<style>
.stock-head{background:#111827;color:#fff;border-radius:8px;padding:22px;margin-bottom:16px;display:flex;justify-content:space-between;gap:16px;align-items:center}.stock-head h2{margin:0;font-weight:800}.metric-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px}.metric{background:#fff;border:1px solid #e8edf5;border-radius:8px;padding:16px;box-shadow:0 10px 24px rgba(15,23,42,.06)}.metric small{display:block;text-transform:uppercase;color:#667085;font-weight:800;font-size:11px}.metric b{font-size:22px;color:#111827}.filter-card{background:#fff;border:1px solid #e8edf5;border-radius:8px;padding:14px;margin-bottom:16px}.stock-table-card{background:#fff;border:1px solid #e8edf5;border-radius:8px;padding:16px}.low-row{background:#fff7ed}.raw-adjust-btn{white-space:nowrap}.raw-chip{display:inline-flex;align-items:center;gap:6px;background:#eef2ff;color:#4338ca;border-radius:999px;padding:4px 10px;font-size:11px;font-weight:700}.replacement-drawer{position:fixed;top:0;right:-480px;width:460px;max-width:100%;height:100vh;background:#fff;z-index:1050;box-shadow:-20px 0 45px rgba(15,23,42,.2);transition:.25s;padding:20px;overflow:auto}.replacement-drawer.open{right:0}.replacement-item{border:1px solid #e5e7eb;border-radius:8px;padding:12px;margin-bottom:10px;cursor:pointer;background:#f8fafc}.replacement-item:hover{border-color:#0ea5e9;background:#f0f9ff}.mini-label{font-size:11px;text-transform:uppercase;color:#64748b;font-weight:800}.stock-modal-summary{background:#f8fafc;border:1px dashed #cbd5e1;border-radius:12px;padding:12px}@media(max-width:992px){.metric-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:576px){.metric-grid{grid-template-columns:1fr}.stock-head{display:block}}
</style>
@endpush

@section('content')
<div class="stock-head">
    <div>
        <h2><i class="fas fa-boxes mr-2"></i>Current Stock Valuation</h2>
        <small>Overall value, monthly stock movement value and item-level balance.</small>
    </div>
    <div>
        <a href="{{ route('admin.stocks.history') }}" class="btn btn-outline-light btn-sm"><i class="fas fa-clock-rotate-left mr-1"></i>History</a>
        <button type="button" id="openReplacementItems" class="btn btn-info btn-sm"><i class="fas fa-sync-alt mr-1"></i>Replacement Items</button>
        <a href="{{ route('admin.stocks.special-stock-out') }}" class="btn btn-warning btn-sm"><i class="fas fa-dolly mr-1"></i>Special Stock Out</a>
    </div>
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
        <div class="col-md-3 form-group mb-md-0"><label>Product Type</label><select name="product_type_id" class="form-control"><option value="">All Product Types</option>@foreach($productTypes as $type)<option value="{{ $type->id }}" @selected((string)$productTypeId === (string)$type->id)>{{ $type->name }}</option>@endforeach</select></div>
        <div class="col-md-2 form-group mb-md-0"><label>Serial / VTS / SKU</label><input name="q" class="form-control" value="{{ $serialSearch }}" placeholder="Search stock"></div>
        <div class="col-md-1"><button class="btn btn-primary"><i class="fas fa-filter mr-1"></i>Apply</button><a href="{{ route('admin.stocks.index') }}" class="btn btn-light mt-1">Reset</a></div>
    </div>
</form>

<div class="stock-table-card table-responsive">
    <table id="stockTable" class="table table-hover">
        <thead><tr><th>Item Id</th><th>Item</th><th>SKU</th><th>Nature</th><th>Stock</th><th>Incoming</th><th>Serials In Stock</th><th>Purchase Cost</th><th>Value</th><th>Low Warning</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        @foreach($items as $item)
            @php $isLow = $item->low_stock_qty && $item->current_stock <= $item->low_stock_qty; @endphp
            @php $isRawMaterial = ($item->productType?->nature === 'raw_material'); @endphp
            <tr class="{{ $isLow ? 'low-row' : '' }}">
                <td>{{ $item->id }}</td>
                <td>
                    <b>{{ $item->name }}</b><br>
                    <small class="text-muted">{{ $item->item_code }}</small>
                    @if($isRawMaterial)
                        <div class="mt-1"><span class="raw-chip"><i class="fas fa-industry"></i> Raw Material</span></div>
                    @endif
                </td>
                <td>{{ $item->sku ?: '-' }}</td>
                <td>{{ $item->productType?->name }}<br><small>{{ str_replace('_',' ', $item->productType?->nature ?? '-') }}</small></td>
                <td><b>{{ number_format((float)$item->current_stock,3) }}</b> {{ $item->unit }}</td>
                <td>@if((float)($incomingByItem[$item->id] ?? 0)>0)<span class="badge badge-info"><i class="fas fa-plus mr-1"></i>{{ number_format((float)$incomingByItem[$item->id],3) }} {{ $item->unit }}</span><small class="d-block text-muted">In transit — not in stock yet</small>@else<span class="text-muted">-</span>@endif</td>
                <td>
                    @php $stockUnits = collect($serialsByItem[$item->id] ?? []); @endphp
                    @forelse($stockUnits->take(10) as $unit)
                        <span class="badge badge-info mr-1 mb-1">{{ $unit['serial_no'] ?? $unit['vts_sim'] ?? $unit['sku'] ?? $unit['key'] ?? 'Unit' }}</span>
                    @empty
                        <span class="text-muted">-</span>
                    @endforelse
                    @if($stockUnits->count() > 10)<small class="text-muted">+{{ $stockUnits->count() - 10 }} more</small>@endif
                    <span class="d-none">{{ $stockUnits->flatMap(fn($unit) => [$unit['serial_no'] ?? null, $unit['vts_sim'] ?? null, $unit['sku'] ?? null, $unit['batch_no'] ?? null])->filter()->join(' ') }}</span>
                </td>
                <td>Rs {{ number_format((float)$item->purchase_price,2) }}</td>
                <td>Rs {{ number_format((float)$item->calculated_stock_value,2) }}</td>
                <td>@if($isLow)<span class="badge badge-warning">Low Stock</span>@else <span class="badge badge-success">OK</span>@endif</td>
                <td>{{ ucfirst($item->status) }}</td>
                <td>
                    @if($companyAdmin && $isRawMaterial)
                        <button type="button"
                            class="btn btn-outline-primary btn-sm raw-adjust-btn"
                            data-toggle="modal"
                            data-target="#stockAdjustModal"
                            data-item-id="{{ $item->id }}"
                            data-item-name="{{ $item->name }}"
                            data-item-code="{{ $item->item_code }}"
                            data-current-stock="{{ number_format((float)$item->current_stock, 3, '.', '') }}"
                            data-unit="{{ $item->unit }}"
                            data-purchase-price="{{ number_format((float)$item->purchase_price, 2, '.', '') }}">
                            <i class="fas fa-sliders-h mr-1"></i>Adjust
                        </button>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div id="replacementDrawer" class="replacement-drawer">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <div class="mini-label">Received through approval</div>
            <h4 class="m-0">Replacement Items</h4>
        </div>
        <button type="button" id="closeReplacementItems" class="btn btn-light btn-sm"><i class="fas fa-times"></i></button>
    </div>
    @forelse($replacementReceived as $group)
        <div class="replacement-item" data-toggle="modal" data-target="#stockReplacementModal{{ $group['item']->id }}">
            <div class="d-flex justify-content-between">
                <strong>{{ $group['item']->name }}</strong>
                <span class="badge badge-primary">{{ $group['quantity'] }} PCS</span>
            </div>
            <small class="text-muted">{{ $group['item']->sku ?: $group['item']->item_code }}</small>
        </div>
        <div class="modal fade" id="stockReplacementModal{{ $group['item']->id }}" tabindex="-1">
            <div class="modal-dialog modal-xl"><div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $group['item']->name }} Replacement History</h5>
                    <button class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body table-responsive">
                    <table class="table table-sm table-striped">
                        <thead><tr><th>Date</th><th>Party Details</th><th>Returned Serial</th><th>Issued Serial</th><th>Invoice / Sale</th><th>Customer</th><th>Status</th><th>Detail</th></tr></thead>
                        <tbody>
                        @foreach($group['rows'] as $row)
                            <tr>
                                <td>{{ $row->request_date?->format('d M Y') }}</td>
                                <td>{{ $row->party?->display_name ?: '-' }}<br><small>{{ $row->party?->phone }} {{ $row->party?->gstin ? '| GST '.$row->party?->gstin : '' }}</small></td>
                                <td>{{ $row->returned_unit['serial_no'] ?? $row->returned_unit['vts_sim'] ?? $row->returned_unit['buyer_code'] ?? $row->returned_unit['key'] ?? '-' }}<br><small>Batch: {{ $row->returned_unit['production_batch_no'] ?? $row->returned_unit['batch_no'] ?? '-' }} | {{ $row->returned_unit['production_date'] ?? '-' }}</small></td>
                                <td>{{ $row->issued_unit['serial_no'] ?? $row->issued_unit['vts_sim'] ?? $row->issued_unit['buyer_code'] ?? $row->issued_unit['key'] ?? '-' }}<br><small>Batch: {{ $row->issued_unit['production_batch_no'] ?? $row->issued_unit['batch_no'] ?? '-' }} | {{ $row->issued_unit['production_date'] ?? '-' }}</small></td>
                                <td>{{ $row->invoice?->invoice_no ?: '-' }}<br><small>{{ $row->invoice?->billing_date?->format('d M Y') }} | Sale Rs {{ number_format((float)($row->invoiceItem?->unit_price ?? 0),2) }}</small></td>
                                <td>{{ $row->customer_name }}<br><small>{{ $row->customer_phone }}</small></td>
                                <td><span class="badge badge-{{ $row->status === 'completed' ? 'success' : 'info' }}">{{ ucfirst($row->status) }}</span></td>
                                <td><a href="{{ route('admin.replacements.show', $row) }}" class="btn btn-outline-primary btn-xs">Open</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div></div>
        </div>
    @empty
        <div class="alert alert-light border">No approved replacement item has been received yet.</div>
    @endforelse
</div>

<div class="modal fade" id="stockAdjustModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <form id="stockAdjustForm" method="POST" action="">
                @csrf
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0">Raw Material Stock Adjustment</h5>
                        <small class="text-muted">Only company admin can maintain raw material quantity.</small>
                    </div>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="stock-modal-summary mb-3">
                        <div class="row">
                            <div class="col-md-4 mb-2 mb-md-0">
                                <small class="text-muted d-block">Item</small>
                                <strong id="adjustItemName">-</strong><br>
                                <span class="text-muted" id="adjustItemCode">-</span>
                            </div>
                            <div class="col-md-4 mb-2 mb-md-0">
                                <small class="text-muted d-block">Current Stock</small>
                                <strong id="adjustCurrentStock">0.000</strong>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Purchase Price</small>
                                <strong id="adjustPurchasePrice">Rs 0.00</strong>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Target Stock Quantity</label>
                        <input type="number" step="0.001" min="0" name="target_stock" id="adjustTargetStock" class="form-control" required>
                        <small class="text-muted">Enter the actual stock you want to keep after maintenance.</small>
                    </div>

                    <div class="form-group">
                        <label>Note</label>
                        <textarea name="note" class="form-control" rows="3" placeholder="Optional reason for this adjustment"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Save Change</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$('#stockTable').DataTable({pageLength:25,order:[[0,'asc']]});
$('#openReplacementItems').on('click',function(){$('#replacementDrawer').addClass('open');});
$('#closeReplacementItems').on('click',function(){$('#replacementDrawer').removeClass('open');});

$('#stockAdjustModal').on('show.bs.modal', function (event) {
    const button = $(event.relatedTarget);
    const itemId = button.data('item-id');
    const itemName = button.data('item-name');
    const itemCode = button.data('item-code');
    const currentStock = button.data('current-stock');
    const unit = button.data('unit');
    const purchasePrice = button.data('purchase-price');
    const action = '{{ url('admin/stocks') }}' + '/' + itemId + '/adjust';

    const modal = $(this);
    modal.find('#stockAdjustForm').attr('action', action);
    modal.find('#adjustItemName').text(itemName);
    modal.find('#adjustItemCode').text(itemCode);
    modal.find('#adjustCurrentStock').text(Number(currentStock).toFixed(3) + ' ' + unit);
    modal.find('#adjustPurchasePrice').text('Rs ' + Number(purchasePrice).toFixed(2));
    modal.find('#adjustTargetStock').val(Number(currentStock).toFixed(3));
});

$('#stockAdjustForm').on('submit', function (e) {
    e.preventDefault();
    const form = this;
    Swal.fire({
        title: 'Change stock quantity?',
        text: 'Ye raw material stock update hoga aur history me save rahega.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, save change',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
});

@if(session('success'))
Swal.fire({
    icon: 'success',
    title: 'Saved',
    text: @json(session('success')),
});
@endif
</script>
@endpush
