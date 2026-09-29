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
            <div class="col-md-2"><b>PO Date</b><br>{{ $invoice->po_date?->format('d M Y') ?: '-' }}</div>
            <div class="col-md-2"><b>Type</b><br>{{ ucfirst($invoice->sale_type) }}</div>
            <div class="col-md-2"><b>Total</b><br>Rs {{ number_format((float)($invoiceReturnDetails['net_total'] ?? $invoice->grand_total),2) }}
                @if($invoiceReturnDetails['has_return'] ?? false)<br><small class="text-muted">Original: Rs {{ number_format((float)$invoice->grand_total,2) }}</small>@endif
            </div>
            <div class="col-md-3">@if($invoice->attachment)<b>Attachment</b><br><a href="{{ asset('storage/'.$invoice->attachment) }}" target="_blank">Open attachment</a>@endif</div>
        </div>
        @if($invoice->sourceDeliveryChallan)
            <div class="alert alert-info py-2"><b>Source Delivery Challan:</b> <a href="{{ route('admin.delivery-challans.show', $invoice->sourceDeliveryChallan) }}">{{ $invoice->sourceDeliveryChallan->challan_no }}</a></div>
        @elseif($invoice->sourcePendingOrder)
            <div class="alert alert-info py-2"><b>Source Pending Order:</b> #{{ $invoice->sourcePendingOrder->id }}</div>
        @endif
        @if($invoiceReturnDetails['has_return'] ?? false)
            <div class="alert alert-warning">
                <b>This invoice has sales return activity.</b>
                Returned quantity: {{ number_format((float) ($invoiceReturnDetails['returned_qty'] ?? 0), 3) }} |
                Returned amount minus: Rs {{ number_format((float) ($invoiceReturnDetails['returned_amount'] ?? 0), 2) }} |
                Net invoice value: Rs {{ number_format((float) ($invoiceReturnDetails['net_total'] ?? $invoice->grand_total), 2) }}
            </div>
        @endif
        <table class="table table-hover">
            <thead><tr><th>Item</th><th>Sold Qty</th><th>Returned Qty</th><th>Remaining</th><th>Selected Finished Goods</th><th>Price</th><th>Tax</th><th>Total</th><th>Returned Amount</th><th>Net Total</th><th>Returns</th></tr></thead>
            <tbody>
            @foreach($invoice->items as $line)
                @php($lineSummary = collect($invoiceReturnDetails['items'] ?? [])->firstWhere('line_id', $line->id))
                <tr class="{{ ($lineSummary['returned_qty'] ?? 0) > 0 ? 'table-warning' : '' }}">
                    <td>{{ $line->item?->name }}</td>
                    <td>{{ $lineSummary['sold_qty'] ?? $line->quantity }}</td>
                    <td class="{{ ($lineSummary['returned_qty'] ?? 0) > 0 ? 'text-warning' : '' }}">{{ number_format((float) ($lineSummary['returned_qty'] ?? 0), 3) }}</td>
                    <td>{{ number_format((float) ($lineSummary['remaining_qty'] ?? $line->quantity), 3) }}</td>
                    <td>@foreach(($line->selected_units ?? []) as $unit)<span class="badge badge-info mr-1">{{ $unit['serial_no'] ?? 'No serial' }} / {{ $unit['batch_no'] ?? '-' }}@if(!empty($unit['vts_sim'])) / {{ $unit['vts_sim'] }}@endif</span>@endforeach</td>
                    <td>Rs {{ number_format((float)$line->unit_price,2) }}</td>
                    <td>Rs {{ number_format((float)$line->tax_amount,2) }}</td>
                    <td>Rs {{ number_format((float)$line->line_total,2) }}</td>
                    <td class="{{ ($lineSummary['returned_amount'] ?? 0) > 0 ? 'text-danger font-weight-bold' : '' }}">- Rs {{ number_format((float) ($lineSummary['returned_amount'] ?? 0), 2) }}</td>
                    <td class="font-weight-bold">Rs {{ number_format((float) ($lineSummary['net_amount'] ?? $line->line_total), 2) }}</td>
                    <td>
                        @forelse(($lineSummary['returns'] ?? []) as $returnRow)
                            <div class="mb-1">
                                <b>{{ $returnRow['return_no'] }}</b><br>
                                <small class="text-muted">{{ $returnRow['return_date'] }} | Qty {{ number_format((float) $returnRow['return_qty'], 3) }} | Rs {{ number_format((float) ($returnRow['return_amount'] ?? 0), 2) }} minus | {{ $returnRow['returned_by'] }}</small>
                            </div>
                        @empty
                            <span class="text-muted">-</span>
                        @endforelse
                    </td>
                </tr>
            @endforeach
            </tbody>
            @if($invoiceReturnDetails['has_return'] ?? false)
            <tfoot>
                <tr><th colspan="9" class="text-right">Original Invoice Total</th><th colspan="2">Rs {{ number_format((float)$invoice->grand_total,2) }}</th></tr>
                <tr><th colspan="9" class="text-right text-danger">Less Sales Return</th><th colspan="2" class="text-danger">- Rs {{ number_format((float)($invoiceReturnDetails['returned_amount'] ?? 0),2) }}</th></tr>
                <tr><th colspan="9" class="text-right">Net Invoice Value</th><th colspan="2">Rs {{ number_format((float)($invoiceReturnDetails['net_total'] ?? $invoice->grand_total),2) }}</th></tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@include('admin.partials.update-history', ['auditLogs' => $auditLogs])
@endsection
