@extends('layouts.admin')
@section('title', 'Bill Wise Profit')
@push('styles')
<style>
.profit-detail-btn{border-radius:999px;font-weight:800}.profit-modal .modal-content{border:0;border-radius:18px;overflow:hidden;box-shadow:0 28px 86px rgba(15,23,42,.28)}.profit-modal .modal-header{border:0;background:linear-gradient(135deg,#0f172a,#0f766e);color:#fff;padding:20px 24px}.profit-modal .modal-body{background:#f8fafc}.detail-box{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px;height:100%}.detail-box span{display:block;font-size:11px;text-transform:uppercase;color:#64748b;font-weight:900;letter-spacing:.5px}.detail-box b{color:#0f172a}.detail-table{background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden}.profit-pill{display:inline-flex;align-items:center;border-radius:999px;padding:6px 10px;font-weight:900;background:#ecfdf5;color:#047857}
</style>
@endpush
@section('content')
@include('admin.reports.partials.styles')
<div data-export-title="Bill Wise Profit Report" data-export-file="bill-wise-profit">@include('admin.reports.partials.branded-export')</div>
<div class="report-hero">
    <h1>Bill Wise Profit</h1>
    <form class="report-filter" method="GET">
        <div><label>Filter</label><select name="period" id="profitPeriod" class="form-control"><option value="month" @selected($filters['period']==='month')>Selected Month</option><option value="custom" @selected($filters['period']==='custom')>Custom Date</option><option value="all" @selected($filters['period']==='all')>All</option></select></div>
        <div><label>Month</label><input type="month" name="month" class="form-control" value="{{ $filters['month'] }}"></div>
        <div class="profit-custom-date"><label>From</label><input type="date" name="from_date" class="form-control" value="{{ $filters['from'] }}"></div>
        <div class="profit-custom-date"><label>To</label><input type="date" name="to_date" class="form-control" value="{{ $filters['to'] }}"></div>
        <div><label>Party</label><select name="party_id" class="form-control"><option value="">All Parties</option>@foreach($parties as $party)<option value="{{ $party->id }}" @selected($filters['partyId']==$party->id)>{{ $party->display_name }}</option>@endforeach</select></div>
        <button class="btn btn-info report-btn">Apply</button>
    </form>
</div>
<div class="metric-strip">
    <div class="metric"><span>Total Sale</span><strong>Rs {{ number_format($bills->sum('sale'),2) }}</strong></div>
    <div class="metric"><span>Total Cost</span><strong>Rs {{ number_format($bills->sum('cost'),2) }}</strong></div>
    <div class="metric"><span>Profit / Loss</span><strong>Rs {{ number_format($bills->sum('profit'),2) }}</strong></div>
    <div class="metric"><span>Profit % on Sale</span><strong>{{ number_format((float) collect($bills)->sum('profit') / max(0.01, collect($bills)->sum('sale')) * 100, 2) }}%</strong></div>
</div>
<div class="report-card">
    <table id="profitTable" class="table report-table">
        <thead><tr><th>Date</th><th>Invoice</th><th>Party</th><th>Cost</th><th>Sale</th><th>Profit / Loss</th><th>Profit % on Cost</th><th>Profit % on Sale</th><th>Details</th></tr></thead>
        <tbody>@foreach($bills as $row)<tr><td>{{ $row['bill']->billing_date?->format('d-m-Y') }}</td><td>{{ $row['bill']->invoice_no }}</td><td>{{ $row['bill']->party?->display_name ?: 'Cash / Walk-in' }}</td><td>Rs {{ number_format($row['cost'],2) }}</td><td>Rs {{ number_format($row['sale'],2) }}</td><td><strong class="{{ $row['profit'] >= 0 ? 'text-success' : 'text-danger' }}">Rs {{ number_format($row['profit'],2) }}</strong></td><td><strong class="{{ $row['profit_percent'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($row['profit_percent'],2) }}%</strong></td><td><strong class="{{ ($row['profit_percent_on_sale'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) ($row['profit_percent_on_sale'] ?? 0),2) }}%</strong></td><td><button type="button" class="btn btn-sm btn-outline-primary profit-detail-btn" data-detail='@json($row["detail"])' data-print-url="{{ route('admin.sales.detail-pdf', $row['bill']) }}"><i class="fas fa-eye mr-1"></i>View Details</button></td></tr>@endforeach</tbody>
    </table>
</div>

<div class="modal fade profit-modal" id="billProfitDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div><h5 class="modal-title mb-0" id="profitDetailTitle">Invoice Details</h5><small id="profitDetailSub"></small></div>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-3 mb-3"><div class="detail-box"><span>Total</span><b id="detailGrandTotal">Rs 0.00</b></div></div>
                    <div class="col-md-3 mb-3"><div class="detail-box"><span>Cost</span><b id="detailCost">Rs 0.00</b></div></div>
                    <div class="col-md-3 mb-3"><div class="detail-box"><span>Profit / Loss</span><b id="detailProfit" class="profit-pill">Rs 0.00</b></div></div>
                    <div class="col-md-3 mb-3"><div class="detail-box"><span>Profit % on Cost</span><b id="detailProfitPercent" class="profit-pill">0.00%</b></div></div>
                    <div class="col-md-3 mb-3"><div class="detail-box"><span>Profit % on Sale</span><b id="detailProfitPercentSale" class="profit-pill">0.00%</b></div></div>
                </div>
                <div class="row mb-3">
                    <div class="col-lg-6 mb-3"><div class="detail-box"><span>Billing Details</span><b id="detailBilling"></b><hr><span>Shipping Details</span><b id="detailShipping"></b></div></div>
                    <div class="col-lg-6 mb-3"><div class="detail-box"><span>Party Details</span><b id="detailPartyName"></b><div id="detailPartyMeta" class="mt-2 text-muted"></div></div></div>
                </div>
                <div class="detail-table table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Item</th><th>HSN</th><th>Qty</th><th>Rate</th><th>Tax</th><th>Cost</th><th>Total</th><th>Profit</th><th>Profit %</th></tr></thead>
                        <tbody id="detailItemRows"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <small class="text-muted">Print page me browser ke Save as PDF option se PDF download ho jayega.</small>
                <a href="#" target="_blank" id="detailPrintUrl" class="btn btn-primary"><i class="fas fa-file-pdf mr-1"></i>Download PDF</a>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$('#profitTable').DataTable({pageLength:25});
function money(value){return 'Rs '+(Number(value)||0).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2});}
function syncProfitDateFilter(){
    const custom = $('#profitPeriod').val() === 'custom';
    $('.profit-custom-date').toggle(custom).find('input').prop('disabled', !custom).prop('required', custom);
    $('input[name="month"]').prop('disabled', custom || $('#profitPeriod').val() === 'all');
}
$('#profitPeriod').on('change', syncProfitDateFilter);
syncProfitDateFilter();
$(document).on('click','.profit-detail-btn',function(){
    const detail = $(this).data('detail');
    $('#profitDetailTitle').text('Invoice ' + detail.invoice);
    $('#profitDetailSub').text(`${detail.date || '-'} | ${detail.party.name || 'Cash / Walk-in'} | ${detail.sale_type || '-'}`);
    $('#detailGrandTotal').text(money(detail.amounts.total));
    $('#detailCost').text(money(detail.amounts.cost));
    $('#detailProfit').text(money(detail.amounts.profit)).toggleClass('text-danger', Number(detail.amounts.profit) < 0);
    $('#detailProfitPercent').text(`${Number(detail.amounts.profit_percent || 0).toFixed(2)}%`).toggleClass('text-danger', Number(detail.amounts.profit_percent) < 0);
    $('#detailProfitPercentSale').text(`${Number(detail.amounts.profit_percent_on_sale || 0).toFixed(2)}%`).toggleClass('text-danger', Number(detail.amounts.profit_percent_on_sale) < 0);
    $('#detailBilling').text(`${detail.phone || '-'} | ${detail.billing_address || '-'}`);
    $('#detailShipping').text(detail.shipping_address || '-');
    $('#detailPartyName').text(detail.party.name || 'Cash / Walk-in');
    $('#detailPartyMeta').html(`${detail.party.legal_name || '-'}<br>${detail.party.phone || '-'} | ${detail.party.email || '-'}<br>GSTIN: ${detail.party.gstin || '-'}<br>${detail.party.city || '-'}`);
    $('#detailItemRows').html((detail.items || []).map(item => {
        const bom = (item.bom || []).map(row => `${row.line_type === 'service' ? 'Service' : 'Raw'}: ${row.name} (${Number(row.qty_per_unit || 0)} ${row.unit || ''} @ ${money(row.unit_price || row.purchase_price)} = ${money(row.amount || 0)})`).join('<br>') || '-';
        return `<tr><td><b>${item.name}</b><br><small>${item.description || '-'}</small><br><small><b>BOM:</b><br>${bom}</small></td><td>${item.hsn || '-'}</td><td>${Number(item.qty||0).toFixed(2)} ${item.unit || ''}</td><td>${money(item.rate)}</td><td>${money(item.tax)}</td><td>${money(item.cost)}</td><td>${money(item.amount)}</td><td class="${Number(item.profit) < 0 ? 'text-danger' : 'text-success'}"><b>${money(item.profit)}</b></td><td class="${Number(item.profit_percent) < 0 ? 'text-danger' : 'text-success'}"><b>${Number(item.profit_percent || 0).toFixed(2)}%</b></td></tr>`;
    }).join('') || '<tr><td colspan="9" class="text-center text-muted">No items.</td></tr>');
    $('#detailPrintUrl').attr('href', $(this).data('print-url'));
    $('#billProfitDetailModal').modal('show');
});
</script>
@endpush
