<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Ageing Report - {{ $bill->invoice_no }}</title>
    <style>
        @page {
            size: A4;
            margin: 16mm 14mm 16mm 14mm;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            color: #111827;
            font-size: 11.5px;
            line-height: 1.55;
            margin: 0;
            padding: 0;
        }

        /* ---------- Letterhead ---------- */
        .letterhead {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            border-bottom: 3px solid #111827;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .letterhead .logo-block { display: flex; align-items: center; gap: 10px; }
        .logo { height: 50px; max-width: 120px; object-fit: contain; }
        .company-name { font-size: 16px; font-weight: 800; margin: 0 0 2px 0; }
        .company-sub { font-size: 10.5px; color: #374151; }
        .letterhead .right { text-align: right; }
        .doc-title {
            font-size: 17px;
            font-weight: 800;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .status-badge {
            display: inline-block;
            margin-top: 6px;
            padding: 3px 10px;
            border-radius: 999px;
            background: #fee2e2;
            color: #991b1b;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        /* ---------- Ref / Date row ---------- */
        .ref-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-bottom: 14px;
            color: #374151;
        }

        /* ---------- Address / To block ---------- */
        .to-block { margin-bottom: 14px; }
        .to-block .label { font-size: 10px; text-transform: uppercase; color: #6b7280; letter-spacing: 0.4px; margin-bottom: 3px; }
        .to-block .party-name { font-weight: 700; font-size: 13px; }

        .subject {
            font-weight: 700;
            margin: 14px 0;
            padding: 8px 10px;
            background: #f3f4f6;
            border-left: 3px solid #111827;
        }

        /* ---------- Letter body ---------- */
        .letter-body p { margin: 0 0 10px 0; text-align: justify; }
        .highlight { font-weight: 700; color: #991b1b; }

        /* ---------- Details table ---------- */
        .section-title {
            font-size: 12px;
            font-weight: 800;
            margin: 18px 0 6px 0;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 4px;
        }
        .info-grid {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 10px;
        }
        .info-box {
            width: 48%;
            border: 1px solid #d1d5db;
            background: #f9fafb;
            padding: 8px 10px;
            font-size: 11px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .table th, .table td {
            border: 1px solid #d1d5db;
            padding: 5px 6px;
            text-align: left;
            font-size: 10.8px;
        }
        .table th { background: #f3f4f6; font-weight: 700; }
        .table tr { page-break-inside: avoid; }

        .totals {
            margin-top: 8px;
            margin-left: auto;
            width: 260px;
            border: 1px solid #111827;
            background: #fff;
        }
        .totals div {
            display: flex;
            justify-content: space-between;
            padding: 6px 10px;
            font-size: 11.5px;
            border-bottom: 1px solid #e5e7eb;
        }
        .totals div:last-child { border-bottom: none; }
        .totals .balance-row {
            background: #111827;
            color: #fff;
            font-weight: 800;
        }

        .bank-box {
            border: 1px solid #d1d5db;
            background: #f9fafb;
            padding: 8px 10px;
            font-size: 11px;
            margin-top: 12px;
        }

        /* ---------- Closing / Signature ---------- */
        .closing { margin-top: 18px; page-break-inside: avoid; }
        .signature-block {
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .signature-line {
            width: 220px;
            border-top: 1px solid #111827;
            margin-top: 34px;
            padding-top: 4px;
            font-size: 11px;
            font-weight: 700;
        }
        .footer-note {
            margin-top: 20px;
            font-size: 9.5px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 6px;
            text-align: center;
        }
    </style>
</head>
<body>

    {{-- ===== Letterhead ===== --}}
    <div class="letterhead">
        <div class="logo-block">
            @if($company?->logo)
                <img class="logo" src="{{ asset('storage/'.$company->logo) }}" alt="Logo">
            @endif
            <div>
                <p class="company-name">{{ $company?->name ?? 'Company' }}</p>
                <div class="company-sub">{{ $company?->address ?? '' }}</div>
                <div class="company-sub">
                    {{ $company?->phone ?? '' }}{{ $company?->email ? ' | '.$company->email : '' }}
                </div>
                <div class="company-sub">
                    GST: {{ $company?->gst_number ?: '-' }} | PAN: {{ $company?->pan_number ?: '-' }}
                </div>
            </div>
        </div>
        <div class="right">
            <p class="doc-title">PAYMENT REMINDER<br>LETTER</p>
            <span class="status-badge">{{ $status }}</span>
        </div>
    </div>

    {{-- ===== Ref / Date ===== --}}
    <div class="ref-row">
        <div><strong>Ref No.:</strong> {{ $bill->invoice_no }}/AGN</div>
        <div><strong>Date:</strong> {{ now()->format('d M Y') }}</div>
    </div>

    {{-- ===== To ===== --}}
    <div class="to-block">
        <div class="label">To,</div>
        <div class="party-name">{{ $party?->display_name ?? 'Concerned Party' }}</div>
        <div>{{ $party?->billing_address ?: $party?->shipping_address }}</div>
        <div>Phone: {{ $party?->phone ?: '-' }} | Email: {{ $party?->email ?: '-' }}</div>
        @if($party?->gstin)<div>GSTIN: {{ $party->gstin }}</div>@endif
    </div>

    <div class="subject">
        Subject: Reminder for Clearance of Outstanding Payment against Invoice No. {{ $bill->invoice_no }}
    </div>

    {{-- ===== Letter Body ===== --}}
    <div class="letter-body">
        <p>Dear {{ $party?->display_name ?? 'Sir/Madam' }},</p>

        <p>
            We hope this letter finds you well. As per our records, the invoice referenced above, raised on
            <strong>{{ $bill->billing_date?->format('d M Y') }}</strong>, remains
            <span class="highlight">{{ strtolower($status) }}</span> as on the date of this communication.
            A complete break-up of the invoice along with the payment history, if any, is provided below for
            your ready reference.
        </p>

        <p>
            We have always valued our business association with you and have extended reasonable time and
            flexibility wherever required. However, we now request you to treat this matter with priority and
            arrange to clear the outstanding balance of
            <span class="highlight">&#8377; {{ number_format($totals['balance'], 2) }}</span>
            at the earliest, and in any case within <strong>7 (seven) working days</strong> from the date of this
            letter, in order to avoid any inconvenience or disruption to our ongoing business dealings.
        </p>

        <p>
            Should the payment have already been made, we request you to kindly share the transaction
            details or proof of payment at the earliest so that our records may be updated accordingly. In
            case of any discrepancy in the amount stated above, please bring it to our notice immediately so
            that the matter can be resolved without delay.
        </p>

        <p>
            We trust you will give this matter your immediate attention and look forward to receiving the
            outstanding payment at the earliest.
        </p>
    </div>

    {{-- ===== Invoice Details ===== --}}
    <div class="section-title">Invoice Details</div>
    <div class="info-grid">
        <div class="info-box">
            <strong>Invoice No.:</strong> {{ $bill->invoice_no }}<br>
            <strong>Invoice Date:</strong> {{ $bill->billing_date?->format('d M Y') }}<br>
            <strong>Kind:</strong> {{ ucfirst($kind) }}
        </div>
        <div class="info-box">
            <strong>Tax Amount:</strong> &#8377; {{ number_format((float) $bill->tax_amount, 2) }}<br>
            <strong>Discount:</strong> &#8377; {{ number_format((float) $bill->discount_amount, 2) }}<br>
            <strong>Status:</strong> {{ $status }}
        </div>
    </div>

    <table class="table">
        <thead>
            <tr><th>Item</th><th>Qty</th><th>Rate (&#8377;)</th><th>Amount (&#8377;)</th></tr>
        </thead>
        <tbody>
            @foreach($bill->items ?? [] as $item)
                <tr>
                    <td>{{ $item->item?->name ?? '-' }}<br><small>{{ $item->description }}</small></td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format((float) ($item->unit_price ?? 0), 2) }}</td>
                    <td>{{ number_format((float) ($item->line_total ?? 0), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div><span>Grand Total</span><span>&#8377; {{ number_format($totals['grand_total'], 2) }}</span></div>
        <div><span>Return Adjusted</span><span>&#8377; {{ number_format($totals['returned'] ?? 0, 2) }}</span></div>
        <div><span>Effective Total</span><span>&#8377; {{ number_format($totals['effective_total'] ?? $totals['grand_total'], 2) }}</span></div>
        <div><span>Paid</span><span>&#8377; {{ number_format($totals['paid'], 2) }}</span></div>
        <div class="balance-row"><span>Balance Due</span><span>&#8377; {{ number_format($totals['balance'], 2) }}</span></div>
    </div>

    @if($payments->count())
        <div class="section-title">Payment History</div>
        <table class="table">
            <thead>
                <tr><th>Date</th><th>Amount (&#8377;)</th><th>Mode</th><th>Bank</th><th>Reference</th></tr>
            </thead>
            <tbody>
                @foreach($payments as $payment)
                    <tr>
                        <td>{{ $payment->payment?->payment_date?->format('d M Y') ?: $payment->created_at?->format('d M Y') }}</td>
                        <td>{{ number_format((float) $payment->amount, 2) }}</td>
                        <td>{{ $payment->payment?->payment_mode ?: '-' }}</td>
                        <td>{{ $payment->payment?->bankAccount?->bank_name ?: '-' }}</td>
                        <td>{{ $payment->payment?->reference_no ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($party?->bank_name || $party?->account_number || $party?->upi_id)
        <div class="bank-box">
            <strong>Party Banking Details:</strong><br>
            Bank: {{ $party?->bank_name ?: '-' }} | A/C: {{ $party?->account_number ?: '-' }} |
            IFSC: {{ $party?->ifsc_code ?: '-' }} | UPI: {{ $party?->upi_id ?: '-' }}
        </div>
    @endif

    {{-- ===== Closing & Signature ===== --}}
    <div class="closing">
        <p>Thanking you,</p>
        <p>For <strong>{{ $company?->name ?? 'the Company' }}</strong></p>
    </div>

    <div class="signature-block">
        <div class="signature-line">Authorized Signatory</div>
    </div>

    <div class="footer-note">
        This is a computer-generated letter and does not require a physical signature unless otherwise specified.
    </div>

</body>
</html>
