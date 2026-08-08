@extends('layouts.admin')
@section('title', 'Cheque Payment Report')

@push('styles')
<style>
.cheque-report-hero{background:#0f172a;color:#fff;border-radius:16px;padding:22px;box-shadow:0 16px 38px rgba(15,23,42,.16)}.report-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px;box-shadow:0 10px 24px rgba(15,23,42,.06)}.metric{border:1px solid #e2e8f0;border-radius:12px;padding:13px;background:#f8fafc;height:100%}.metric span{font-size:11px;text-transform:uppercase;color:#64748b;font-weight:850}.metric b{display:block;font-size:20px;color:#0f172a}.bar-row{display:flex;align-items:center;gap:10px;margin:9px 0}.bar-label{width:130px;font-weight:800;color:#334155}.bar-track{flex:1;height:12px;background:#e2e8f0;border-radius:999px;overflow:hidden}.bar-fill{height:100%;background:#16a34a}.status-pill{display:inline-flex;border-radius:999px;padding:4px 9px;font-size:11px;font-weight:850;background:#ecfdf5;color:#166534}.status-pill.pending{background:#fff7ed;color:#9a3412}.status-pill.expired{background:#fef2f2;color:#991b1b}
</style>
@endpush

@section('content')
<div class="cheque-report-hero mb-4">
    <h3 class="mb-1"><i class="fas fa-money-check-alt mr-2"></i>Cheque Payment Report</h3>
    <div style="color:#cbd5e1">Cheque issue, payment settlement, due balance, age and clearance planning.</div>
</div>

<form class="report-card mb-4" method="GET">
    <div class="row align-items-end">
        <div class="col-md-2 form-group"><label>From</label><input type="date" name="from_date" value="{{ $from }}" class="form-control"></div>
        <div class="col-md-2 form-group"><label>To</label><input type="date" name="to_date" value="{{ $to }}" class="form-control"></div>
        <div class="col-md-3 form-group"><label>Party</label><select name="party_id" class="form-control select2"><option value="">All Parties</option>@foreach($parties as $party)<option value="{{ $party->id }}" @selected(request('party_id')==$party->id)>{{ $party->display_name }}</option>@endforeach</select></div>
        <div class="col-md-3 form-group"><label>Cheque Book</label><select name="cheque_book_id" class="form-control select2"><option value="">All Books</option>@foreach($books as $book)<option value="{{ $book->id }}" @selected(request('cheque_book_id')==$book->id)>{{ $book->book_no }} | {{ $book->bankAccount?->account_name }}</option>@endforeach</select></div>
        <div class="col-md-2 form-group"><label>Clearance</label><select name="clearance_range" id="clearanceRange" class="form-control"><option value="">All</option><option value="this_week" @selected(request('clearance_range')==='this_week')>This Week Clearance</option><option value="this_month" @selected(request('clearance_range')==='this_month')>This Month Clearance</option><option value="next_month" @selected(request('clearance_range')==='next_month')>Next Month Clearance</option><option value="custom" @selected(request('clearance_range')==='custom')>Custom Date Clearance</option></select></div>
        <div class="col-md-2 form-group clearance-custom" style="{{ request('clearance_range')==='custom' ? '' : 'display:none' }}"><label>Clear From</label><input type="date" name="clearance_from" value="{{ request('clearance_from', $clearanceFrom) }}" class="form-control"></div>
        <div class="col-md-2 form-group clearance-custom" style="{{ request('clearance_range')==='custom' ? '' : 'display:none' }}"><label>Clear To</label><input type="date" name="clearance_to" value="{{ request('clearance_to', $clearanceTo) }}" class="form-control"></div>
        <div class="col-md-2 form-group"><button class="btn btn-primary btn-block"><i class="fas fa-filter mr-1"></i>Filter</button></div>
    </div>
</form>

<div class="row mb-4">
    <div class="col-md-3 mb-2"><div class="metric"><span>Cheque Amount</span><b>Rs {{ number_format($totals['amount'],2) }}</b></div></div>
    <div class="col-md-3 mb-2"><div class="metric"><span>Payment Done</span><b>Rs {{ number_format($totals['paid'],2) }}</b></div></div>
    <div class="col-md-3 mb-2"><div class="metric"><span>Due Amount</span><b>Rs {{ number_format($totals['due'],2) }}</b></div></div>
    <div class="col-md-3 mb-2"><div class="metric"><span>Pending Cheques</span><b>{{ number_format($totals['pending']) }}</b></div></div>
</div>

<div class="report-card mb-4">
    <h5 class="font-weight-bold mb-3">Clearance Planning</h5>
    @forelse($rows->groupBy(fn($row) => $row['days_left'] === null ? 'No Date' : ($row['days_left'] < 0 ? 'Overdue' : ($row['days_left'] <= 7 ? 'Next 7 Days' : ($row['days_left'] <= 30 ? 'Next 30 Days' : 'Later')))) as $label => $group)
        @php $pct = $totals['amount'] > 0 ? min(100, ($group->sum(fn($row) => (float) $row['leaf']->amount) / $totals['amount']) * 100) : 0; @endphp
        <div class="bar-row"><div class="bar-label">{{ $label }}</div><div class="bar-track"><div class="bar-fill" style="width:{{ $pct }}%"></div></div><strong>Rs {{ number_format($group->sum(fn($row) => (float) $row['leaf']->amount),2) }}</strong></div>
    @empty
        <div class="text-muted">No cheque data for this filter.</div>
    @endforelse
</div>

<div class="report-card mb-4">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Cheque</th><th>Party</th><th>Book / Bank</th><th>Issue Date</th><th>Amount</th><th>Settled Bills</th><th>Due</th><th>Age</th><th>Clearance</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                @php $leaf = $row['leaf']; $expired = $row['days_left'] !== null && $row['days_left'] < 0 && $row['due'] > 0; @endphp
                <tr>
                    <td><b>{{ $leaf->cheque_no }}</b></td>
                    <td>{{ $leaf->party?->display_name }}</td>
                    <td>{{ $leaf->chequeBook?->book_no }}<br><small>{{ $leaf->bankAccount?->account_name }} | {{ $leaf->bankAccount?->account_number }} | {{ $leaf->bankAccount?->ifsc_code }}</small></td>
                    <td>{{ $leaf->cheque_date?->format('d M Y') }}</td>
                    <td>Rs {{ number_format((float)$leaf->amount,2) }}</td>
                    <td>
                        <b>Rs {{ number_format($row['paid'],2) }}</b>
                        @if($leaf->payment?->allocations?->isNotEmpty())
                            <div class="small text-muted mt-1">
                                @foreach($leaf->payment->allocations as $allocation)
                                    <div>{{ $allocation->bill_no }}: Rs {{ number_format((float) $allocation->amount,2) }}</div>
                                @endforeach
                                <div>Settlement: {{ $leaf->payment->payment_date?->format('d M Y') }}</div>
                            </div>
                        @endif
                    </td>
                    <td><b class="{{ $row['due'] > 0 ? 'text-danger' : 'text-success' }}">Rs {{ number_format($row['due'],2) }}</b></td>
                    <td>{{ $row['age'] }} days</td>
                    <td>{{ $leaf->validity_months }} month<br><small>{{ $leaf->clearance_due_date?->format('d M Y') }} | {{ $leaf->clearance_due_date?->format('l') }} | {{ $row['days_left'] === null ? '-' : ($row['days_left'].' days left') }}</small></td>
                    <td>
                        <span class="status-pill {{ $leaf->status === 'completed' ? '' : ($expired ? 'expired' : ($row['due'] > 0 ? 'pending' : '')) }}">{{ $leaf->status === 'completed' ? 'Completed' : ($expired ? 'Expired' : ucfirst(str_replace('_',' ',$leaf->status))) }}</span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-center text-muted py-4">No cheques found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($rows->isNotEmpty())
<div class="report-card">
    <h5 class="font-weight-bold mb-3">Cheque Design Preview</h5>
    @include('admin.cheques.partials.cheque-preview', ['leaf' => $rows->first()['leaf'], 'bank' => $rows->first()['leaf']->bankAccount])
</div>
@endif
@push('scripts')
<script>
$('#clearanceRange').on('change', function(){
    $('.clearance-custom').toggle(this.value === 'custom');
});
</script>
@endpush
@endsection
