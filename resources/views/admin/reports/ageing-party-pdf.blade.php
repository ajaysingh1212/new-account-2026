<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Ageing Report - {{ $party?->display_name ?: 'Cash' }}</title>
<style>
:root{
    --accent:#2563eb;--accent-dark:#1e40af;--bg:#f1f5fb;--sheet:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b;--row:#f8faff;
    --purple:#7c3aed;--purple-dark:#5b21b6;
    --green:#16a34a;--green-bg:#ecfdf5;--green-border:#a7f3d0;
    --red:#dc2626;--red-bg:#fef2f2;--red-border:#fecaca;
    --blue:#2563eb;--blue-bg:#eff6ff;--blue-border:#bfdbfe;
    --amber:#d97706;--amber-bg:#fffbeb;--amber-border:#fde68a;
    --slate-bg:#f8fafc;
}
*{box-sizing:border-box}
body{font-family:'Segoe UI',Arial,sans-serif;background:var(--bg);color:var(--text);padding:24px 16px;margin:0}
.toolbar{max-width:1180px;margin:0 auto 14px;text-align:right}
.print{background:var(--purple);color:#fff;border:0;border-radius:7px;padding:9px 18px;font-weight:800;cursor:pointer;box-shadow:0 4px 14px rgba(124,58,237,.35)}
.sheet{max-width:1180px;margin:0 auto;background:var(--sheet);border:1.5px solid var(--border);border-radius:14px;overflow:hidden;box-shadow:0 8px 40px rgba(15,23,42,.1)}

/* ===== Header ===== */
.header{background:linear-gradient(120deg,var(--purple) 0%,var(--accent) 55%,var(--accent-dark) 100%);color:#fff;padding:28px 30px;display:flex;justify-content:space-between;gap:20px;position:relative;overflow:hidden}
.header::after{content:"";position:absolute;right:-40px;top:-60px;width:200px;height:200px;border-radius:50%;background:rgba(255,255,255,.08)}
.brand{display:flex;gap:16px;position:relative;z-index:1}
.logo{width:72px;height:72px;object-fit:contain;border-radius:12px;background:rgba(255,255,255,.15);border:2px solid rgba(255,255,255,.25);padding:4px}
.logo-placeholder{width:72px;height:72px;border-radius:12px;background:rgba(255,255,255,.12);border:2px solid rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:800}
.company{font-size:26px;font-weight:900}
.meta{font-size:12px;opacity:.9;line-height:1.7}
.doc{text-align:right;position:relative;z-index:1}
.doc-type{font-size:30px;font-weight:900;letter-spacing:.08em}
.doc-num{font-size:13px;opacity:.9}

.content{padding:22px 24px}

/* ===== Letter (expanded, detailed content) ===== */
.letter{
    position:relative;
    border:1px solid var(--border);
    border-left:5px solid var(--purple);
    background:linear-gradient(180deg,#faf9ff 0%,#f8fafc 100%);
    border-radius:10px;
    padding:18px 22px;
    margin-bottom:18px;
    line-height:1.7;
}
.letter .subject-badge{
    display:inline-block;background:var(--purple);color:#fff;font-size:10.5px;font-weight:800;
    letter-spacing:.04em;text-transform:uppercase;padding:4px 10px;border-radius:999px;margin-bottom:10px;
}
.letter .salutation{font-weight:700;margin-bottom:8px;color:var(--accent-dark);font-size:13px}
.letter .body-text{font-size:12px;color:#1e293b;text-align:justify}
.letter .body-text p{margin:0 0 9px 0}
.letter .body-text p:last-child{margin-bottom:0}
.letter .amt{color:var(--red);font-weight:800}
.letter .closing{margin-top:10px;font-size:12px}
.letter .closing strong{display:block;color:var(--purple-dark)}

/* ===== Payment / QR card (moved up, prominent) ===== */
.payment-card{
    display:flex;border-radius:12px;overflow:hidden;border:1px solid var(--border);
    margin-bottom:18px;box-shadow:0 6px 18px rgba(37,99,235,.1);
}
.payment-card .qr-side{
    background:linear-gradient(135deg,var(--blue),var(--purple));
    padding:16px 20px;display:flex;flex-direction:column;align-items:center;justify-content:center;
    gap:8px;width:210px;flex-shrink:0;
}
.payment-card .qr-side img{width:170px;height:170px;background:#fff;border-radius:10px;padding:8px;box-shadow:0 2px 8px rgba(0,0,0,.15)}
.payment-card .qr-side .scan-label{color:#fff;font-weight:800;font-size:11.5px;letter-spacing:.05em;text-transform:uppercase;text-align:center}
.payment-card .qr-side .no-qr{width:170px;height:170px;background:rgba(255,255,255,.15);border:2px dashed rgba(255,255,255,.5);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:11px;text-align:center;padding:10px}
.payment-card .info-side{flex:1;padding:16px 22px;background:#fff}
.payment-card .info-side .ptitle{font-size:12.5px;font-weight:800;color:var(--purple-dark);margin-bottom:10px;text-transform:uppercase;letter-spacing:.03em}
.payment-card .info-row{display:flex;justify-content:space-between;padding:6px 0;font-size:12px;border-bottom:1px dashed var(--border)}
.payment-card .info-row:last-child{border-bottom:0}
.payment-card .info-row .k{color:var(--muted);font-weight:600}
.payment-card .info-row .v{font-weight:800;text-align:right}
.payment-card .info-note{margin-top:10px;font-size:10.5px;color:var(--muted);border-top:1px solid var(--border);padding-top:8px}

/* ===== Ageing pivot table — UNCHANGED ===== */
.ageing-table{width:100%;border-collapse:collapse;font-size:12px}
.ageing-table th{background:#111827;color:#fff;text-align:left;padding:9px 8px;border:1px solid #111827;font-size:10px;text-transform:uppercase}
.ageing-table td{border:1px solid var(--border);padding:8px;vertical-align:top}
.num{text-align:right;white-space:nowrap}
.bill-line{border-top:1px dashed #cbd5e1;margin-top:5px;padding-top:5px}
.bill-line:first-of-type{border-top:0}
.muted{color:var(--muted)}
.old-detail{display:none}

/* ===== KPI strip — moved below the ageing table ===== */
.strip{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-top:16px}
.cell{padding:12px 14px;border-radius:10px;border:1px solid var(--border);background:#fff;border-top:3px solid var(--border)}
.cell.c-neutral{border-top-color:#64748b}
.cell.c-green{border-top-color:var(--green);background:var(--green-bg)}
.cell.c-red{border-top-color:var(--red);background:var(--red-bg)}
.cell.c-blue{border-top-color:var(--blue);background:var(--blue-bg)}
.cell.c-amber{border-top-color:var(--amber);background:var(--amber-bg)}
.label{font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}
.value{font-size:16px;font-weight:900;margin-top:3px}
.c-green .value{color:var(--green)}
.c-red .value{color:var(--red)}
.c-blue .value{color:var(--blue)}
.c-amber .value{color:var(--amber)}

/* ===== Signature ===== */
.signature{display:flex;justify-content:flex-end;padding:26px 24px 22px;gap:14px}
.sign-box{width:260px;text-align:center;border-top:2px solid var(--purple);padding-top:8px;font-weight:800;color:#1e293b}
.sign-box .muted{font-weight:500}

/* ===== Footer ===== */
.footer{border-top:1px solid var(--border);background:#f8faff;padding:12px 24px;color:var(--muted);font-size:11px;text-align:center}

@media print{
    body{background:#fff;padding:0}
    .toolbar{display:none}
    .sheet{box-shadow:none;border:0;border-radius:0;max-width:none}
    @page{size:A4 landscape;margin:10mm}
    .header,.cell,.letter,.payment-card,.qr-side{-webkit-print-color-adjust:exact;print-color-adjust:exact}
    .ageing-table{font-size:10.5px}
    .ageing-table th,.ageing-table td{padding:6px}
    .ageing-table tr,.bill-line{page-break-inside:avoid}
    .letter,.payment-card,.signature,.footer,.strip{page-break-inside:avoid}
}
</style>
</head>
<body>
<div class="toolbar"><button class="print" onclick="window.print()">Print / Save PDF</button></div>
<main class="sheet">
    <div class="header">
        <div class="brand">
            @if($company?->logo)<img class="logo" src="{{ asset('storage/'.$company->logo) }}" alt="logo">@else<div class="logo-placeholder">AC</div>@endif
            <div>
                <div class="company">{{ $company?->name ?? config('app.name') }}</div>
                <div class="meta">
                    {{ $company?->phone }} {{ $company?->email ? '| '.$company->email : '' }}<br>
                    GST: {{ $company?->gst_number ?: '-' }} | PAN: {{ $company?->pan_number ?: '-' }}<br>
                    {{ $company?->address }}
                </div>
            </div>
        </div>
        <div class="doc">
            <div class="doc-type">AGEING</div>
            <div class="doc-num">{{ ucfirst($kind) }} | As on {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</div>
            <div class="doc-num">Party: {{ $party?->display_name ?: 'Cash / Walk-in' }}</div>
        </div>
    </div>

    <div class="content">

        {{-- ===== Letter — expanded, detailed content ===== --}}
        <div class="letter">
            <span class="subject-badge">Subject: Request for Clearance of Outstanding Ageing Balance</span>
            <div class="salutation">Dear {{ $party?->display_name ?: 'Customer' }},</div>
            <div class="body-text">
                <p>
                    We hope this letter finds you well. This is a formal yet cordial reminder regarding the
                    outstanding balance currently reflected against your account in our books of accounts.
                </p>
                <p>
                    As per our latest reconciliation carried out as on <strong>{{ \Carbon\Carbon::parse($to)->format('d M Y') }}</strong>,
                    an amount of <span class="amt">Rs {{ number_format($totals['due'],2) }}</span> remains
                    outstanding against <strong>{{ $bills->count() }}</strong> invoice(s) raised in the ordinary
                    course of our business dealings. A complete invoice-wise break-up, along with the applicable
                    ageing buckets, has been enclosed below for your convenient reference and verification.
                </p>
                <p>
                    We sincerely value the business relationship we share and have consistently extended
                    flexibility and goodwill wherever required. However, in the interest of maintaining smooth
                    and healthy financial operations on both ends, we kindly request you to arrange for
                    clearance of the above outstanding amount at the earliest, and preferably within
                    <strong>7 (seven) working days</strong> from the date of this letter.
                </p>
                <p>
                    To make the process as convenient as possible, we have shared our complete payment
                    details along with a scannable UPI QR code below — please feel free to use whichever mode
                    of payment suits you best.
                </p>
                <p>
                    Should the payment already stand cleared from your end, we would be grateful if you could
                    share the transaction reference so that our records may be updated without delay. Likewise,
                    should there be any discrepancy in the figures mentioned above, please do not hesitate to
                    reach out to us immediately so that the matter can be resolved promptly.
                </p>
                <div class="closing">
                    We sincerely thank you for your continued trust and cooperation, and look forward to
                    receiving the outstanding payment at the earliest.<br><br>
                    Warm regards,
                    <strong>{{ $company?->name ?? 'the Company' }}</strong>
                </div>
            </div>
        </div>

        {{-- ===== Payment / QR card — moved up, prominent and enlarged ===== --}}
        <div class="payment-card">
            <div class="qr-side">
                @if($bankAccount?->upi_qr_code)
                    <img src="{{ asset('storage/'.$bankAccount->upi_qr_code) }}" alt="UPI QR Code">
                @else
                    <div class="no-qr">QR Code<br>Not Available</div>
                @endif
                <div class="scan-label">Scan &amp; Pay Instantly</div>
            </div>
            <div class="info-side">
                <div class="ptitle">Payment Details for Clearance of Dues</div>
                @if($bankAccount)
                    <div class="info-row"><span class="k">Bank Name</span><span class="v">{{ $bankAccount->bank_name ?: '-' }}</span></div>
                    <div class="info-row"><span class="k">Account Number</span><span class="v">{{ $bankAccount->account_number ?: '-' }}</span></div>
                    <div class="info-row"><span class="k">IFSC Code</span><span class="v">{{ $bankAccount->ifsc_code ?: '-' }}</span></div>
                    <div class="info-row"><span class="k">UPI ID</span><span class="v">{{ $bankAccount->upi_id ?: '-' }}</span></div>
                @else
                    <div class="muted">No bank account linked for this company.</div>
                @endif
                <div class="info-note">
                    Payments found on record: <strong>{{ $totals['payment_count'] }}</strong>.
                    This report combines all open invoices for the selected party and ageing filters.
                </div>
            </div>
        </div>

        {{-- ===================================================================== --}}
        {{-- AGEING SLAB TABLE — kept exactly as-is, no structural or visual change --}}
        {{-- ===================================================================== --}}
        <table class="ageing-table">
            <thead>
                <tr>
                    <th style="min-width:190px">Party</th>
                    <th class="num">Receivable</th>
                    <th class="num">Payable</th>
                    @foreach($slabs as $label)<th style="min-width:150px">{{ $label }}</th>@endforeach
                    <th class="num">Total Due</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>{{ $party?->display_name ?: 'Cash / Walk-in' }}</strong><br><span class="muted">{{ $bills->count() }} open bill(s)</span><br>{{ ucfirst($kind) }}</td>
                    <td class="num">Rs {{ number_format($totals['receivable'],2) }}</td>
                    <td class="num">Rs {{ number_format($totals['payable'],2) }}</td>
                    @foreach($slabBills as $slab)
                        <td>
                            @if($slab['count'])
                                <strong>Rs {{ number_format($slab['due'],2) }}</strong><br>
                                <span class="muted">{{ $slab['count'] }} bill(s)</span>
                                @foreach($slab['bills'] as $bill)
                                    @php $record = $bill['record']; @endphp
                                    <div class="bill-line">
                                        <strong>{{ $record->invoice_no }}</strong>
                                        <span class="muted">{{ $record->billing_date?->format('d M Y') }} | {{ ucfirst($bill['kind']) }} | Rs {{ number_format($bill['due'],2) }}</span>
                                    </div>
                                @endforeach
                            @else
                                <span class="muted">-</span>
                            @endif
                        </td>
                    @endforeach
                    <td class="num"><strong>Rs {{ number_format($totals['due'],2) }}</strong></td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td><strong>Total</strong></td>
                    <td class="num"><strong>Rs {{ number_format($totals['receivable'],2) }}</strong></td>
                    <td class="num"><strong>Rs {{ number_format($totals['payable'],2) }}</strong></td>
                    @foreach($slabBills as $slab)<td><strong>Rs {{ number_format($slab['due'],2) }}</strong></td>@endforeach
                    <td class="num"><strong>Rs {{ number_format($totals['due'],2) }}</strong></td>
                </tr>
            </tfoot>
        </table>
        {{-- ===================== END OF UNCHANGED SLAB TABLE ===================== --}}

        {{-- ===== KPI summary strip — now placed just below the table ===== --}}
        <div class="strip">
            <div class="cell c-neutral"><div class="label">Open Bills</div><div class="value">{{ $bills->count() }}</div></div>
            <div class="cell c-green"><div class="label">Receivable</div><div class="value">Rs {{ number_format($totals['receivable'],2) }}</div></div>
            <div class="cell c-red"><div class="label">Payable</div><div class="value">Rs {{ number_format($totals['payable'],2) }}</div></div>
            <div class="cell c-blue"><div class="label">Paid</div><div class="value">Rs {{ number_format($totals['paid'],2) }}</div></div>
            <div class="cell c-amber"><div class="label">Total Due</div><div class="value">Rs {{ number_format($totals['due'],2) }}</div></div>
        </div>

    </div>

    <div class="signature">
        <div class="sign-box">Authorised Signature<br><span class="muted">{{ $company?->name }}</span></div>
    </div>

    <div class="footer">
        Filter applied: {{ ucfirst($kind) }}. Payable shows payable only, receivable shows receivable only, both shows both.
    </div>

    <div class="old-detail">
    <div class="party">
        <div class="box">
            <div class="section-title">Party Details</div>
            <div class="party-name">{{ $party?->display_name ?: 'Cash / Walk-in' }}</div>
            <div class="muted">
                Legal: {{ $party?->legal_name ?: '-' }}<br>
                Phone: {{ $party?->phone ?: '-' }} | Email: {{ $party?->email ?: '-' }}<br>
                GSTIN: {{ $party?->gstin ?: '-' }} | PAN: {{ $party?->pan_number ?: '-' }}<br>
                {{ $party?->billing_address ?: $party?->shipping_address }}
            </div>
        </div>
        <div class="box">
            <div class="section-title">Ageing Slab Summary</div>
            @foreach($slabs as $key => $label)
                @php $cell = $slabRow['slabs'][$key] ?? ['due' => 0, 'bills' => 0]; @endphp
                <div>{{ $label }}: <strong>Rs {{ number_format((float) $cell['due'],2) }}</strong> <span class="muted">({{ $cell['bills'] }} bills)</span></div>
            @endforeach
        </div>
    </div>
    <div class="content">
        <div class="section-title">Invoice Ageing and Item Details</div>
        @foreach($bills as $bill)
            @php $record = $bill['record']; @endphp
            <div class="page-break">
                <table>
                    <tr class="invoice-head">
                        <td colspan="8">
                            {{ strtoupper($bill['kind']) }} Invoice #{{ $record->invoice_no }} |
                            Date {{ $record->billing_date?->format('d M Y') }} |
                            Age {{ $bill['age'] }} days |
                            Total Rs {{ number_format($bill['total'],2) }} |
                            Paid Rs {{ number_format($bill['paid'],2) }} |
                            Due Rs {{ number_format($bill['due'],2) }}
                        </td>
                    </tr>
                    <tr><th>#</th><th>Item</th><th>HSN</th><th class="num">Qty</th><th class="num">Rate</th><th class="num">Discount</th><th class="num">Tax</th><th class="num">Amount</th></tr>
                    @foreach($bill['items'] as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $item['name'] }}</strong><br><span class="muted">{{ $item['description'] }}</span></td>
                            <td>{{ $item['hsn'] }}</td>
                            <td class="num">{{ number_format($item['qty'],2) }} {{ $item['unit'] }}</td>
                            <td class="num">{{ number_format($item['rate'],2) }}</td>
                            <td class="num">{{ number_format($item['discount'],2) }}</td>
                            <td class="num">{{ number_format($item['tax'],2) }}<br><span class="muted">{{ number_format($item['tax_percent'],2) }}%</span></td>
                            <td class="num"><strong>{{ number_format($item['amount'],2) }}</strong></td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endforeach
        <div class="summary">
            <div class="line"><span>Subtotal</span><strong>Rs {{ number_format($totals['subtotal'],2) }}</strong></div>
            <div class="line"><span>Discount</span><strong>Rs {{ number_format($totals['discount'],2) }}</strong></div>
            <div class="line"><span>Tax</span><strong>Rs {{ number_format($totals['tax'],2) }}</strong></div>
            <div class="line"><span>Grand Total</span><strong>Rs {{ number_format($totals['grand_total'],2) }}</strong></div>
            <div class="line"><span>Payment Received/Allocated</span><strong>Rs {{ number_format($totals['paid'],2) }}</strong></div>
            <div class="line total"><span>Total Outstanding</span><strong>Rs {{ number_format($totals['due'],2) }}</strong></div>
        </div>
    </div>
    </div>
</main>
</body>
</html>
