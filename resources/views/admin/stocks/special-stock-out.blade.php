@extends('layouts.admin')
@section('title','Special Stock Out Stock')
@section('content')
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
@push('scripts')<script>$('#specialStockOutTable').DataTable({pageLength:25,order:[[1,'desc']]});</script>@endpush
