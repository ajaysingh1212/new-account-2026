@extends('layouts.admin')
@section('title','Sales Returns')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between"><h3 class="card-title m-0">Sales Returns</h3><a href="{{ route('admin.sales-returns.create') }}" class="btn btn-primary btn-sm">New Return</a></div>
    <div class="card-body table-responsive">
        <table id="returnsTable" class="table table-hover">
            <thead><tr><th>No</th><th>Date</th><th>Invoice</th><th>Party</th><th>Serials</th><th>Total</th><th>Action</th></tr></thead>
            <tbody>
            @foreach($returns as $return)
                @php
                    $serialUnits = collect($return->items ?? [])
                        ->flatMap(fn($line) => collect($line->selected_units ?? [])->filter(fn($unit) => is_array($unit)))
                        ->values();
                @endphp
                <tr>
                    <td>{{ $return->return_no }}</td>
                    <td>{{ $return->return_date?->format('d M Y') }}</td>
                    <td>{{ $return->invoice?->invoice_no }}</td>
                    <td>{{ $return->party?->display_name ?: 'Cash' }}</td>
                    <td>
                        @forelse($serialUnits->take(6) as $unit)
                            <span class="badge badge-info mr-1 mb-1">{{ $unit['serial_no'] ?? $unit['vts_sim'] ?? $unit['sku'] ?? $unit['key'] ?? 'Unit' }}</span>
                        @empty
                            <span class="text-muted">-</span>
                        @endforelse
                        @if($serialUnits->count() > 6)<small class="text-muted">+{{ $serialUnits->count() - 6 }} more</small>@endif
                    </td>
                    <td>Rs {{ number_format((float)$return->grand_total,2) }}</td>
                    <td><a href="{{ route('admin.sales-returns.show',$return) }}" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a> @can('sales.edit')<a href="{{ route('admin.sales-returns.edit',$return) }}" class="btn btn-warning btn-sm" title="Update returned serials"><i class="fas fa-barcode"></i></a>@endcan</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
@push('scripts')<script>$('#returnsTable').DataTable({pageLength:25, columnDefs:[{orderable:false, targets:6}]});</script>@endpush
