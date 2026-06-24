@php
    $exportCompany = auth()->user()?->currentCompany;
    $exportTitle = $exportTitle ?? trim($__env->yieldContent('title')) ?: 'Transaction Report';
    $exportFile = $exportFile ?? \Illuminate\Support\Str::slug($exportTitle);
    $exportCompanyData = [
        'name' => $exportCompany?->name ?? 'Company',
        'address' => $exportCompany?->address ?? '',
        'phone' => $exportCompany?->phone ?? '',
        'email' => $exportCompany?->email ?? '',
        'gst' => $exportCompany?->gst_number ?? '',
        'logo' => $exportCompany?->logo_url ?? '',
    ];
@endphp
<div class="d-flex justify-content-end mb-3 report-export-actions" style="gap:8px">
    <button type="button" class="btn btn-success btn-sm js-report-excel"><i class="fas fa-file-excel mr-1"></i> Excel</button>
    <button type="button" class="btn btn-danger btn-sm js-report-pdf"><i class="fas fa-file-pdf mr-1"></i> PDF</button>
</div>
@once
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<script>
(function(){
    const company = @json($exportCompanyData);

    function context(button){
        const shell = button.closest('.report-card, .card, main, .content-wrapper') || document;
        return {
            table: shell.querySelector('table') || document.querySelector('table'),
            title: button.closest('[data-export-title]')?.dataset.exportTitle || document.title.replace(/\s*\|.*$/, ''),
            file: button.closest('[data-export-file]')?.dataset.exportFile || 'transaction-report'
        };
    }
    function rows(table){
        return [...table.querySelectorAll('tr')].map(tr => [...tr.cells].map(cell => cell.innerText.trim().replace(/\s+/g,' ')));
    }
    function chartData(data){
        return data.slice(1, 9).map(row => {
            const value = [...row].reverse().map(v => Number(String(v).replace(/[^0-9.-]/g,''))).find(v => Number.isFinite(v)) || 0;
            return {label: String(row[0] || '').slice(0,12), value: Math.abs(value)};
        });
    }
    function drawChart(doc, data, startY){
        const points = chartData(data), max = Math.max(1, ...points.map(p=>p.value));
        doc.setFontSize(10); doc.setTextColor(30,41,59); doc.text('Report Snapshot', 14, startY);
        points.forEach((p,i) => {
            const y=startY+7+(i*7), width=(p.value/max)*70;
            doc.setFillColor(37,99,235); doc.roundedRect(48,y-3,width,4,1,1,'F');
            doc.setFontSize(7); doc.setTextColor(71,85,105); doc.text(p.label,14,y); doc.text(p.value.toLocaleString('en-IN'),122,y,{align:'right'});
        });
        return startY + 12 + points.length*7;
    }
    async function logoData(){
        if(!company.logo) return null;
        try { const blob=await fetch(company.logo).then(r=>r.blob()); return await new Promise(ok=>{const f=new FileReader();f.onload=()=>ok(f.result);f.readAsDataURL(blob)}); } catch(e){ return null; }
    }
    document.addEventListener('click', async function(event){
        const excel=event.target.closest('.js-report-excel'), pdf=event.target.closest('.js-report-pdf');
        if(!excel && !pdf) return;
        const button=excel||pdf, ctx=context(button); if(!ctx.table) return;
        const data=rows(ctx.table);
        if(excel){
            const heading=[[company.name],[ctx.title],[company.address],[`Phone: ${company.phone}   Email: ${company.email}   GSTIN: ${company.gst}`],[]];
            const points=chartData(data), graph=[[],['REPORT GRAPH'],...points.map(p=>[p.label,'█'.repeat(Math.max(1,Math.round((p.value/Math.max(1,...points.map(x=>x.value)))*24))),p.value])];
            const sheet=XLSX.utils.aoa_to_sheet(heading.concat(data,graph));
            sheet['!cols']=data[0].map((_,i)=>({wch:Math.min(42,Math.max(14,...data.map(r=>String(r[i]||'').length+2)))}));
            sheet['!merges']=[0,1,2,3].map(r=>({s:{r,c:0},e:{r,c:Math.max(0,(data[0]?.length||1)-1)}}));
            const book=XLSX.utils.book_new(); XLSX.utils.book_append_sheet(book,sheet,'Report'); XLSX.writeFile(book,ctx.file+'.xlsx'); return;
        }
        const {jsPDF}=window.jspdf, doc=new jsPDF({orientation:data[0]?.length>6?'landscape':'portrait'}), pageWidth=doc.internal.pageSize.getWidth();
        const logo=await logoData(); if(logo) try{doc.addImage(logo,'PNG',14,10,20,20)}catch(e){}
        doc.setFillColor(15,23,42); doc.rect(0,0,pageWidth,36,'F'); doc.setTextColor(255); doc.setFontSize(17); doc.text(company.name,logo?39:14,15); doc.setFontSize(8); doc.text([company.address,`Phone: ${company.phone} | Email: ${company.email} | GSTIN: ${company.gst}`].filter(Boolean),logo?39:14,21);
        doc.setTextColor(15,23,42); doc.setFontSize(14); doc.text(ctx.title,14,45); doc.setFontSize(8); doc.setTextColor(100); doc.text('Generated '+new Date().toLocaleString('en-IN'),pageWidth-14,45,{align:'right'});
        const tableStart=drawChart(doc,data,52);
        doc.autoTable({head:[data[0]],body:data.slice(1),startY:tableStart,theme:'grid',styles:{fontSize:7,cellPadding:2},headStyles:{fillColor:[15,118,110],textColor:255,fontStyle:'bold'},alternateRowStyles:{fillColor:[241,245,249]},didDrawPage:()=>{doc.setFontSize(7);doc.setTextColor(100);doc.text('Page '+doc.internal.getNumberOfPages(),pageWidth-14,doc.internal.pageSize.getHeight()-6,{align:'right'})}});
        doc.save(ctx.file+'.pdf');
    });
})();
</script>
@endpush
@endonce
