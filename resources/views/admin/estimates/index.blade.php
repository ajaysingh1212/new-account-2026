@extends('layouts.admin')
@section('title','Estimates')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h3 class="card-title m-0">Estimate / Quotation</h3>
        <a href="{{ route('admin.estimates.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Add Estimate</a>
    </div>
    <div class="card-body table-responsive">
        <table id="estimatesTable" class="table table-hover">
            <thead><tr><th>No</th><th>Date</th><th>Valid Until</th><th>Party</th><th>Created By</th><th>Total</th><th>Profit</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            @foreach($estimates as $estimate)
                @php($canManage = app(\App\Services\EntryVisibilityService::class)->canManage(auth()->user(), $estimate))
                <tr>
                    <td><strong>{{ $estimate->estimate_no }}</strong></td>
                    <td>{{ $estimate->estimate_date?->format('d M Y') }}</td>
                    <td>{{ $estimate->valid_until?->format('d M Y') ?: '-' }}</td>
                    <td>{{ $estimate->party?->display_name ?: 'Walk-in' }}</td>
                    <td>{{ $estimate->creator?->name ?? 'System' }}<br><small class="text-muted">{{ $estimate->creator?->rolesForCompany($estimate->company_id)->pluck('name')->join(', ') }}</small></td>
                    <td>Rs {{ number_format((float) $estimate->grand_total, 2) }}</td>
                    @php($detail = $estimateDetails[$estimate->id] ?? null)
                    <td>
                        Rs {{ number_format((float) data_get($detail, 'amounts.profit', 0), 2) }}
                        <br><small class="text-muted">{{ number_format((float) data_get($detail, 'amounts.profit_percent', 0), 2) }}%</small>
                    </td>
                    <td>
                        <span class="badge-active">{{ ucfirst($estimate->status) }}</span>
                        @if($estimate->convertedInvoice)
                            <div><span class="badge badge-success mt-1">Converted</span></div>
                        @endif
                    </td>
                    <td>
                        <button type="button" class="btn btn-primary btn-sm estimate-detail-btn" title="Profit and item details" data-detail='@json($detail ?? [])' data-pdf="{{ route('admin.estimates.detail-pdf', $estimate) }}"><i class="fas fa-chart-line"></i></button>
                        <a href="{{ route('admin.estimates.show', $estimate) }}" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
                        @if($canManage && $estimate->status !== 'converted' && $estimate->status !== 'cancelled' && auth()->user()->can('estimates.convert'))
                            <a href="{{ route('admin.estimates.convert-form', $estimate) }}" class="btn btn-success btn-sm"><i class="fas fa-sync mr-1"></i>Convert</a>
                        @endif
                        @if($canManage && $estimate->status !== 'converted' && auth()->user()->can('estimates.edit'))<a href="{{ route('admin.estimates.edit', $estimate) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>@endif
                        @can('estimates.print')<a href="{{ route('admin.estimates.print', $estimate) }}" class="btn btn-secondary btn-sm" target="_blank"><i class="fas fa-print"></i></a>@endcan
                        @can('estimates.print')<a href="{{ route('admin.estimates.detail-pdf', $estimate) }}" class="btn btn-dark btn-sm" target="_blank" title="Profit & Item Details PDF"><i class="fas fa-file-pdf"></i></a>@endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="modal fade" id="estimateDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content" style="border:0;border-radius:16px;overflow:hidden;">
            <div class="modal-header" style="background:linear-gradient(135deg,#111827,#0f766e);color:#fff;border:0;">
                <div><h5 class="modal-title mb-0" id="estimateDetailTitle">Estimate Details</h5><small id="estimateDetailSub"></small></div>
                <div><a href="#" target="_blank" class="btn btn-light btn-sm" id="estimateDetailPdf"><i class="fas fa-file-pdf mr-1"></i>PDF</a><button type="button" class="close text-white ml-2" data-dismiss="modal"><span>&times;</span></button></div>
            </div>
            <div class="modal-body" style="background:#f8fafc;">
                <div class="row" id="estimateDetailMetrics"></div>
                <div class="row">
                    <div class="col-lg-5 mb-3"><div class="p-3 bg-white rounded border h-100"><h6 class="font-weight-bold">Party & CRM</h6><div id="estimateDetailParty" class="small"></div></div></div>
                    <div class="col-lg-7 mb-3"><div class="p-3 bg-white rounded border h-100"><h6 class="font-weight-bold">Items, Pricing, BOM & Units</h6><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Item</th><th>Qty</th><th>Sale</th><th>Cost</th><th>Profit</th><th>Profit %</th></tr></thead><tbody id="estimateDetailItems"></tbody></table></div></div></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')<script>
