@extends('layouts.admin')
@section('title','Purchase Bill')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0">Purchase {{ $bill->invoice_no }}</h3>
        <div>@if(!$bill->source_sales_invoice_id)<a href="{{ route('admin.purchases.edit',$bill) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit mr-1"></i>Edit</a>@endif <a href="{{ route('admin.purchases.index') }}" class="btn btn-secondary btn-sm">Back</a></div>
    </div>
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        @if($bill->source_sales_invoice_id)
            <div class="alert alert-info">
                Auto inter-company purchase. Direct edit allowed nahi hai. Source company:
                <strong>{{ $bill->interCompanySourceCompany?->name ?? '-' }}</strong>.
                Sale creator: <strong>{{ $bill->sourceSalesInvoice?->creator?->name ?? '-' }}</strong>
                ({{ $bill->sourceSalesInvoice?->creator?->email ?? '-' }}).
                Source sale edit hone par ye purchase, inventory aur ledger auto update honge.
            </div>
            @if(($stockGapRows ?? collect())->isNotEmpty())
                <div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap" id="interCompanyStockWarning">
                    <div><strong><i class="fas fa-triangle-exclamation mr-1"></i> Stock entry incomplete</strong><br><small>{{ $stockGapRows->count() }} item(s) purchase me hain, lekin stock movement me missing hain.</small></div>
                    @can('purchase.edit')<button type="button" class="btn btn-warning btn-sm" data-toggle="modal" data-target="#repairInterCompanyStockModal"><i class="fas fa-wrench mr-1"></i>Review & repair</button>@endcan
                </div>
            @endif
        @endif
        <div class="row mb-3">
            <div class="col-md-3"><b>Party</b><br>{{ $bill->party?->display_name ?: 'Cash' }}</div>
            <div class="col-md-2"><b>Date</b><br>{{ $bill->billing_date?->format('d M Y') }}</div>
            <div class="col-md-2"><b>Supplier Bill</b><br>{{ $bill->supplier_bill_no ?: '-' }}</div>
            <div class="col-md-2"><b>Total</b><br>Rs {{ number_format((float)($purchaseReturnDetails['net_total'] ?? $bill->grand_total),2) }}
                @if($purchaseReturnDetails['has_return'] ?? false)<br><small class="text-muted">Original: Rs {{ number_format((float)$bill->grand_total,2) }}</small>@endif
            </div>
            <div class="col-md-3">@if($bill->attachment)<b>Attachment</b><br><a href="{{ asset('storage/'.$bill->attachment) }}" target="_blank">Open attachment</a>@endif</div>
        </div>
        @if($purchaseReturnDetails['has_return'] ?? false)
            <div class="alert alert-warning">
                <b>This bill has purchase return activity.</b>
                Returned quantity: {{ number_format((float) ($purchaseReturnDetails['returned_qty'] ?? 0), 3) }} |
                Returned amount minus: Rs {{ number_format((float) ($purchaseReturnDetails['returned_amount'] ?? 0), 2) }} |
                Net bill value: Rs {{ number_format((float) ($purchaseReturnDetails['net_total'] ?? $bill->grand_total), 2) }}
            </div>
        @endif
        <table class="table table-hover">
            <thead><tr><th>Item</th><th>Purchased Qty</th><th>Returned Qty</th><th>Remaining</th><th>Finished Goods Units</th><th>Price</th><th>Tax</th><th>Total</th><th>Returned Amount</th><th>Net Total</th><th>Returns</th></tr></thead>
            <tbody>
            @foreach($bill->items as $line)
                @php($lineSummary = collect($purchaseReturnDetails['items'] ?? [])->firstWhere('line_id', $line->id))
                <tr class="{{ ($lineSummary['returned_qty'] ?? 0) > 0 ? 'table-warning' : '' }}"><td>{{ $line->item?->name }}</td><td>{{ $lineSummary['purchased_qty'] ?? $line->quantity }}</td><td class="{{ ($lineSummary['returned_qty'] ?? 0) > 0 ? 'text-warning' : '' }}">{{ number_format((float) ($lineSummary['returned_qty'] ?? 0), 3) }}</td><td>{{ number_format((float) ($lineSummary['remaining_qty'] ?? $line->quantity), 3) }}</td><td>@foreach(($line->selected_units ?? []) as $unit)<span class="badge badge-info mr-1">{{ $unit['serial_no'] ?? 'No serial' }} / {{ $unit['batch_no'] ?? '-' }}@if(!empty($unit['vts_sim'])) / {{ $unit['vts_sim'] }}@endif</span>@endforeach</td><td>Rs {{ number_format((float)$line->unit_price,2) }}</td><td>Rs {{ number_format((float)$line->tax_amount,2) }}</td><td>Rs {{ number_format((float)$line->line_total,2) }}</td><td class="{{ ($lineSummary['returned_amount'] ?? 0) > 0 ? 'text-danger font-weight-bold' : '' }}">- Rs {{ number_format((float) ($lineSummary['returned_amount'] ?? 0), 2) }}</td><td class="font-weight-bold">Rs {{ number_format((float) ($lineSummary['net_amount'] ?? $line->line_total), 2) }}</td><td>@forelse(($lineSummary['returns'] ?? []) as $returnRow)<div class="mb-1"><b>{{ $returnRow['return_no'] }}</b><br><small class="text-muted">{{ $returnRow['return_date'] }} | Qty {{ number_format((float) $returnRow['return_qty'], 3) }} | Rs {{ number_format((float) ($returnRow['return_amount'] ?? 0), 2) }} minus | {{ $returnRow['returned_by'] }}</small></div>@empty<span class="text-muted">-</span>@endforelse</td></tr>
            @endforeach
            </tbody>
            @if($purchaseReturnDetails['has_return'] ?? false)
            <tfoot>
                <tr><th colspan="9" class="text-right">Original Bill Total</th><th colspan="2">Rs {{ number_format((float)$bill->grand_total,2) }}</th></tr>
                <tr><th colspan="9" class="text-right text-danger">Less Purchase Return</th><th colspan="2" class="text-danger">- Rs {{ number_format((float)($purchaseReturnDetails['returned_amount'] ?? 0),2) }}</th></tr>
                <tr><th colspan="9" class="text-right">Net Bill Value</th><th colspan="2">Rs {{ number_format((float)($purchaseReturnDetails['net_total'] ?? $bill->grand_total),2) }}</th></tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@if(($stockGapRows ?? collect())->isNotEmpty())
<div class="modal fade" id="repairInterCompanyStockModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document"><div class="modal-content">
        <div class="modal-header bg-warning"><h5 class="modal-title"><i class="fas fa-wrench mr-1"></i> Repair missing stock</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
        <form method="POST" action="{{ route('admin.purchases.repair-inter-company-stock', $bill) }}">@csrf
            <div class="modal-body"><p class="mb-3">Selected items ka missing quantity target company ke stock me ek hi baar add hoga. Existing stock movements duplicate nahi honge.</p>
                @foreach($stockGapRows as $row)<label class="d-flex justify-content-between align-items-center border rounded p-3 mb-2"><span><input type="checkbox" name="line_ids[]" value="{{ $row['line_id'] }}" checked class="mr-2"> <strong>{{ $row['item'] }}</strong></span><span>Expected {{ number_format($row['expected'],3) }} | Posted {{ number_format($row['posted'],3) }} | <b class="text-danger">Missing {{ number_format($row['missing'],3) }}</b></span></label>@endforeach
            </div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button><button class="btn btn-warning"><i class="fas fa-check mr-1"></i> Update stock</button></div>
        </form>
    </div></div>
</div>
@endif
@include('admin.partials.update-history', ['auditLogs' => $auditLogs])
@endsection
