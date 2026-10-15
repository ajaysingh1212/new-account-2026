@extends('layouts.admin')
@section('title','Sales')
@push('styles')
<style>
.sales-heading{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:20px;flex-wrap:wrap}
.sales-heading h2{font-size:25px;font-weight:800;color:#17233b;margin:0 0 5px}
.sales-toolbar{display:flex;align-items:end;gap:12px;flex-wrap:wrap;padding-bottom:18px}
.sales-toolbar label{display:block;font-size:12px;font-weight:700;color:#64748b}
.sales-toolbar .sales-search{flex:1;min-width:220px}
.sales-table-window{max-height:520px;overflow:auto;border:1px solid #e8edf5;border-radius:12px}
.sales-table-window.expanded{max-height:none}
#salesTable{margin:0!important;min-width:1100px}
#salesTable thead th{position:sticky;top:0;background:#f8fafc;z-index:2;white-space:nowrap}
#salesTable td:last-child{min-width:190px}
#salesTable tbody td{vertical-align:top!important}
.sale-cell-preview{height:104px;overflow:hidden;overflow-wrap:anywhere}
.sale-row-expanded .sale-cell-preview{height:auto;min-height:104px;overflow:visible}
.sale-row-toggle{display:block;margin-top:12px;font-size:12px;font-weight:600}
.sale-row-actions{display:flex;gap:4px;flex-wrap:wrap;align-content:start}
.sales-list-footer{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding-top:16px}
</style>
@endpush
@section('content')
<div class="sales-heading">
    <div><h2>Sales invoices</h2><div class="text-muted">Manage invoices, track sales and find every serial in one place.</div></div>
    <a href="{{ route('admin.sales.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-2"></i>Create invoice</a>
</div>
<div class="card">
    <div class="card-body">
        <div class="sales-toolbar">
            <div class="sales-search"><label for="salesSearch">Search invoices</label><input type="search" id="salesSearch" class="form-control" placeholder="Invoice, party, item, serial, VTS or SKU..."></div>
            <div><label for="salesMonth">Billing month</label><input type="month" id="salesMonth" class="form-control" value="{{ now()->format('Y-m') }}"></div>
            <button type="button" id="salesCurrentMonth" class="btn btn-outline-primary">Current month</button>
            <button type="button" id="salesAllMonths" class="btn btn-light">All months</button>
        </div>
        <div class="sales-table-window" id="salesTableWindow">
        <table id="salesTable" class="table table-hover">
            <thead><tr><th>No</th><th>Date</th><th>Party</th><th>Items Sold</th><th>Serial / VTS / SKU</th><th>Created By</th><th>Type</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            @php
                $monthSerials = [];
            @endphp
            @foreach($invoices as $invoice)
                @php
                    $monthKey = $invoice->billing_date?->format('Y-m') ?? 'no-date';
                    $monthSerials[$monthKey] = ($monthSerials[$monthKey] ?? 0) + 1;
                @endphp
                <tr data-month="{{ $monthKey }}" class="sale-invoice-row">
                    <td>
                        <div class="sale-cell-preview" id="sale-row-{{ $invoice->id }}-number">
                        {{ $monthSerials[$monthKey] }}
                        <div><small class="text-muted">{{ $invoice->invoice_no }}</small></div>
                        </div>
                    </td>
                    <td><div class="sale-cell-preview">{{ $invoice->billing_date?->format('d M Y') }}</div></td>
                    <td><div class="sale-cell-preview">{{ $invoice->party?->display_name ?: 'Cash' }}</div></td>
                    <td><div class="sale-cell-preview" id="sale-row-{{ $invoice->id }}-items">@foreach($invoice->items as $line)<div><b>{{ $line->item?->name }}</b> <small class="text-muted">x {{ $line->quantity }}</small></div>@endforeach</div></td>
                    <td>
                        <div class="sale-cell-preview" id="sale-row-{{ $invoice->id }}-serials">
                        @foreach($invoice->items as $line)
                            @foreach(($line->selected_units ?? []) as $unit)
                                <div class="mb-2">
                                    <span class="badge badge-info">{{ $unit['serial_no'] ?? $unit['vts_sim'] ?? $unit['sku'] ?? $unit['key'] ?? 'Unit' }}</span>
                                    <small class="d-block text-muted">{{ collect(['VTS' => $unit['vts_sim'] ?? null, 'SKU' => $unit['sku'] ?? null, 'Batch' => $unit['batch_no'] ?? null, 'Production' => $unit['production_batch_no'] ?? null])->filter()->map(fn($value, $label) => $label.': '.$value)->join(' · ') }}</small>
                                </div>
                            @endforeach
                        @endforeach
                        </div>
                    </td>
                    <td><div class="sale-cell-preview">{{ $invoice->creator?->name ?? 'System' }}<br><small class="text-muted">{{ $invoice->creator?->rolesForCompany($invoice->company_id)->pluck('name')->join(', ') }}</small></div></td>
                    <td><div class="sale-cell-preview">{{ ucfirst($invoice->sale_type) }}</div></td>
                    <td><div class="sale-cell-preview">Rs {{ number_format((float)$invoice->grand_total,2) }}</div></td>
                    <td>
                        <div class="sale-cell-preview" id="sale-row-{{ $invoice->id }}-status">
                        <span class="badge-active">{{ ucfirst($invoice->status) }}</span>
                        @if(($invoiceReturnDetails[$invoice->id]['has_return'] ?? false))
                            <div><span class="badge badge-warning mt-1">Sales Return</span></div>
                        @endif
                        @foreach($invoice->creditNotes as $note)
                            <div><a href="{{ route('admin.credit-notes.show',$note) }}" class="badge badge-info mt-1">CN {{ $note->credit_note_no }}</a> @if(!$note->hasCompleteSalesReturn())<span class="badge badge-warning mt-1">Return pending</span>@endif</div>
                        @endforeach
                        </div>
                    </td>
                    <td>
                        <div class="sale-cell-preview">
                        <div class="sale-row-actions">
                        <button type="button" class="btn btn-primary btn-sm sale-detail-btn" title="Profit and item details" data-detail='@json($invoiceDetails[$invoice->id] ?? [])' data-pdf="{{ route('admin.sales.detail-pdf',$invoice) }}"><i class="fas fa-chart-line"></i></button>
                        @if($invoice->inter_company_transfer)
                                    <button type="button" class="btn btn-success btn-sm inter-stock-btn" title="Auto purchase stock diagnostic" data-invoice="{{ $invoice->invoice_no }}" data-url="{{ route('admin.sales.inter-company-stock-status', $invoice) }}" data-repair-url="{{ route('admin.sales.repair-inter-company-stock', $invoice) }}"><i class="fas fa-barcode"></i></button>
                        @endif
                        <a href="{{ route('admin.sales.show',$invoice) }}" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
                        @can('sales.edit')<a href="{{ route('admin.sales.edit',$invoice) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>@endcan
                        @can('sales.print')<a href="{{ route('admin.sales.print',$invoice) }}" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-print"></i></a>@endcan
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm sale-row-toggle" aria-expanded="false" aria-label="Show all details for invoice {{ $invoice->invoice_no }}" aria-controls="sale-row-{{ $invoice->id }}-items sale-row-{{ $invoice->id }}-serials sale-row-{{ $invoice->id }}-status">See more</button>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        <div class="sales-list-footer">
            <span id="salesResultCount" class="text-muted small" role="status" aria-live="polite"></span>
            <button type="button" id="salesSeeMore" class="btn btn-outline-primary" aria-expanded="false" aria-controls="salesTableWindow">See more <i class="fas fa-chevron-down ml-1"></i></button>
        </div>
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
const currentSalesMonth = @json(now()->format('Y-m'));
$.fn.dataTable.ext.search.push(function(settings, data, index) {
    if (settings.nTable.id !== 'salesTable') return true;
    const month = document.getElementById('salesMonth').value;
    return !month || settings.aoData[index].nTr.dataset.month === month;
});
const salesTable = $('#salesTable').DataTable({paging:false, info:false, dom:'t', order:[], columnDefs:[{orderable:false, targets:[0,9]}]});
const salesWindow = document.getElementById('salesTableWindow');
let lastSalesSearch = '';
function setInvoiceRowExpanded(row, expanded) {
    row.classList.toggle('sale-row-expanded', expanded);
    const button = row.querySelector('.sale-row-toggle');
    if (button) {
        button.setAttribute('aria-expanded', String(expanded));
        button.textContent = expanded ? 'See less' : 'See more';
    }
}
function updateSalesList() {
    const query = $('#salesSearch').val().trim();
    if (query !== lastSalesSearch) {
        // Keep every item in the search index and reveal matches beyond the preview.
        salesTable.rows().nodes().each(row => setInvoiceRowExpanded(row, Boolean(query)));
        lastSalesSearch = query;
    }
    const count = salesTable.rows({search:'applied'}).count();
    $('#salesResultCount').text(`${count} invoice${count === 1 ? '' : 's'} found · ${$('#salesMonth').val() || 'All months'}`);
    const expanded = salesWindow.classList.contains('expanded');
    $('#salesSeeMore').prop('hidden', !expanded && salesWindow.scrollHeight <= salesWindow.clientHeight + 1)
        .attr('aria-expanded', String(expanded)).text(expanded ? 'See less ↑' : 'See more ↓');
    window.AppSearchHighlight?.apply(salesTable.table().body(), $('#salesSearch').val());
}
$('#salesSearch').on('input', function(){ salesTable.search(this.value).draw(); });
$('#salesMonth').on('change', function(){ salesWindow.scrollTop = 0; salesTable.draw(); });
$('#salesCurrentMonth').on('click', function(){ $('#salesMonth').val(currentSalesMonth).trigger('change'); });
$('#salesAllMonths').on('click', function(){ $('#salesMonth').val('').trigger('change'); });
$('#salesSeeMore').on('click', function(){ salesWindow.classList.toggle('expanded'); updateSalesList(); });
$('#salesTable').on('click', '.sale-row-toggle', function() {
    const row = this.closest('tr');
    setInvoiceRowExpanded(row, !row.classList.contains('sale-row-expanded'));
    updateSalesList();
});
$('#salesTable').on('draw.dt', updateSalesList);
window.addEventListener('resize', updateSalesList);
updateSalesList();
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
