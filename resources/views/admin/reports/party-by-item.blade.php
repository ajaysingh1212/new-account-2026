@extends('layouts.admin')
@section('title','Party Report By Item')
@section('content')
@include('admin.reports.partials.styles')
<div class="report-hero">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Party Report By Item</h1>

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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
document.getElementById('exportExcel').addEventListener('click', function () {

    let table = document.getElementById('partyItem');
    let workbook = XLSX.utils.table_to_book(table, {
        sheet: "Party Report By Item"
    });

    XLSX.writeFile(workbook, 'party-report-by-item.xlsx');

});
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

<script>
document.getElementById('exportPdf').addEventListener('click', function () {

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('landscape');

    doc.setFontSize(16);
    doc.text('Party Report By Item', 14, 15);

    doc.autoTable({
        html: '#partyItem',
        startY: 22,
        styles: {
            fontSize: 8
        }
    });

    doc.save('party-report-by-item.pdf');

});
</script>

    <form class="report-filter" method="GET">
        <div>
            <label>Month</label>
            <input type="month" name="month" class="form-control"
                   value="{{ $filters['month'] }}">
        </div>

        <div>
            <label>Party</label>
            <select name="party_id" class="form-control">
                <option value="">All Parties</option>
                @foreach($parties as $party)
                    <option value="{{ $party->id }}"
                        @selected($filters['partyId']==$party->id)>
                        {{ $party->display_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div></div>

        <button class="btn btn-info report-btn">Apply</button>
    </form>
</div>
<div class="report-card">
   <table id="partyItem" class="table report-table">
      <thead>
         <tr>
            <th>Date</th>
            <th>Party</th>
            <th>Item</th>
            <th>Type</th>
            <th>Qty</th>
            <th>Amount</th>
         </tr>
      </thead>
      <tbody>
         @foreach($sales as $row)
         <tr>
            <td>{{ $row['date']?->format('d-m-Y') }}</td>
            <td>{{ $row['party'] }}</td>
            <td>{{ $row['item'] }}</td>
            <td>{{ $row['type'] }}</td>
            <td>{{ $row['qty'] }}</td>
            <td>Rs {{ number_format($row['amount'],2) }}</td>
         </tr>
         @endforeach
      </tbody>
   </table>
</div>
@endsection
@push('scripts')<script>$('#partyItem').DataTable({pageLength:25});</script>@endpush