@extends('layouts.admin')
@section('title', $mode === 'profit' ? 'Party Wise Profit And Loss' : 'Sale Purchase By Party')
@section('content')
@include('admin.reports.partials.styles')
<div class="report-hero">
   <div class="d-flex justify-content-between align-items-center mb-3">
    <h1>{{ $mode === 'profit' ? 'Party Wise Profit And Loss' : 'Sale Purchase By Party' }}</h1>

    <div>
        <a class="btn btn-danger report-btn"
           href="javascript:void(0)"
           id="exportPdf">
            <i class="fas fa-file-pdf mr-1"></i> Download PDF
        </a>

        <a class="btn btn-success report-btn ml-2"
           href="javascript:void(0)"
           id="exportExcel">
            <i class="fas fa-file-excel mr-1"></i> Excel
        </a>
    </div>
</div>

    
   <form class="report-filter" method="GET">
      <div><label>Month</label><input type="month" name="month" class="form-control" value="{{ $filters['month'] }}"></div>
      <div>
         <label>Party</label>
         <select name="party_id" class="form-control">
            <option value="">All Parties</option>
            @foreach($parties as $party)<option value="{{ $party->id }}" @selected($filters['partyId']==$party->id)>{{ $party->display_name }}</option>@endforeach
         </select>
      </div>
      <div></div>
      <button class="btn btn-info report-btn">Apply</button>
   </form>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
document.getElementById('exportExcel').addEventListener('click', function () {

    let table = document.getElementById('spParty');
    let workbook = XLSX.utils.table_to_book(table, {
        sheet: "Party Wise Profit Loss"
    });

    XLSX.writeFile(workbook, 'party-wise-profit-loss.xlsx');

});
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

<script>
document.getElementById('exportPdf').addEventListener('click', function () {

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('landscape');

    doc.text('Party Wise Profit And Loss', 14, 15);

    doc.autoTable({
        html: '#spParty',
        startY: 20
    });

    doc.save('party-wise-profit-loss.pdf');

});
</script>
<div class="report-card">
   <table id="spParty" class="table report-table">
      <thead>
         <tr>
            <th>Party</th>
            <th>Sales</th>
            <th>Purchase</th>
            <th>Net</th>
         </tr>
      </thead>
      <tbody>
         @foreach($rows as $row)
         <tr>
            <td>{{ $row['party']->display_name }}</td>
            <td>Rs {{ number_format($row['sale'],2) }}</td>
            <td>Rs {{ number_format($row['purchase'],2) }}</td>
            <td><strong>Rs {{ number_format($row['net'],2) }}</strong></td>
         </tr>
         @endforeach
      </tbody>
   </table>
</div>
@endsection
@push('scripts')<script>$('#spParty').DataTable({pageLength:25});</script>@endpush