<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profit Invoice - {{ $invoice->invoice_no }}</title>
    <style>
        :root{--navy:#0f172a;--teal:#0f766e;--mint:#ecfdf5;--slate:#64748b;--line:#dbe4ee;--profit:#047857;--loss:#b91c1c}*{box-sizing:border-box;-webkit-print-color-adjust:exact;print-color-adjust:exact}@page{size:A4 landscape;margin:10mm}body{font-family:Arial,sans-serif;color:var(--navy);margin:0;background:#eef2f7;font-size:12px;line-height:1.45}.page{max-width:1120px;margin:22px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 20px 55px rgba(15,23,42,.12)}.head{display:flex;justify-content:space-between;gap:25px;padding:25px 30px;background:linear-gradient(135deg,var(--navy),#143b4a 60%,var(--teal));color:#fff}.brand h1,.head h1{margin:0;font-size:25px}.head>div:last-child{text-align:right}.doc-label{font-size:10px;letter-spacing:1.8px;text-transform:uppercase;color:#99f6e4;font-weight:800}.muted{color:#64748b}.head .muted{color:#d7e3ea}.content{padding:22px 30px 28px}.grid{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:18px}.metric{border:1px solid var(--line);border-radius:10px;padding:12px;background:#f8fafc}.metric.highlight{background:var(--mint);border-color:#a7f3d0}.metric span{display:block;font-size:9px;letter-spacing:.7px;text-transform:uppercase;color:var(--slate);font-weight:bold}.metric b{font-size:17px;white-space:nowrap}.profit{color:var(--profit)!important}.loss{color:var(--loss)!important}table{width:100%;border-collapse:separate;border-spacing:0;border:1px solid var(--line);border-radius:10px;overflow:hidden;margin-top:8px}thead{display:table-header-group}tr{page-break-inside:avoid}th{background:var(--navy);color:#fff;text-align:left;font-size:9px;letter-spacing:.5px;text-transform:uppercase}th,td{border-right:1px solid var(--line);border-bottom:1px solid var(--line);padding:9px 8px;vertical-align:top}th:last-child,td:last-child{border-right:0}tbody tr:last-child td{border-bottom:0}tbody tr:nth-child(even){background:#f8fafc}.section{margin:18px 30px 0}.section:last-child{padding-bottom:26px}.section h2{font-size:11px;letter-spacing:.8px;text-transform:uppercase;color:var(--teal);margin:0 0 7px}.small{font-size:9px;color:var(--slate)}.formula{display:flex;justify-content:space-between;border-top:1px solid var(--line);padding:12px 30px 20px;color:var(--slate);font-size:9px}.formula b{color:var(--teal)}.print{position:fixed;right:22px;top:18px;z-index:5;border:0;border-radius:9px;padding:10px 16px;background:var(--teal);color:#fff;font-weight:700;cursor:pointer;box-shadow:0 8px 24px rgba(15,118,110,.25)}@media print{body{background:#fff}.page{margin:0;max-width:none;border-radius:0;box-shadow:none}.print{display:none}.head{padding:18px 20px}.content{padding:16px 20px}.section{margin:15px 20px 0}.section:last-child{padding-bottom:10px}.formula{padding:10px 20px}}
    </style>
</head>
<body>
<button class="print" onclick="window.print()">Print / Save PDF</button>
<main class="page">
    <div class="head">
        <div class="brand"><h1>{{ $company?->name ?? 'Company' }}</h1><div class="muted">{{ $company?->address }}</div>@if($company?->gst_number)<div class="muted">GSTIN: {{ $company->gst_number }}</div>@endif</div>
        <div><div class="doc-label">Profit Invoice</div><h1>#{{ $detail['invoice'] }}</h1><div class="muted">{{ $detail['date'] }} | {{ $detail['sale_type'] }}</div></div>
    </div>
    <div class="content"><div class="grid">
        <div class="metric"><span>Sale Total</span><b>Rs {{ number_format($detail['amounts']['total'],2) }}</b></div>
        <div class="metric"><span>Purchase Cost</span><b>Rs {{ number_format($detail['amounts']['cost'],2) }}</b></div>
        <div class="metric highlight"><span>Profit / Loss</span><b class="{{ $detail['amounts']['profit'] >= 0 ? 'profit' : 'loss' }}">Rs {{ number_format($detail['amounts']['profit'],2) }}</b></div>
        <div class="metric highlight"><span>Profit % on Cost</span><b class="{{ $detail['amounts']['profit_percent'] >= 0 ? 'profit' : 'loss' }}">{{ number_format($detail['amounts']['profit_percent'],2) }}%</b></div>
        <div class="metric"><span>Tax</span><b>Rs {{ number_format($detail['amounts']['tax'],2) }}</b></div>
    </div></div>
    <div class="section">
        <h2>Customer and Invoice Details</h2>
        <table><tr><td><b>{{ $detail['party']['name'] }}</b><br>Legal: {{ $detail['party']['legal_name'] }}<br>Phone: {{ $detail['party']['phone'] }}<br>Email: {{ $detail['party']['email'] }}</td><td>GSTIN: {{ $detail['party']['gstin'] }}<br>City: {{ $detail['party']['city'] }}<br>Billing: {{ $detail['billing_address'] }}<br>Shipping: {{ $detail['shipping_address'] }}</td></tr></table>
    </div>
    <div class="section">
        <h2>Item Profitability</h2>
        <table>
            <thead><tr><th>Item</th><th>Qty</th><th>Sale</th><th>Cost</th><th>Profit</th><th>Profit %</th><th>BOM / Units</th></tr></thead>
            <tbody>
            @foreach($detail['items'] as $item)
                <tr>
                    <td><b>{{ $item['name'] }}</b><br><span class="small">{{ $item['description'] }}</span></td>
                    <td>{{ number_format($item['qty'],2) }} {{ $item['unit'] }}</td>
                    <td>Rs {{ number_format($item['amount'],2) }}</td>
                    <td>Rs {{ number_format($item['cost'],2) }}</td>
                    <td class="{{ $item['profit'] >= 0 ? 'profit' : 'loss' }}">Rs {{ number_format($item['profit'],2) }}</td>
                    <td class="{{ $item['profit_percent'] >= 0 ? 'profit' : 'loss' }}"><b>{{ number_format($item['profit_percent'],2) }}%</b></td>
                    <td class="small">
                        <b>BOM</b><br>
                        @forelse($item['bom'] as $bom){{ ucfirst($bom['line_type'] ?? 'raw_material') }} - {{ $bom['name'] }}: {{ $bom['qty_per_unit'] }} {{ $bom['unit'] }} @ Rs {{ number_format($bom['unit_price'] ?? $bom['purchase_price'],2) }} = Rs {{ number_format($bom['amount'] ?? 0,2) }}<br>@empty -<br>@endforelse
                        <b>CRM Units</b><br>
                        @forelse($item['units'] as $unit){{ $unit['serial_no'] }} / {{ $unit['vts_sim'] }} / {{ $unit['batch_no'] }} / {{ $unit['buyer_code'] }}<br>@empty - @endforelse
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="formula"><b>Profit % = (Profit / Cost) x 100</b><span>Generated for internal profitability analysis.</span></div>
</main>
</body>
</html>
