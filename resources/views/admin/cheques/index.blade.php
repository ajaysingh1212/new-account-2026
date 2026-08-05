@extends('layouts.admin')
@section('title', 'Cheque Book')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 font-weight-bold">Cheque Book</h4>
    <div><a href="{{ route('admin.cheques.books.create') }}" class="btn btn-primary"><i class="fas fa-book mr-1"></i>Create Book</a> <a href="{{ route('admin.cheques.leaves.create') }}" class="btn btn-success"><i class="fas fa-money-check-alt mr-1"></i>Issue Cheque</a></div>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="card mb-4"><div class="card-header font-weight-bold">Cheque Books</div><div class="card-body table-responsive"><table class="table table-hover"><thead><tr><th>Book No</th><th>Bank</th><th>Year From-To</th><th>Leaves</th><th>Status</th></tr></thead><tbody>@foreach($books as $book)<tr><td><b>{{ $book->book_no }}</b></td><td>{{ $book->bankAccount?->account_name }}<br><small>{{ $book->bankAccount?->bank_name }} | {{ $book->bankAccount?->account_number }}</small></td><td>{{ $book->valid_from?->format('d M Y') ?: '-' }} to {{ $book->valid_to?->format('d M Y') ?: '-' }}</td><td>{{ $book->leaves_count }} / {{ $book->leaf_count }} used</td><td>{{ ucfirst($book->status) }}</td></tr>@endforeach</tbody></table></div></div>
<div class="card"><div class="card-header font-weight-bold">Issued Cheques</div><div class="card-body table-responsive"><table class="table table-hover"><thead><tr><th>No</th><th>Date</th><th>Party</th><th>Bank</th><th>Amount</th><th>Clearance</th><th>Status</th><th>Payment</th></tr></thead><tbody>@foreach($leaves as $leaf)<tr><td><b>{{ $leaf->cheque_no }}</b></td><td>{{ $leaf->cheque_date?->format('d M Y') }}</td><td>{{ $leaf->party?->display_name }}</td><td>{{ $leaf->bankAccount?->account_name }}</td><td>Rs {{ number_format((float)$leaf->amount,2) }}</td><td>{{ $leaf->validity_months }} month<br><small>{{ $leaf->clearance_due_date?->format('d M Y') }}</small></td><td>{{ ucfirst(str_replace('_',' ',$leaf->status)) }}</td><td>{{ $leaf->payment_done ? 'Done' : 'Pending' }}</td></tr>@endforeach</tbody></table></div></div>
@endsection
