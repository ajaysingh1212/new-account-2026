<div class="modal fade" id="marutiStockOutModal" tabindex="-1" role="dialog" aria-labelledby="marutiStockOutTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <form method="POST" action="{{ route('admin.stocks.maruti-stock-out.store') }}" class="modal-content" id="marutiStockOutForm">
            @csrf
            <input type="hidden" name="selected_json" id="marutiStockOutSelected">
            <div class="modal-header">
                <h5 class="modal-title" id="marutiStockOutTitle">Maruti Suzuki — Move Available Stock to Special Stock Out</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <p>All available stock is selected below, across all stock filters. Save to remove it from available stock. You can restore any unit later from Special Stock Out.</p>
                <div id="marutiStockOutMessage" role="status" class="alert alert-info">Loading available stock…</div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead><tr><th><input type="checkbox" id="marutiStockOutAll" checked aria-label="Select all stock"></th><th>Item</th><th>Serial</th><th>SIM / VTS</th><th>SKU</th><th>Batch</th><th>Quantity</th></tr></thead>
                        <tbody id="marutiStockOutRows"></tbody>
                    </table>
                </div>
                <label for="marutiStockOutNotes">Notes (optional)</label>
                <textarea name="notes" id="marutiStockOutNotes" class="form-control" maxlength="2000" rows="2"></textarea>
            </div>
            <div class="modal-footer">
                <strong id="marutiStockOutCount" class="mr-auto"></strong>
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning" id="marutiStockOutSave" disabled>Save to Special Stock Out</button>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
$(function () {
    let loadingRequest;
    const body = $('#marutiStockOutRows'), save = $('#marutiStockOutSave'), all = $('#marutiStockOutAll');
    function updateSelection() {
        const boxes = body.find('input[type=checkbox]'), selected = boxes.filter(':checked');
        all.prop('checked', boxes.length > 0 && boxes.length === selected.length).prop('indeterminate', selected.length > 0 && selected.length < boxes.length);
        const quantity = selected.toArray().reduce((sum, box) => sum + Number(box.dataset.quantity), 0);
        $('#marutiStockOutCount').text(selected.length + ' selected • Quantity: ' + quantity.toFixed(3));
        save.prop('disabled', selected.length === 0);
    }
    $('#marutiStockOutModal').on('show.bs.modal', function () {
        if (loadingRequest) loadingRequest.abort();
        body.empty(); save.prop('disabled', true); all.prop('checked', true).prop('indeterminate', false);
        $('#marutiStockOutCount').text('');
        $('#marutiStockOutMessage').show().removeClass('alert-danger').addClass('alert-info').text('Loading available stock…');
        loadingRequest = $.getJSON(@json(route('admin.stocks.maruti-stock-out.available'))).done(function (data) {
            data.rows.forEach(function (row) {
                const unit = row.unit_data || {}, tr = $('<tr>');
                tr.append($('<td>').append($('<input>', {type: 'checkbox', value: row.selection_key, checked: true}).attr('data-quantity', row.quantity).attr('aria-label', 'Select ' + row.item_name)));
                [row.item_name + ' (' + (row.item_code || '') + ')', unit.serial_no, unit.vts_sim, row.sku, unit.batch_no || unit.production_batch_no, Number(row.quantity).toFixed(3) + ' ' + (row.unit || '')].forEach(function (value) { tr.append($('<td>').text(value || '—')); });
                body.append(tr);
            });
            $('#marutiStockOutMessage').toggle(data.rows.length === 0).text('No available stock to move.');
            updateSelection();
        }).fail(function (xhr, status) {
            if (status !== 'abort') $('#marutiStockOutMessage').removeClass('alert-info').addClass('alert-danger').text('Could not load stock. Close this popup and try again.');
        });
    });
    all.on('change', function () { body.find('input[type=checkbox]').prop('checked', this.checked); updateSelection(); });
    body.on('change', 'input', updateSelection);
    $('#marutiStockOutForm').on('submit', function () {
        $('#marutiStockOutSelected').val(JSON.stringify(body.find('input:checked').toArray().map(box => box.value)));
        save.prop('disabled', true).text('Saving…');
    });
});
</script>
@endpush
