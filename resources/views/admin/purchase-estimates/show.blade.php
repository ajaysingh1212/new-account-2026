@extends('layouts.admin')
@section('title','Purchase Estimate')
@section('content')
@php($canManage = app(\App\Services\EntryVisibilityService::class)->canManage(auth()->user(), $estimate))
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0">Purchase Estimate {{ $estimate->estimate_no }}</h3>
        <div>
            @if($canManage && $estimate->status !== 'converted' && $estimate->status !== 'cancelled')
                @can('purchase_estimates.convert')
                    @if($estimate->status === 'draft')<button class="btn btn-info btn-sm" data-toggle="modal" data-target="#transitModal"><i class="fas fa-truck mr-1"></i> Mark In Transit</button>@endif
                    @if($estimate->status === 'transit')<button class="btn btn-success btn-sm" data-toggle="modal" data-target="#receiveModal"><i class="fas fa-box-open mr-1"></i> Receive & Convert</button>@endif
                @endcan
                @can('purchase_estimates.edit')<form method="POST" action="{{ route('admin.purchase-estimates.cancel', $estimate) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-danger btn-sm"><i class="fas fa-times mr-1"></i> Cancel</button></form>@endcan
                @can('purchase_estimates.edit') @if($estimate->status === 'draft')<a href="{{ route('admin.purchase-estimates.edit', $estimate) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit mr-1"></i> Edit</a>@endif @endcan
                @can('purchase_estimates.delete')<form method="POST" action="{{ route('admin.purchase-estimates.destroy', $estimate) }}" class="d-inline">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash mr-1"></i> Delete</button></form>@endcan
            @endif
            @can('purchase_estimates.print')<a href="{{ route('admin.purchase-estimates.print', $estimate) }}" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-print mr-1"></i> Print</a>@endcan
            <a href="{{ route('admin.purchase-estimates.index') }}" class="btn btn-light btn-sm">Back</a>
        </div>
    </div>
    <div class="card-body">
        <p><b>Party:</b> {{ $estimate->party?->display_name ?: 'Cash / No Party' }} | <b>Total:</b> Rs {{ number_format((float)$estimate->grand_total,2) }} | <b>Status:</b> {{ ucfirst($estimate->status) }} @if($estimate->is_smart_purchase)<span class="badge badge-primary ml-2">Smart Purchase</span>@endif</p>
        @if($estimate->status === 'transit')<div class="alert alert-info"><i class="fas fa-truck mr-2"></i>Items are in transit since {{ $estimate->transit_at?->format('d M Y h:i A') }}. Payment: <b>{{ $estimate->payment_completed ? 'Paid via '.($estimate->paymentBankAccount?->account_name ?: 'Bank') : 'Not paid — will become payable on receipt' }}</b></div>@endif
        @if($estimate->convertedBill)<p><b>Converted Purchase:</b> <a href="{{ route('admin.purchases.show', $estimate->convertedBill) }}">{{ $estimate->convertedBill->invoice_no }}</a></p>@endif
        <table class="table"><thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Tax %</th><th>Tax</th><th>Total</th></tr></thead><tbody>@foreach($estimate->items as $line)<tr><td>{{ $line->item?->name }}</td><td>{{ $line->quantity }}</td><td>Rs {{ number_format((float)$line->unit_price,2) }}</td><td>{{ number_format((float)$line->tax_percent,2) }}%</td><td>Rs {{ number_format((float)$line->tax_amount,2) }}</td><td>Rs {{ number_format((float)$line->line_total,2) }}</td></tr>@endforeach</tbody></table>
    </div>
</div>
<div class="modal fade" id="transitModal"><div class="modal-dialog"><div class="modal-content"><form method="POST" action="{{ route('admin.purchase-estimates.transit',$estimate) }}">@csrf<div class="modal-header"><h5>Move Items to Transit</h5><button class="close" data-dismiss="modal">&times;</button></div><div class="modal-body"><div class="form-group"><label>Payment Status</label><select name="payment_completed" id="paymentCompleted" class="form-control" required><option value="0">Not Paid (Payable)</option><option value="1">Payment Completed</option></select></div><div id="paymentFields" class="d-none"><div class="form-group"><label>Paid From Bank</label><select name="payment_bank_account_id" class="form-control"><option value="">Select Bank</option>@foreach($bankAccounts as $bank)<option value="{{ $bank->id }}">{{ $bank->account_name }} (Rs {{ number_format((float)$bank->current_balance,2) }})</option>@endforeach</select></div><div class="row"><div class="col-6 form-group"><label>Payment Mode</label><select name="payment_mode" class="form-control"><option>Bank Transfer</option><option>UPI</option><option>Cheque</option><option>Cash</option></select></div><div class="col-6 form-group"><label>Reference ID</label><input name="payment_reference" class="form-control"></div></div></div></div><div class="modal-footer"><button class="btn btn-info">Confirm Transit</button></div></form></div></div></div>
<div class="modal fade" id="receiveModal"><div class="modal-dialog modal-lg"><div class="modal-content"><form method="POST" action="{{ route('admin.purchase-estimates.convert',$estimate) }}">@csrf<div class="modal-header"><h5>Verify Received Quantities</h5><button class="close" data-dismiss="modal">&times;</button></div><div class="modal-body"><p class="text-muted">Actual quantity kam aayi ho to yahan reduce karein. Final purchase, stock and ledger isi quantity se banenge.</p><table class="table"><thead><tr><th>Item</th><th>Ordered</th><th>Received</th></tr></thead><tbody>@foreach($estimate->items as $line)<tr><td>{{ $line->item?->name }}</td><td>{{ number_format((float)$line->quantity,3) }} {{ $line->unit }}</td><td><input type="number" name="received_quantity[{{ $line->id }}]" value="{{ (float)$line->quantity }}" min="0" max="{{ (float)$line->quantity }}" step="0.001" class="form-control" required></td></tr>@endforeach</tbody></table></div><div class="modal-footer"><button class="btn btn-success">Create Final Purchase</button></div></form></div></div></div>
@endsection
@push('scripts')<script>$('#paymentCompleted').on('change',function(){$('#paymentFields').toggleClass('d-none',this.value!=='1');$('#paymentFields').find('select,input').prop('required',this.value==='1')}).trigger('change');</script>@endpush
