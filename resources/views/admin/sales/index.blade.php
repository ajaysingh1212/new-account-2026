@extends('layouts.admin')
@section('title','Sales')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h3 class="card-title m-0">Sales Invoices</h3>
        <a href="{{ route('admin.sales.create') }}" class="btn btn-primary btn-sm">Add Sale</a>
    </div>
    <div class="card-body table-responsive">
        <table id="salesTable" class="table table-hover">
            <thead><tr><th>No</th><th>Date</th><th>Party</th><th>Items Sold</th><th>Serial / VTS / SKU</th><th>Created By</th><th>Type</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            @foreach($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->invoice_no }}</td>
                    <td>{{ $invoice->billing_date?->format('d M Y') }}</td>
                    <td>{{ $invoice->party?->display_name ?: 'Cash' }}</td>
                    <td>@foreach($invoice->items as $line)<div><b>{{ $line->item?->name }}</b> <small class="text-muted">x {{ $line->quantity }}</small></div>@endforeach</td>
                    <td>@foreach($invoice->items as $line)@foreach(($line->selected_units ?? []) as $unit)<span class="badge badge-info mr-1 mb-1">{{ $unit['serial_no'] ?? $unit['vts_sim'] ?? $unit['sku'] ?? $unit['key'] ?? 'Unit' }}</span>@endforeach @endforeach<span class="d-none">{{ $invoice->items->flatMap(fn($line) => collect($line->selected_units ?? [])->flatMap(fn($unit) => [$unit['serial_no'] ?? null, $unit['vts_sim'] ?? null, $unit['sku'] ?? null, $unit['batch_no'] ?? null, $unit['production_batch_no'] ?? null, $unit['key'] ?? null]))->filter()->join(' ') }}</span></td>
                    <td>{{ $invoice->creator?->name ?? 'System' }}<br><small class="text-muted">{{ $invoice->creator?->rolesForCompany($invoice->company_id)->pluck('name')->join(', ') }}</small></td>
                    <td>{{ ucfirst($invoice->sale_type) }}</td>
                    <td>Rs {{ number_format((float)$invoice->grand_total,2) }}</td>
                    <td><span class="badge-active">{{ ucfirst($invoice->status) }}</span></td>
                    <td>
                        <button type="button" class="btn btn-primary btn-sm sale-detail-btn" title="Profit and item details" data-detail='@json($invoiceDetails[$invoice->id] ?? [])' data-pdf="{{ route('admin.sales.detail-pdf',$invoice) }}"><i class="fas fa-chart-line"></i></button>
                        <a href="{{ route('admin.sales.show',$invoice) }}" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
                        @can('sales.edit')<a href="{{ route('admin.sales.edit',$invoice) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>@endcan
                        @can('sales.print')<a href="{{ route('admin.sales.print',$invoice) }}" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-print"></i></a>@endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="modal fade" id="saleDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content" style="border:0;border-radius:16px;overflow:hidden;">
            <div class="modal-header" style="background:linear-gradient(135deg,#111827,#0f766e);color:#fff;border:0;">
                <div><h5 class="modal-title mb-0" id="saleDetailTitle">Invoice Details</h5><small id="saleDetailSub"></small></div>
                <div><a href="#" target="_blank" class="btn btn-light btn-sm" id="saleDetailPdf"><i class="fas fa-file-pdf mr-1"></i>PDF</a><button type="button" class="close text-white ml-2" data-dismiss="modal"><span>&times;</span></button></div>
            </div>
            <div class="modal-body" style="background:#f8fafc;">
                <div class="row" id="saleDetailMetrics"></div>
                <div class="row">
                    <div class="col-lg-5 mb-3"><div class="p-3 bg-white rounded border h-100"><h6 class="font-weight-bold">Party & CRM</h6><div id="saleDetailParty" class="small"></div></div></div>
                    <div class="col-lg-7 mb-3"><div class="p-3 bg-white rounded border h-100"><h6 class="font-weight-bold">Items, Pricing, BOM & Units</h6><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Item</th><th>Qty</th><th>Sale</th><th>Cost</th><th>Profit</th><th>Profit %</th></tr></thead><tbody id="saleDetailItems"></tbody></table></div></div></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')<script>
$('#salesTable').DataTable({pageLength:25, columnDefs:[{orderable:false, targets:9}]});
const money = value => 'Rs ' + Number(value || 0).toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
$(document).on('click', '.sale-detail-btn', function() {
    const detail = $(this).data('detail') || {};
    $('#saleDetailTitle').text('Invoice ' + (detail.invoice || '-'));
    $('#saleDetailSub').text([detail.date, detail.party?.name, detail.sale_type].filter(Boolean).join(' | '));
    $('#saleDetailPdf').attr('href', $(this).data('pdf'));
    const metrics = [
        ['Sale Total', detail.amounts?.total],
        ['Purchase Cost', detail.amounts?.cost],
        ['Profit / Loss', detail.amounts?.profit],
        ['Profit % on Cost', detail.amounts?.profit_percent, '%'],
    ];
    $('#saleDetailMetrics').html(metrics.map(([label, value, suffix]) => `<div class="col-md-3 mb-3"><div class="p-3 bg-white rounded border"><small class="text-muted text-uppercase font-weight-bold">${label}</small><div class="h5 mb-0 ${Number(value) < 0 ? 'text-danger' : ''}">${suffix === '%' ? Number(value || 0).toFixed(2) + '%' : money(value)}</div></div></div>`).join(''));
    $('#saleDetailParty').html(`<b>${detail.party?.name || 'Cash / Walk-in'}</b><br>Legal: ${detail.party?.legal_name || '-'}<br>Phone: ${detail.party?.phone || detail.phone || '-'}<br>Email: ${detail.party?.email || '-'}<br>GSTIN: ${detail.party?.gstin || '-'}<br>City: ${detail.party?.city || '-'}<hr class="my-2">Billing: ${detail.billing_address || '-'}<br>Shipping: ${detail.shipping_address || '-'}`);
    $('#saleDetailItems').html((detail.items || []).map(item => {
        const bom = (item.bom || []).map(row => `${row.name}: ${Number(row.qty_per_unit || 0)} ${row.unit || ''} @ ${money(row.purchase_price)}`).join('<br>') || '-';
        const units = (item.units || []).map(unit => `${unit.serial_no || '-'} / ${unit.vts_sim || '-'} / ${unit.batch_no || '-'} / ${unit.buyer_code || '-'}`).join('<br>') || '-';
        return `<tr><td><b>${item.name}</b><br><small>${item.description || '-'}</small><br><small><b>BOM:</b><br>${bom}</small><br><small><b>CRM Units:</b><br>${units}</small></td><td>${Number(item.qty || 0).toFixed(2)} ${item.unit || ''}</td><td>${money(item.amount)}</td><td>${money(item.cost)}</td><td class="${Number(item.profit) < 0 ? 'text-danger' : 'text-success'}"><b>${money(item.profit)}</b></td><td class="${Number(item.profit_percent) < 0 ? 'text-danger' : 'text-success'}"><b>${Number(item.profit_percent || 0).toFixed(2)}%</b></td></tr>`;
    }).join('') || '<tr><td colspan="6" class="text-center text-muted">No item details.</td></tr>');
    $('#saleDetailModal').modal('show');
});
</script>@endpush
