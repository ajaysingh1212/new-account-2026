@extends('layouts.admin')
@section('title','Ageing Diagnosis')

@push('styles')
<style>
.diag-head{background:#111827;color:#fff;border-radius:8px;padding:22px;margin-bottom:16px;display:flex;justify-content:space-between;gap:16px;align-items:center}.diag-head h2{margin:0;font-weight:800}.diag-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:16px}.diag-card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:14px;box-shadow:0 8px 22px rgba(15,23,42,.05)}.diag-card small{display:block;text-transform:uppercase;color:#64748b;font-size:11px;font-weight:800}.diag-card b{font-size:18px}.section-card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin-bottom:16px}.invoice-band{background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px;margin:14px 0}.profit{color:#047857}.loss{color:#b91c1c}.mini{font-size:12px;color:#64748b}.ageing-break-table th{background:#111827;color:#fff}.bill-line{border-top:1px dashed #cbd5e1;margin-top:5px;padding-top:5px}.bill-line:first-of-type{border-top:0}.old-item-diagnosis{display:none}@media(max-width:992px){.diag-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:576px){.diag-grid{grid-template-columns:1fr}.diag-head{display:block}}
</style>
@endpush

@section('content')
<div class="diag-head">
    <div>
        <h2><i class="fas fa-stethoscope mr-2"></i>Ageing Diagnosis</h2>
        <small>{{ $party?->display_name ?: 'Cash / Walk-in' }} | {{ ucfirst($kind) }} | As on {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</small>
    </div>
    <div>
        <a href="{{ route('admin.reports.ageing.party-print', ['party' => $partyKey, 'kind' => $kind, 'to_date' => $to]) }}" target="_blank" class="btn btn-info btn-sm"><i class="fas fa-file-pdf mr-1"></i>Combined PDF</a>
        <a href="{{ route('admin.reports.ageing', ['kind' => $kind, 'to_date' => $to, 'party_id' => $party?->id]) }}" class="btn btn-light btn-sm">Back</a>
    </div>
</div>

<div class="diag-grid">
    <div class="diag-card"><small>Open Bills</small><b>{{ $bills->count() }}</b></div>
    <div class="diag-card"><small>Total Sale</small><b>Rs {{ number_format($totals['sale'],2) }}</b></div>
    <div class="diag-card"><small>Returned</small><b>Rs {{ number_format($totals['returned'] ?? 0,2) }}</b></div>
    <div class="diag-card"><small>Outstanding</small><b>Rs {{ number_format($totals['due'],2) }}</b></div>
    <div class="diag-card"><small>Profit / Loss</small><b class="{{ $totals['profit'] >= 0 ? 'profit' : 'loss' }}">Rs {{ number_format($totals['profit'],2) }}</b></div>
</div>

<div class="section-card">
    <h4>Party Details</h4>
    <div class="row">
        <div class="col-md-4"><strong>{{ $party?->display_name ?: 'Cash / Walk-in' }}</strong><br><span class="mini">Legal: {{ $party?->legal_name ?: '-' }}</span></div>
        <div class="col-md-4">Phone: {{ $party?->phone ?: '-' }}<br>Email: {{ $party?->email ?: '-' }}<br>GSTIN: {{ $party?->gstin ?: '-' }}</div>
        <div class="col-md-4">{{ $party?->billing_address ?: $party?->shipping_address ?: '-' }}</div>
    </div>
</div>

<div class="section-card">
    <h4>Ageing Slab Diagnosis</h4>
    <div class="table-responsive">
        <table class="table table-sm ageing-break-table">
            <thead><tr><th>Party</th><th>Receivable</th><th>Payable</th>@foreach($slabs as $label)<th>{{ $label }}</th>@endforeach<th>Total Due</th></tr></thead>
            <tbody><tr>
                <td><strong>{{ $party?->display_name ?: 'Cash / Walk-in' }}</strong><br><small>{{ $bills->count() }} open bill(s)</small><br>{{ ucfirst($kind) }}</td>
                <td>Rs {{ number_format($totals['receivable'],2) }}</td>
                <td>Rs {{ number_format($totals['payable'],2) }}</td>
                @foreach($slabBills as $slab)
                    <td>
                        @if($slab['count'])
                            <strong>Rs {{ number_format($slab['due'],2) }}</strong><br><small>{{ $slab['count'] }} bill(s)</small>
                            @foreach($slab['bills'] as $bill)
                                @php $record = $bill['record']; @endphp
                                <div class="bill-line"><strong>{{ $record->invoice_no }}</strong><br><small>{{ $record->billing_date?->format('d M Y') }} | {{ ucfirst($bill['kind']) }} | Rs {{ number_format($bill['due'],2) }}</small></div>
                            @endforeach
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                @endforeach
                <td><strong>Rs {{ number_format($totals['due'],2) }}</strong></td>
            </tr></tbody>
        </table>
    </div>
</div>

<div class="section-card old-item-diagnosis">
    <h4>Invoice, Item, Pricing, Profit/Loss And Payment Details</h4>
    @foreach($bills as $bill)
        @php $record = $bill['record']; @endphp
        <div class="invoice-band">
            <div class="row">
                <div class="col-md-3"><strong>Invoice #{{ $record->invoice_no }}</strong><br><span class="mini">{{ $record->billing_date?->format('d M Y') }} | {{ ucfirst($bill['kind']) }}</span></div>
                <div class="col-md-3">Age: <strong>{{ $bill['age'] }} days</strong><br>Due: <strong>Rs {{ number_format($bill['due'],2) }}</strong></div>
                <div class="col-md-3">Total: Rs {{ number_format($bill['total'],2) }}<br>Returned: Rs {{ number_format($bill['returned'] ?? 0,2) }}<br>Paid: Rs {{ number_format($bill['paid'],2) }}</div>
                <div class="col-md-3">Cost: Rs {{ number_format($bill['cost'],2) }}<br>Profit: <strong class="{{ $bill['profit'] >= 0 ? 'profit' : 'loss' }}">Rs {{ number_format($bill['profit'],2) }} ({{ number_format($bill['profit_percent'],2) }}%)</strong></div>
            </div>
        </div>
        <div class="table-responsive mb-3">
            <table class="table table-sm table-bordered">
                <thead><tr><th>Item</th><th>Qty</th><th>Rate</th><th>Discount</th><th>Tax</th><th>Amount</th><th>Cost</th><th>Profit/Loss</th><th>Serial / Units</th></tr></thead>
                <tbody>
                @foreach($bill['items'] as $item)
                    <tr>
                        <td><strong>{{ $item['name'] }}</strong><br><small>{{ $item['description'] }}</small></td>
                        <td>{{ number_format($item['qty'],2) }} {{ $item['unit'] }}</td>
                        <td>Rs {{ number_format($item['rate'],2) }}</td>
                        <td>Rs {{ number_format($item['discount'],2) }}</td>
                        <td>{{ number_format($item['tax_percent'],2) }}%<br>Rs {{ number_format($item['tax'],2) }}</td>
                        <td><strong>Rs {{ number_format($item['amount'],2) }}</strong></td>
                        <td>Rs {{ number_format($item['cost'],2) }}</td>
                        <td><span class="{{ $item['profit'] >= 0 ? 'profit' : 'loss' }}">Rs {{ number_format($item['profit'],2) }}<br>{{ number_format($item['profit_percent'],2) }}%</span></td>
                        <td>
                            @forelse($item['units'] as $unit)
                                {{ $unit['serial_no'] ?? '-' }} / {{ $unit['vts_sim'] ?? '-' }} / {{ $unit['buyer_code'] ?? '-' }}<br>
                            @empty
                                <span class="text-muted">-</span>
                            @endforelse
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <h6>Payments Against Invoice #{{ $record->invoice_no }}</h6>
        @if($bill['payments']->count())
            <div class="table-responsive mb-3">
                <table class="table table-sm">
                    <thead><tr><th>Date</th><th>Amount</th><th>Mode</th><th>Bank</th><th>Reference</th></tr></thead>
                    <tbody>
                    @foreach($bill['payments'] as $payment)
                        <tr>
                            <td>{{ $payment->payment?->payment_date?->format('d M Y') ?: $payment->created_at?->format('d M Y') }}</td>
                            <td>Rs {{ number_format((float) $payment->amount,2) }}</td>
                            <td>{{ $payment->payment?->payment_mode ?: '-' }}</td>
                            <td>{{ $payment->payment?->bankAccount?->bank_name ?: '-' }}</td>
                            <td>{{ $payment->payment?->reference_no ?: '-' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-light border">No payment allocated against this invoice.</div>
        @endif
    @endforeach
</div>
@endsection
