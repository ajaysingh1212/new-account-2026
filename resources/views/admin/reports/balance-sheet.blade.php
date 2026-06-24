@extends('layouts.admin')
@section('title','Balance Sheet')
@section('content')
@include('admin.reports.partials.styles')
<div data-export-title="Balance Sheet" data-export-file="balance-sheet">@include('admin.reports.partials.branded-export')</div>
@php
    $receivable = abs((float)$parties->where('current_balance','<',0)->sum('current_balance'));
    $payable = (float)$parties->where('current_balance','>',0)->sum('current_balance');
    $bank = (float)$banks->sum('current_balance');
@endphp
<div class="report-hero"><h1>Balance Sheet</h1><div class="text-info mt-1">Assets, liabilities and current balances.</div></div>
<div class="metric-strip"><div class="metric"><span>Bank/Cash</span><strong>Rs {{ number_format($bank,2) }}</strong></div><div class="metric"><span>Receivable</span><strong>Rs {{ number_format($receivable,2) }}</strong></div><div class="metric"><span>Payable</span><strong>Rs {{ number_format($payable,2) }}</strong></div></div>
<div class="report-card"><table class="table report-table"><thead><tr><th>Assets</th><th>Amount</th><th>Liabilities</th><th>Amount</th></tr></thead><tbody><tr><td>Bank/Cash Balance</td><td>Rs {{ number_format($bank,2) }}</td><td>Party Payable</td><td>Rs {{ number_format($payable,2) }}</td></tr><tr><td>Party Receivable</td><td>Rs {{ number_format($receivable,2) }}</td><td>Net Worth</td><td>Rs {{ number_format(($bank+$receivable)-$payable,2) }}</td></tr></tbody></table></div>
@endsection
