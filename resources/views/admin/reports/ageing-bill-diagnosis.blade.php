@extends('layouts.admin')
@section('title','Ageing Diagnosis')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h3 class="m-0">Ageing Diagnosis</h3>
            <small>Invoice-wise ageing, tax, item and payment trace.</small>
        </div>
        <div>
            <a href="{{ route('admin.reports.ageing.print', ['kind' => $kind, 'bill' => $bill->id]) }}" target="_blank" class="btn btn-info btn-sm"><i class="fas fa-file-pdf mr-1"></i>PDF Format</a>
            <a href="{{ route('admin.reports.ageing') }}" class="btn btn-light btn-sm">Back</a>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4"><strong>Company</strong><div>{{ $company?->name ?? '-' }}</div></div>
            <div class="col-md-4"><strong>Party</strong><div>{{ $party?->display_name ?? '-' }}</div></div>
            <div class="col-md-4"><strong>Invoice</strong><div>{{ $bill->invoice_no }} | {{ $bill->billing_date?->format('d M Y') }}</div></div>
        </div>
        <div class="row mb-3">
            <div class="col-md-3"><div class="alert alert-info mb-0"><strong>Total</strong><br>Rs {{ number_format($summary['total'], 2) }}</div></div>
            <div class="col-md-3"><div class="alert alert-success mb-0"><strong>Paid</strong><br>Rs {{ number_format($summary['paid'], 2) }}</div></div>
            <div class="col-md-3"><div class="alert alert-warning mb-0"><strong>Pending</strong><br>Rs {{ number_format($summary['pending'], 2) }}</div></div>
            <div class="col-md-3"><div class="alert alert-secondary mb-0"><strong>Age</strong><br>{{ $days }} days</div></div>
        </div>
        <div class="alert alert-light border">
            Ageing calculation: bill date {{ $bill->billing_date?->format('d M Y') }} se today tak {{ $days }} days. Grand total Rs {{ number_format($summary['total'], 2) }} minus allocated payments Rs {{ number_format($summary['paid'], 2) }} = pending Rs {{ number_format($summary['pending'], 2) }}.
        </div>
        <h5>Bill Items, Taxing And Amount</h5>
        <div class="table-responsive mb-4">
            <table class="table table-sm">
                <thead><tr><th>Item</th><th>Qty</th><th>Rate</th><th>Discount</th><th>Tax %</th><th>Tax</th><th>Amount</th></tr></thead>
                <tbody>
                @foreach($bill->items ?? [] as $item)
                    <tr><td>{{ $item->item?->name ?? '-' }}<br><small>{{ $item->description }}</small></td><td>{{ $item->quantity }}</td><td>{{ number_format((float) ($item->unit_price ?? 0), 2) }}</td><td>{{ number_format((float) ($item->discount_amount ?? 0), 2) }}</td><td>{{ number_format((float) ($item->tax_percent ?? 0), 2) }}</td><td>{{ number_format((float) ($item->tax_amount ?? 0), 2) }}</td><td>{{ number_format((float) ($item->line_total ?? 0), 2) }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="row mb-3">
            <div class="col-md-4"><div class="border rounded p-2"><strong>Subtotal</strong><br>Rs {{ number_format((float) $bill->subtotal, 2) }}</div></div>
            <div class="col-md-4"><div class="border rounded p-2"><strong>Tax / Discount</strong><br>Tax Rs {{ number_format((float) $bill->tax_amount, 2) }} | Discount Rs {{ number_format((float) $bill->discount_amount, 2) }}</div></div>
            <div class="col-md-4"><div class="border rounded p-2"><strong>Grand Total</strong><br>Rs {{ number_format((float) $bill->grand_total, 2) }}</div></div>
        </div>
        <h5>Payment Trace</h5>
        @if($payments->count())
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Date</th><th>Amount</th><th>Mode</th><th>Bank</th><th>Reference</th></tr></thead>
                    <tbody>
                    @foreach($payments as $payment)
                        <tr><td>{{ $payment->payment?->payment_date?->format('d M Y') ?: $payment->created_at?->format('d M Y') }}</td><td>{{ number_format((float) $payment->amount, 2) }}</td><td>{{ $payment->payment?->payment_mode ?: '-' }}</td><td>{{ $payment->payment?->bankAccount?->bank_name ?: '-' }}</td><td>{{ $payment->payment?->reference_no ?: '-' }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-light">No payments logged against this bill.</div>
        @endif
    </div>
</div>
@endsection
