@extends('layouts.admin')

@section('title', 'Stock Transfer Detail')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1 class="m-0">Transfer: {{ $stockTransfer->transfer_no }}</h1></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.stock-transfers.index') }}">Stock Transfers</a></li>
                    <li class="breadcrumb-item active">{{ $stockTransfer->transfer_no }}</li>
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

    <div class="row">
        <!-- Left: Info -->
        <div class="col-md-8">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Transfer Info</h3>
                    <div class="card-tools">
                        @if($stockTransfer->isPending())
                            <span class="badge badge-warning badge-lg p-2"><i class="fas fa-clock"></i> Pending Approval</span>
                        @elseif($stockTransfer->isApproved())
                            <span class="badge badge-success badge-lg p-2"><i class="fas fa-check-circle"></i> Approved</span>
                        @else
                            <span class="badge badge-danger badge-lg p-2"><i class="fas fa-times-circle"></i> Rejected</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr><th width="40%">Transfer No</th><td>{{ $stockTransfer->transfer_no }}</td></tr>
                                <tr><th>Date</th><td>{{ $stockTransfer->transfer_date->format('d M Y') }}</td></tr>
                                <tr><th>From Company</th><td><strong>{{ $stockTransfer->fromCompany->name }}</strong></td></tr>
                                <tr><th>To Company</th><td><strong>{{ $stockTransfer->toCompany->name }}</strong></td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr><th width="40%">Created By</th><td>{{ $stockTransfer->creator->name ?? '-' }}</td></tr>
                                <tr><th>Created At</th><td>{{ $stockTransfer->created_at->format('d M Y H:i') }}</td></tr>
                                @if($stockTransfer->approvedBy)
                                    <tr>
                                        <th>{{ $stockTransfer->isApproved() ? 'Approved By' : 'Rejected By' }}</th>
                                        <td>{{ $stockTransfer->approvedBy->name }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ $stockTransfer->isApproved() ? 'Approved At' : 'Rejected At' }}</th>
                                        <td>{{ $stockTransfer->approved_at?->format('d M Y H:i') }}</td>
                                    </tr>
                                @endif
                                @if($stockTransfer->notes)
                                    <tr><th>Notes</th><td>{{ $stockTransfer->notes }}</td></tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    @if($stockTransfer->isRejected() && $stockTransfer->rejection_reason)
                        <div class="alert alert-danger mb-0">
                            <strong>Rejection Reason:</strong> {{ $stockTransfer->rejection_reason }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Items Table -->
            <div class="card card-outline card-success">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-boxes mr-1"></i> Transfer Items</h3></div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Item</th>
                                <th>Unit</th>
                                <th>Stock at Transfer</th>
                                <th>Transfer Qty</th>
                                <th>Unit Price</th>
                                <th>Total Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stockTransfer->items as $i => $line)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>
                                    <strong>{{ $line->item->name }}</strong><br>
                                    <small class="text-muted">{{ $line->item->item_code }}</small>
                                </td>
                                <td>{{ $line->item->unit }}</td>
                                <td>{{ number_format($line->stock_before, 3) }}</td>
                                <td><strong>{{ number_format($line->quantity, 3) }}</strong></td>
                                <td>₹{{ number_format($line->unit_price, 2) }}</td>
                                <td>₹{{ number_format($line->quantity * $line->unit_price, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <th colspan="4" class="text-right">Total:</th>
                                <th>{{ number_format($stockTransfer->items->sum('quantity'), 3) }}</th>
                                <th></th>
                                <th>₹{{ number_format($stockTransfer->items->sum(fn($l) => $l->quantity * $l->unit_price), 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right: Actions -->
        <div class="col-md-4">

            {{-- Approve/Reject box for receiving company admin --}}
            @php
                $user = auth()->user();
                $companyId = $user->current_company_id;
                $canApprove = $stockTransfer->isPending() && (
                    $user->isSuperAdmin() ||
                    ($stockTransfer->to_company_id === $companyId && ($user->isAdmin() || $user->can('stock_transfers.approve')))
                );
            @endphp

            @if($canApprove)
            <div class="card card-outline card-warning">
                <div class="card-header bg-warning"><h3 class="card-title text-white"><i class="fas fa-gavel mr-1"></i> Approval Action</h3></div>
                <div class="card-body">
                    <p class="text-muted">Aap <strong>{{ $stockTransfer->toCompany->name }}</strong> ke admin hain. Ye transfer approve ya reject karein.</p>

                    <form action="{{ route('admin.stock-transfers.approve', $stockTransfer) }}" method="POST" class="mb-3">
                        @csrf
                        <button type="submit" class="btn btn-success btn-block"
                            onclick="return confirm('Transfer approve karein? Stock dono companies mein update ho jayega.')">
                            <i class="fas fa-check-circle"></i> Approve Transfer
                        </button>
                    </form>

                    <hr>
                    <form action="{{ route('admin.stock-transfers.reject', $stockTransfer) }}" method="POST" id="rejectForm">
                        @csrf
                        <div class="form-group">
                            <label>Rejection Reason <span class="text-danger">*</span></label>
                            <textarea name="rejection_reason" class="form-control" rows="3" placeholder="Rejection ka reason likhein..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger btn-block"
                            onclick="return confirm('Transfer reject karein?')">
                            <i class="fas fa-times-circle"></i> Reject Transfer
                        </button>
                    </form>
                </div>
            </div>
            @elseif($stockTransfer->isPending())
            <div class="card card-outline card-warning">
                <div class="card-body text-center text-muted">
                    <i class="fas fa-clock fa-3x mb-3 text-warning"></i>
                    <p>Ye transfer <strong>{{ $stockTransfer->toCompany->name }}</strong> ke admin ki approval ka wait kar raha hai.</p>
                </div>
            </div>
            @endif

            <div class="card">
                <div class="card-header"><h3 class="card-title">Transfer Summary</h3></div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><th>Total Items</th><td>{{ $stockTransfer->items->count() }}</td></tr>
                        <tr><th>Total Qty</th><td>{{ number_format($stockTransfer->items->sum('quantity'), 3) }}</td></tr>
                        <tr><th>Total Value</th><td>₹{{ number_format($stockTransfer->items->sum(fn($l) => $l->quantity * $l->unit_price), 2) }}</td></tr>
                        <tr><th>Status</th>
                            <td>
                                @if($stockTransfer->isPending()) <span class="badge badge-warning">Pending</span>
                                @elseif($stockTransfer->isApproved()) <span class="badge badge-success">Approved</span>
                                @else <span class="badge badge-danger">Rejected</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <a href="{{ route('admin.stock-transfers.index') }}" class="btn btn-secondary btn-block">
                <i class="fas fa-arrow-left"></i> Wapas List Par
            </a>
        </div>
    </div>

</div>
</section>
@endsection
