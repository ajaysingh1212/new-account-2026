@extends('layouts.admin')
@section('title','Replacement Detail')

@push('styles')
<style>
.serial-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}.serial-option{position:relative;border:1px solid #dbe4ee;border-radius:8px;padding:12px;background:#f8fafc;cursor:pointer;display:block}.serial-option:hover{border-color:#0ea5e9;background:#f0f9ff}.serial-option input{position:absolute;right:12px;top:12px}.serial-option.active{border-color:#0ea5e9;box-shadow:0 0 0 3px rgba(14,165,233,.16);background:#eff6ff}.price-pill{display:inline-block;border-radius:999px;background:#eef6ff;color:#1d4ed8;padding:4px 10px;font-weight:800;font-size:12px}@media(max-width:768px){.serial-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div class="card"><div class="card-header d-flex justify-content-between align-items-center"><h3 class="card-title m-0">{{ $replacement->replacement_no }}</h3><div><a href="{{ route('admin.replacements.index') }}" class="btn btn-light btn-sm">Back</a>@if(in_array($replacement->status, ['pending','rejected'], true))<a href="{{ route('admin.replacements.edit', $replacement) }}" class="btn btn-warning btn-sm ml-2">Edit</a><form action="{{ route('admin.replacements.destroy', $replacement) }}" method="POST" onsubmit="return confirm('Delete this replacement request?');" style="display:inline"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE"><button class="btn btn-danger btn-sm ml-2">Delete</button></form>@endif</div></div>
<div class="card-body">
    <div class="row">
        <div class="col-md-4"><h5>Party / Customer</h5><p><b>{{ $replacement->party?->display_name ?: $replacement->customer_name }}</b><br>{{ $replacement->customer_phone }}<br>{{ $replacement->customer_email }}<br>{{ $replacement->customer_address }}</p></div>
        <div class="col-md-4"><h5>Sold Item</h5><p><b>{{ $replacement->item?->name }}</b><br>SKU: {{ $replacement->item?->sku ?: '-' }}<br>Invoice: {{ $replacement->invoice?->invoice_no }} | Selling date: {{ $replacement->invoice?->billing_date?->format('d M Y') }}<br>Sale price: Rs {{ number_format((float)($replacement->invoiceItem?->unit_price ?? 0),2) }} | Line: Rs {{ number_format((float)($replacement->invoiceItem?->line_total ?? 0),2) }}<br>Current selling price: Rs {{ number_format((float)($replacement->item?->sale_price ?? 0),2) }}</p></div>
        <div class="col-md-4"><h5>Status</h5><p><span class="badge badge-primary">{{ ucfirst($replacement->status) }}</span><br>Reason: {{ $replacement->request_reason }}</p></div>
    </div>
    <h5>Returned Serial</h5>
    <p>{{ $replacement->returned_unit['serial_no'] ?? '-' }} | VTS: {{ $replacement->returned_unit['vts_sim'] ?? '-' }} | Buyer: {{ $replacement->returned_unit['buyer_code'] ?? '-' }} | Key: {{ $replacement->returned_unit['key'] ?? '-' }} | Production: {{ $replacement->returned_unit['production_date'] ?? '-' }} | Batch: {{ $replacement->returned_unit['production_batch_no'] ?? $replacement->returned_unit['batch_no'] ?? '-' }}</p>
    <div class="row mb-3">@foreach($replacement->product_images ?? [] as $label => $path)<div class="col-md-3"><strong>{{ ucwords(str_replace('_',' ', $label)) }}</strong><img src="{{ asset('storage/'.$path) }}" class="img-fluid rounded border mt-1"></div>@endforeach</div>

    @if($replacement->status === 'pending')
        <div class="row">
            <div class="col-md-6"><div class="card bg-light"><div class="card-body"><h5>Approve</h5><form method="POST" action="{{ route('admin.replacements.approve', $replacement) }}" enctype="multipart/form-data">@csrf<textarea name="admin_reason" class="form-control mb-2" placeholder="Approval note optional"></textarea><input type="file" name="admin_attachment" class="form-control mb-2"><button class="btn btn-success">Approve</button></form></div></div></div>
            <div class="col-md-6"><div class="card bg-light"><div class="card-body"><h5>Reject</h5><form method="POST" action="{{ route('admin.replacements.reject', $replacement) }}" enctype="multipart/form-data">@csrf<textarea name="admin_reason" class="form-control mb-2" required placeholder="Reject reason"></textarea><input type="file" name="admin_attachment" class="form-control mb-2"><button class="btn btn-danger">Reject</button></form></div></div></div>
        </div>
    @endif

    @if($replacement->status === 'approved')
        <div class="card bg-light"><div class="card-body"><h5>Issue Replacement Stock</h5>
            @if(empty($availableUnits))
                <div class="alert alert-warning">Same item current stock me available nahi hai.</div>
            @else
                <form method="POST" action="{{ route('admin.replacements.issue', $replacement) }}" enctype="multipart/form-data">@csrf
                    <div class="alert alert-info">Same item ke available serial numbers neeche hain. Ek serial select karein; select karte hi production/detail popup khulega.</div>
                    <div class="serial-grid mb-3">
                        @foreach($availableUnits as $index => $unit)
                            @php $serial = $unit['serial_no'] ?? $unit['vts_sim'] ?? $unit['buyer_code'] ?? $unit['key'] ?? 'Unit'; @endphp
                            <label class="serial-option" data-toggle="modal" data-target="#unitModal{{ $index }}">
                                <input type="radio" name="issued_unit" value='@json($unit)' required>
                                <strong>{{ $serial }}</strong><br>
                                <small>Batch: {{ $unit['production_batch_no'] ?? $unit['batch_no'] ?? '-' }} | Date: {{ $unit['production_date'] ?? $unit['last_movement_date'] ?? '-' }}</small><br>
                                <span class="price-pill">SKU {{ $unit['sku'] ?? $replacement->item?->sku ?? '-' }}</span>
                            </label>
                            <div class="modal fade" id="unitModal{{ $index }}" tabindex="-1">
                                <div class="modal-dialog"><div class="modal-content">
                                    <div class="modal-header"><h5 class="modal-title">Selected Serial Detail</h5><button class="close" data-dismiss="modal">&times;</button></div>
                                    <div class="modal-body">
                                        <table class="table table-sm">
                                            <tr><th>Serial</th><td>{{ $unit['serial_no'] ?? '-' }}</td></tr>
                                            <tr><th>VTS / SIM</th><td>{{ $unit['vts_sim'] ?? '-' }}</td></tr>
                                            <tr><th>Buyer Code</th><td>{{ $unit['buyer_code'] ?? '-' }}</td></tr>
                                            <tr><th>SKU</th><td>{{ $unit['sku'] ?? $replacement->item?->sku ?? '-' }}</td></tr>
                                            <tr><th>Production Batch</th><td>{{ $unit['production_batch_no'] ?? $unit['batch_no'] ?? '-' }}</td></tr>
                                            <tr><th>Production Date</th><td>{{ $unit['production_date'] ?? '-' }}</td></tr>
                                            <tr><th>Last Stock Ref</th><td>{{ $unit['last_reference_no'] ?? '-' }}</td></tr>
                                        </table>
                                    </div>
                                </div></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="form-group"><label>Narration</label><textarea name="issue_narration" class="form-control" required></textarea></div>
                    <div class="form-group"><label>Attachment</label><input type="file" name="issue_attachment" class="form-control"></div>
                    <button class="btn btn-primary">Issue Replacement</button>
                </form>
            @endif
        </div></div>
    @endif

    @if($replacement->status === 'completed')
        <div class="alert alert-success"><b>Issued:</b> {{ $replacement->issued_unit['serial_no'] ?? $replacement->issued_unit['vts_sim'] ?? '-' }} on {{ $replacement->issued_at?->format('d M Y h:i A') }}<br>{{ $replacement->issue_narration }}</div>
    @endif

    @if($replacement->admin_reason)<div class="alert alert-secondary"><b>Admin reason:</b> {{ $replacement->admin_reason }}</div>@endif
</div></div>
@endsection

@push('scripts')
<script>
$('.serial-option input[type=radio]').on('change', function(){
    $('.serial-option').removeClass('active');
    $(this).closest('.serial-option').addClass('active');
});
$('.serial-option').on('click', function(){
    $(this).find('input[type=radio]').prop('checked', true).trigger('change');
});
</script>
@endpush
