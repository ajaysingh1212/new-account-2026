@extends('layouts.admin')
@section('title','Stock History')
@section('content')
<div class="card">
    <div class="card-body">
        <form method="GET" class="row align-items-end">
            <div class="col-md-3"><label>Item</label><select name="item_id" class="form-control select2"><option value="">All Items</option>@foreach($items as $item)<option value="{{ $item->id }}" @selected(request('item_id')==$item->id)>{{ $item->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label>Period</label><select name="period" class="form-control" id="movementPeriod"><option value="today" @selected($period==='today')>Today</option><option value="week" @selected($period==='week')>This Week</option><option value="month" @selected($period==='month')>This Month</option><option value="year" @selected($period==='year')>This Year</option><option value="custom" @selected($period==='custom')>Custom Date</option></select></div>
            <div class="col-md-2 custom-date"><label>From</label><input type="date" name="from_date" class="form-control" value="{{ $from }}"></div>
            <div class="col-md-2 custom-date"><label>To</label><input type="date" name="to_date" class="form-control" value="{{ $to }}"></div>
            <div class="col-md-3"><label>Serial / SKU</label><input name="q" class="form-control" value="{{ $serialSearch }}" placeholder="Serial / VTS / SKU / reference"><button class="btn btn-primary mt-2">Filter</button><a href="{{ route('admin.stocks.history') }}" class="btn btn-light mt-2">Reset</a></div>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-body table-responsive">
        <table id="historyTable" class="table table-hover">
            <thead><tr><th>Type</th><th>Date</th><th>Item</th><th>Event</th><th>Previous Qty</th><th>Change</th><th>New Qty</th><th>Party / Note</th><th>Ref</th><th>By</th><th>Role</th></tr></thead>
            <tbody>
            @foreach($historyRows as $row)
                <tr>
                    <td>
                        @if($row['kind'] === 'adjustment')
                            <span class="badge badge-primary">Manual</span>
                        @else
                            <span class="badge badge-info">Movement</span>
                        @endif
                    </td>
                    <td>{{ optional($row['event_at'])->format('d M Y h:i A') }}</td>
                    <td>{{ $row['item']?->name }}<br><small class="text-muted">{{ $row['item']?->sku ?: $row['item']?->item_code }}</small></td>
                    <td>
                        {{ $row['title'] }}
                        @if($row['kind'] === 'adjustment')
                            <br><small class="text-muted">Raw material stock maintained by admin</small>
                        @endif
                    </td>
                    <td>{{ number_format((float) $row['previous_stock'], 3) }}</td>
                    <td>
                        @if((float) $row['change'] >= 0)
                            <span class="badge badge-success">+{{ number_format((float) $row['change'], 3) }}</span>
                        @else
                            <span class="badge badge-danger">{{ number_format((float) $row['change'], 3) }}</span>
                        @endif
                    </td>
                    <td>{{ number_format((float) $row['new_stock'], 3) }}</td>
                    <td>
                        {{ $row['party'] ?: ($row['note'] ?: '-') }}
                    </td>
                    <td>{{ $row['reference'] ?: '-' }}</td>
                    <td><strong>{{ $row['actor'] ?? 'System' }}</strong></td>
                    <td><small class="text-muted">{{ $row['role'] ?? 'No role' }}</small></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
@push('scripts')<script>$('#historyTable').DataTable({pageLength:25});function toggleMovementDates(){ $('.custom-date').toggle($('#movementPeriod').val()==='custom'); } $('#movementPeriod').on('change',toggleMovementDates);toggleMovementDates();</script>@endpush
