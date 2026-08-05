@php
    $bank = $bank ?? null;
    $leaf = $leaf ?? null;
    $chequeNo = $chequeNo ?? ($leaf?->cheque_no ?: 'AUTO');
    $date = $date ?? ($leaf?->cheque_date?->format('d / m / Y') ?: now()->format('d / m / Y'));
    $payee = $payee ?? ($leaf?->payee_name ?: $leaf?->party?->display_name);
    $amount = $amount ?? ($leaf ? number_format((float) $leaf->amount, 2, '.', '') : '0.00');
    $words = $words ?? ($leaf?->amount_words ?: '');
    $memo = $memo ?? ($leaf?->memo ?: '');
@endphp
<style>
.cheque-preview-wrap{width:100%;max-width:720px;container-type:inline-size}.cheque-preview{background:#afece8;color:#16233c;border-radius:10px;position:relative;padding:clamp(14px,3.6cqi,26px) clamp(14px,4.4cqi,32px) clamp(10px,2.8cqi,20px);overflow:hidden;box-shadow:0 18px 40px rgba(15,23,42,.18)}.cheque-preview:before{content:"";position:absolute;inset:0;opacity:.10;background-image:repeating-radial-gradient(circle at 0% 50%,transparent 0 6px,#3c6f60 6px 7px,transparent 7px 13px),repeating-radial-gradient(circle at 100% 50%,transparent 0 6px,#3c6f60 6px 7px,transparent 7px 13px),repeating-radial-gradient(circle at 50% -20%,transparent 0 9px,#a4772c 9px 10px,transparent 10px 19px);background-size:120px 120px,120px 120px,240px 240px}.cheque-preview:after{content:"CHEQUE";position:absolute;top:42%;left:50%;transform:translate(-50%,-50%) rotate(-11deg);font-family:serif;font-size:clamp(26px,9cqi,64px);letter-spacing:.2em;color:#8a2f2f;opacity:.06;white-space:nowrap}.cheque-preview .bank-row{display:flex;justify-content:space-between;gap:16px;position:relative;z-index:1}.cheque-preview .bank-mark{display:flex;gap:12px;align-items:center}.cheque-preview .seal{width:46px;height:46px;border-radius:50%;border:1.6px solid #a4772c;display:flex;align-items:center;justify-content:center;font-family:serif;font-weight:700;color:#a4772c}.cheque-preview .bank-name{font-family:serif;font-weight:800;font-size:23px;line-height:1}.cheque-preview .bank-sub,.cheque-preview label,.cheque-preview .lbl,.cheque-preview .micr{font-family:monospace;text-transform:uppercase;color:#2b3b58;font-size:10px;letter-spacing:.08em}.cheque-preview .cheque-no{text-align:right;font-family:monospace}.cheque-preview .cheque-no .val{font-size:17px;font-weight:700;color:#8a2f2f}.cheque-preview .line{border-bottom:1px solid #16233c;min-height:28px;font-family:serif;font-style:italic;font-size:17px;color:#16233c;padding:3px 2px}.cheque-preview .row-date{display:flex;justify-content:flex-end;gap:10px;align-items:end;margin-top:6px;position:relative;z-index:1}.cheque-preview .row-pay,.cheque-preview .row-words{display:flex;align-items:end;gap:14px;margin-top:20px;position:relative;z-index:1}.cheque-preview .row-pay .line,.cheque-preview .row-words .line{flex:1}.cheque-preview .amount-box{border:1.4px solid #16233c;padding:6px 10px;background:rgba(255,255,255,.35);font-family:monospace;font-weight:700;color:#8a2f2f}.cheque-preview .row-bottom{display:flex;justify-content:space-between;gap:24px;margin-top:30px;position:relative;z-index:1}.cheque-preview .memo-block,.cheque-preview .sig-block{flex:1}.cheque-preview .micr{margin-top:20px;padding-top:12px;border-top:1px dashed rgba(22,35,60,.25);display:flex;justify-content:center;gap:10px;position:relative;z-index:1;flex-wrap:wrap}
</style>
<div class="cheque-preview-wrap">
    <div class="cheque-preview">
        <div class="bank-row">
            <div class="bank-mark">
                <div class="seal">{{ strtoupper(substr($bank?->bank_name ?: $bank?->account_name ?: 'BK', 0, 2)) }}</div>
                <div><div class="bank-name">{{ $bank?->bank_name ?: $bank?->account_name ?: 'Bank Account' }}</div><div class="bank-sub">A/C {{ $bank?->account_number ?: '-' }} | IFSC {{ $bank?->ifsc_code ?: '-' }}</div></div>
            </div>
            <div class="cheque-no"><div class="lbl">Cheque</div><div class="val">{{ $chequeNo }}</div></div>
        </div>
        <div class="row-date"><label>Date</label><div class="line" style="width:150px;text-align:right">{{ $date }}</div></div>
        <div class="row-pay"><label>Pay to the order of</label><div class="line">{{ $payee ?: '-' }}</div><div class="amount-box">Rs {{ $amount }}</div></div>
        <div class="row-words"><label>Rupees</label><div class="line">{{ $words ?: '-' }}</div><span class="bank-sub">only</span></div>
        <div class="row-bottom"><div class="memo-block"><div class="line">{{ $memo ?: '-' }}</div><label>Memo</label></div><div class="sig-block"><div class="line">&nbsp;</div><label>Authorised signature</label></div></div>
        <div class="micr"><span>{{ $chequeNo }}</span><span>{{ $bank?->ifsc_code ?: 'IFSC' }}</span><span>{{ $bank?->account_number ?: 'ACCOUNT' }}</span></div>
    </div>
</div>
