@extends('layouts.admin')
@section('title','Return Trace Report')
@section('content')
<div data-export-title="Item / Return Trace Report" data-export-file="item-trace">@include('admin.reports.partials.branded-export')</div>
<div class="card">
    <div class="card-header"><h3 class="card-title m-0">Return Trace Report</h3></div>
    <div class="card-body">
        <form method="GET" class="row align-items-end">
            <div class="col-md-3 form-group"><label>Search Type</label><select name="type" class="form-control"><option value="invoice" @selected($type==='invoice')>Invoice Number</option><option value="buyer" @selected($type==='buyer')>Buyer Code / Serial / Purchase Batch</option><option value="production" @selected($type==='production')>Production Batch Number</option></select></div>
            <div class="col-md-5 form-group"><label>Search Value</label><input name="q" class="form-control" value="{{ $term }}" placeholder="Invoice no, buyer code, serial no, production no"></div>
            <div class="col-md-2 form-group"><button class="btn btn-primary"><i class="fas fa-search mr-1"></i>Trace</button></div>
        </form>
    </div>
</div>

@if($result)
    @if($type === 'invoice')
        @php($invoice = $result['sales'])
        @if($invoice)
        <div class="card"><div class="card-header"><h3 class="card-title m-0">Invoice {{ $invoice->invoice_no }}</h3></div><div class="card-body">
            <p><b>Sale Date:</b> {{ $invoice->billing_date?->format('d M Y') }} | <b>Sold To:</b> {{ $invoice->party?->display_name ?: 'Cash' }} | <b>Total:</b> Rs {{ number_format((float)$invoice->grand_total,2) }}</p>
            <table class="table table-hover"><thead><tr><th>Item</th><th>Qty</th><th>Selected Unit</th><th>Production</th><th>Buyer</th></tr></thead><tbody>
            @foreach($invoice->items as $line)
                @foreach(($line->selected_units ?: [[]]) as $unit)
                    <tr><td>{{ $line->item?->name }}</td><td>{{ $line->quantity }}</td><td>{{ $unit['serial_no'] ?? '-' }} / {{ $unit['batch_no'] ?? '-' }}</td><td>{{ $unit['production_batch_no'] ?? '-' }}</td><td>{{ $unit['buyer_code'] ?? '-' }}</td></tr>
                @endforeach
            @endforeach
            </tbody></table>
        </div></div>
        @else <div class="alert alert-warning">No invoice found.</div>@endif
    @elseif($type === 'production')
        @php($batch = $result['production'])
        @if($batch)
        <div class="card"><div class="card-header"><h3 class="card-title m-0">Production {{ $batch->batch_no }}</h3></div><div class="card-body">
            <p><b>Finished Item:</b> {{ $batch->finishedItem?->name }} | <b>Date:</b> {{ $batch->production_date?->format('d M Y') }} | <b>Qty:</b> {{ $batch->quantity }}</p>
            <h5>Produced Units</h5>
            <table class="table"><thead><tr><th>Buyer</th><th>Serial</th><th>Purchase Batch</th><th>Warehouse</th></tr></thead><tbody>@foreach(($batch->units_data ?? []) as $unit)<tr><td>{{ $unit['buyer_code'] ?? '-' }}</td><td>{{ $unit['serial_no'] ?? '-' }}</td><td>{{ $unit['batch_no'] ?? '-' }}</td><td>{{ $unit['warehouse'] ?? '-' }}</td></tr>@endforeach</tbody></table>
            <h5>Raw Material / Purchase Detail</h5>
            <table class="table"><thead><tr><th>Raw Item</th><th>Qty Per Unit</th><th>Purchase Price</th></tr></thead><tbody>@foreach($batch->finishedItem?->bomMaterials ?? [] as $bom)<tr><td>{{ $bom->rawItem?->name }}</td><td>{{ $bom->qty_per_unit }}</td><td>Rs {{ number_format((float)($bom->rawItem?->purchase_price ?? 0),2) }}</td></tr>@endforeach</tbody></table>
        </div></div>
        @else <div class="alert alert-warning">No production batch found.</div>@endif
    @else
        <div class="card"><div class="card-header"><h3 class="card-title m-0">Unit Trace</h3></div><div class="card-body">
            <h5>Production Matches</h5>
            <table class="table"><thead><tr><th>Production</th><th>Item</th><th>Buyer</th><th>Serial</th><th>Purchase Batch</th></tr></thead><tbody>@foreach($result['unitMatches'] as $match)<tr><td>{{ $match['batch']->batch_no }}</td><td>{{ $match['batch']->finishedItem?->name }}</td><td>{{ $match['unit']['buyer_code'] ?? '-' }}</td><td>{{ $match['unit']['serial_no'] ?? '-' }}</td><td>{{ $match['unit']['batch_no'] ?? '-' }}</td></tr>@endforeach</tbody></table>
            <h5>Sales Matches</h5>
            <table class="table"><thead><tr><th>Invoice</th><th>Date</th><th>Sold To</th><th>Total</th></tr></thead><tbody>@foreach($result['sales'] as $invoice)<tr><td>{{ $invoice->invoice_no }}</td><td>{{ $invoice->billing_date?->format('d M Y') }}</td><td>{{ $invoice->party?->display_name ?: 'Cash' }}</td><td>Rs {{ number_format((float)$invoice->grand_total,2) }}</td></tr>@endforeach</tbody></table>
        </div></div>
    @endif
@endif
@endsection
