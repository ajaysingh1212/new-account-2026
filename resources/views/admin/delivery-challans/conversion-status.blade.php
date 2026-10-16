@if($challan->convertedInvoice)
    <a href="{{ route('admin.sales.show', $challan->convertedInvoice) }}" class="badge badge-success mt-1" title="Open Sales Invoice {{ $challan->convertedInvoice->invoice_no }}"><i class="fas fa-file-invoice mr-1"></i> {{ $challan->convertedInvoice->invoice_no }}</a>
@elseif($challan->status === 'converted' || $challan->converted_at || $challan->converted_sales_invoice_id)
    <span class="badge badge-warning mt-1"><i class="fas fa-exclamation-triangle mr-1"></i> Sales invoice missing</span>
    @if($canManage && $challan->status !== 'cancelled' && auth()->user()->can('delivery_challans.edit'))
        <form method="POST" action="{{ route('admin.delivery-challans.retry-conversion', $challan) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-warning btn-sm" title="Retry creating sales invoice" aria-label="Retry creating sales invoice" onclick="this.disabled=true; this.form.submit();"><i class="fas fa-redo mr-1"></i> Retry</button>
        </form>
    @endif
@endif
