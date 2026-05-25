@extends('layouts.admin')
@section('title','Buyer Master')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h3 class="card-title m-0">Buyer Master</h3>
        <a href="{{ route('admin.buyers.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i>New Buyer</a>
    </div>
    <div class="card-body table-responsive">
        <table id="buyersTable" class="table table-hover">
            <thead><tr><th>Code</th><th>Name</th><th>Phone</th><th>Email</th><th>Status</th><th>Created By</th><th>Action</th></tr></thead>
            <tbody>
            @foreach($buyers as $buyer)
                <tr>
                    <td><b>{{ $buyer->buyer_code }}</b></td>
                    <td>{{ $buyer->name }}</td>
                    <td>{{ $buyer->phone ?: '-' }}</td>
                    <td>{{ $buyer->email ?: '-' }}</td>
                    <td><span class="badge badge-{{ $buyer->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($buyer->status) }}</span></td>
                    <td>{{ $buyer->creator?->name ?? 'System' }}</td>
                    <td><a href="{{ route('admin.buyers.edit',$buyer) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
@push('scripts')<script>$('#buyersTable').DataTable({pageLength:25});</script>@endpush
