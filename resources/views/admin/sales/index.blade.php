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
                    <td>
                        <span class="badge-active">{{ ucfirst($invoice->status) }}</span>
                        @if(($invoiceReturnDetails[$invoice->id]['has_return'] ?? false))
                            <div><span class="badge badge-warning mt-1">Sales Return</span></div>
                        @endif
                    </td>
                    <td>
                        <button type="button" class="btn btn-primary btn-sm sale-detail-btn" title="Profit and item details" data-detail='@json($invoiceDetails[$invoice->id] ?? [])' data-pdf="{{ route('admin.sales.detail-pdf',$invoice) }}"><i class="fas fa-chart-line"></i></button>
                        @if($invoice->inter_company_transfer)
                                    <button type="button" class="btn btn-success btn-sm inter-stock-btn" title="Auto purchase stock diagnostic" data-invoice="{{ $invoice->invoice_no }}" data-url="{{ route('admin.sales.inter-company-stock-status', $invoice) }}" data-repair-url="{{ route('admin.sales.repair-inter-company-stock', $invoice) }}"><i class="fas fa-barcode"></i></button>
                        @endif
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
<div class="modal fade" id="interStockModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content" style="border:0;border-radius:16px;overflow:hidden;">
            <div class="modal-header bg-dark text-white border-0">
                <div><h5 class="modal-title mb-0" id="interStockTitle">Auto Purchase Stock Diagnostic</h5><small>Serial-wise target company stock status</small></div>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" style="background:#f8fafc;" id="interStockBody"></div>
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
        const bom = (item.bom || []).map(row => {
            const amount = Number(row.amount || 0);
            const type = row.line_type === 'service' ? 'Service' : 'Raw';
            return `${type} - ${row.name}: ${Number(row.qty_per_unit || 0)} ${row.unit || ''} @ ${money(row.unit_price || row.purchase_price)} = ${money(amount)}`;
        }).join('<br>') || '-';
        const units = (item.units || []).map(unit => `${unit.serial_no || '-'} / ${unit.vts_sim || '-'} / ${unit.batch_no || '-'} / ${unit.buyer_code || '-'}`).join('<br>') || '-';
        return `<tr><td><b>${item.name}</b><br><small>${item.description || '-'}</small><br><small><b>BOM:</b><br>${bom}</small><br><small><b>CRM Units:</b><br>${units}</small></td><td>${Number(item.qty || 0).toFixed(2)} ${item.unit || ''}</td><td>${money(item.amount)}</td><td>${money(item.cost)}</td><td class="${Number(item.profit) < 0 ? 'text-danger' : 'text-success'}"><b>${money(item.profit)}</b></td><td class="${Number(item.profit_percent) < 0 ? 'text-danger' : 'text-success'}"><b>${Number(item.profit_percent || 0).toFixed(2)}%</b></td></tr>`;
    }).join('') || '<tr><td colspan="6" class="text-center text-muted">No item details.</td></tr>');
    $('#saleDetailModal').modal('show');
});
let activeInterStockButton = null;
const renderInterCompanyStockStatus = statuses => {
    if (!statuses.length) {
        $('#interStockBody').html('<div class="p-4 bg-white rounded border text-muted">No auto purchase target data found.</div>');
        return;
    }
    $('#interStockBody').html(statuses.map(target => {
        const badge = Number(target.missing || 0) > 0 ? '<span class="badge badge-danger">Missing ' + target.missing + '</span>' : '<span class="badge badge-success">All added</span>';
        const rows = (target.details || []).map(row => {
            const label = row.serial_no || row.vts_sim || row.sku || row.buyer_code || row.key || '-';
            const status = row.status === 'added' ? '<span class="badge badge-success">Added</span>' : '<span class="badge badge-danger">Missing</span>';
            const history = (row.history || []).map(h => `${h.date || '-'} | ${h.company || '-'} | ${h.direction || '-'} | ${h.type || '-'} | ${h.reference || '-'}`).join('<br>') || '-';
            const locations = (row.locations || []).map(l => `${l.company}: ${Number(l.net || 0).toFixed(3)}`).join('<br>') || '-';
            const locationText = locations === '-' ? 'Current stock location: kahin active nahi mila.' : `Current stock location: ${locations.replace(/<br>/g, ', ')}`;
            const documentText = (row.history || []).length
                ? (row.history || []).slice(0, 5).map(h => `${h.company || '-'} / ${h.type || '-'} / ${h.reference || '-'}`).join('\n')
                : 'Sales, sales return ya purchase return history nahi mili.';
            const repair = row.unit_token
                ? `<button type="button" class="btn ${row.status === 'missing' ? 'btn-warning' : 'btn-outline-warning'} btn-sm repair-inter-stock" title="Check and add this unit" data-company-id="${target.company_id}" data-line-id="${row.line_id}" data-unit-token="${row.unit_token}" data-location="${encodeURIComponent(locationText)}" data-documents="${encodeURIComponent(documentText)}" data-confirmed="0"><i class="fas fa-wrench"></i></button>`
                : '-';
            return `<tr><td><b>${row.item || '-'}</b><br><small>${label}</small></td><td>${status}</td><td>${row.reason || '-'}</td><td>${locations}</td><td><small>${history}</small></td><td>${repair}</td></tr>`;
        }).join('') || '<tr><td colspan="6" class="text-center text-muted">No serial detail available.</td></tr>';
        return `<div class="bg-white rounded border mb-3 p-3"><div class="d-flex justify-content-between align-items-center mb-2"><div><b>${target.company || 'Target company'}</b><br><small>Purchase: ${target.purchase || 'Not created'}</small></div>${badge}</div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Item / Serial</th><th>Status</th><th>Reason</th><th>Current Location</th><th>Recent History</th><th>Action</th></tr></thead><tbody>${rows}</tbody></table></div></div>`;
    }).join(''));
};
$(document).on('click', '.inter-stock-btn', async function() {
    const button = $(this);
    activeInterStockButton = button;
    $('#interStockTitle').text('Auto Purchase Diagnostic - Invoice ' + (button.data('invoice') || '-'));
    $('#interStockBody').html('<div class="p-4 bg-white rounded border text-muted">Loading diagnostic...</div>');
    $('#interStockModal').modal('show');
    try {
        const response = await fetch(button.data('url'), {headers: {Accept: 'application/json'}});
        if (!response.ok) {
            throw new Error('Request failed');
        }
        renderInterCompanyStockStatus(await response.json());
    } catch (error) {
        $('#interStockBody').html('<div class="p-4 bg-white rounded border text-danger">Diagnostic load nahi ho paya. Page refresh karke dobara try karein.</div>');
    }
});
$(document).on('click', '.repair-inter-stock', async function() {
    if (!activeInterStockButton) return;
    const button = $(this);
    if (String(button.attr('data-confirmed')) !== '1') {
        alert(`${decodeURIComponent(button.attr('data-location'))}\n\nRecent sale/return records:\n${decodeURIComponent(button.attr('data-documents'))}\n\nAgar phir bhi stock mein add karna hai to isi button par dobara click karein.`);
        button.attr('data-confirmed', '1').removeClass('btn-outline-warning').addClass('btn-danger').html('<i class="fas fa-plus"></i>');
        return;
    }
    button.prop('disabled', true);
    button.html('<i class="fas fa-spinner fa-spin"></i>');
    try {
        const response = await fetch(activeInterStockButton.data('repair-url'), {
            method: 'POST',
            headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            body: JSON.stringify({
                target_company_ids: [button.data('company-id')],
                line_ids: [button.data('line-id')],
                unit_token: String(button.data('unit-token')),
                force_add: true,
            }),
        });
        if (!response.ok) throw new Error('Repair failed');
        const statusResponse = await fetch(activeInterStockButton.data('url'), {headers: {Accept: 'application/json'}});
        if (!statusResponse.ok) throw new Error('Status refresh failed');
        renderInterCompanyStockStatus(await statusResponse.json());
    } catch (error) {
        button.prop('disabled', false).html('<i class="fas fa-wrench"></i>');
        alert('Stock add nahi ho paya. Page refresh karke dobara try karein.');
    }
});
</script>@endpush
