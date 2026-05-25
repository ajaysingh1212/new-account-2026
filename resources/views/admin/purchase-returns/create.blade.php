@extends('layouts.admin')
@section('title','New Purchase Return')
@section('content')
<form method="POST" action="{{ route('admin.purchase-returns.store') }}">@csrf
<div class="card">
    <div class="card-header"><h3 class="card-title m-0">New Purchase Return</h3></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 form-group"><label>Purchase Bill</label><select name="purchase_bill_id" id="sourceBill" class="form-control select2" required><option value="">Select bill</option>@foreach($bills as $bill)<option value="{{ $bill->id }}">{{ $bill->invoice_no }} | {{ $bill->party?->display_name ?: 'Cash' }} | Rs {{ number_format((float)$bill->grand_total,2) }}</option>@endforeach</select></div>
            <div class="col-md-2 form-group"><label>Return No</label><input name="return_no" class="form-control" value="{{ $returnNo }}"></div>
            <div class="col-md-2 form-group"><label>Date</label><input type="date" name="return_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
            <div class="col-md-4 form-group"><label>Reason</label><input name="reason" class="form-control"></div>
        </div>
        <div id="returnLines"></div>
        @include('admin.partials.entry-visibility')
    </div>
    <div class="card-footer text-right"><button class="btn btn-primary">Post Purchase Return</button></div>
</div>
</form>
@endsection
@push('scripts')
<script>
const BILLS=@json($bills->mapWithKeys(fn($bill)=>[$bill->id=>$bill->items->map(fn($line)=>['id'=>$line->id,'item'=>$line->item?->name,'qty'=>(float)$line->quantity,'unit'=>$line->unit])->values()]));
$('#sourceBill').change(function(){let lines=BILLS[$(this).val()]||[];$('#returnLines').html(`<table class="table table-hover"><thead><tr><th>Item</th><th>Purchased Qty</th><th>Return Qty</th></tr></thead><tbody>${lines.map(l=>`<tr><td>${l.item}<input type="hidden" name="line_id[]" value="${l.id}"></td><td>${l.qty} ${l.unit||''}</td><td><input type="number" step="0.001" max="${l.qty}" min="0" name="quantity[]" class="form-control" value="${l.qty}"></td></tr>`).join('')}</tbody></table>`)});
</script>
@endpush
