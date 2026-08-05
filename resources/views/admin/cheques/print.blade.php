<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cheque Settlement - {{ $payload['cheque']['cheque_no'] }}</title>
    <style>
        :root{--navy:#0f172a;--teal:#0f766e;--line:#dbe4ee;--soft:#f8fafc;--slate:#64748b;--danger:#b91c1c;--ok:#047857}*{box-sizing:border-box;-webkit-print-color-adjust:exact;print-color-adjust:exact}@page{size:A4;margin:10mm}body{font-family:Arial,sans-serif;color:var(--navy);margin:0;background:#eef2f7;font-size:12px;line-height:1.45}.page{max-width:960px;margin:22px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 20px 55px rgba(15,23,42,.12)}.head{display:flex;justify-content:space-between;gap:20px;padding:24px 28px;background:linear-gradient(135deg,var(--navy),#143b4a 60%,var(--teal));color:#fff}.head h1{margin:0;font-size:24px}.muted{color:#64748b}.head .muted{color:#d7e3ea}.doc-label{font-size:10px;letter-spacing:1.6px;text-transform:uppercase;color:#99f6e4;font-weight:800}.content{padding:22px 28px}.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:16px}.metric{border:1px solid var(--line);border-radius:10px;padding:11px;background:var(--soft)}.metric span{display:block;font-size:9px;letter-spacing:.7px;text-transform:uppercase;color:var(--slate);font-weight:bold}.metric b{font-size:16px}.section{margin-top:18px}.section h2{font-size:11px;letter-spacing:.8px;text-transform:uppercase;color:var(--teal);margin:0 0 7px}table{width:100%;border-collapse:separate;border-spacing:0;border:1px solid var(--line);border-radius:10px;overflow:hidden}th{background:var(--navy);color:#fff;text-align:left;font-size:9px;letter-spacing:.5px;text-transform:uppercase}th,td{border-right:1px solid var(--line);border-bottom:1px solid var(--line);padding:8px;vertical-align:top}th:last-child,td:last-child{border-right:0}tr:last-child td{border-bottom:0}tbody tr:nth-child(even){background:var(--soft)}.two{display:grid;grid-template-columns:1fr 1fr;gap:12px}.box{border:1px solid var(--line);border-radius:10px;padding:12px;background:#fff}.small{font-size:10px;color:var(--slate)}.disclaimer{border:1px dashed #f59e0b;background:#fffbeb;color:#92400e;border-radius:10px;padding:12px;margin-top:14px;font-size:11px}.print{position:fixed;right:22px;top:18px;z-index:5;border:0;border-radius:9px;padding:10px 16px;background:var(--teal);color:#fff;font-weight:700;cursor:pointer;box-shadow:0 8px 24px rgba(15,118,110,.25)}@media print{body{background:#fff}.page{margin:0;max-width:none;border-radius:0;box-shadow:none}.print{display:none}.head{padding:18px 20px}.content{padding:16px 20px}}
    </style>
</head>
<body>
<button class="print" onclick="window.print()">Print / Save PDF</button>
<main class="page">
    <div class="head">
        <div>
            <h1>{{ $company?->name ?? 'Company' }}</h1>
            <div class="muted">{{ $company?->address }}</div>
            @if($company?->gst_number)<div class="muted">GSTIN: {{ $company->gst_number }}</div>@endif
        </div>
        <div style="text-align:right">
            <div class="doc-label">Cheque Settlement Bill</div>
            <h1>#{{ $payload['cheque']['cheque_no'] }}</h1>
            <div class="muted">Settlement {{ $payload['cheque']['settlement_date'] ?: '-' }}</div>
        </div>
    </div>
    <div class="content">
        <div class="grid">
            <div class="metric"><span>Cheque Amount</span><b>Rs {{ number_format($payload['cheque']['amount'],2) }}</b></div>
            <div class="metric"><span>Settled</span><b>Rs {{ number_format($payload['totals']['settled'],2) }}</b></div>
            <div class="metric"><span>Due</span><b>Rs {{ number_format($payload['totals']['due'],2) }}</b></div>
            <div class="metric"><span>Clearance</span><b>{{ $payload['cheque']['clearance_date'] ?: '-' }}</b></div>
        </div>

        <div class="section">
            <h2>Cheque Image</h2>
            @include('admin.cheques.partials.cheque-preview', ['leaf' => $leaf, 'bank' => $leaf->bankAccount])
            <div class="disclaimer"><b>Disclaimer:</b> This cheque image is generated from system records for settlement and audit reference only. Actual bank clearance depends on bank validation, available balance, cheque validity, signature verification and applicable banking rules.</div>
        </div>

        <div class="section two">
            <div class="box">
                <h2>Party Details</h2>
                <b>{{ $payload['party']['name'] ?: '-' }}</b><br>
                Legal: {{ $payload['party']['legal_name'] ?: '-' }}<br>
                Phone: {{ $payload['party']['phone'] ?: '-' }}<br>
                Email: {{ $payload['party']['email'] ?: '-' }}<br>
                GSTIN: {{ $payload['party']['gstin'] ?: '-' }} | PAN: {{ $payload['party']['pan'] ?: '-' }}<br>
                Address: {{ $payload['party']['billing_address'] ?: '-' }}
            </div>
            <div class="box">
                <h2>Bank / Cheque Details</h2>
                <b>{{ $payload['bank']['name'] ?: '-' }}</b><br>
                Account: {{ $payload['bank']['account_number'] ?: '-' }}<br>
                IFSC: {{ $payload['bank']['ifsc_code'] ?: '-' }}<br>
                Branch: {{ $payload['bank']['branch_name'] ?: '-' }}<br>
                Age: {{ $payload['cheque']['age'] }} days | Validity: {{ $payload['cheque']['validity_months'] }} month<br>
                Clearance Day: {{ $payload['cheque']['clearance_day'] ?: '-' }} | Days Left: {{ $payload['cheque']['days_left'] ?? '-' }}
            </div>
        </div>

        <div class="section">
            <h2>Invoice / Bill Settlement</h2>
            @forelse($payload['bills'] as $bill)
                <table style="margin-bottom:12px">
                    <thead><tr><th colspan="6">{{ $bill['bill_type'] }} Bill {{ $bill['bill_no'] }} | Date {{ $bill['bill_date'] ?: '-' }} | Settled Rs {{ number_format($bill['settled_amount'],2) }}</th></tr><tr><th>Item</th><th>Qty</th><th>Rate</th><th>Discount</th><th>Tax</th><th>Total</th></tr></thead>
                    <tbody>
                    @forelse($bill['items'] as $item)
                        <tr>
                            <td><b>{{ $item['name'] }}</b><br><span class="small">{{ $item['description'] ?: '-' }}</span></td>
                            <td>{{ number_format($item['quantity'],2) }} {{ $item['unit'] }}</td>
                            <td>Rs {{ number_format($item['rate'],2) }}</td>
                            <td>Rs {{ number_format($item['discount_amount'],2) }}<br><span class="small">{{ $item['discount_type'] ?: '-' }} {{ number_format($item['discount_value'],2) }}</span></td>
                            <td>{{ number_format($item['tax_percent'],2) }}%<br><span class="small">Rs {{ number_format($item['tax_amount'],2) }}</span></td>
                            <td><b>Rs {{ number_format($item['line_total'],2) }}</b></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="small">No item detail found for this bill.</td></tr>
                    @endforelse
                    <tr><td colspan="3"></td><td>Discount Rs {{ number_format($bill['invoice']['discount_amount'] ?? 0,2) }}</td><td>Tax Rs {{ number_format($bill['invoice']['tax_amount'] ?? 0,2) }}</td><td><b>Bill Rs {{ number_format($bill['invoice']['grand_total'] ?? $bill['bill_total'],2) }}</b></td></tr>
                    </tbody>
                </table>
            @empty
                <div class="box small">No bill settlement found for this cheque.</div>
            @endforelse
        </div>
    </div>
</main>
</body>
</html>
