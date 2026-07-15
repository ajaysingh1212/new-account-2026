@extends('layouts.admin')
@section('title','Estimate')
@section('content')
@php($canManage = app(\App\Services\EntryVisibilityService::class)->canManage(auth()->user(), $estimate))
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0">Estimate {{ $estimate->estimate_no }}</h3>
        <div>
            @if($canManage && $estimate->status !== 'converted' && $estimate->status !== 'cancelled')
                @can('estimates.convert')<a href="{{ route('admin.estimates.convert-form', $estimate) }}" class="btn btn-success btn-sm"><i class="fas fa-sync mr-1"></i> Convert to Sale</a>@endcan
                @can('estimates.edit')<form method="POST" action="{{ route('admin.estimates.cancel', $estimate) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-danger btn-sm"><i class="fas fa-times mr-1"></i> Cancel</button></form>@endcan
                @can('estimates.edit')<a href="{{ route('admin.estimates.edit', $estimate) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit mr-1"></i> Edit</a>@endcan
                @can('estimates.delete')<form method="POST" action="{{ route('admin.estimates.destroy', $estimate) }}" class="d-inline">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash mr-1"></i> Delete</button></form>@endcan
            @endif
            @can('estimates.print')<a href="{{ route('admin.estimates.print', $estimate) }}" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-print mr-1"></i> Print</a>@endcan
        </div>
    </div>
    <div class="card-body">
        <p><b>Party:</b> {{ $estimate->party?->display_name ?: 'Walk-in' }} | <b>Total:</b> Rs {{ number_format((float)$estimate->grand_total,2) }} | <b>Status:</b> {{ ucfirst($estimate->status) }}</p>
        @if($estimate->convertedInvoice)<p><b>Converted Sale:</b> <a href="{{ route('admin.sales.show', $estimate->convertedInvoice) }}">{{ $estimate->convertedInvoice->invoice_no }}</a></p>@endif
        <table class="table"><thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Tax</th><th>Total</th></tr></thead><tbody>@foreach($estimate->items as $line)<tr><td>{{ $line->item?->name }}</td><td>{{ $line->quantity }}</td><td>Rs {{ number_format((float)$line->unit_price,2) }}</td><td>Rs {{ number_format((float)$line->tax_amount,2) }}</td><td>Rs {{ number_format((float)$line->line_total,2) }}</td></tr>@endforeach</tbody></table>
    </div>
</div>
@endsection
