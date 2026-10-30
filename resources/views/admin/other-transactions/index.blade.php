@extends('layouts.admin')
@section('title', 'Other Income / Expense')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0">Other Income / Expense Approval Desk</h3>
        <div class="d-flex flex-wrap align-items-center" style="gap:8px">
            @can('other_transactions.delete')
                <form method="POST" action="{{ route('admin.other-transactions.bulk-delete') }}" id="bulkDeleteOtherTransactions" class="d-flex flex-wrap align-items-center" style="gap:8px">
                    @csrf
                    <input type="hidden" name="mode" id="bulkDeleteMode" value="selected">
                    <input type="hidden" name="revert_transactions" id="bulkRevertValue" value="1">
                    <button type="submit" class="btn btn-danger btn-sm" id="deleteSelectedOtherTransactions" disabled>
                        <i class="fas fa-trash mr-1"></i> Delete Selected (<span id="selectedOtherTransactionCount">0</span>)
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm" id="deleteAllOtherTransactions" data-count="{{ $manageableCount }}" {{ $manageableCount === 0 ? 'disabled' : '' }}>
                        <i class="fas fa-trash-alt mr-1"></i> Delete All ({{ $manageableCount }})
                    </button>
                </form>
            @endcan
            @can('other_transactions.create')
                <a href="{{ route('admin.other-transactions.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> New Entry</a>
            @endcan
        </div>
    </div>
    <div class="card-body table-responsive">
        <table id="otherTxnTable" class="table table-hover">
            <thead>
                <tr>
                    @can('other_transactions.delete')<th><input type="checkbox" id="selectAllOtherTransactions" aria-label="Select all manageable entries" title="Select all entries"></th>@endcan
                    <th>Date</th><th>No</th><th>Type</th><th>Ledger</th><th>Party</th><th>Amount</th><th>Bank</th><th>Status</th><th>Created</th><th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($transactions as $transaction)
                <tr>
                    @can('other_transactions.delete')
                        <td>
                            @if(app(\App\Services\EntryVisibilityService::class)->canManage(auth()->user(), $transaction))
                                <input type="checkbox" class="other-transaction-select" value="{{ $transaction->id }}" aria-label="Select {{ $transaction->transaction_no }}">
                            @endif
                        </td>
                    @endcan
                    <td>{{ $transaction->transaction_date?->format('d M Y') }}</td>
                    <td>{{ $transaction->transaction_no }}</td>
                    <td>{{ ucfirst($transaction->transaction_kind) }}</td>
                    <td>{{ $transaction->ledger?->name ?: '-' }}</td>
                    <td>{{ $transaction->party_name ?: '-' }}</td>
                    <td><b>Rs {{ number_format((float) $transaction->total_amount, 2) }}</b></td>
                    <td>{{ $transaction->bankAccount?->account_name ?: '-' }}</td>
                    <td><span class="{{ $transaction->status === 'approved' ? 'badge-active' : ($transaction->status === 'rejected' ? 'badge-inactive' : 'badge-admin') }}">{{ str_replace('_', ' ', ucfirst($transaction->status)) }}</span></td>
                    <td>{{ $transaction->creator?->name ?? 'System' }}</td>
                    <td>
                        <a href="{{ route('admin.other-transactions.show', $transaction) }}" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
                        @if($transaction->status !== 'approved')
                            @can('other_transactions.edit')
                                <a href="{{ route('admin.other-transactions.edit', $transaction) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                            @endcan
                        @endif
                        @include('admin.other-transactions.delete-button', ['transaction' => $transaction])
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@include('admin.other-transactions.delete-modal')
@endsection
@push('scripts')
<script>
$(function(){
    const table=$('#otherTxnTable').DataTable({pageLength:25,order:[[@can('other_transactions.delete') 1 @else 0 @endcan,'desc']]});
    const selectedBoxes=()=>table.rows().nodes().to$().find('.other-transaction-select');
    function refreshBulkControls(){
        const count=selectedBoxes().filter(':checked').length;
        $('#selectedOtherTransactionCount').text(count);
        $('#deleteSelectedOtherTransactions').prop('disabled',count===0);
        const boxes=selectedBoxes();
        $('#selectAllOtherTransactions').prop('checked',boxes.length>0&&boxes.filter(':checked').length===boxes.length);
    }
    $('#selectAllOtherTransactions').on('change',function(){selectedBoxes().prop('checked',this.checked);refreshBulkControls()});
    $(document).on('change','.other-transaction-select',refreshBulkControls);
    $('#bulkDeleteOtherTransactions').on('submit',function(event){
        const mode=$('#bulkDeleteMode').val();
        const ids=selectedBoxes().filter(':checked').map(function(){return this.value}).get();
        if(mode==='selected'&&!ids.length){event.preventDefault();return}
        $('#bulkDeleteOtherTransactions input[name="transaction_ids[]"]').remove();
        if(mode==='selected')ids.forEach(id=>$('<input>',{type:'hidden',name:'transaction_ids[]',value:id}).appendTo(this));
        const count=mode==='all'?Number($('#deleteAllOtherTransactions').data('count')):ids.length;
        const action=mode==='all'?'sabhi manageable entries':'selected '+(count===1?'entry':count+' entries');
        if(!confirm(`${count} ${action} delete karne hain? Linked bank postings aur ledger effects reverse honge. Yeh action undo nahi ho sakta.`)){event.preventDefault()}
    });
    $('#deleteAllOtherTransactions').on('click',function(){
        $('#bulkDeleteMode').val('all');
        $('#bulkDeleteOtherTransactions').trigger('submit');
    });
    $('#deleteSelectedOtherTransactions').on('click',function(){$('#bulkDeleteMode').val('selected')});
    $('#bulkDeleteOtherTransactions').on('click','button[type="submit"]',function(){$('#bulkDeleteMode').val('selected')});
    refreshBulkControls();
});
</script>
@endpush
