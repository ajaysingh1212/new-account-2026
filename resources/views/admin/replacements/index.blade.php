@extends('layouts.admin')
@section('title','Replacements')

@push('styles')
<style>
.replacement-head{background:#111827;color:#fff;border-radius:8px;padding:20px;display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}.replacement-panel{position:fixed;top:0;right:-460px;width:440px;max-width:100%;height:100vh;background:#fff;z-index:1050;box-shadow:-18px 0 40px rgba(15,23,42,.18);transition:.25s;padding:20px;overflow:auto}.replacement-panel.open{right:0}.received-row{border:1px solid #e5e7eb;border-radius:8px;padding:12px;margin-bottom:10px;cursor:pointer}.status-pill{border-radius:999px;padding:4px 10px;font-weight:700;font-size:12px}.status-pending{background:#fff7ed;color:#c2410c}.status-approved{background:#eff6ff;color:#1d4ed8}.status-completed{background:#ecfdf5;color:#047857}.status-rejected{background:#fef2f2;color:#b91c1c}
</style>
@endpush

@section('content')
<div class="replacement-head">
    <div><h2 class="m-0"><i class="fas fa-sync-alt mr-2"></i>Replacement</h2><small>Serial trace, approval, issue stock, and received replacement tracking.</small></div>
    <div>
        <button type="button" id="openReceived" class="btn btn-outline-light btn-sm"><i class="fas fa-box-open mr-1"></i>Replacement Items</button>
        <a href="{{ route('admin.replacements.create') }}" class="btn btn-warning btn-sm"><i class="fas fa-plus mr-1"></i>New Replacement</a>
    </div>
</div>

<div class="card"><div class="card-body table-responsive">
    <table id="replacementTable" class="table table-hover">
        <thead><tr><th>No</th><th>Date</th><th>Party</th><th>Item</th><th>Returned Serial</th><th>Issued Serial</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        @foreach($replacements as $replacement)
            <tr>
                <td><strong>{{ $replacement->replacement_no }}</strong></td>
                <td>{{ $replacement->request_date?->format('d M Y') }}</td>
                <td>{{ $replacement->party?->display_name ?: $replacement->customer_name }}</td>
                <td>{{ $replacement->item?->name }}<br><small>{{ $replacement->item?->sku ?: $replacement->item?->item_code }}</small></td>
                <td>{{ $replacement->returned_unit['serial_no'] ?? $replacement->returned_unit['vts_sim'] ?? $replacement->returned_unit['buyer_code'] ?? '-' }}</td>
                <td>{{ $replacement->issued_unit['serial_no'] ?? $replacement->issued_unit['vts_sim'] ?? '-' }}</td>
                <td><span class="status-pill status-{{ $replacement->status }}">{{ ucfirst($replacement->status) }}</span></td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <a href="{{ route('admin.replacements.show', $replacement) }}" class="btn btn-info"><i class="fas fa-eye"></i></a>
                        @if(in_array($replacement->status, ['pending','rejected'], true))
                            <a href="{{ route('admin.replacements.edit', $replacement) }}" class="btn btn-warning"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('admin.replacements.destroy', $replacement) }}" method="POST" onsubmit="return confirm('Delete this replacement request?');" style="display:inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div></div>

<div id="receivedPanel" class="replacement-panel">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="m-0">Replacement Received Items</h4>
        <button type="button" id="closeReceived" class="btn btn-light btn-sm"><i class="fas fa-times"></i></button>
    </div>
    @forelse($received as $group)
        <div class="received-row" data-toggle="modal" data-target="#receivedModal{{ $group['item']->id }}">
            <strong>{{ $group['item']->name }}</strong><br>
            <span class="text-muted">{{ $group['item']->sku ?: $group['item']->item_code }}</span>
            <span class="badge badge-primary float-right">{{ $group['quantity'] }} PCS</span>
        </div>
        <div class="modal fade" id="receivedModal{{ $group['item']->id }}" tabindex="-1">
            <div class="modal-dialog modal-xl"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">{{ $group['item']->name }} Replacement Detail</h5><button class="close" data-dismiss="modal">&times;</button></div>
                <div class="modal-body table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Date</th><th>Party</th><th>Returned Serial</th><th>Issued Serial</th><th>Invoice</th><th>Reason</th><th>Status</th></tr></thead>
                        <tbody>@foreach($group['rows'] as $row)<tr><td>{{ $row->request_date?->format('d M Y') }}</td><td>{{ $row->party?->display_name ?: $row->customer_name }}<br><small>{{ $row->customer_phone }}</small></td><td>{{ $row->returned_unit['serial_no'] ?? $row->returned_unit['vts_sim'] ?? '-' }}</td><td>{{ $row->issued_unit['serial_no'] ?? $row->issued_unit['vts_sim'] ?? '-' }}</td><td>{{ $row->invoice?->invoice_no }}</td><td>{{ $row->request_reason }}</td><td>{{ ucfirst($row->status) }}</td></tr>@endforeach</tbody>
                    </table>
                </div>
            </div></div>
        </div>
    @empty
        <div class="text-muted">No approved replacement items yet.</div>
    @endforelse
</div>
@endsection

@push('scripts')
<script>
$('#replacementTable').DataTable({pageLength:25, order:[[1,'desc']]});
$('#openReceived').on('click', function(){ $('#receivedPanel').addClass('open'); });
$('#closeReceived').on('click', function(){ $('#receivedPanel').removeClass('open'); });
</script>
@endpush
