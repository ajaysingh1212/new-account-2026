@extends('layouts.admin')
@section('title','Production Batch')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0">Production {{ $batch->batch_no }}</h3>
        <div>
            @can('production.create')
                @if(($batch->status ?? 'posted') !== 'reverted')
                    <a href="{{ route('admin.production-batches.edit', $batch) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit mr-1"></i>Edit</a>
                    <form action="{{ route('admin.production-batches.revert', $batch) }}" method="POST" class="d-inline" onsubmit="return confirm('Revert this production batch? Finished goods stock will be removed and raw material stock will be restored.');">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-undo mr-1"></i>Revert</button>
                    </form>
                @endif
            @endcan
            <a href="{{ route('admin.production-batches.index') }}" class="btn btn-secondary btn-sm">Back</a>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3"><b>Finished Item</b><br>{{ $batch->finishedItem?->name }}</div>
            <div class="col-md-2"><b>Date</b><br>{{ $batch->production_date?->format('d M Y') }}</div>
            <div class="col-md-2"><b>Quantity</b><br>{{ $batch->quantity }}</div>
            <div class="col-md-2"><b>Raw Cost</b><br>Rs {{ number_format((float)$batch->raw_material_cost,2) }}</div>
            <div class="col-md-2"><b>Cost/Unit</b><br>Rs {{ number_format((float)$batch->cost_per_unit,2) }}</div>
            <div class="col-md-1">
                <b>Status</b><br>
                <span class="badge badge-{{ $batch->status === 'reverted' ? 'secondary' : 'success' }}">{{ ucfirst($batch->status ?? 'posted') }}</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead><tr><th>#</th><th>Buyer Code</th><th>Serial No.</th><th>Batch No. (Purchase)</th><th>VTS/SIM No.</th><th>Sale Price</th><th>GST</th><th>Warehouse</th><th>Notes</th></tr></thead>
                <tbody>
                @foreach(($batch->status === 'reverted' ? collect() : collect($batch->units_data ?? [])->filter(fn($unit) => empty($unit['reverted_at']))) as $i => $unit)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $unit['buyer_code'] ?? '-' }}</td>
                        <td>{{ $unit['serial_no'] ?? '-' }}</td>
                        <td>{{ $unit['batch_no'] ?? '-' }}</td>
                        <td>{{ $unit['vts_sim'] ?? '-' }}</td>
                        <td>Rs {{ number_format((float)($unit['sale_price'] ?? 0),2) }}</td>
                        <td>{{ $unit['gst'] ?? 0 }}%</td>
                        <td>{{ $unit['warehouse'] ?? '-' }}</td>
                        <td>{{ $unit['notes'] ?? '-' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($batch->notes)<p class="mt-3"><b>Notes:</b> {{ $batch->notes }}</p>@endif
    </div>
</div>
@include('admin.partials.update-history', ['auditLogs' => $auditLogs])
@endsection
