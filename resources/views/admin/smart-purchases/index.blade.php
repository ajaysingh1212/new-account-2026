@extends('layouts.admin')
@section('title','Smart Purchase')

@section('content')
<style>
.smart-shell{background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;box-shadow:0 10px 28px rgba(15,23,42,.07)}
.smart-head{background:#172033;color:#fff;padding:18px 22px}.smart-head h4{margin:0;font-weight:800}
.smart-body{padding:20px 22px}.metric{border:1px solid #e5e7eb;border-radius:8px;padding:12px;background:#f8fafc}.metric span{display:block;font-size:11px;text-transform:uppercase;color:#64748b;font-weight:800}.metric b{font-size:20px;color:#111827}
.step-tabs{display:flex;gap:8px;margin-bottom:18px}.step-tabs button{border:1px solid #cbd5e1;background:#fff;border-radius:8px;padding:8px 14px;font-weight:700}.step-tabs button.active{background:#172033;color:#fff;border-color:#172033}
.sp-table th{font-size:12px;text-transform:uppercase;color:#475569;background:#f8fafc}.amount-preview{font-weight:800;color:#0f766e}
</style>

<div class="smart-shell">
    <div class="smart-head d-flex justify-content-between align-items-center">
        <div>
            <h4><i class="fas fa-brain mr-2"></i>Smart Purchase</h4>
            <small>Sales period ke finished goods ko BOM raw-material demand me convert karke purchase post karein.</small>
        </div>
        <a href="{{ route('admin.purchases.index') }}" class="btn btn-outline-light btn-sm"><i class="fas fa-list mr-1"></i>Purchase Bills</a>
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
            <div class="col-md-4"><div class="metric"><span>Total Invoice Valuation</span><b>Rs {{ number_format($analysis['invoice_total'],2) }}</b></div></div>
            <div class="col-md-4"><div class="metric"><span>Total Raw Material Valuation</span><b>Rs {{ number_format($analysis['raw_total'],2) }}</b></div></div>
            <div class="col-md-4"><div class="metric"><span>Difference</span><b>Rs {{ number_format($analysis['difference'],2) }}</b></div></div>
        </div>

        <div class="step-tabs">
            <button type="button" class="active" data-step="1">1. BOM Demand</button>
            <button type="button" data-step="2">2. Purchase Qty</button>
            <button type="button" data-step="3">3. Party & Post</button>
        </div>

        <form method="POST" action="{{ route('admin.smart-purchases.store') }}" id="smartPurchaseForm">
            @csrf
            <div class="step-pane" data-pane="1">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover sp-table">
                        <thead><tr><th>Raw Material</th><th>Sold BOM Sources</th><th>Required Qty</th><th>Stock Qty</th><th>Default Price</th><th>Valuation</th></tr></thead>
                        <tbody>
                        @forelse($analysis['materials'] as $row)
                            <tr>
                                <td><b>{{ $row['item']->name }}</b><br><small>{{ $row['item']->item_code }} | {{ $row['item']->unit }}</small></td>
                                <td>{{ $row['sources'] ?: '-' }}</td>
                                <td>{{ number_format($row['required_qty'],3) }}</td>
                                <td>{{ number_format((float)$row['item']->current_stock,3) }}</td>
                                <td>Rs {{ number_format((float)$row['item']->purchase_price,2) }}</td>
                                <td>Rs {{ number_format($row['valuation'],2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">Selected period me BOM-enabled sold item nahi mila.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-primary next-step" @disabled($analysis['materials']->isEmpty())>Next <i class="fas fa-arrow-right ml-1"></i></button>
            </div>

            <div class="step-pane d-none" data-pane="2">
                <div class="table-responsive">
                    <table class="table table-bordered sp-table" id="purchasePlanTable">
                        <thead><tr><th>Raw Material</th><th>Stock Qty</th><th>Required Qty</th><th>Purchase Qty</th><th>Price</th><th>Tax %</th><th>Total</th></tr></thead>
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
                    <div class="col-md-2 form-group"><label>Type</label><select name="purchase_type" class="form-control"><option value="credit">Credit</option><option value="cash">Cash</option></select></div>
                    <div class="col-md-4 form-group"><label>Party</label><select name="party_id" class="form-control select2"><option value="">Cash/No Party</option>@foreach($parties as $party)<option value="{{ $party->id }}">{{ $party->display_name }} | {{ $party->phone }}</option>@endforeach</select></div>
                    <div class="col-md-2 form-group"><label>PO / Invoice No</label><input name="invoice_no" class="form-control" value="{{ $invoiceNo }}"></div>
                    <div class="col-md-2 form-group"><label>Billing Date</label><input type="date" name="billing_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
                    <div class="col-md-2 form-group"><label>Supplier Bill No</label><input name="supplier_bill_no" class="form-control"></div>
                    <div class="col-md-3 form-group"><label>Purchase Bill Date</label><input type="date" name="purchase_bill_date" class="form-control"></div>
                    <div class="col-md-3 form-group"><label>Reference No</label><input name="reference_no" class="form-control"></div>
                    <div class="col-md-3 form-group"><label>Cost Center</label><select name="cost_center_id" class="form-control"><option value="">Select</option>@foreach($costCenters as $cc)<option value="{{ $cc->id }}">{{ $cc->name }}</option>@endforeach</select></div>
                    <div class="col-md-3 form-group"><label>Sub Cost Center</label><select name="sub_cost_center_id" class="form-control"><option value="">Select</option>@foreach($subCostCenters as $scc)<option value="{{ $scc->id }}">{{ $scc->name }}</option>@endforeach</select></div>
                    <div class="col-md-12 form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                </div>
                @include('admin.partials.entry-visibility')
                <button type="button" class="btn btn-outline-secondary prev-step"><i class="fas fa-arrow-left mr-1"></i>Back</button>
                <button class="btn btn-success"><i class="fas fa-check mr-1"></i>Post Smart Purchase</button>
            </div>
        </form>
    </div>
</div>
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
$(document).on('input','.plan-qty,.plan-price,.plan-tax',calc);
calc();
})();
</script>
@endpush
