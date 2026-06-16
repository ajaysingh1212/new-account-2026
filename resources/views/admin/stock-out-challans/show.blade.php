@extends('layouts.admin')
@section('title','Special Stock Out')
@section('content')
@php($canManage = app(\App\Services\EntryVisibilityService::class)->canManage(auth()->user(), $stockOutChallan))
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0">Special Stock Out {{ $stockOutChallan->challan_no }}</h3>
        <div>
            @if($canManage && $stockOutChallan->status !== 'cancelled')
                @can('stock_out_challans.edit')<form method="POST" action="{{ route('admin.stock-out-challans.cancel', $stockOutChallan) }}" class="d-inline" onsubmit="return confirm('Cancel and restore stock?')">@csrf @method('PATCH')<button class="btn btn-danger btn-sm"><i class="fas fa-times mr-1"></i>Cancel</button></form>@endcan
                @can('stock_out_challans.edit')<a href="{{ route('admin.stock-out-challans.edit', $stockOutChallan) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit mr-1"></i>Edit</a>@endcan
            @endif
            @if($canManage) @can('stock_out_challans.delete')<form method="POST" action="{{ route('admin.stock-out-challans.destroy', $stockOutChallan) }}" class="d-inline">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash mr-1"></i>Delete</button></form>@endcan @endif
            @can('stock_out_challans.print')<a href="{{ route('admin.stock-out-challans.print', $stockOutChallan) }}" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-print mr-1"></i>Print</a>@endcan
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3"><b>Receiver:</b><br>{{ $stockOutChallan->display_party }}</div>
            <div class="col-md-3"><b>Date:</b><br>{{ $stockOutChallan->challan_date?->format('d M Y') }}</div>
            <div class="col-md-3"><b>Status:</b><br>{{ ucfirst($stockOutChallan->status) }}</div>
            <div class="col-md-3"><b>Reference:</b><br>{{ $stockOutChallan->reference_no ?: '-' }}</div>
        </div>
        <div class="alert alert-light border">
            <b>Audit:</b> {{ $stockOutChallan->creator?->name ?? 'System' }} | Role: {{ $stockOutChallan->user_role ?: '-' }} | IP: {{ $stockOutChallan->ip_address ?: '-' }} | Created: {{ $stockOutChallan->created_at?->format('d M Y h:i A') }}
        </div>
        <table class="table"><thead><tr><th>Item</th><th>Description</th><th>Qty</th><th>Unit</th><th>Display Rate</th><th>Total</th></tr></thead><tbody>
            @foreach($stockOutChallan->items as $line)
                <tr><td>{{ $line->item?->name }}</td><td>{{ $line->description ?: '-' }}</td><td>{{ number_format((float)$line->quantity,3) }}</td><td>{{ $line->unit }}</td><td>Rs {{ number_format((float)$line->unit_price,2) }}</td><td>Rs {{ number_format((float)$line->line_total,2) }}</td></tr>
            @endforeach
        </tbody></table>
        @if($stockOutChallan->notes)<p><b>Notes:</b> {{ $stockOutChallan->notes }}</p>@endif
    </div>
</div>
@endsection
