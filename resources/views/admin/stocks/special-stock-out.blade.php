@extends('layouts.admin')
@section('title','Special Stock Out Stock')
@section('content')
@if($isMaruti)
<div class="card">
    <div class="card-header"><h3 class="card-title">Maruti Suzuki — Saved Stock Out &amp; Restore History</h3></div>
    <div class="card-body">
        <p>Saved stock stays outside available stock until restored. Each serial can be restored individually and used in sales, estimates and delivery challans.</p>
        <div class="mb-3"><strong>Outside Stock:</strong> {{ number_format($marutiStockOuts->whereNull('restored_at')->sum('quantity'), 3) }} &nbsp; <strong>Restored:</strong> {{ number_format($marutiStockOuts->whereNotNull('restored_at')->sum('quantity'), 3) }}</div>
        <div class="table-responsive">
            <table id="marutiStockOutHistory" class="table table-sm table-bordered table-hover">
                <thead><tr><th>Saved At / Batch</th><th>Item</th><th>Serial</th><th>SIM / VTS</th><th>SKU / Unit Key</th><th>Qty</th><th>Notes / Saved By</th><th>Status / Restored By</th><th>Action</th></tr></thead>
                <tbody>
                @foreach($marutiStockOuts as $entry)
                    <tr>
                        <td data-order="{{ $entry->id }}">{{ $entry->created_at?->format('d M Y h:i A') }}<br><small>{{ $entry->batch_no }}</small></td>
                        <td>{{ $entry->item_name }}<br><small>{{ $entry->item_code }} | Batch: {{ $entry->unit_data['batch_no'] ?? $entry->unit_data['production_batch_no'] ?? '-' }}</small></td>
                        <td>{{ $entry->unit_data['serial_no'] ?? '-' }}</td>
                        <td>{{ $entry->unit_data['vts_sim'] ?? '-' }}</td>
                        <td>{{ $entry->sku ?: '-' }}<br><small>{{ $entry->unit_data['key'] ?? '-' }}</small></td>
                        <td>{{ number_format((float) $entry->quantity, 3) }} {{ $entry->unit }}</td>
                        <td>{{ $entry->notes ?: '-' }}<br><small>{{ $entry->creator?->name ?? 'System' }}</small></td>
                        <td>
                            @if($entry->restored_at)
                                <span class="badge badge-success">Restored</span><br><small>{{ $entry->restored_at->format('d M Y h:i A') }}<br>{{ $entry->restorer?->name ?? 'System' }}</small>
                            @else
                                <span class="badge badge-warning">Outside Stock</span>
                            @endif
                        </td>
                        <td>
                            @if($canManageMarutiStockOut && !$entry->restored_at)
                                <form method="POST" action="{{ route('admin.stocks.maruti-stock-out.restore', $entry->id) }}" class="maruti-restore-form">
                                    @csrf
                                    <button class="btn btn-success btn-sm" type="submit">Restore to Stock</button>
                                </form>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h3 class="card-title m-0">Special Stock Out Balance</h3>
            <small class="text-muted">Items issued through Special Stock Out and still tracked outside billing.</small>
        </div>
        <a href="{{ route('admin.stock-out-challans.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i>New Stock Out</a>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4"><div class="small text-muted">Total Entries</div><h4>{{ $challans->count() }}</h4></div>
            <div class="col-md-4"><div class="small text-muted">Total Quantity</div><h4>{{ number_format($rows->sum('quantity'),3) }}</h4></div>
            <div class="col-md-4"><div class="small text-muted">Display Value</div><h4>Rs {{ number_format($rows->sum('value'),2) }}</h4></div>
        </div>
        <div class="table-responsive">
            <table id="specialStockOutTable" class="table table-hover">
                <thead><tr><th>Challan</th><th>Date</th><th>Item</th><th>Qty</th><th>Receiver</th><th>Created By</th><th>Role</th><th>IP</th><th>Time</th></tr></thead>
                <tbody>
                @foreach($rows as $row)
                    @php($challan = $row['challan'])
                    <tr>
                        <td><a href="{{ route('admin.stock-out-challans.show', $challan) }}"><b>{{ $challan->challan_no }}</b></a></td>
                        <td>{{ $challan->challan_date?->format('d M Y') }}</td>
                        <td>{{ $row['item']?->name }}<br><small>{{ $row['item']?->item_code }}</small></td>
                        <td>{{ number_format($row['quantity'],3) }} {{ $row['unit'] }}</td>
                        <td>{{ $challan->display_party }}<br><small>{{ $challan->phone ?: '-' }}</small></td>
                        <td>{{ $challan->creator?->name ?? 'System' }}</td>
                        <td>{{ $challan->user_role ?: '-' }}</td>
                        <td>{{ $challan->ip_address ?: '-' }}</td>
                        <td>{{ $challan->created_at?->format('d M Y h:i A') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$('#specialStockOutTable').DataTable({pageLength:25,order:[[1,'desc']]});
$('#marutiStockOutHistory').DataTable({pageLength:25,order:[[0,'desc']]});
$('.maruti-restore-form').on('submit', function () { $(this).find('button').prop('disabled', true).text('Restoring…'); });
</script>
@endpush
