@extends('layouts.admin')
@section('title','All Parties Report')
@section('content')
@include('admin.reports.partials.styles')
<div class="report-hero">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="mb-0">All Parties</h1>
            <div class="text-info mt-1">
                Complete party master with balances and ownership.
            </div>
        </div>

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
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
document.getElementById('exportExcel').addEventListener('click', function () {

    let table = document.getElementById('partiesReport');
    let workbook = XLSX.utils.table_to_book(table, {
        sheet: "All Parties"
    });

    XLSX.writeFile(workbook, 'all-parties-report.xlsx');

});
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

<script>
document.getElementById('exportPdf').addEventListener('click', function () {

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('landscape');

    doc.text('All Parties Report', 14, 15);

    doc.autoTable({
        html: '#partiesReport',
        startY: 20
    });

    doc.save('all-parties-report.pdf');

});
</script>
<div class="report-card">
   <table id="partiesReport" class="table report-table">
      <thead>
         <tr>
            <th>Code</th>
            <th>Party</th>
            <th>GSTIN</th>
            <th>Phone</th>
            <th>Balance</th>
            <th>Status</th>
            <th>Created By</th>
         </tr>
      </thead>
      <tbody>
         @foreach($parties as $p)
         <tr>
            <td>{{ $p->party_code }}</td>
            <td>{{ $p->display_name }}</td>
            <td>{{ $p->gstin ?: '-' }}</td>
            <td>{{ $p->phone ?: '-' }}</td>
            <td>Rs {{ number_format((float)$p->current_balance,2) }}</td>
            <td>{{ ucfirst($p->status) }}</td>
            <td>{{ $p->creator?->name ?? 'System' }}</td>
         </tr>
         @endforeach
      </tbody>
   </table>
</div>
@endsection
@push('scripts')<script>$('#partiesReport').DataTable({pageLength:25});</script>@endpush