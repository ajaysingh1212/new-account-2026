@can('expenses.delete')
    @if(app(\App\Services\EntryVisibilityService::class)->canManage(auth()->user(), $expense))
        <form method="POST" action="{{ route('admin.expenses.destroy', $expense) }}" class="d-inline"
              onsubmit="return confirm('Delete this expense? Its bank postings and expense ledger effects will be reversed.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm" title="Delete expense and reverse its postings"><i class="fas fa-trash mr-1"></i>Delete</button>
        </form>
    @endif
@endcan
