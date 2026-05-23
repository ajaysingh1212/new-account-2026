@php
    $party = $party ?? null;
    $lines = $lines ?? collect();
    $docNo = $docNo ?? '';
    $docDate = $docDate ?? null;
    $accent = $accent ?? '#2563eb';
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }} {{ $docNo }}</title>
    <style>
        *{box-sizing:border-box} body{font-family:Arial,sans-serif;background:#eef4ff;margin:0;padding:18px;color:#0f172a}.sheet{max-width:980px;margin:auto;background:#fff;border:2px solid #111827;border-radius:10px;overflow:hidden}.top{display:flex;justify-content:space-between;align-items:flex-start;padding:18px 22px;background:#f8fbff}.brand{font-size:28px;font-weight:800;color:{{ $accent }}}.doc-title{background:{{ $accent }};color:#fff;padding:18px 22px;display:flex;justify-content:space-between;align-items:center}.doc-title h1{margin:0;font-size:24px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;padding:18px 22px}.box{border:1px solid #cbd5e1;border-radius:8px;padding:12px;min-height:110px}.box h3{margin:0 0 8px;color:{{ $accent }};font-size:15px}.meta td{padding:5px 0 5px 18px}.items{width:calc(100% - 44px);margin:8px 22px 18px;border-collapse:collapse}.items th{background:linear-gradient(90deg,#38bdf8,#22d3ee);color:#fff;text-align:left}.items th,.items td{border:1px solid #e2e8f0;padding:9px;font-size:13px}.items tfoot td{font-weight:800;background:#eff6ff}.summary{margin:0 22px 22px auto;width:360px;border:1px solid #2563eb}.summary div{display:flex;justify-content:space-between;padding:9px 12px;border-bottom:1px solid #dbeafe}.summary div:last-child{border-bottom:0;font-size:18px;font-weight:800}.footer{display:grid;grid-template-columns:1fr 1fr 1fr;gap:0;margin:0 22px 22px;border:1px solid #64748b}.footer>div{min-height:145px;border-right:1px solid #64748b;padding:12px}.footer>div:last-child{border-right:0;text-align:center;padding-top:70px}.muted{color:#64748b;font-size:12px}@media print{body{background:#fff;padding:0}.sheet{border-radius:0;max-width:none}.no-print{display:none}}
    </style>
</head>
<body>
<div class="no-print" style="max-width:980px;margin:0 auto 12px;text-align:right"><button onclick="window.print()" style="padding:10px 18px;border:0;border-radius:6px;background:{{ $accent }};color:#fff;font-weight:700">Print</button></div>
<div class="sheet">
    <div class="top">
        <div><div class="brand">{{ config('app.name', 'Eemot Account') }}</div><div class="muted">Professional Accounting System</div></div>
        <table class="meta"><tr><td><b>No:</b></td><td>{{ $docNo }}</td></tr><tr><td><b>Date:</b></td><td>{{ $docDate ? \Illuminate\Support\Carbon::parse($docDate)->format('d-m-Y') : '-' }}</td></tr></table>
    </div>
    <div class="doc-title"><h1>{{ $title }}</h1><div>{{ strtoupper($status ?? 'posted') }}</div></div>
    <div class="grid">
        <div class="box"><h3>Party Details</h3><b>{{ $party?->display_name ?: 'Cash / Walk-in' }}</b><br>{{ $party?->phone }}<br>{{ $party?->gstin }}<br>{!! nl2br(e($party?->billing_address ?? $billingAddress ?? '')) !!}</div>
        <div class="box"><h3>Billing / Shipping Address</h3><b>Billing</b><br>{!! nl2br(e($billingAddress ?? '')) !!}<br><br><b>Shipping</b><br>{!! nl2br(e($shippingAddress ?? '')) !!}</div>
    </div>
    <table class="items">
        <thead><tr><th>#</th><th>Item Name</th><th>HSN/SAC</th><th>Qty</th><th>Unit</th><th>Price/Unit</th><th>Amount</th></tr></thead>
        <tbody>
        @foreach($lines as $line)
            <tr><td>{{ $loop->iteration }}</td><td>{{ $line->item?->name }}<br><span class="muted">{{ $line->description }}</span></td><td>{{ $line->item?->hsn_code }}</td><td>{{ $line->quantity }}</td><td>{{ $line->unit }}</td><td>Rs {{ number_format((float)$line->unit_price,2) }}</td><td><b>Rs {{ number_format((float)$line->line_total,2) }}</b></td></tr>
        @endforeach
        </tbody>
        <tfoot><tr><td colspan="3">Total</td><td>{{ number_format((float)$lines->sum('quantity'), 3) }}</td><td colspan="2"></td><td>Rs {{ number_format((float)$grandTotal,2) }}</td></tr></tfoot>
    </table>
    <div class="summary"><div><span>Subtotal</span><b>Rs {{ number_format((float)$subtotal,2) }}</b></div><div><span>Discount</span><b>Rs {{ number_format((float)$discount,2) }}</b></div><div><span>GST / Tax</span><b>Rs {{ number_format((float)$tax,2) }}</b></div><div><span>Grand Total</span><b>Rs {{ number_format((float)$grandTotal,2) }}</b></div></div>
    <div class="footer"><div><b>Bank Details</b><br><span class="muted">Print-selected bank account can be shown here.</span></div><div><b>Terms & Conditions</b><br>{!! nl2br(e($terms ?? '')) !!}</div><div><b>Authorized Signatory</b></div></div>
</div>
</body>
</html>
