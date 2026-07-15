@extends('layouts.admin')
@section('title','Delivery Challan')
@section('content')
@php($canManage = app(\App\Services\EntryVisibilityService::class)->canManage(auth()->user(), $deliveryChallan))
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0">Delivery Challan {{ $deliveryChallan->challan_no }}</h3>
        <div>
            @if($canManage && $deliveryChallan->status !== 'cancelled')
                @can('delivery_challans.edit')
                    @if(!$deliveryChallan->convertedInvoice)
                        <form method="POST" action="{{ route('admin.delivery-challans.convert', $deliveryChallan) }}" class="d-inline" onsubmit="return confirm('Convert this delivery challan to sale? Stock will not change again.');">
                            @csrf
                            <button class="btn btn-success btn-sm"><i class="fas fa-sync mr-1"></i> Convert to Sale</button>
                        </form>
                    @endif
                @endcan
                @can('delivery_challans.edit')
                    @if(!$deliveryChallan->convertedInvoice)
                        <form method="POST" action="{{ route('admin.delivery-challans.cancel', $deliveryChallan) }}" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button class="btn btn-danger btn-sm"><i class="fas fa-times mr-1"></i> Cancel</button>
                        </form>
                    @endif
                @endcan
                @can('delivery_challans.edit')
                    @if(!$deliveryChallan->convertedInvoice)
                        <a href="{{ route('admin.delivery-challans.edit', $deliveryChallan) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit mr-1"></i> Edit</a>
                    @endif
                @endcan
            @endif
            @if($canManage)
                @can('delivery_challans.delete')
                    @if(!$deliveryChallan->convertedInvoice)
                        <form method="POST" action="{{ route('admin.delivery-challans.destroy', $deliveryChallan) }}" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash mr-1"></i> Delete</button>
                        </form>
                    @endif
                @endcan
            @endif
            @can('delivery_challans.print')<a href="{{ route('admin.delivery-challans.print', $deliveryChallan) }}" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-print mr-1"></i> Print</a>@endcan
        </div>
    </div>
    <div class="card-body">
        <p><b>Party:</b> {{ $deliveryChallan->party?->display_name ?: 'Walk-in' }} | <b>Date:</b> {{ $deliveryChallan->challan_date?->format('d M Y') }} | <b>Status:</b> {{ ucfirst($deliveryChallan->status) }}</p>
        @if($deliveryChallan->convertedInvoice)<p><b>Converted Sale:</b> <a href="{{ route('admin.sales.show', $deliveryChallan->convertedInvoice) }}">{{ $deliveryChallan->convertedInvoice->invoice_no }}</a></p>@endif
        <p><b>Dispatch:</b> {{ $deliveryChallan->dispatch_through ?: '-' }} | <b>Vehicle:</b> {{ $deliveryChallan->vehicle_no ?: '-' }} | <b>LR:</b> {{ $deliveryChallan->lr_no ?: '-' }}</p>
        <table class="table"><thead><tr><th>Item</th><th>Qty</th><th>Unit</th><th>Price</th><th>Total</th></tr></thead><tbody>@foreach($deliveryChallan->items as $line)<tr><td>{{ $line->item?->name }}</td><td>{{ $line->quantity }}</td><td>{{ $line->unit }}</td><td>Rs {{ number_format((float)$line->unit_price,2) }}</td><td>Rs {{ number_format((float)$line->line_total,2) }}</td></tr>@endforeach</tbody></table>
    </div>
</div>
@endsection
