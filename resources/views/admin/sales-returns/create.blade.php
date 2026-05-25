@extends('layouts.admin')
@section('title','New Sales Return')
@section('content')
<form method="POST" action="{{ route('admin.sales-returns.store') }}">@csrf
<div class="card">
    <div class="card-header"><h3 class="card-title m-0">New Sales Return</h3></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 form-group"><label>Sale Invoice</label><select name="sales_invoice_id" id="sourceInvoice" class="form-control select2" required><option value="">Select invoice</option>@foreach($invoices as $invoice)<option value="{{ $invoice->id }}">{{ $invoice->invoice_no }} | {{ $invoice->party?->display_name ?: 'Cash' }} | Rs {{ number_format((float)$invoice->grand_total,2) }}</option>@endforeach</select></div>
            <div class="col-md-2 form-group"><label>Return No</label><input name="return_no" class="form-control" value="{{ $returnNo }}"></div>
            <div class="col-md-2 form-group"><label>Date</label><input type="date" name="return_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
            <div class="col-md-4 form-group"><label>Reason</label><input name="reason" class="form-control"></div>
        </div>
        <div id="returnLines"></div>
        @include('admin.partials.entry-visibility')
    </div>
    <div class="card-footer text-right"><button class="btn btn-primary">Post Sales Return</button></div>
</div>
</form>
@endsection
@push('scripts')
<script>

    const INVOICES = @json($invoiceData);

    $(document).ready(function () {

        $('#sourceInvoice').on('change', function () {

            let invoiceId = $(this).val();
            let lines = INVOICES[invoiceId] || [];

            if (lines.length === 0) {

                $('#returnLines').html(`
                    <div class="alert alert-warning">
                        No invoice items found.
                    </div>
                `);

                return;
            }

            let tableRows = '';

            lines.forEach(function (line) {

                tableRows += `
                    <tr>
                        <td>
                            ${line.item}
                            <input type="hidden" name="line_id[]" value="${line.id}">
                        </td>

                        <td>
                            ${line.qty} ${line.unit}
                        </td>

                        <td>
                            <input
                                type="number"
                                name="quantity[]"
                                class="form-control"
                                min="0"
                                max="${line.qty}"
                                step="0.001"
                                value="${line.qty}"
                                required
                            >
                        </td>

                        <td>
                            ₹ ${parseFloat(line.price).toFixed(2)}
                        </td>

                        <td>
                            ${line.tax}%
                        </td>
                    </tr>
                `;
            });

            $('#returnLines').html(`
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Sold Qty</th>
                                <th>Return Qty</th>
                                <th>Unit Price</th>
                                <th>Tax</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${tableRows}
                        </tbody>
                    </table>
                </div>
            `);

        });

    });

</script>
@endpush
