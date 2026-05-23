@extends('layouts.admin')

@section('title', 'New Stock Transfer')

@push('styles')
<style>
#items-table tbody tr td { vertical-align: middle; }
.qty-input { max-width: 110px; }
.badge-remaining { font-size: 0.85em; }
.select2-container { width: 100% !important; }
.item-row-remove { cursor:pointer; }
</style>
@endpush

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
         
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.stock-transfers.index') }}">Stock Transfers</a></li>
                    <li class="breadcrumb-item active">New</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
<div class="container-fluid">

    @if(session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('admin.stock-transfers.store') }}" method="POST" id="transferForm">
        @csrf

        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-exchange-alt mr-1"></i> Transfer Details</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Transfer Date <span class="text-danger">*</span></label>
                            <input type="date" name="transfer_date" class="form-control" value="{{ old('transfer_date', $today) }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Transfer To Company <span class="text-danger">*</span></label>
                            @if($toCompanies->isEmpty())
                                <input type="text" class="form-control" value="Koi merged company nahi hai" disabled>
                                <small class="text-danger">SuperAdmin se company merge karwayein pehle.</small>
                            @else
                                <select name="to_company_id" class="form-control select2" required>
                                    <option value="">-- Company Select Karein --</option>
                                    @foreach($toCompanies as $c)
                                        <option value="{{ $c->id }}" {{ old('to_company_id') == $c->id ? 'selected' : '' }}>
                                            {{ $c->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Notes</label>
                            <input type="text" name="notes" class="form-control" value="{{ old('notes') }}" placeholder="Optional notes...">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Section -->
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-boxes mr-1"></i> Transfer Items</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-success btn-sm" id="addItemBtn" @if($finishedItems->isEmpty()) disabled @endif>
                        <i class="fas fa-plus"></i> Item Add Karein
                    </button>
                </div>
            </div>
            <div class="card-body p-0">

                <!-- Item Dropdown (hidden by default, shown on Add click) -->
                <div class="px-3 pt-3" id="itemSelectBox" style="display:none;">
                    <div class="row align-items-end">
                        <div class="col-md-8">
                            <label>Finished Good Select Karein</label>
                            <select id="itemDropdown" class="form-control select2-item">
                                <option value="">-- Item Select Karein --</option>
                                @foreach($finishedItems as $item)
                                    <option value="{{ $item->id }}"
                                        data-name="{{ $item->name }}"
                                        data-code="{{ $item->item_code }}"
                                        data-unit="{{ $item->unit }}"
                                        data-stock="{{ $item->current_stock }}"
                                        data-price="{{ $item->sale_price ?? 0 }}">
                                        {{ $item->name }} ({{ $item->item_code }}) — Stock: {{ number_format($item->current_stock, 2) }} {{ $item->unit }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-primary w-100" id="confirmAddItem">
                                <i class="fas fa-check"></i> Add
                            </button>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-secondary w-100" id="cancelAddItem">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </div>
                    <hr>
                </div>

                @if($finishedItems->isEmpty())
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-box-open fa-2x mb-2"></i><br>
                        Koi finished good available nahi hai current stock mein.
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered mb-0" id="items-table">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:35%">Item</th>
                                <th style="width:12%">Unit</th>
                                <th style="width:15%">Available Stock</th>
                                <th style="width:18%">Transfer Qty</th>
                                <th style="width:12%">Remaining</th>
                                <th style="width:8%">Remove</th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <tr id="emptyRow">
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-arrow-up text-success"></i> Upar se item add karein
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="row">
                    <div class="col-md-6">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i>
                            Transfer tab hoga jab receiving company ka admin approve karega.
                        </small>
                    </div>
                    <div class="col-md-6 text-right">
                        <span class="mr-3"><strong>Total Items: </strong><span id="totalItems">0</span></span>
                        <a href="{{ route('admin.stock-transfers.index') }}" class="btn btn-secondary mr-2">Cancel</a>
                        <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                            <i class="fas fa-paper-plane"></i> Transfer Request Bhejein
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </form>
</div>
</section>
@endsection

@push('scripts')
<script>
$(function () {
    // Track added items: { id: { name, unit, stock, price } }
    let addedItems = {};

    // Init Select2
    $('.select2').select2({ placeholder: '-- Select --', allowClear: true });
    initItemSelect2();

    function initItemSelect2() {
        $('#itemDropdown').select2({
            placeholder: '-- Item Select Karein --',
            allowClear: true,
            dropdownParent: $('#itemSelectBox'),
        });
    }

    // Show item select box
    $('#addItemBtn').on('click', function () {
        $('#itemSelectBox').slideDown(150);
        refreshDropdownOptions();
    });

    $('#cancelAddItem').on('click', function () {
        $('#itemSelectBox').slideUp(150);
        $('#itemDropdown').val(null).trigger('change');
    });

    // Refresh dropdown — hide already added items
    function refreshDropdownOptions() {
        $('#itemDropdown option').each(function () {
            const val = $(this).val();
            if (val && addedItems[val]) {
                $(this).prop('disabled', true);
            } else {
                $(this).prop('disabled', false);
            }
        });
        $('#itemDropdown').val(null).trigger('change');
    }

    // Add item to table
    $('#confirmAddItem').on('click', function () {
        const sel = $('#itemDropdown');
        const id  = sel.val();
        if (!id) { alert('Pehle item select karein.'); return; }

        const opt   = sel.find('option:selected');
        const name  = opt.data('name');
        const code  = opt.data('code');
        const unit  = opt.data('unit');
        const stock = parseFloat(opt.data('stock'));
        const price = parseFloat(opt.data('price')) || 0;

        addedItems[id] = { name, code, unit, stock, price };
        addRow(id, name, code, unit, stock, price);

        sel.val(null).trigger('change');
        $('#itemSelectBox').slideUp(150);
        updateTotals();
    });

    function addRow(id, name, code, unit, stock, price) {
        $('#emptyRow').hide();

        const idx = Object.keys(addedItems).length - 1;
        const row = `
        <tr id="row_${id}" data-id="${id}" data-max="${stock}">
            <td>
                <strong>${name}</strong><br>
                <small class="text-muted">${code}</small>
                <input type="hidden" name="items[${idx}][item_id]" value="${id}">
            </td>
            <td>${unit}</td>
            <td class="text-center">
                <span class="badge badge-info">${formatNum(stock)}</span>
            </td>
            <td>
                <input type="number"
                    name="items[${idx}][quantity]"
                    class="form-control qty-input transfer-qty"
                    min="0.001"
                    max="${stock}"
                    step="0.001"
                    value=""
                    placeholder="0"
                    data-id="${id}"
                    data-max="${stock}">
                <small class="text-danger qty-err-${id}" style="display:none">Max: ${formatNum(stock)}</small>
            </td>
            <td class="text-center">
                <span class="badge badge-warning badge-remaining remaining_${id}">—</span>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger item-row-remove" data-id="${id}">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>`;
        $('#itemsBody').append(row);
    }

    // Qty change -> update remaining
    $(document).on('input', '.transfer-qty', function () {
        const id    = $(this).data('id');
        const max   = parseFloat($(this).data('max'));
        let val     = parseFloat($(this).val()) || 0;

        if (val > max) {
            $(this).val(max);
            val = max;
            $(`.qty-err-${id}`).show();
        } else {
            $(`.qty-err-${id}`).hide();
        }
        const remaining = max - val;
        $(`.remaining_${id}`).text(formatNum(remaining))
            .removeClass('badge-warning badge-success badge-danger')
            .addClass(remaining < 0 ? 'badge-danger' : remaining === 0 ? 'badge-secondary' : 'badge-success');

        updateTotals();
    });

    // Remove row
    $(document).on('click', '.item-row-remove', function () {
        const id = $(this).data('id');
        $(`#row_${id}`).remove();
        delete addedItems[id];
        reindexNames();
        updateTotals();
        if (Object.keys(addedItems).length === 0) {
            $('#emptyRow').show();
        }
    });

    // Re-index name attributes after removal
    function reindexNames() {
        let i = 0;
        $('#itemsBody tr[data-id]').each(function () {
            $(this).find('[name*="items["]').each(function () {
                const n = $(this).attr('name').replace(/items\[\d+\]/, `items[${i}]`);
                $(this).attr('name', n);
            });
            i++;
        });
    }

    function updateTotals() {
        const count = Object.keys(addedItems).length;
        $('#totalItems').text(count);
        $('#submitBtn').prop('disabled', count === 0);
    }

    function formatNum(n) {
        return parseFloat(n).toLocaleString('en-IN', { maximumFractionDigits: 3 });
    }

    // Form submit validation
    $('#transferForm').on('submit', function (e) {
        let valid = true;
        let hasQty = false;
        $('.transfer-qty').each(function () {
            const val = parseFloat($(this).val()) || 0;
            const max = parseFloat($(this).data('max'));
            if (val <= 0) { valid = false; }
            if (val > max) { valid = false; }
            if (val > 0) hasQty = true;
        });
        if (!hasQty || !valid) {
            e.preventDefault();
            alert('Har item ki valid quantity bharein (0 se jyada aur available stock se kam).');
        }
    });
});
</script>
@endpush
