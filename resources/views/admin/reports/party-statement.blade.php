@extends('layouts.admin')
@section('title','Party Statement')
@section('content')
@include('admin.reports.partials.styles')
<div class="report-hero">
     <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Party Statement</h1>

        <div>
           <a class="btn btn-danger report-btn"
                href="javascript:void(0)"
                id="exportPdf">
                    <i class="fas fa-file-pdf mr-1"></i> Download PDF
            </a>
            <a class="btn btn-success report-btn ml-2" href="javascript:void(0)" id="exportExcel">
                <i class="fas fa-file-excel mr-1"></i> Excel
            </a>
        </div>
    </div>
   <form class="report-filter" method="GET">
        
      <div><label>From</label><input type="date" name="from_date" class="form-control" value="{{ $from }}"></div>
      <div><label>To</label><input type="date" name="to_date" class="form-control" value="{{ $to }}"></div>
      <div>
        
         <label>Party</label>
         <select name="party_id" class="form-control">
            <option value="">All Parties</option>
            @foreach($parties as $party)<option value="{{ $party->id }}" @selected($partyId==$party->id)>{{ $party->display_name }}</option>@endforeach
         </select>
      </div>

      
      <button class="btn btn-info report-btn">Apply</button>
      
   </form>
   <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
document.getElementById('exportExcel').addEventListener('click', function () {

    let table = document.getElementById('ledgerTable');
    let workbook = XLSX.utils.table_to_book(table, {sheet:"Party Statement"});
    XLSX.writeFile(workbook, 'party-statement.xlsx');

});
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

<script>
document.getElementById('exportPdf').addEventListener('click', function () {

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('landscape');

    doc.text('Party Statement', 14, 15);

    doc.autoTable({
        html: '#ledgerTable',
        startY: 20
    });

    doc.save('party-statement.pdf');

});
</script>
</div>
<div class="report-card">
   <h3>Ledger Entries</h3>
   <table id="ledgerTable" class="table report-table">
      <thead>
         <tr>
            <th>Date</th>
            <th>Party</th>
            <th>Type</th>
            <th>Ref</th>
            <th>Debit</th>
            <th>Credit</th>
            <th>Balance</th>
         </tr>
      </thead>
      <tbody>
         @foreach($ledgers as $l)
         <tr>
            <td>{{ $l->entry_date?->format('d-m-Y') }}</td>
            <td>{{ $l->party?->display_name }}</td>
            <td>{{ str_replace('_',' ',ucfirst($l->entry_type)) }}</td>
            <td>{{ $l->reference_no }}</td>
            <td>Rs {{ number_format((float)$l->debit,2) }}</td>
            <td>Rs {{ number_format((float)$l->credit,2) }}</td>
            <td>Rs {{ number_format((float)$l->balance_after,2) }}</td>
         </tr>
         @endforeach
      </tbody>
   </table>
</div>
@endsection
@push('scripts')<script>$('#ledgerTable').DataTable({pageLength:25});</script>@endpush