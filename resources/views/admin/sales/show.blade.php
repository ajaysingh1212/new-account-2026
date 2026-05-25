@extends('layouts.admin')
@section('title','Sales Invoice')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0">Sale {{ $invoice->invoice_no }}</h3>
        <div><a href="{{ route('admin.sales.edit',$invoice) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit mr-1"></i>Edit</a> <a href="{{ route('admin.sales.index') }}" class="btn btn-secondary btn-sm">Back</a></div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3"><b>Party</b><br>{{ $invoice->party?->display_name ?: 'Cash' }}</div>
            <div class="col-md-2"><b>Date</b><br>{{ $invoice->billing_date?->format('d M Y') }}</div>
            <div class="col-md-2"><b>Type</b><br>{{ ucfirst($invoice->sale_type) }}</div>
            <div class="col-md-2"><b>Total</b><br>Rs {{ number_format((float)$invoice->grand_total,2) }}</div>
            <div class="col-md-3">@if($invoice->attachment)<b>Attachment</b><br><a href="{{ asset('storage/'.$invoice->attachment) }}" target="_blank">Open attachment</a>@endif</div>
        </div>
        <table class="table table-hover">
            <thead><tr><th>Item</th><th>Qty</th><th>Selected Finished Goods</th><th>Price</th><th>Tax</th><th>Total</th></tr></thead>
            <tbody>
            @foreach($invoice->items as $line)
                <tr>
                    <td>{{ $line->item?->name }}</td>
                    <td>{{ $line->quantity }}</td>
                    <td>@foreach(($line->selected_units ?? []) as $unit)<span class="badge badge-info mr-1">{{ $unit['serial_no'] ?? 'No serial' }} / {{ $unit['batch_no'] ?? '-' }}</span>@endforeach</td>
                    <td>Rs {{ number_format((float)$line->unit_price,2) }}</td>
                    <td>Rs {{ number_format((float)$line->tax_amount,2) }}</td>
                    <td>Rs {{ number_format((float)$line->line_total,2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@include('admin.partials.update-history', ['auditLogs' => $auditLogs])
@endsection
