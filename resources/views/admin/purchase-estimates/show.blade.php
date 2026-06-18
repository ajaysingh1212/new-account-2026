@extends('layouts.admin')
@section('title','Purchase Estimate')
@section('content')
@php($canManage = app(\App\Services\EntryVisibilityService::class)->canManage(auth()->user(), $estimate))
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0">Purchase Estimate {{ $estimate->estimate_no }}</h3>
        <div>
            @if($canManage && $estimate->status !== 'converted' && $estimate->status !== 'cancelled')
                @can('purchase_estimates.convert')<form method="POST" action="{{ route('admin.purchase-estimates.convert', $estimate) }}" class="d-inline">@csrf <button class="btn btn-success btn-sm"><i class="fas fa-sync mr-1"></i> Convert to Purchase</button></form>@endcan
                @can('purchase_estimates.edit')<form method="POST" action="{{ route('admin.purchase-estimates.cancel', $estimate) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-danger btn-sm"><i class="fas fa-times mr-1"></i> Cancel</button></form>@endcan
                @can('purchase_estimates.edit')<a href="{{ route('admin.purchase-estimates.edit', $estimate) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit mr-1"></i> Edit</a>@endcan
                @can('purchase_estimates.delete')<form method="POST" action="{{ route('admin.purchase-estimates.destroy', $estimate) }}" class="d-inline">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash mr-1"></i> Delete</button></form>@endcan
            @endif
            @can('purchase_estimates.print')<a href="{{ route('admin.purchase-estimates.print', $estimate) }}" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-print mr-1"></i> Print</a>@endcan
            <a href="{{ route('admin.purchase-estimates.index') }}" class="btn btn-light btn-sm">Back</a>
        </div>
    </div>
    <div class="card-body">
        <p><b>Party:</b> {{ $estimate->party?->display_name ?: 'Cash / No Party' }} | <b>Total:</b> Rs {{ number_format((float)$estimate->grand_total,2) }} | <b>Status:</b> {{ ucfirst($estimate->status) }}</p>
        @if($estimate->convertedBill)<p><b>Converted Purchase:</b> <a href="{{ route('admin.purchases.show', $estimate->convertedBill) }}">{{ $estimate->convertedBill->invoice_no }}</a></p>@endif
        <table class="table"><thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Tax %</th><th>Tax</th><th>Total</th></tr></thead><tbody>@foreach($estimate->items as $line)<tr><td>{{ $line->item?->name }}</td><td>{{ $line->quantity }}</td><td>Rs {{ number_format((float)$line->unit_price,2) }}</td><td>{{ number_format((float)$line->tax_percent,2) }}%</td><td>Rs {{ number_format((float)$line->tax_amount,2) }}</td><td>Rs {{ number_format((float)$line->line_total,2) }}</td></tr>@endforeach</tbody></table>
    </div>
</div>
@endsection
