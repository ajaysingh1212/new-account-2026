@can('other_transactions.delete')
    @if(app(\App\Services\EntryVisibilityService::class)->canManage(auth()->user(), $transaction))
        <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#deleteOtherTransactionModal"
                data-delete-url="{{ route('admin.other-transactions.destroy', $transaction) }}" data-entry-no="{{ $transaction->transaction_no }}" title="Delete entry">
            <i class="fas fa-trash mr-1"></i>
        </button>
    @endif
@endcan
