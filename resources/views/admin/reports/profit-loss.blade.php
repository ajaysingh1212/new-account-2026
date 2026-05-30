@extends('layouts.admin')
@section('title','Profit And Loss')
@section('content')
@include('admin.reports.partials.styles')
<div class="report-hero"><h1>Profit And Loss</h1><form class="report-filter" method="GET"><div><label>Month</label><input type="month" name="month" class="form-control" value="{{ $filters['month'] }}"></div><div></div><div></div><button class="btn btn-info report-btn">Apply</button></form></div>
@php($profit = $sales - $purchases - ($expenses ?? 0))
<div class="metric-strip"><div class="metric"><span>Total Sales</span><strong>Rs {{ number_format($sales,2) }}</strong></div><div class="metric"><span>Total Purchase</span><strong>Rs {{ number_format($purchases,2) }}</strong></div><div class="metric"><span>Approved Expenses</span><strong>Rs {{ number_format($expenses ?? 0,2) }}</strong></div><div class="metric"><span>{{ $profit >= 0 ? 'Profit' : 'Loss' }}</span><strong class="{{ $profit >= 0 ? 'text-success' : 'text-danger' }}">Rs {{ number_format(abs($profit),2) }}</strong></div></div>
<div class="report-card"><table class="table report-table"><tr><th>Particular</th><th>Amount</th></tr><tr><td>Sales</td><td>Rs {{ number_format($sales,2) }}</td></tr><tr><td>Purchase</td><td>Rs {{ number_format($purchases,2) }}</td></tr><tr><td>Approved Expenses</td><td>Rs {{ number_format($expenses ?? 0,2) }}</td></tr><tr><th>Net {{ $profit >= 0 ? 'Profit' : 'Loss' }}</th><th>Rs {{ number_format(abs($profit),2) }}</th></tr></table></div>
@endsection
