@extends('layouts.admin')
@section('title','CRM Revert')
@section('content')
<style>
.revert-shell{background:#fff;border:1px solid #e8edf5;border-radius:12px;box-shadow:0 14px 34px rgba(15,23,42,.08);overflow:hidden}.revert-head{background:#0f172a;color:#fff;padding:22px 24px}.revert-head h2{font-weight:850;margin:0}.revert-body{padding:22px 24px}.result-card{border:1px solid #e5e7eb;border-radius:10px;padding:16px;background:#f8fafc}.raw-pill{display:inline-flex;gap:6px;border:1px solid #dbeafe;background:#eff6ff;color:#1d4ed8;border-radius:999px;padding:5px 10px;margin:3px;font-size:12px;font-weight:700}.serial-box{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:10px}.serial-meta{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:10px}.serial-meta span{display:block;font-size:10px;text-transform:uppercase;color:#64748b;font-weight:800}.serial-meta b{color:#0f172a}
</style>
<div class="revert-shell">
    <div class="revert-head">
        <h2><i class="fas fa-undo-alt mr-2"></i>CRM Revert</h2>
        <small>Batch number se full batch revert karein, ya serial number se sirf selected finished goods unit revert karein.</small>
    </div>
    <div class="revert-body">
        <form method="GET" class="row align-items-end mb-4">
            <div class="col-md-3 form-group"><label>Search Type</label><select name="mode" class="form-control"><option value="batch" @selected($mode==='batch')>Batch Number</option><option value="serial" @selected($mode==='serial')>Serial / Buyer / VTS No.</option></select></div>
            <div class="col-md-6 form-group"><label>Number</label><input name="q" class="form-control" value="{{ $term }}" placeholder="Enter batch no or serial no"></div>
            <div class="col-md-3"><button class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i>Search</button></div>
        </form>

        @if($term && !$result)
            <div class="alert alert-warning">No matching CRM production record found.</div>
        @endif

        @if($result)
            @php($batch = $result['batch'])
            <div class="result-card">
                <div class="d-flex justify-content-between flex-wrap mb-3">
                    <div>
                        <h5 class="font-weight-bold mb-1">{{ $batch->finishedItem?->name }} | {{ $batch->batch_no }}</h5>
                        <div class="text-muted">Production date {{ $batch->production_date?->format('d M Y') }} | Batch qty {{ number_format((float)$batch->quantity,3) }}</div>
                    </div>
                    <span class="badge badge-{{ $batch->status === 'posted' ? 'success' : 'secondary' }}">{{ strtoupper($batch->status) }}</span>
                </div>

                @if($mode === 'serial')
                    <div class="serial-box mb-3">
                        <div class="serial-meta"><span>Serial</span><b>{{ $result['unit']['serial_no'] ?? '-' }}</b></div>
                        <div class="serial-meta"><span>Buyer Code</span><b>{{ $result['unit']['buyer_code'] ?? '-' }}</b></div>
                        <div class="serial-meta"><span>VTS/SIM</span><b>{{ $result['unit']['vts_sim'] ?? '-' }}</b></div>
                        <div class="serial-meta"><span>Status</span><b>{{ empty($result['unit']['reverted_at']) ? 'Available to revert' : 'Already reverted' }}</b></div>
                    </div>
                @endif

                <h6 class="font-weight-bold">Raw material that will be restored</h6>
                <div class="mb-3">
                    @forelse($result['raw'] as $raw)
                        <span class="raw-pill">{{ $raw['name'] }}: {{ number_format($raw['qty'],3) }} {{ $raw['unit'] }}</span>
                    @empty
                        <span class="text-muted">No BOM raw materials found.</span>
                    @endforelse
                </div>

                <form method="POST" action="{{ route('admin.production-reverts.store') }}" onsubmit="return confirm('Confirm revert? Stock will be adjusted immediately.');">
                    @csrf
                    <input type="hidden" name="mode" value="{{ $mode }}">
                    <input type="hidden" name="q" value="{{ $term }}">
                    <button class="btn btn-danger" @disabled($batch->status !== 'posted' || ($mode === 'serial' && !empty($result['unit']['reverted_at'])))><i class="fas fa-undo mr-1"></i>{{ $mode === 'serial' ? 'Revert This Serial' : 'Revert Full Batch' }}</button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
