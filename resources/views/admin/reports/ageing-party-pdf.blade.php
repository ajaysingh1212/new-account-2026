<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Ageing Report - {{ $party?->display_name ?: 'Cash' }}</title>
<style>
:root{
    /* ==== active theme (swapped by JS) ==== */
    --accent:#2563eb;--accent-dark:#1e40af;
    --purple:#7c3aed;--purple-dark:#5b21b6;

    --bg:#f1f5fb;--sheet:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b;--row:#f8faff;

    --green:#16a34a;--green-bg:#ecfdf5;--green-border:#a7f3d0;
    --red:#dc2626;--red-bg:#fef2f2;--red-border:#fecaca;
    --blue:#2563eb;--blue-bg:#eff6ff;--blue-border:#bfdbfe;
    --amber:#d97706;--amber-bg:#fffbeb;--amber-border:#fde68a;
    --slate-bg:#f8fafc;
}
/* ---- dark mode palette ---- */
body.dark-mode{
    --bg:#0b1220;--sheet:#111827;--border:#243147;--text:#e2e8f0;--muted:#94a3b8;--row:#0f1729;
}
*{box-sizing:border-box}
body{font-family:'Segoe UI',Arial,sans-serif;background:var(--bg);color:var(--text);padding:24px 16px;margin:0;transition:background .2s,color .2s}

