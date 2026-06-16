@php
    $party        = $party        ?? null;
    $lines        = $lines        ?? collect();
    $docNo        = $docNo        ?? '';
    $docDate      = $docDate      ?? null;
    $accent       = $accent       ?? '#2563eb';
    $company      = $company      ?? auth()->user()?->currentCompany;
    $bankAccount  = $bankAccount  ?? null;
    $subtotal     = (float)($subtotal   ?? 0);
    $discount     = (float)($discount   ?? 0);
    $tax          = (float)($tax        ?? 0);
    $grandTotal   = (float)($grandTotal ?? 0);
    $terms        = $terms        ?? '';
    $status       = $status       ?? 'posted';
    $title        = $title        ?? 'INVOICE';
    $billingAddress  = $billingAddress  ?? '';
    $shippingAddress = $shippingAddress ?? '';

    /* ── Amount in Words ── */
    function numberToWordsIN(float $amount): string {
        $ones  = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten',
                  'Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen',
                  'Eighteen','Nineteen'];
        $tens  = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
        $words = function(int $n) use (&$words, $ones, $tens): string {
            if ($n < 20)  return $ones[$n];
            if ($n < 100) return $tens[intdiv($n,10)] . ($n%10 ? ' '.$ones[$n%10] : '');
            return $ones[intdiv($n,100)] . ' Hundred' . ($n%100 ? ' '.$words($n%100) : '');
        };
        $paise = (int)round(($amount - floor($amount)) * 100);
        $rupees = (int)floor($amount);
        $parts = [];
        if ($rupees >= 10000000)   { $parts[] = $words(intdiv($rupees,10000000)).' Crore'; $rupees %= 10000000; }
        if ($rupees >= 100000)     { $parts[] = $words(intdiv($rupees,100000)).' Lakh';  $rupees %= 100000; }
        if ($rupees >= 1000)       { $parts[] = $words(intdiv($rupees,1000)).' Thousand'; $rupees %= 1000; }
        if ($rupees > 0)           { $parts[] = $words($rupees); }
        $result = implode(' ', $parts) ?: 'Zero';
        $result .= ' Rupees';
        if ($paise)  $result .= ' and '.$words($paise).' Paise';
        return $result.' Only';
    }
    $amountInWords = numberToWordsIN($grandTotal);
@endphp
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{{ $title }} {{ $docNo }}</title>

