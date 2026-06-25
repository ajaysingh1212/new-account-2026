<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Detail - {{ $invoice->invoice_no }}</title>
    <style>
        body{font-family:Arial,sans-serif;color:#111827;margin:0;background:#f3f4f6}.page{max-width:1120px;margin:24px auto;background:#fff;padding:28px;border-radius:10px}.head{display:flex;justify-content:space-between;border-bottom:3px solid #0f766e;padding-bottom:18px}.brand h1{margin:0;font-size:24px}.muted{color:#64748b}.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:18px 0}.metric{border:1px solid #e5e7eb;border-radius:8px;padding:12px}.metric span{display:block;font-size:11px;text-transform:uppercase;color:#64748b;font-weight:bold}.metric b{font-size:20px}.profit{color:#047857}.loss{color:#b91c1c}table{width:100%;border-collapse:collapse;margin-top:12px}th{background:#f8fafc;text-align:left;color:#475569;font-size:12px;text-transform:uppercase}th,td{border:1px solid #e5e7eb;padding:9px;vertical-align:top}.section{margin-top:20px}.section h2{font-size:16px;margin:0 0 8px}.small{font-size:12px}.print{position:fixed;right:22px;top:18px}@media print{body{background:#fff}.page{margin:0;max-width:none;border-radius:0}.print{display:none}}
    </style>
</head>
<body>
<button class="print" onclick="window.print()">Print / Save PDF</button>
<main class="page">
    <div class="head">
        <div class="brand"><h1>{{ $company?->name ?? 'Company' }}</h1><div class="muted">{{ $company?->address }}</div></div>
        <div><h1>Invoice {{ $detail['invoice'] }}</h1><div class="muted">{{ $detail['date'] }} | {{ $detail['sale_type'] }}</div></div>
    </div>
    <div class="grid">
        <div class="metric"><span>Sale Total</span><b>Rs {{ number_format($detail['amounts']['total'],2) }}</b></div>
        <div class="metric"><span>Purchase Cost</span><b>Rs {{ number_format($detail['amounts']['cost'],2) }}</b></div>
        <div class="metric"><span>Profit / Loss</span><b class="{{ $detail['amounts']['profit'] >= 0 ? 'profit' : 'loss' }}">Rs {{ number_format($detail['amounts']['profit'],2) }}</b></div>
        <div class="metric"><span>Tax</span><b>Rs {{ number_format($detail['amounts']['tax'],2) }}</b></div>
    </div>
    <div class="section">
        <h2>Party & CRM Details</h2>
        <table><tr><td><b>{{ $detail['party']['name'] }}</b><br>Legal: {{ $detail['party']['legal_name'] }}<br>Phone: {{ $detail['party']['phone'] }}<br>Email: {{ $detail['party']['email'] }}</td><td>GSTIN: {{ $detail['party']['gstin'] }}<br>City: {{ $detail['party']['city'] }}<br>Billing: {{ $detail['billing_address'] }}<br>Shipping: {{ $detail['shipping_address'] }}</td></tr></table>
    </div>
    <div class="section">
        <h2>Items, Pricing, BOM, CRM Units</h2>
        <table>
            <thead><tr><th>Item</th><th>Qty</th><th>Sale</th><th>Cost</th><th>Profit</th><th>BOM / Units</th></tr></thead>
            <tbody>
            @foreach($detail['items'] as $item)
                <tr>
                    <td><b>{{ $item['name'] }}</b><br><span class="small">{{ $item['description'] }}</span></td>
                    <td>{{ number_format($item['qty'],2) }} {{ $item['unit'] }}</td>
                    <td>Rs {{ number_format($item['amount'],2) }}</td>
                    <td>Rs {{ number_format($item['cost'],2) }}</td>
                    <td class="{{ $item['profit'] >= 0 ? 'profit' : 'loss' }}">Rs {{ number_format($item['profit'],2) }}</td>
                    <td class="small">
                        <b>BOM</b><br>
                        @forelse($item['bom'] as $bom){{ $bom['name'] }}: {{ $bom['qty_per_unit'] }} {{ $bom['unit'] }} @ Rs {{ number_format($bom['purchase_price'],2) }}<br>@empty -<br>@endforelse
                        <b>CRM Units</b><br>
                        @forelse($item['units'] as $unit){{ $unit['serial_no'] }} / {{ $unit['vts_sim'] }} / {{ $unit['batch_no'] }} / {{ $unit['buyer_code'] }}<br>@empty - @endforelse
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</main>
</body>
</html>
