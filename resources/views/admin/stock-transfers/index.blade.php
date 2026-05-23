@extends('layouts.admin')

@section('title', 'Stock Transfers')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Stock Transfers</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
<div class="container-fluid">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('error') }}
        </div>
    @endif

    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-exchange-alt mr-1"></i> All Transfers</h3>
            <div class="card-tools">
                @can('stocks.view')
                <a href="{{ route('admin.stock-transfers.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> New Transfer
                </a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="thead-dark">
                        <tr>
                            <th>Transfer No</th>
                            <th>Date</th>
                            <th>From Company</th>
                            <th>To Company</th>
                            <th>Items</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transfers as $t)
                        <tr>
                            <td><strong>{{ $t->transfer_no }}</strong></td>
                            <td>{{ $t->transfer_date->format('d M Y') }}</td>
                            <td>{{ $t->fromCompany->name }}</td>
                            <td>{{ $t->toCompany->name }}</td>
                            <td><span class="badge badge-info">{{ $t->items_count ?? $t->items->count() }} items</span></td>
                            <td>
                                @if($t->isPending())
                                    <span class="badge badge-warning"><i class="fas fa-clock"></i> Pending</span>
                                @elseif($t->isApproved())
                                    <span class="badge badge-success"><i class="fas fa-check"></i> Approved</span>
                                @else
                                    <span class="badge badge-danger"><i class="fas fa-times"></i> Rejected</span>
                                @endif
                            </td>
                            <td>{{ $t->creator->name ?? '-' }}</td>
                            <td>
                                <a href="{{ route('admin.stock-transfers.show', $t) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Koi transfer nahi mila.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</section>
@endsection