<style>
/* ══════════════════════════════════════════
   CSS CUSTOM PROPERTIES — theme-aware
══════════════════════════════════════════ */
:root{
  --accent:{{ $accent }};
  --accent-light:color-mix(in srgb,var(--accent) 12%,#fff);
  --accent-dark:color-mix(in srgb,var(--accent) 60%,#000);
  --bg:#f1f5fb;
  --sheet:#ffffff;
  --border:#e2e8f0;
  --text:#0f172a;
  --muted:#64748b;
  --row-odd:#f8faff;
  --row-even:#ffffff;
  --shadow:0 8px 40px rgba(0,0,0,.10);
  --header-text:#ffffff;
  --tag-bg:color-mix(in srgb,var(--accent) 15%,#fff);
  --tag-text:var(--accent-dark);
  --footer-bg:#f8faff;
  --label:#334155;
}
[data-theme="dark"]{
  --bg:#0d1117;
  --sheet:#161b22;
  --border:#30363d;
  --text:#e6edf3;
  --muted:#8b949e;
  --row-odd:#1c2128;
  --row-even:#161b22;
  --shadow:0 8px 40px rgba(0,0,0,.5);
  --tag-bg:color-mix(in srgb,var(--accent) 25%,#000);
  --tag-text:color-mix(in srgb,var(--accent) 90%,#fff);
  --footer-bg:#1c2128;
  --label:#94a3b8;
  --accent-light:color-mix(in srgb,var(--accent) 20%,#0d1117);
}

/* ══ RESET ══ */
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',Arial,sans-serif;background:var(--bg);color:var(--text);padding:24px 16px;min-height:100vh;transition:background .3s,color .3s}

/* ══ TOOLBAR ══ */
.toolbar{
  max-width:1020px;margin:0 auto 16px;
  display:flex;gap:10px;align-items:center;flex-wrap:wrap;
  padding:10px 14px;background:var(--sheet);
  border:1px solid var(--border);border-radius:10px;
  box-shadow:var(--shadow);
}
.toolbar label{font-size:12px;font-weight:600;color:var(--muted);letter-spacing:.05em}
.color-options{display:flex;gap:7px;flex-wrap:wrap}
.color-dot{
  width:26px;height:26px;border-radius:50%;cursor:pointer;border:3px solid transparent;
  transition:transform .15s,border-color .15s;
}
.color-dot:hover{transform:scale(1.2)}
.color-dot.active{border-color:var(--text)}
.custom-color{
  width:26px;height:26px;border-radius:50%;cursor:pointer;
  border:2px dashed var(--muted);overflow:hidden;padding:0;
}
.custom-color input[type=color]{width:40px;height:40px;border:0;cursor:pointer;margin:-7px -7px;opacity:0;position:absolute}
.custom-color{position:relative;display:flex;align-items:center;justify-content:center;font-size:14px;color:var(--muted)}
.sep{width:1px;height:28px;background:var(--border);margin:0 4px}
.btn-tool{
  padding:6px 14px;border:1px solid var(--border);border-radius:6px;
  background:var(--sheet);color:var(--text);cursor:pointer;font-size:12px;
  font-weight:600;display:flex;align-items:center;gap:5px;transition:all .2s;
}
.btn-tool:hover{border-color:var(--accent);color:var(--accent)}
.btn-print{
  margin-left:auto;padding:7px 18px;
  background:var(--accent);color:#fff;border:0;border-radius:7px;
  cursor:pointer;font-weight:700;font-size:13px;letter-spacing:.04em;
  box-shadow:0 2px 8px color-mix(in srgb,var(--accent) 45%,transparent);
  transition:opacity .2s;
}
.btn-print:hover{opacity:.88}

/* ══ SHEET ══ */
.sheet{
  max-width:1020px;margin:0 auto;
  background:var(--sheet);border:1.5px solid var(--border);
  border-radius:14px;overflow:hidden;
  box-shadow:var(--shadow);
}

/* ── HEADER BAND ── */
.header{
  background:linear-gradient(135deg,var(--accent) 0%,var(--accent-dark) 100%);
  color:#fff;padding:28px 30px;position:relative;overflow:hidden;
}
.header::before{
  content:'';position:absolute;right:-60px;top:-60px;
  width:220px;height:220px;border-radius:50%;
  background:rgba(255,255,255,.07);
}
.header::after{
  content:'';position:absolute;right:40px;bottom:-80px;
  width:160px;height:160px;border-radius:50%; 
  background:rgba(255,255,255,.05);
}
.header-inner{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;position:relative;z-index:1}
.brand-row{display:flex;gap:16px;align-items:flex-start}
.logo{
  width:72px;height:72px;object-fit:contain;border-radius:12px;
  background:rgba(255,255,255,.15);backdrop-filter:blur(4px);
  border:2px solid rgba(255,255,255,.25);padding:4px;flex-shrink:0;
}
.logo-placeholder{
  width:72px;height:72px;border-radius:12px;
  background:rgba(255,255,255,.12);border:2px solid rgba(255,255,255,.25);
  display:flex;align-items:center;justify-content:center;font-size:28px;flex-shrink:0;
}
.company-name{font-size:26px;font-weight:800;letter-spacing:-.01em;line-height:1.1}
.company-meta{font-size:12px;opacity:.82;margin-top:5px;line-height:1.7}
.doc-badge{
  text-align:right;flex-shrink:0;
}
.doc-type{
  font-size:32px;font-weight:900;letter-spacing:.08em;
  opacity:.95;line-height:1;
}
.doc-num{font-size:13px;opacity:.75;margin-top:4px}
.doc-num span{
  font-size:16px;font-weight:700;opacity:1;display:block;margin-top:2px;
}
.status-pill{
  display:inline-block;margin-top:8px;padding:3px 12px;
  border-radius:20px;background:rgba(255,255,255,.2);
  font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;
  border:1px solid rgba(255,255,255,.3);
}

/* ── META STRIP ── */
.meta-strip{
  display:grid;grid-template-columns:repeat(4,1fr);
  border-bottom:1px solid var(--border);
}
.meta-cell{
  padding:14px 20px;border-right:1px solid var(--border);
}
.meta-cell:last-child{border-right:0}
.meta-lbl{font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);margin-bottom:3px}
.meta-val{font-size:14px;font-weight:600;color:var(--text)}

/* ── PARTY GRID ── */
.party-grid{display:grid;grid-template-columns:1fr 1fr;border-bottom:1px solid var(--border)}
.party-box{padding:18px 24px;border-right:1px solid var(--border)}
.party-box:last-child{border-right:0}
.section-lbl{
  font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;
  color:var(--accent);margin-bottom:8px;display:flex;align-items:center;gap:6px;
}
.section-lbl::after{content:'';flex:1;height:1px;background:var(--accent-light)}
.party-name{font-size:15px;font-weight:700;color:var(--text);margin-bottom:4px}
.party-info{font-size:12.5px;color:var(--muted);line-height:1.7}

/* ── ITEMS TABLE ── */
.items-wrap{padding:20px 24px} 
.items-lbl{
  font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;
  color:var(--accent);margin-bottom:12px;display:flex;align-items:center;gap:6px;
}
.items-lbl::after{content:'';flex:1;height:1px;background:var(--border)}
table.items{width:100%;border-collapse:collapse;font-size:13px}
table.items thead tr{background:var(--accent);color:#fff}
table.items thead th{padding:10px 12px;text-align:left;font-weight:600;font-size:11.5px;letter-spacing:.04em;white-space:nowrap}
table.items thead th:not(:last-child){border-right:1px solid rgba(255,255,255,.15)}
table.items tbody tr:nth-child(odd){background:var(--row-odd)}
table.items tbody tr:nth-child(even){background:var(--row-even)}
table.items tbody tr:hover{background:var(--accent-light)}
table.items td{padding:10px 12px;border-bottom:1px solid var(--border);vertical-align:top}
table.items td:not(:last-child){border-right:1px solid var(--border)}
.item-name{font-weight:600;color:var(--text)}
.item-desc{font-size:11px;color:var(--muted);margin-top:2px}
.amount-cell{font-weight:700;color:var(--text);text-align:right;white-space:nowrap}
.num-cell{text-align:right;white-space:nowrap}
.tfoot-row td{
  background:var(--accent-light);font-weight:700;
  border-top:2px solid var(--accent);padding:10px 12px;
}
.tfoot-row td:last-child{text-align:right;color:var(--accent);font-size:14px}

/* ── SUMMARY ── */
.summary-row{display:flex;justify-content:flex-end;padding:0 24px 20px}
.summary-box{width:360px;border:1.5px solid var(--border);border-radius:10px;overflow:hidden}
.sum-line{display:flex;justify-content:space-between;align-items:center;padding:10px 16px;border-bottom:1px solid var(--border);font-size:13px}
.sum-line:last-child{border-bottom:0}
.sum-line.total-line{background:var(--accent);color:#fff;font-size:16px;font-weight:800}
.sum-label{color:var(--muted);font-size:12px}
.sum-line.total-line .sum-label{color:rgba(255,255,255,.8)}
.amount-words{
  margin:0 24px 20px;padding:12px 16px;
  background:var(--accent-light);border:1px solid var(--border);
  border-radius:8px;font-size:12.5px;color:var(--text);line-height:1.5;
}
.amount-words strong{color:var(--accent);font-size:11px;text-transform:uppercase;letter-spacing:.07em;display:block;margin-bottom:3px}

/* ── FOOTER SECTION ── */
.footer-grid{
  display:grid;grid-template-columns:1.3fr 1.5fr 1fr;
  border-top:1.5px solid var(--border);background:var(--footer-bg);
}
.footer-cell{padding:20px 22px;border-right:1px solid var(--border)}
.footer-cell:last-child{border-right:0;text-align:center}
.footer-lbl{font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--accent);margin-bottom:10px}
.bank-detail{font-size:12.5px;line-height:1.85;color:var(--text)}
.bank-key{color:var(--muted);font-size:11px}
.qr{width:90px;height:90px;object-fit:contain;margin-top:8px;border-radius:6px;border:1px solid var(--border)}
.terms-text{font-size:12px;line-height:1.7;color:var(--muted)}
.sign-box{
  margin-top:20px;border-top:1.5px solid var(--text);
  padding-top:8px;font-size:11px;font-weight:600;
  color:var(--muted);letter-spacing:.04em;
}
.seal-circle{
  width:80px;height:80px;border-radius:50%;
  border:2px dashed var(--border);margin:0 auto 12px;
  display:flex;align-items:center;justify-content:center;
  font-size:10px;color:var(--muted);text-align:center;line-height:1.3;
}

/* ── WATERMARK ── */
.sheet-inner{position:relative}
.watermark{
  position:absolute;top:50%;left:50%;transform:translate(-50%,-50%) rotate(-35deg);
  font-size:110px;font-weight:900;letter-spacing:.1em;
  color:var(--accent);opacity:.03;pointer-events:none;z-index:0;white-space:nowrap;
  text-transform:uppercase;
}

/* ══ PRINT ══ */
@media print{
  body{background:#fff!important;padding:0;color:#000!important}
  .toolbar{display:none!important}
  .sheet{border-radius:0;box-shadow:none;border:0;max-width:none}
  .watermark{display:none}

  /* Light print overrides */
  body.print-light{--sheet:#fff;--bg:#fff;--text:#000;--muted:#555;--border:#ccc;--row-odd:#f5f5f5;--row-even:#fff;--footer-bg:#f5f5f5;--accent-light:#e8f0fe}
  body.print-light table.items thead tr{background:var(--accent)!important}
  body.print-light .sum-line.total-line{background:var(--accent)!important}

  /* Dark print overrides */
  body.print-dark{--sheet:#111;--bg:#111;--text:#eee;--muted:#999;--border:#333;--row-odd:#1a1a1a;--row-even:#111;--footer-bg:#181818;--accent-light:#1a2040}
  body.print-dark table.items thead tr{background:var(--accent)!important}

  @page{margin:12mm;size:A4}
}
</style>
</head>
<body class="print-light">

<!-- ══ TOOLBAR ══ -->
<div class="toolbar no-print">
  <label>Theme</label>
  <div class="color-options" id="colorPicker">
    <div class="color-dot active" style="background:#2563eb" data-color="#2563eb" title="Blue"></div>
    <div class="color-dot" style="background:#7c3aed" data-color="#7c3aed" title="Violet"></div>
    <div class="color-dot" style="background:#059669" data-color="#059669" title="Emerald"></div>
    <div class="color-dot" style="background:#dc2626" data-color="#dc2626" title="Red"></div>
    <div class="color-dot" style="background:#d97706" data-color="#d97706" title="Amber"></div>
    <div class="color-dot" style="background:#0891b2" data-color="#0891b2" title="Cyan"></div>
    <div class="color-dot" style="background:#db2777" data-color="#db2777" title="Pink"></div>
    <div class="color-dot" style="background:#374151" data-color="#374151" title="Slate"></div>
    <div class="custom-color" title="Custom color">
      🎨<input type="color" id="customColor" value="{{ $accent }}">
    </div>
  </div>

  <div class="sep"></div>
  <label>Mode</label>
  <button class="btn-tool" id="themeToggle" onclick="toggleTheme()">🌙 Dark</button>

  <div class="sep"></div>
  <label>Print Style</label>
  <select id="printMode" class="btn-tool" style="padding:5px 10px;border-radius:6px">
    <option value="light">☀️ Light Print</option>
    <option value="dark">🌑 Dark Print</option>
    <option value="current">🎨 As Shown</option>
  </select>

  <button class="btn-print no-print" onclick="doPrint()">🖨️ Print / PDF</button>
</div>

<!-- ══ INVOICE SHEET ══ -->
<div class="sheet">
  <div class="sheet-inner">
    <div class="watermark">{{ $title }}</div>

    <!-- HEADER -->
    <div class="header">
      <div class="header-inner">
        <div class="brand-row">
          @if($company?->logo)
            <img class="logo" src="{{ asset('storage/'.$company->logo) }}" alt="logo">
          @else
            <div class="logo-placeholder">🏢</div>
          @endif
          <div>
            <div class="company-name">{{ $company?->name ?? config('app.name','Eemot Account') }}</div>
            <div class="company-meta">
              @if($company?->phone) 📞 {{ $company->phone }}&ensp;@endif
              @if($company?->email) ✉️ {{ $company->email }}<br>@endif
              @if($company?->gst_number) GST: <b>{{ $company->gst_number }}</b>&ensp;@endif
              @if($company?->pan_number) PAN: <b>{{ $company->pan_number }}</b><br>@endif
              @if($company?->address) 📍 {{ $company->address }}@endif
            </div>
          </div>
        </div>
        <div class="doc-badge">
          <div class="doc-type">{{ strtoupper($title) }}</div>
          <div class="doc-num">
            Invoice No.<span>#{{ $docNo }}</span>
          </div>
          <div class="status-pill">{{ strtoupper($status) }}</div>
        </div>
      </div>
    </div><!-- /header -->

    <!-- META STRIP -->
    <div class="meta-strip">
      <div class="meta-cell">
        <div class="meta-lbl">Invoice Date</div>
        <div class="meta-val">{{ $docDate ? \Illuminate\Support\Carbon::parse($docDate)->format('d M, Y') : '—' }}</div>
      </div>
      <div class="meta-cell">
        <div class="meta-lbl">Due Date</div>
        <div class="meta-val">{{ isset($dueDate) && $dueDate ? \Illuminate\Support\Carbon::parse($dueDate)->format('d M, Y') : '—' }}</div>
      </div>
      <div class="meta-cell">
        <div class="meta-lbl">Payment Terms</div>
        <div class="meta-val">{{ $paymentTerms ?? 'Immediate' }}</div>
      </div>
      <div class="meta-cell">
        <div class="meta-lbl">Place of Supply</div>
        <div class="meta-val">{{ $placeOfSupply ?? '—' }}</div>
      </div>
    </div>

    <!-- PARTY -->
    <div class="party-grid">
      <div class="party-box">
        <div class="section-lbl">Bill To</div>
        <div class="party-name">{{ $party?->display_name ?: 'Cash / Walk-in' }}</div>
        <div class="party-info">
          @if($party?->phone) 📞 {{ $party->phone }}<br>@endif
          @if($party?->email) ✉️ {{ $party->email }}<br>@endif
          @if($party?->gstin) GSTIN: <b>{{ $party->gstin }}</b><br>@endif
          @if($billingAddress) {!! nl2br(e($billingAddress)) !!}@endif
        </div>
      </div>
      <div class="party-box">
        <div class="section-lbl">Ship To</div>
        @if($shippingAddress)
          <div class="party-info">{!! nl2br(e($shippingAddress)) !!}</div>
        @else
          <div class="party-info" style="color:var(--muted);font-style:italic">Same as billing address</div>
        @endif
      </div>
    </div>

    <!-- ITEMS -->
    <div class="items-wrap">
      <div class="items-lbl">Items / Services</div>
      <table class="items">
        <thead>
          <tr>
            <th style="width:36px">#</th>
            <th>Item &amp; Description</th>
            <th>HSN/SAC</th>
            <th class="num-cell">Qty</th>
            <th>Unit</th>
            <th class="num-cell">Rate (₹)</th>
            <th>Tax %</th>
            <th class="num-cell">Amount (₹)</th>
          </tr>
        </thead>
        <tbody>
        @forelse($lines as $line)
          <tr>
            <td style="text-align:center;color:var(--muted);font-size:11px">{{ $loop->iteration }}</td>
            <td>
              <div class="item-name">{{ $line->item?->name }}</div>
              @if($line->description)<div class="item-desc">{{ $line->description }}</div>@endif
            </td>
            <td style="color:var(--muted);font-size:12px">{{ $line->item?->hsn_code }}</td>
            <td class="num-cell">{{ number_format((float)$line->quantity,2) }}</td>
            <td style="color:var(--muted);font-size:12px">{{ $line->unit }}</td>
            <td class="num-cell">{{ number_format((float)$line->unit_price,2) }}</td>
            <td class="num-cell" style="color:var(--muted)">{{ $line->tax_rate ?? '—' }}</td>
            <td class="amount-cell">{{ number_format((float)$line->line_total,2) }}</td>
          </tr>
        @empty
          <tr><td colspan="8" style="text-align:center;padding:28px;color:var(--muted)">No items added.</td></tr>
        @endforelse
        </tbody>
        <tfoot>
          <tr class="tfoot-row">
            <td colspan="3"><b>TOTAL</b></td>
            <td class="num-cell">{{ number_format((float)$lines->sum('quantity'),3) }}</td>
            <td colspan="3"></td>
            <td class="amount-cell">₹ {{ number_format($grandTotal,2) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- SUMMARY -->
    <div class="summary-row">
      <div class="summary-box">
        <div class="sum-line">
          <span class="sum-label">Subtotal</span>
          <b>₹ {{ number_format($subtotal,2) }}</b>
        </div>
        <div class="sum-line">
          <span class="sum-label">Discount</span>
          <b style="color:#ef4444">− ₹ {{ number_format($discount,2) }}</b>
        </div>
        <div class="sum-line">
          <span class="sum-label">GST / Tax</span>
          <b>₹ {{ number_format($tax,2) }}</b>
        </div>
        <div class="sum-line" style="border-top:1.5px solid var(--border)">
          <span class="sum-label">Round Off</span>
          <b>₹ {{ number_format($grandTotal - floor($grandTotal),2) }}</b>
        </div>
        <div class="sum-line total-line">
          <span class="sum-label">Grand Total</span>
          <b>₹ {{ number_format($grandTotal,2) }}</b>
        </div>
      </div>
    </div>

    <!-- AMOUNT IN WORDS -->


    <!-- FOOTER -->
    <div class="footer-grid">
      <!-- Bank -->
      <div class="footer-cell" style="display: flex;">

        @if($bankAccount)
          <div class="bank-detail">
            <span class="bank-key">Bank</span><br>
            <b>{{ $bankAccount->bank_name }}</b>
            @if($bankAccount->branch_name) — {{ $bankAccount->branch_name }}@endif<br>
            <span class="bank-key">Account Holder</span><br>
            {{ $bankAccount->account_holder_name ?: $company?->name }}<br>
            <span class="bank-key">A/C No.</span> {{ $bankAccount->account_number ?: '—' }}<br>
            <span class="bank-key">IFSC</span> {{ $bankAccount->ifsc_code ?: '—' }}<br>
            <span class="bank-key">UPI ID</span> {{ $bankAccount->upi_id ?: '—' }}<br>
          </div>
          @if($bankAccount->upi_qr_code)
            <img class="qr" src="{{ asset('storage/'.$bankAccount->upi_qr_code) }}" alt="UPI QR">
          @endif
        @else
          <div class="bank-detail" style="color:var(--muted);font-style:italic">No bank account linked.</div>
        @endif
      </div>

      <!-- Terms -->
      <div class="footer-cell" style="width:300px;">
        <div class="footer-lbl">Terms &amp; Conditions</div>
        <div class="terms-text">
          @if($terms)
            {!! nl2br(e($terms)) !!}
          @else
            <span style="font-style:italic;color:var(--muted)">No terms specified.</span>
          @endif
        </div>

      </div>

      <!-- Signature -->
      <div class="footer-cell" style="display:flex;flex-direction:column;align-items:center;justify-content:flex-end">
            <div class=""  >
                <strong>Amount in Words</strong><br>
                {{ $amountInWords }}
            </div>
        <div class="seal-circle">Company<br>Seal</div>

        <div style="width:100%;border-top:1.5px solid var(--text);padding-top:8px;text-align:center">
          <div style="font-size:11px;font-weight:700;letter-spacing:.04em;color:var(--muted)">Authorised Signatory</div>
          <div style="font-size:12px;font-weight:600;margin-top:4px;color:var(--text)">{{ $company?->name }}</div>
        </div>
      </div>
    </div><!-- /footer -->

    <!-- BOTTOM STRIP -->
    <div style="padding:10px 24px;background:var(--accent);text-align:center">
      <span style="color:rgba(255,255,255,.85);font-size:11px;letter-spacing:.06em">
        Thank you for your business! &nbsp;·&nbsp; {{ $company?->name }} &nbsp;·&nbsp; {{ $company?->email }}
      </span>
    </div>

  </div>
</div><!-- /sheet -->

<script>
/* ── Accent color application ── */
function applyAccent(hex){
  document.documentElement.style.setProperty('--accent', hex);
  document.documentElement.style.setProperty('--accent-dark',  shadeColor(hex, -35));
  document.documentElement.style.setProperty('--accent-light', hexToRgba(hex, .12));
}
function shadeColor(hex, pct){
  let n = parseInt(hex.slice(1),16),
      r = Math.min(255,Math.max(0,(n>>16)+(pct/100*255)|0)),
      g = Math.min(255,Math.max(0,((n>>8)&0xff)+(pct/100*255)|0)),
      b = Math.min(255,Math.max(0,(n&0xff)+(pct/100*255)|0));
  return '#'+[r,g,b].map(v=>v.toString(16).padStart(2,'0')).join('');
}
function hexToRgba(hex,a){
  let n=parseInt(hex.slice(1),16);
  return `rgba(${n>>16},${(n>>8)&255},${n&255},${a})`;
}

/* ── Color dots ── */
document.querySelectorAll('.color-dot').forEach(el=>{
  el.addEventListener('click',()=>{
    document.querySelectorAll('.color-dot').forEach(d=>d.classList.remove('active'));
    el.classList.add('active');
    applyAccent(el.dataset.color);
  });
});
document.getElementById('customColor').addEventListener('input',e=>{
  document.querySelectorAll('.color-dot').forEach(d=>d.classList.remove('active'));
  applyAccent(e.target.value);
});

/* ── Dark/Light toggle ── */
let darkMode = false;
function toggleTheme(){
  darkMode = !darkMode;
  document.documentElement.setAttribute('data-theme', darkMode ? 'dark' : 'light');
  document.getElementById('themeToggle').textContent = darkMode ? '☀️ Light' : '🌙 Dark';
}

/* ── Print with selected style ── */
function doPrint(){
  const mode = document.getElementById('printMode').value;
  document.body.classList.remove('print-light','print-dark');
  if(mode === 'light')   document.body.classList.add('print-light');
  if(mode === 'dark')    document.body.classList.add('print-dark');
  window.print();
}

/* ── Init from PHP accent ── */
applyAccent('{{ $accent }}');
</script>
</body>
</html>
