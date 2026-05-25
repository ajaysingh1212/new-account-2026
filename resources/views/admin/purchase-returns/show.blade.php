@extends('layouts.admin')
@section('title','Purchase Return')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title m-0">Purchase Return {{ $return->return_no }}</h3></div><div class="card-body">
<p><b>Bill:</b> {{ $return->bill?->invoice_no }} | <b>Party:</b> {{ $return->party?->display_name ?: 'Cash' }} | <b>Total:</b> Rs {{ number_format((float)$return->grand_total,2) }}</p>
<table class="table"><thead><tr><th>Item</th><th>Qty</th><th>Tax</th><th>Total</th></tr></thead><tbody>@foreach($return->items as $line)<tr><td>{{ $line->item?->name }}</td><td>{{ $line->quantity }}</td><td>Rs {{ number_format((float)$line->tax_amount,2) }}</td><td>Rs {{ number_format((float)$line->line_total,2) }}</td></tr>@endforeach</tbody></table>
</div></div>
@endsection
