@extends('layouts.admin')
@section('title','Smart Purchase')

@section('content')
<style>
.smart-shell{background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;box-shadow:0 10px 28px rgba(15,23,42,.07)}
.smart-head{background:#172033;color:#fff;padding:18px 22px}.smart-head h4{margin:0;font-weight:800}
.smart-body{padding:20px 22px}.metric{border:1px solid #e5e7eb;border-radius:8px;padding:12px;background:#f8fafc}.metric span{display:block;font-size:11px;text-transform:uppercase;color:#64748b;font-weight:800}.metric b{font-size:20px;color:#111827}
.step-tabs{display:flex;gap:8px;margin-bottom:18px}.step-tabs button{border:1px solid #cbd5e1;background:#fff;border-radius:8px;padding:8px 14px;font-weight:700}.step-tabs button.active{background:#172033;color:#fff;border-color:#172033}
.sp-table th{font-size:12px;text-transform:uppercase;color:#475569;background:#f8fafc}.amount-preview{font-weight:800;color:#0f766e}
.service-shell{margin-top:18px;border:1px solid #dbeafe;border-radius:14px;overflow:hidden;background:linear-gradient(180deg,#eff6ff,#fff)}
.service-shell-head{padding:16px 18px;background:linear-gradient(135deg,#0f172a,#0f766e);color:#fff}
.service-shell-head h5{margin:0;font-weight:900}
.service-card-row{border:1px solid #e2e8f0;border-radius:12px;padding:14px;background:#fff;height:100%}
.service-pill{display:inline-flex;align-items:center;border-radius:999px;padding:5px 10px;font-weight:800;background:#ecfeff;color:#0f766e}
</style>

<div class="smart-shell">
    <div class="smart-head d-flex justify-content-between align-items-center">
        <div>
            <h4><i class="fas fa-brain mr-2"></i>Smart Purchase</h4>
            <small>Actual production/CRM raw-material consumption ko supplier-wise purchase estimates me plan karein.</small>
        </div>
        <a href="{{ route('admin.purchase-estimates.index') }}" class="btn btn-outline-light btn-sm"><i class="fas fa-list mr-1"></i>Purchase Estimates</a>
    </div>
    <div class="smart-body">
        <form method="GET" class="row align-items-end">
            <div class="col-md-2 form-group">
                <label>Period</label>
                <select name="preset" class="form-control" onchange="this.form.submit()">
                    <option value="30" @selected($period['preset']==='30')>Last 30 Days</option>
                    <option value="60" @selected($period['preset']==='60')>Last 60 Days</option>
                    <option value="90" @selected($period['preset']==='90')>Last 90 Days</option>
                    <option value="all" @selected($period['preset']==='all')>Over All</option>
                    <option value="custom" @selected($period['preset']==='custom')>Custom</option>
                </select>
            </div>
            <div class="col-md-2 form-group"><label>From</label><input type="date" name="from_date" class="form-control" value="{{ $period['from']->toDateString() }}"></div>
            <div class="col-md-2 form-group"><label>To</label><input type="date" name="to_date" class="form-control" value="{{ $period['to']->toDateString() }}"></div>
            <div class="col-md-2 form-group"><button class="btn btn-primary"><i class="fas fa-filter mr-1"></i>Filter</button></div>
        </form>

        <div class="row mb-3">
            <div class="col-md-4"><div class="metric"><span>Actual Consumed Valuation</span><b>Rs {{ number_format($analysis['raw_total'],2) }}</b></div></div>
            <div class="col-md-4"><div class="metric"><span>Raw Materials Used</span><b>{{ $analysis['materials']->count() }}</b></div></div>
            <div class="col-md-4"><div class="metric"><span>Period</span><b style="font-size:15px">{{ $period['from']->format('d M') }} – {{ $period['to']->format('d M Y') }}</b></div></div>
        </div>

        @php
            $serviceNameOptions = collect($serviceAnalysis ?? [])->pluck('service')->filter()->unique()->sort()->values();
        @endphp

        <div class="service-shell">
            <div class="service-shell-head d-flex justify-content-between align-items-center flex-wrap" style="gap:10px">
                <div>
                    <h5><i class="fas fa-concierge-bell mr-2"></i>Service Cost Intelligence</h5>
                    <small>Finished goods ke BOM se nikle service cost ko service name wise track karein.</small>
                </div>
                <div class="service-pill">Rs {{ number_format((float) $serviceAnalysis->sum('amount'),2) }}</div>
            </div>
            <div class="p-3 p-md-4">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="font-weight-bold">Filter by Service</label>
                        <select id="smartServiceFilter" class="form-control">
                            <option value="">All Services</option>
                            @foreach($serviceNameOptions as $serviceName)
                                <option value="{{ $serviceName }}">{{ $serviceName }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row">
                    @foreach($serviceAnalysis->groupBy('service')->sortByDesc(fn($rows) => $rows->sum('amount')) as $service => $rows)
                        @php
                            $amount = (float) $rows->sum('amount');
                            $qty = (float) $rows->sum('qty');
                            $pct = $serviceAnalysis->sum('amount') > 0 ? round($amount / $serviceAnalysis->sum('amount') * 100, 2) : 0;
                        @endphp
                        <div class="col-md-6 mb-3 smart-service-row" data-service="{{ $service }}">
                            <div class="service-card-row">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div><b>{{ $service }}</b><br><small class="text-muted">{{ number_format($qty,2) }} qty | {{ number_format($pct,2) }}%</small></div>
                                    <strong>Rs {{ number_format($amount,2) }}</strong>
                                </div>
                                <div style="height:10px;background:#e2e8f0;border-radius:999px;overflow:hidden">
                                    <div style="height:100%;width:{{ min(100, max(3, $pct)) }}%;background:linear-gradient(90deg,#0f766e,#0ea5e9)"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    @if($serviceAnalysis->isEmpty())
                        <div class="col-12"><div class="alert alert-info mb-0">Selected period me koi service cost nahi mila.</div></div>
                    @endif
                </div>
                <div class="table-responsive mt-2">
                    <table class="table table-sm table-bordered mb-0">
                        <thead><tr><th>Date</th><th>Invoice</th><th>Party</th><th>Item</th><th>Service</th><th>Qty</th><th>Unit Cost</th><th>Amount</th></tr></thead>
                        <tbody>
                        @forelse($serviceAnalysis as $row)
                            <tr class="smart-service-detail-row" data-service="{{ $row['service'] }}">
                                <td>{{ $row['invoice_date']?->format('d M Y') }}</td>
                                <td>{{ $row['invoice'] }}</td>
                                <td>{{ $row['party'] }}</td>
                                <td>{{ $row['item'] }}</td>
                                <td><b>{{ $row['service'] }}</b></td>
                                <td>{{ number_format((float) $row['qty'],2) }}</td>
                                <td>Rs {{ number_format((float) $row['unit_price'],2) }}</td>
                                <td><strong>Rs {{ number_format((float) $row['amount'],2) }}</strong></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted">No service details available for this range.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="step-tabs">
            <button type="button" class="active" data-step="1">1. Consumption</button>
            <button type="button" data-step="2">2. Qty & Supplier</button>
            <button type="button" data-step="3">3. Estimate Details</button>
        </div>

        <form method="POST" enctype="multipart/form-data" action="{{ route('admin.smart-purchases.store') }}" id="smartPurchaseForm">
            @csrf
            <input type="hidden" name="analysis_from" value="{{ $period['from']->toDateString() }}"><input type="hidden" name="analysis_to" value="{{ $period['to']->toDateString() }}">
            <div class="step-pane" data-pane="1">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover sp-table">
                        <thead><tr><th>Raw Material</th><th>Used In</th><th>Consumed Qty</th><th>Stock Qty</th><th>Rate</th><th>Valuation</th></tr></thead>
                        <tbody>
                        @forelse($analysis['materials'] as $row)
                            <tr>
                                <td><b>{{ $row['item']->name }}</b><br><small>{{ $row['item']->item_code }} | {{ $row['item']->unit }}</small></td>
                                <td>{{ $row['sources'] ?: '-' }}</td>
                                <td><button type="button" class="btn btn-link p-0 consumption-detail" data-target="#consumption{{ $row['item']->id }}">{{ number_format($row['required_qty'],3) }}</button></td>
                                <td>{{ number_format((float)$row['item']->current_stock,3) }}</td>
                                <td>Rs {{ number_format((float)$row['item']->purchase_price,2) }}</td>
                                <td>Rs {{ number_format($row['valuation'],2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">Selected period me production raw-material consumption nahi mila.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-primary next-step" @disabled($analysis['materials']->isEmpty())>Next <i class="fas fa-arrow-right ml-1"></i></button>
            </div>

            <div class="step-pane d-none" data-pane="2">
                <div class="table-responsive">
                    <table class="table table-bordered sp-table" id="purchasePlanTable">
                        <thead><tr><th>Raw Material</th><th>Stock</th><th>Consumed</th><th>Purchase Qty</th><th>Previous / Selected Supplier</th><th>Price</th><th>Tax %</th><th>Total</th></tr></thead>
                        <tbody>
                        @foreach($analysis['materials'] as $row)
                            <tr>
                                <td>
                                    <input type="hidden" name="item_id[]" value="{{ $row['item']->id }}">
                                    <b>{{ $row['item']->name }}</b><br><small>{{ $row['item']->unit }}</small>
                                </td>
                                <td>{{ number_format((float)$row['item']->current_stock,3) }}</td>
                                <td>{{ number_format($row['required_qty'],3) }}</td>
                                <td><input type="number" step="0.001" min="0.001" name="quantity[]" class="form-control plan-qty" value="{{ max(0, round($row['required_qty'] - (float)$row['item']->current_stock, 3)) ?: round($row['required_qty'],3) }}" required></td>
                                <td><div class="input-group"><select name="party_id[]" class="form-control supplier-select" required><option value="">Select supplier</option>@foreach($parties as $party)<option value="{{ $party->id }}" @selected((int)$row['previous_party_id']===(int)$party->id)>{{ $party->display_name }}</option>@endforeach</select><div class="input-group-append"><button type="button" class="btn btn-outline-success add-party" title="Add supplier"><i class="fas fa-plus"></i></button></div></div><small class="text-muted">Previous: {{ $row['previous_party_name'] ?: 'Not found' }}</small></td>
                                <td><input type="number" step="0.01" min="0" name="unit_price[]" class="form-control plan-price" value="{{ (float)$row['item']->purchase_price }}" required></td>
                                <td><input type="number" step="0.01" min="0" name="tax_percent[]" class="form-control plan-tax" value="{{ (float)$row['item']->purchase_gst_percent }}"></td>
                                <td class="amount-preview">Rs 0.00</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="text-right mb-3"><b>Purchase Total: Rs <span id="purchaseTotal">0.00</span></b></div>
                <button type="button" class="btn btn-outline-secondary prev-step"><i class="fas fa-arrow-left mr-1"></i>Back</button>
                <button type="button" class="btn btn-primary next-step">Next <i class="fas fa-arrow-right ml-1"></i></button>
            </div>

            <div class="step-pane d-none" data-pane="3">
                <div class="row">
                    <div class="col-md-3 form-group"><label>Estimate Date</label><input type="date" name="billing_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
                    <div class="col-md-3 form-group"><label>Reference No</label><input name="reference_no" class="form-control"></div>
                    <div class="col-md-3 form-group"><label>Cost Center</label><select name="cost_center_id" class="form-control"><option value="">Select</option>@foreach($costCenters as $cc)<option value="{{ $cc->id }}">{{ $cc->name }}</option>@endforeach</select></div>
                    <div class="col-md-3 form-group"><label>Sub Cost Center</label><select name="sub_cost_center_id" class="form-control"><option value="">Select</option>@foreach($subCostCenters as $scc)<option value="{{ $scc->id }}">{{ $scc->name }}</option>@endforeach</select></div>
                    <div class="col-md-6 form-group"><label>Attachment</label><input type="file" name="attachment" class="form-control"></div>
                    <div class="col-md-12 form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="2" placeholder="This will be marked as a Smart Purchase entry"></textarea></div>
                </div>
                @include('admin.partials.entry-visibility')
                <button type="button" class="btn btn-outline-secondary prev-step"><i class="fas fa-arrow-left mr-1"></i>Back</button>
                <button class="btn btn-success"><i class="fas fa-check mr-1"></i>Create Purchase Estimate(s)</button>
            </div>
        </form>
    </div>
</div>
<div class="card mt-4"><div class="card-header"><h5 class="mb-0"><i class="fas fa-history mr-2"></i>Smart Purchase Entries</h5></div><div class="card-body table-responsive"><table class="table table-hover"><thead><tr><th>Estimate</th><th>Date</th><th>Supplier</th><th>Period</th><th>Total</th><th>Status</th><th>Action</th></tr></thead><tbody>@forelse($smartEstimates as $entry)<tr><td>{{ $entry->estimate_no }}</td><td>{{ $entry->estimate_date?->format('d M Y') }}</td><td>{{ $entry->party?->display_name }}</td><td>{{ $entry->analysis_from?->format('d M') }} – {{ $entry->analysis_to?->format('d M Y') }}</td><td>Rs {{ number_format((float)$entry->grand_total,2) }}</td><td><span class="badge badge-{{ $entry->status==='transit'?'info':($entry->status==='converted'?'success':'secondary') }}">{{ ucfirst($entry->status) }}</span></td><td><a href="{{ route('admin.purchase-estimates.show',$entry) }}" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>@if($entry->status==='draft')<a href="{{ route('admin.purchase-estimates.edit',$entry) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>@endif</td></tr>@empty<tr><td colspan="7" class="text-center text-muted">No Smart Purchase entry yet.</td></tr>@endforelse</tbody></table></div></div>
@foreach($analysis['materials'] as $row)
<div class="modal fade" id="consumption{{ $row['item']->id }}"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">{{ $row['item']->name }} consumption details</h5><button class="close" data-dismiss="modal">&times;</button></div><div class="modal-body"><table class="table table-sm"><thead><tr><th>Date</th><th>Production Batch</th><th>Finished Item</th><th>Produced Qty</th><th>Raw Used</th><th>Value</th></tr></thead><tbody>@foreach($row['details'] as $detail)<tr><td>{{ $detail['date'] }}</td><td>{{ $detail['batch_no'] }}</td><td>{{ $detail['finished'] }}</td><td>{{ number_format($detail['finished_qty'],3) }}</td><td>{{ number_format($detail['consumed_qty'],3) }}</td><td>Rs {{ number_format($detail['valuation'],2) }}</td></tr>@endforeach</tbody><tfoot><tr><th colspan="4">Total</th><th>{{ number_format($row['required_qty'],3) }}</th><th>Rs {{ number_format($row['valuation'],2) }}</th></tr></tfoot></table><p><b>Current stock:</b> {{ number_format((float)$row['item']->current_stock,3) }} {{ $row['item']->unit }} &nbsp; <b>Stock valuation:</b> Rs {{ number_format((float)$row['item']->stock_value,2) }}</p></div></div></div></div>
@endforeach
<div class="modal fade" id="partyModal"><div class="modal-dialog modal-xl"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Add Supplier</h5><button class="close" data-dismiss="modal">&times;</button></div><form id="quickPartyForm"><div class="modal-body">
<input type="hidden" name="party_code" value="{{ $partyCode }}"><input type="hidden" name="party_type" value="supplier"><input type="hidden" name="status" value="active">
<ul class="nav nav-tabs mb-3"><li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#supplierIdentity">Identity</a></li><li class="nav-item"><a class="nav-link" data-toggle="tab" href="#supplierTax">Tax & Address</a></li><li class="nav-item"><a class="nav-link" data-toggle="tab" href="#supplierCredit">Credit & Bank</a></li></ul>
<div class="tab-content"><div class="tab-pane fade show active" id="supplierIdentity"><div class="row"><div class="col-md-4 form-group"><label>Display Name *</label><input name="display_name" class="form-control" required></div><div class="col-md-4 form-group"><label>Legal / Trade Name</label><input name="legal_name" class="form-control"></div><div class="col-md-4 form-group"><label>Contact Person</label><input name="contact_person" class="form-control"></div><div class="col-md-3 form-group"><label>Phone</label><input name="phone" class="form-control"></div><div class="col-md-3 form-group"><label>Alternate Phone</label><input name="alternate_phone" class="form-control"></div><div class="col-md-3 form-group"><label>WhatsApp</label><input name="whatsapp_number" class="form-control"></div><div class="col-md-3 form-group"><label>Email</label><input type="email" name="email" class="form-control"></div></div></div>
<div class="tab-pane fade" id="supplierTax"><div class="row"><div class="col-md-3 form-group"><label>Tax Type *</label><select name="tax_type" class="form-control" required><option value="registered">Registered</option><option value="composition">Composition</option><option value="unregistered" selected>Unregistered</option><option value="consumer">Consumer</option><option value="overseas">Overseas</option></select></div><div class="col-md-3 form-group"><label>GSTIN</label><input name="gstin" class="form-control"></div><div class="col-md-3 form-group"><label>PAN</label><input name="pan_number" class="form-control"></div><div class="col-md-3 form-group"><label>Place of Supply</label><input name="place_of_supply" class="form-control"></div><div class="col-md-3 form-group"><label>City</label><input name="city" class="form-control"></div><div class="col-md-3 form-group"><label>State</label><input name="state" class="form-control"></div><div class="col-md-3 form-group"><label>Pincode</label><input name="pincode" class="form-control"></div><div class="col-md-3 form-group"><label>Country</label><input name="country" value="India" class="form-control"></div><div class="col-md-6 form-group"><label>Billing Address</label><textarea name="billing_address" class="form-control"></textarea></div><div class="col-md-6 form-group"><label>Shipping Address</label><textarea name="shipping_address" class="form-control"></textarea></div></div></div>
<div class="tab-pane fade" id="supplierCredit"><div class="row"><div class="col-md-3 form-group"><label>Opening Balance</label><input type="number" step="0.01" min="0" name="opening_balance" value="0" class="form-control"></div><div class="col-md-3 form-group"><label>Balance Type *</label><select name="opening_balance_type" class="form-control" required><option value="payable">Payable</option><option value="receivable">Receivable</option></select></div><div class="col-md-3 form-group"><label>Opening Date</label><input type="date" name="opening_balance_date" value="{{ now()->toDateString() }}" class="form-control"></div><div class="col-md-3 form-group"><label>Credit Days</label><input type="number" min="0" name="credit_days" class="form-control"></div><div class="col-md-4 form-group"><label>Bank Name</label><input name="bank_name" class="form-control"></div><div class="col-md-4 form-group"><label>Account Number</label><input name="account_number" class="form-control"></div><div class="col-md-4 form-group"><label>IFSC Code</label><input name="ifsc_code" class="form-control"></div><div class="col-md-4 form-group"><label>UPI ID</label><input name="upi_id" class="form-control"></div><div class="col-md-8 form-group"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div></div></div></div><div id="partyError" class="text-danger"></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button><button class="btn btn-success">Save Supplier</button></div></form></div></div></div>
@endsection

@push('scripts')
<script>
(function(){
let step=1;
function showStep(n){step=n;$('.step-pane').addClass('d-none');$(`[data-pane="${n}"]`).removeClass('d-none');$('.step-tabs button').removeClass('active');$(`.step-tabs button[data-step="${n}"]`).addClass('active');calc();}
function calc(){let total=0;$('#purchasePlanTable tbody tr').each(function(){const r=$(this),q=+r.find('.plan-qty').val()||0,p=+r.find('.plan-price').val()||0,t=+r.find('.plan-tax').val()||0,amt=q*p*(1+t/100);total+=amt;r.find('.amount-preview').text('Rs '+amt.toFixed(2));});$('#purchaseTotal').text(total.toFixed(2));}
$('.next-step').on('click',()=>showStep(Math.min(3,step+1)));
$('.prev-step').on('click',()=>showStep(Math.max(1,step-1)));
$('.step-tabs button').on('click',function(){showStep(+$(this).data('step'));});
$('.consumption-detail').on('click',function(){$($(this).data('target')).modal('show')});
let partyTarget=null;$(document).on('click','.add-party',function(){partyTarget=$(this).closest('td').find('.supplier-select');$('#partyModal').modal('show')});
$('#quickPartyForm').on('submit',function(e){e.preventDefault();$('#partyError').text('');$.ajax({url:'{{ route('admin.smart-purchases.parties.store') }}',method:'POST',data:$(this).serialize()+'&_token={{ csrf_token() }}',headers:{Accept:'application/json'},success:function(p){$('.supplier-select').each(function(){if(!$(this).find(`option[value="${p.id}"]`).length)$(this).append(new Option(p.display_name,p.id))});partyTarget.val(p.id);$('#partyModal').modal('hide');$('#quickPartyForm')[0].reset()},error:function(xhr){$('#partyError').text(Object.values(xhr.responseJSON?.errors||{}).flat().join(' ')||'Supplier could not be saved.')}})});
$(document).on('input','.plan-qty,.plan-price,.plan-tax',calc);
$('#smartServiceFilter').on('change', function(){
    const value = $(this).val();
    $('.smart-service-row,.smart-service-detail-row').each(function(){
        const service = $(this).data('service');
        $(this).toggle(!value || service === value);
    });
});
calc();
})();
</script>
@endpush
