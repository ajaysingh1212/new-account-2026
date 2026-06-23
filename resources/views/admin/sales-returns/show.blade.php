@extends('layouts.admin')
@section('title','Sales Return')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title m-0">Sales Return {{ $return->return_no }}</h3></div><div class="card-body">
<p><b>Invoice:</b> {{ $return->invoice?->invoice_no }} | <b>Party:</b> {{ $return->party?->display_name ?: 'Cash' }} | <b>Total:</b> Rs {{ number_format((float)$return->grand_total,2) }}</p>
<table class="table"><thead><tr><th>Item</th><th>Qty</th><th>Returned Serials</th><th>Tax</th><th>Total</th></tr></thead><tbody>@foreach($return->items as $line)<tr><td>{{ $line->item?->name }}</td><td>{{ $line->quantity }}</td><td>@forelse(($line->selected_units ?? []) as $unit)<span class="badge badge-info mr-1 mb-1">{{ $unit['serial_no'] ?? $unit['vts_sim'] ?? $unit['buyer_code'] ?? $unit['key'] ?? 'Serial' }}</span>@empty<span class="text-muted">-</span>@endforelse</td><td>Rs {{ number_format((float)$line->tax_amount,2) }}</td><td>Rs {{ number_format((float)$line->line_total,2) }}</td></tr>@endforeach</tbody></table>
</div></div>
@endsection
