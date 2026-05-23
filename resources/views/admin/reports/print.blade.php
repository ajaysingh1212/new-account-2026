<!doctype html>
<html>
<head>
    <title>{{ $title }}</title>
    <style>
        body { font-family: Arial, sans-serif; color:#111827; }
        .wrap { max-width:1100px; margin:20px auto; }
        h1 { margin-bottom:4px; }
        table { width:100%; border-collapse:collapse; margin-top:18px; }
        th,td { border:1px solid #d1d5db; padding:9px; text-align:left; }
        th { background:#f3f4f6; }
        .total { font-weight:700; background:#f8fafc; }
        @media print { button { display:none; } }
    </style>
</head>
<body>
<div class="wrap">
    <button onclick="window.print()">Print / Save PDF</button>
    <h1>{{ $title }}</h1>
    <div>Period: {{ $filters['from'] }} to {{ $filters['to'] }}</div>
    <table>
        <thead><tr><th>Date</th><th>Invoice</th><th>Party</th><th>GSTIN</th><th>Taxable</th><th>GST</th><th>Total</th></tr></thead>
        <tbody>@foreach($rows as $row)<tr><td>{{ $row['date'] }}</td><td>{{ $row['invoice'] }}</td><td>{{ $row['party'] }}</td><td>{{ $row['gstin'] }}</td><td>Rs {{ number_format($row['taxable'],2) }}</td><td>Rs {{ number_format($row['gst'],2) }}</td><td>Rs {{ number_format($row['total'],2) }}</td></tr>@endforeach</tbody>
        <tfoot><tr class="total"><td colspan="4">Grand Total</td><td>Rs {{ number_format($totals['taxable'],2) }}</td><td>Rs {{ number_format($totals['gst'],2) }}</td><td>Rs {{ number_format($totals['total'],2) }}</td></tr></tfoot>
    </table>
</div>
</body>
</html>
