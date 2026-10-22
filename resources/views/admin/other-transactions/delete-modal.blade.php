@can('other_transactions.delete')
<div class="modal fade" id="deleteOtherTransactionModal" tabindex="-1" role="dialog" aria-labelledby="deleteOtherTransactionTitle" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST" class="modal-content" id="deleteOtherTransactionForm">
            @csrf
            @method('DELETE')
            <div class="modal-header">
                <h5 class="modal-title" id="deleteOtherTransactionTitle">Delete Other Income / Expense</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p>Delete entry <strong id="deleteOtherTransactionNo"></strong>?</p>
                <input type="hidden" name="revert_transactions" value="0">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="revertOtherTransactions" name="revert_transactions" value="1" checked aria-describedby="revertOtherTransactionsHelp">
                    <label class="custom-control-label" for="revertOtherTransactions">Revert linked transactions</label>
                </div>
                <p class="text-muted mt-2 mb-0" id="revertOtherTransactionsHelp">Checked: reverse linked bank postings and ledger effects. Unchecked: delete only this entry and keep all linked transactions and balances.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">Delete</button>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
$('#deleteOtherTransactionModal').on('show.bs.modal', function (event) {
    const button = $(event.relatedTarget);
    $('#deleteOtherTransactionForm').attr('action', button.attr('data-delete-url'));
    $('#deleteOtherTransactionNo').text(button.attr('data-entry-no'));
    $('#revertOtherTransactions').prop('checked', true);
});
</script>
@endpush
@endcan