$('#estimatesTable').DataTable({pageLength:25, columnDefs:[{orderable:false, targets:8}]});
const emoney = value => 'Rs ' + Number(value || 0).toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
$(document).on('click', '.estimate-detail-btn', function() {
    const detail = $(this).data('detail') || {};
    $('#estimateDetailTitle').text('Estimate ' + (detail.estimate || detail.invoice || '-'));
    $('#estimateDetailSub').text([detail.date, detail.party?.name].filter(Boolean).join(' | '));
    $('#estimateDetailPdf').attr('href', $(this).data('pdf'));
    const metrics = [
        ['Estimate Total', detail.amounts?.total],
        ['Purchase Cost', detail.amounts?.cost],
        ['Profit / Loss', detail.amounts?.profit],
        ['Profit % on Cost', detail.amounts?.profit_percent, '%'],
    ];
    $('#estimateDetailMetrics').html(metrics.map(([label, value, suffix]) => `<div class="col-md-3 mb-3"><div class="p-3 bg-white rounded border"><small class="text-muted text-uppercase font-weight-bold">${label}</small><div class="h5 mb-0 ${Number(value) < 0 ? 'text-danger' : ''}">${suffix === '%' ? Number(value || 0).toFixed(2) + '%' : emoney(value)}</div></div></div>`).join(''));
    $('#estimateDetailParty').html(`<b>${detail.party?.name || 'Cash / Walk-in'}</b><br>Legal: ${detail.party?.legal_name || '-'}<br>Phone: ${detail.party?.phone || detail.phone || '-'}<br>Email: ${detail.party?.email || '-'}<br>GSTIN: ${detail.party?.gstin || '-'}<br>City: ${detail.party?.city || '-'}<hr class="my-2">Billing: ${detail.billing_address || '-'}<br>Shipping: ${detail.shipping_address || '-'}`);
    $('#estimateDetailItems').html((detail.items || []).map(item => {
        const bom = (item.bom || []).map(row => {
            const amount = Number(row.amount || 0);
            const type = row.line_type === 'service' ? 'Service' : 'Raw';
            return `${type} - ${row.name}: ${Number(row.qty_per_unit || 0)} ${row.unit || ''} @ ${emoney(row.unit_price || row.purchase_price)} = ${emoney(amount)}`;
        }).join('<br>') || '-';
        const units = (item.units || []).length ? item.units.map(unit => `${unit.serial_no || '-'} / ${unit.vts_sim || '-'} / ${unit.batch_no || '-'} / ${unit.buyer_code || '-'}`).join('<br>') : 'Not allocated yet (assigned on conversion to Sales Invoice)';
        return `<tr><td><b>${item.name}</b><br><small>${item.description || '-'}</small><br><small><b>BOM:</b><br>${bom}</small><br><small><b>CRM Units:</b><br>${units}</small></td><td>${Number(item.qty || 0).toFixed(2)} ${item.unit || ''}</td><td>${emoney(item.amount)}</td><td>${emoney(item.cost)}</td><td class="${Number(item.profit) < 0 ? 'text-danger' : 'text-success'}"><b>${emoney(item.profit)}</b></td><td class="${Number(item.profit_percent) < 0 ? 'text-danger' : 'text-success'}"><b>${Number(item.profit_percent || 0).toFixed(2)}%</b></td></tr>`;
    }).join('') || '<tr><td colspan="6" class="text-center text-muted">No item details.</td></tr>');
    $('#estimateDetailModal').modal('show');
});
</script>@endpush
