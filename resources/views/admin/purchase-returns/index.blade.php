@extends('layouts.admin')
@section('title','Purchase Returns')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between"><h3 class="card-title m-0">Purchase Returns</h3><a href="{{ route('admin.purchase-returns.create') }}" class="btn btn-primary btn-sm">New Return</a></div>
    <div class="card-body table-responsive">
        <table id="returnsTable" class="table table-hover"><thead><tr><th>No</th><th>Date</th><th>Bill</th><th>Party</th><th>Total</th><th>Action</th></tr></thead><tbody>
        @foreach($returns as $return)<tr><td>{{ $return->return_no }}</td><td>{{ $return->return_date?->format('d M Y') }}</td><td>{{ $return->bill?->invoice_no }}</td><td>{{ $return->party?->display_name ?: 'Cash' }}</td><td>Rs {{ number_format((float)$return->grand_total,2) }}</td><td><a href="{{ route('admin.purchase-returns.show',$return) }}" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a></td></tr>@endforeach
        </tbody></table>
    </div>
</div>
@endsection
@push('scripts')<script>$('#returnsTable').DataTable({pageLength:25});</script>@endpush