/* ================= Toolbar ================= */
.toolbar{max-width:900px;margin:0 auto 16px;background:var(--sheet);border:1px solid var(--border);border-radius:14px;box-shadow:0 6px 24px rgba(15,23,42,.08);padding:12px 18px;display:flex;align-items:center;gap:18px;flex-wrap:wrap}
.tb-group{display:flex;align-items:center;gap:10px}
.tb-label{font-size:12.5px;font-weight:700;color:var(--muted)}
.tb-divider{width:1px;height:26px;background:var(--border)}
.swatch{width:22px;height:22px;border-radius:50%;cursor:pointer;border:2px solid transparent;box-shadow:inset 0 0 0 1px rgba(0,0,0,.06);transition:transform .12s}
.swatch:hover{transform:scale(1.12)}
.swatch.active{border-color:var(--text);box-shadow:0 0 0 2px var(--sheet),0 0 0 4px var(--text)}
.swatch.custom{background:conic-gradient(red,orange,yellow,green,blue,violet,red);border-style:dashed;border-color:var(--muted)}
.mode-btn{display:flex;align-items:center;gap:6px;background:var(--slate-bg);border:1px solid var(--border);border-radius:8px;padding:7px 14px;font-weight:700;font-size:12.5px;cursor:pointer;color:var(--text)}
.mode-btn.on{background:#1e293b;color:#fff;border-color:#1e293b}
.print-select{border:1px solid var(--border);border-radius:8px;padding:7px 12px;font-weight:700;font-size:12.5px;background:var(--sheet);color:var(--text);cursor:pointer}
.print{margin-left:auto;background:var(--accent);color:#fff;border:0;border-radius:8px;padding:10px 20px;font-weight:800;cursor:pointer;box-shadow:0 4px 14px rgba(37,99,235,.35);font-size:13px;display:flex;align-items:center;gap:8px}

.sheet{max-width:900px;margin:0 auto;background:var(--sheet);border:1.5px solid var(--border);border-radius:14px;overflow:hidden;box-shadow:0 8px 40px rgba(15,23,42,.1)}

/* ===== Header ===== */
.header{background:linear-gradient(120deg,var(--purple) 0%,var(--accent) 55%,var(--accent-dark) 100%);color:#fff;padding:30px 32px;display:flex;justify-content:space-between;gap:20px;position:relative;overflow:hidden}
.header::after{content:"";position:absolute;right:-40px;top:-60px;width:200px;height:200px;border-radius:50%;background:rgba(255,255,255,.08)}
.brand{display:flex;gap:16px;position:relative;z-index:1}
.logo{width:72px;height:72px;object-fit:contain;border-radius:12px;background:rgba(255,255,255,.15);border:2px solid rgba(255,255,255,.25);padding:4px}
.logo-placeholder{width:72px;height:72px;border-radius:12px;background:rgba(255,255,255,.12);border:2px solid rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:800}
.company{font-size:27px;font-weight:900}
.meta{font-size:13px;opacity:.92;line-height:1.7;margin-top:4px}
.doc{text-align:right;position:relative;z-index:1;max-width:340px}
.doc-type{font-size:19px;font-weight:900;letter-spacing:.02em;line-height:1.35}
.doc-num{font-size:13px;opacity:.92;margin-top:4px}

.content{padding:24px 26px}

/* ===== Letter (shortened, 2 paragraphs) ===== */
.letter{
    position:relative;
    border:1px solid var(--border);
    border-left:5px solid var(--purple);
    background:linear-gradient(180deg,#faf9ff 0%,#f8fafc 100%);
    border-radius:10px;
    padding:20px 24px;
    margin-bottom:20px;
    line-height:1.75;
}
body.dark-mode .letter{background:linear-gradient(180deg,#151b2e 0%,#111827 100%)}
.letter .subject-badge{
    display:inline-block;background:var(--purple);color:#fff;font-size:11.5px;font-weight:800;
    letter-spacing:.04em;text-transform:uppercase;padding:5px 12px;border-radius:999px;margin-bottom:12px;
}
.letter .salutation{font-weight:700;margin-bottom:9px;color:var(--accent-dark);font-size:14.5px}
.letter .body-text{font-size:14px;color:var(--text);text-align:justify}
.letter .body-text p{margin:0 0 11px 0}
.letter .body-text p:last-child{margin-bottom:0}
.letter .amt{color:var(--red);font-weight:800}
.letter .paid-note{color:var(--green);font-weight:800}
.letter .closing{margin-top:12px;font-size:14px}
.letter .closing strong{display:block;color:var(--purple-dark)}

/* ===================================================================== */
/* AGEING SLAB TABLE — kept exactly as-is, no structural or visual change */
/* ===================================================================== */
.ageing-table{width:100%;border-collapse:collapse;font-size:13.5px}
.ageing-table th{background:linear-gradient(120deg,var(--purple-dark),var(--accent-dark));color:#fff;text-align:left;padding:11px 9px;border:1px solid var(--accent-dark);font-size:12.5px;font-weight:900;letter-spacing:.03em;text-transform:uppercase}
.ageing-table td{border:1px solid var(--border);padding:9px;vertical-align:top}
.num{text-align:right;white-space:nowrap}
.bill-line{border-top:1px dashed #cbd5e1;margin-top:5px;padding-top:5px}
.bill-line:first-of-type{border-top:0}
.age-pill{display:inline-block;background:var(--amber-bg);color:var(--amber);border:1px solid var(--amber-border);font-size:10px;font-weight:800;padding:1px 6px;border-radius:999px;margin-left:6px;white-space:nowrap}
.muted{color:var(--muted)}
.old-detail{display:none}

/* ===== KPI summary strip — placed right below the ageing table ===== */
.strip{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-top:18px}
.cell{padding:13px 14px;border-radius:10px;border:1px solid var(--border);background:var(--sheet);border-top:3px solid var(--border)}
.cell.c-neutral{border-top-color:#64748b}
.cell.c-green{border-top-color:var(--green);background:var(--green-bg)}
.cell.c-red{border-top-color:var(--red);background:var(--red-bg)}
.cell.c-blue{border-top-color:var(--blue);background:var(--blue-bg)}
.cell.c-amber{border-top-color:var(--amber);background:var(--amber-bg)}
body.dark-mode .cell{background:var(--row)}
body.dark-mode .cell.c-green{background:rgba(22,163,74,.12)}
body.dark-mode .cell.c-red{background:rgba(220,38,38,.12)}
body.dark-mode .cell.c-blue{background:rgba(37,99,235,.12)}
body.dark-mode .cell.c-amber{background:rgba(217,119,6,.12)}
.label{font-size:10.5px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}
.value{font-size:17px;font-weight:900;margin-top:4px}
.c-green .value{color:var(--green)}
.c-red .value{color:var(--red)}
.c-blue .value{color:var(--blue)}
.c-amber .value{color:var(--amber)}

/* ===== Bottom row: Account/Payment details (half) + Signatory (half) ===== */
.bottom-row{display:flex;gap:16px;margin-top:22px;align-items:stretch}
.bottom-row>div{flex:1}

.payment-half{
    display:flex;border-radius:12px;overflow:hidden;border:1px solid var(--border);
    box-shadow:0 6px 18px rgba(37,99,235,.08);
}
.payment-half .qr-side{
    background:linear-gradient(135deg,var(--accent),var(--purple));
    padding:16px;display:flex;flex-direction:column;align-items:center;justify-content:center;
    gap:8px;width:130px;flex-shrink:0;
}
.payment-half .qr-side img{width:100px;height:100px;background:#fff;border-radius:10px;padding:6px;box-shadow:0 2px 8px rgba(0,0,0,.15)}
.payment-half .qr-side .scan-label{color:#fff;font-weight:800;font-size:10px;letter-spacing:.04em;text-transform:uppercase;text-align:center}
.payment-half .qr-side .no-qr{width:100px;height:100px;background:rgba(255,255,255,.15);border:2px dashed rgba(255,255,255,.5);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:10px;text-align:center;padding:6px}
.payment-half .info-side{flex:1;padding:14px 16px;background:var(--sheet)}
.payment-half .info-side .ptitle{font-size:12px;font-weight:800;color:var(--purple-dark);margin-bottom:8px;text-transform:uppercase;letter-spacing:.03em}
.payment-half .info-row{display:flex;justify-content:space-between;padding:5px 0;font-size:12.5px;border-bottom:1px dashed var(--border)}
.payment-half .info-row:last-child{border-bottom:0}
.payment-half .info-row .k{color:var(--muted);font-weight:600}
.payment-half .info-row .v{font-weight:800;text-align:right}
.payment-half .info-note{margin-top:9px;font-size:10.5px;color:var(--muted);border-top:1px solid var(--border);padding-top:7px}

.signatory-half{display:flex;align-items:center;justify-content:center;border:1px solid var(--border);border-radius:12px;background:var(--sheet)}
.sign-box{width:80%;text-align:center;border-top:2px solid var(--purple);padding-top:10px;font-weight:800;color:var(--text);font-size:14px}
.sign-box .muted{font-weight:500;font-size:12.5px;display:block;margin-top:2px}

/* ===== Footer ===== */
.footer{border-top:1px solid var(--border);background:var(--row);padding:12px 24px;color:var(--muted);font-size:11.5px;text-align:center}
.footer .print-meta{margin-top:6px;padding-top:6px;border-top:1px dashed var(--border);font-size:10.5px;color:var(--muted)}
.footer .print-meta strong{color:var(--text)}

@media print{
    body{background:#fff;padding:0}
    .toolbar{display:none}
    .sheet{box-shadow:none;border:0;border-radius:0;max-width:none}
    @page{size:A4 portrait;margin:8mm}
    .header,.cell,.letter,.payment-half,.qr-side,.signatory-half{-webkit-print-color-adjust:exact;print-color-adjust:exact}
    .header{padding:18px 20px}
    .content{padding:14px 16px}
    .letter{padding:13px 15px;line-height:1.55}
    .letter .body-text{font-size:11.5px}
    .letter .body-text p{margin:0 0 7px 0}
    .payment-half .qr-side{width:100px;padding:10px}
    .payment-half .qr-side img{width:80px;height:80px}
    .payment-half .info-side{padding:10px 12px}
    .payment-half .info-row{font-size:10px;padding:3px 0}
    .ageing-table{font-size:9px;table-layout:auto}
    .ageing-table th,.ageing-table td{padding:3px 4px;word-break:break-word}
    .ageing-table th{font-size:8.5px;font-weight:900}
    .bill-line{font-size:8.5px}
    .age-pill{font-size:7.5px;padding:0 4px;margin-left:3px}
    .strip{grid-template-columns:repeat(5,1fr);gap:6px;margin-top:10px}
    .cell{padding:7px 8px}
    .cell .label{font-size:7.8px}
    .cell .value{font-size:11.5px}
    .bottom-row{padding:0 0;margin-top:12px}
    .sign-box{font-size:12px}
    .ageing-table tr,.bill-line{page-break-inside:avoid}
    .letter,.payment-half,.signatory-half,.footer,.strip,.bottom-row{page-break-inside:avoid}
    .footer .print-meta{font-size:9px}

    /* Dark Print style: keep the coloured theme when printing instead of flattening to white */
    body.print-dark{background:var(--bg) !important}
    body.print-dark .sheet{box-shadow:none}
    body.print-dark .header{-webkit-print-color-adjust:exact;print-color-adjust:exact}
}
</style>
</head>
<body>

<div class="toolbar">
    <div class="tb-group">
        <span class="tb-label">Theme</span>
        <span class="swatch active" data-main="#2563eb" data-dark="#1e40af" data-purple="#3b82f6" data-purpledark="#1d4ed8" style="background:#2563eb"></span>
        <span class="swatch" data-main="#7c3aed" data-dark="#5b21b6" data-purple="#a78bfa" data-purpledark="#6d28d9" style="background:#7c3aed"></span>
        <span class="swatch" data-main="#16a34a" data-dark="#15803d" data-purple="#4ade80" data-purpledark="#166534" style="background:#16a34a"></span>
        <span class="swatch" data-main="#dc2626" data-dark="#b91c1c" data-purple="#f87171" data-purpledark="#991b1b" style="background:#dc2626"></span>
        <span class="swatch" data-main="#d97706" data-dark="#b45309" data-purple="#fbbf24" data-purpledark="#92400e" style="background:#d97706"></span>
        <span class="swatch" data-main="#0d9488" data-dark="#0f766e" data-purple="#2dd4bf" data-purpledark="#115e59" style="background:#0d9488"></span>
        <span class="swatch" data-main="#db2777" data-dark="#be185d" data-purple="#f472b6" data-purpledark="#9d174d" style="background:#db2777"></span>
        <span class="swatch" data-main="#334155" data-dark="#1e293b" data-purple="#64748b" data-purpledark="#1e293b" style="background:#334155"></span>
        <span class="swatch custom" id="customSwatch" title="Custom color"></span>
        <input type="color" id="customColorInput" style="display:none">
    </div>
    <div class="tb-divider"></div>
    <div class="tb-group">
        <span class="tb-label">Mode</span>
        <button type="button" class="mode-btn" id="modeBtn" onclick="toggleMode()">&#127769; Dark</button>
    </div>
    <div class="tb-divider"></div>
    <div class="tb-group">
        <span class="tb-label">Print Style</span>
        <select class="print-select" id="printStyle" onchange="setPrintStyle(this.value)">
            <option value="light">&#9728; Light Print</option>
            <option value="dark">&#127761; Dark Print</option>
        </select>
    </div>
    <button class="print" onclick="window.print()">&#128424; Print / PDF</button>
</div>

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
            <div class="doc-type">Request for Clearance of Outstanding Ageing Balance</div>
            <div class="doc-num">{{ ucfirst($kind) }} | As on {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</div>
            <div class="doc-num">Party: {{ $party?->display_name ?: 'Cash / Walk-in' }}</div>
        </div>
    </div>

    <div class="content">

        {{-- ===== Letter — shortened to 2 paragraphs, no fixed day-limit, paid-bill references ===== --}}
        @php
            $paidBills = $bills->filter(fn($b) => ($b['paid'] ?? 0) > 0);
        @endphp
        <div class="letter">
            <span class="subject-badge">Subject: Request for Clearance of Outstanding Ageing Balance</span>
            <div class="salutation">Dear {{ $party?->display_name ?: 'Customer' }},</div>
            <div class="body-text">
                <p>
                    As per our latest reconciliation carried out as on <strong>{{ \Carbon\Carbon::parse($to)->format('d M Y') }}</strong>,
                    an amount of <span class="amt">Rs {{ number_format($totals['due'],2) }}</span> remains outstanding
                    against <strong>{{ $bills->count() }}</strong> invoice(s) raised in the ordinary course of our business
                    dealings; the complete invoice-wise break-up along with the applicable ageing buckets is enclosed below.
                    @if($paidBills->count())
                        We further acknowledge receipt of payment
                        <span class="paid-note">
                            against invoice(s)
                            @foreach($paidBills as $pb)
                                {{ $pb['record']->invoice_no }} (Rs {{ number_format($pb['paid'],2) }}){{ !$loop->last ? ',' : '' }}
                            @endforeach
                        </span>, which has been duly updated in our records.
                    @endif
                </p>
                <p>
                    We kindly request you to arrange for clearance of the above outstanding amount at the earliest.
                    Complete payment details along with a scannable UPI QR code are provided at the bottom of this
                    report for your convenience; should the amount already stand cleared, please do share the
                    transaction reference so our records can be updated accordingly.
                </p>
                <div class="closing">
                    Warm regards,
                    <strong>{{ $company?->name ?? 'the Company' }}</strong>
                </div>
            </div>
        </div>

        {{-- ===================================================================== --}}
        {{-- AGEING SLAB TABLE — kept exactly as-is, no structural or visual change --}}
        {{-- ===================================================================== --}}
        <table class="ageing-table">
            <thead>
                <tr>
                    <th>Party</th>
                    <th class="num">Receivable</th>
                    <th class="num">Payable</th>
                    @foreach($slabs as $label)<th>{{ $label }}</th>@endforeach
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
                                        <span class="age-pill">{{ $bill['age'] }} {{ $bill['age'] == 1 ? 'day' : 'days' }}</span>
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

        {{-- ===== KPI summary strip — placed just below the slab table ===== --}}
        <div class="strip">
            <div class="cell c-neutral"><div class="label">Open Bills</div><div class="value">{{ $bills->count() }}</div></div>
            <div class="cell c-green"><div class="label">Receivable</div><div class="value">Rs {{ number_format($totals['receivable'],2) }}</div></div>
            <div class="cell c-red"><div class="label">Payable</div><div class="value">Rs {{ number_format($totals['payable'],2) }}</div></div>
            <div class="cell c-blue"><div class="label">Paid</div><div class="value">Rs {{ number_format($totals['paid'],2) }}</div></div>
            <div class="cell c-amber"><div class="label">Total Due</div><div class="value">Rs {{ number_format($totals['due'],2) }}</div></div>
        </div>

        {{-- ===== Bottom row: Payment / Account details (half) + Signatory (half) ===== --}}
        <div class="bottom-row">
            <div class="payment-half">
                <div class="qr-side">
                    @if($bankAccount?->upi_qr_code)
                        <img src="{{ asset('storage/'.$bankAccount->upi_qr_code) }}" alt="UPI QR Code">
                    @else
                        <div class="no-qr">QR Code<br>Not Available</div>
                    @endif
                    <div class="scan-label">Scan &amp; Pay</div>
                </div>
                <div class="info-side">
                    <div class="ptitle">Payment / Account Details</div>
                    @if($bankAccount)
                        <div class="info-row"><span class="k">Bank Name</span><span class="v">{{ $bankAccount->bank_name ?: '-' }}</span></div>
                        <div class="info-row"><span class="k">Account No.</span><span class="v">{{ $bankAccount->account_number ?: '-' }}</span></div>
                        <div class="info-row"><span class="k">IFSC Code</span><span class="v">{{ $bankAccount->ifsc_code ?: '-' }}</span></div>
                        <div class="info-row"><span class="k">UPI ID</span><span class="v">{{ $bankAccount->upi_id ?: '-' }}</span></div>
                    @else
                        <div class="muted">No bank account linked for this company.</div>
                    @endif
                    <div class="info-note">
                        Payments found on record: <strong>{{ $totals['payment_count'] }}</strong>.
                    </div>
                </div>
            </div>

            <div class="signatory-half">
                <div class="sign-box">
                    Authorised Signature
                    <span class="muted">{{ $company?->name }}</span>
                </div>
            </div>
        </div>

    </div>

    <div class="footer">
        Filter applied: {{ ucfirst($kind) }}. Payable shows payable only, receivable shows receivable only, both shows both.
        <div class="print-meta">Printed by <strong>{{ auth()->user()->name ?? 'System' }}</strong> on <span id="printTimestamp">-</span></div>
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

<script>
function applyThemeColors(main, dark, purple, purpledark){
    document.documentElement.style.setProperty('--accent', main);
    document.documentElement.style.setProperty('--accent-dark', dark);
    document.documentElement.style.setProperty('--purple', purple);
    document.documentElement.style.setProperty('--purple-dark', purpledark);
}

document.querySelectorAll('.swatch:not(.custom)').forEach(function(sw){
    sw.addEventListener('click', function(){
        document.querySelectorAll('.swatch').forEach(s => s.classList.remove('active'));
        sw.classList.add('active');
        applyThemeColors(sw.dataset.main, sw.dataset.dark, sw.dataset.purple, sw.dataset.purpledark);
    });
});

document.getElementById('customSwatch').addEventListener('click', function(){
    document.getElementById('customColorInput').click();
});
document.getElementById('customColorInput').addEventListener('input', function(e){
    document.querySelectorAll('.swatch').forEach(s => s.classList.remove('active'));
    document.getElementById('customSwatch').classList.add('active');
    var main = e.target.value;
    applyThemeColors(main, main, main, main);
});

function toggleMode(){
    var body = document.body;
    var btn = document.getElementById('modeBtn');
    body.classList.toggle('dark-mode');
    if(body.classList.contains('dark-mode')){
        btn.classList.add('on');
        btn.innerHTML = '&#9728; Light';
    } else {
        btn.classList.remove('on');
        btn.innerHTML = '&#127769; Dark';
    }
}

function setPrintStyle(value){
    document.body.classList.toggle('print-dark', value === 'dark');
}

function updatePrintTimestamp(){
    var now = new Date();
    var formatted = now.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' }) +
        ' ' + now.toLocaleTimeString('en-IN', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
    var el = document.getElementById('printTimestamp');
    if(el) el.textContent = formatted;
}
window.addEventListener('beforeprint', updatePrintTimestamp);
document.querySelector('.print').addEventListener('click', updatePrintTimestamp);
</script>
</body>
</html>
