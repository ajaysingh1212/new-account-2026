@php
    $invoice = $credit_note->invoice;
    $relatedCreditNotes = $invoice?->creditNotes ?? collect();
    $previousCreditNotesTotal = (float) $relatedCreditNotes
        ->where('id', '!=', $credit_note->id)
        ->sum('grand_total');
    $currentCreditNoteTotal = (float) $credit_note->grand_total;
    $totalCreditNotesPassed = $previousCreditNotesTotal + $currentCreditNoteTotal;
    $invoiceTotal = (float) ($invoice?->grand_total ?? 0);
    $invoiceBalanceAfterCreditNotes = max(0, $invoiceTotal - $totalCreditNotesPassed);
@endphp

@include('admin.partials.print-document', [
    'title' => 'Credit Note',
    'docNo' => $credit_note->credit_note_no,
    'docDate' => $credit_note->credit_note_date,
    'status' => $credit_note->hasCompleteSalesReturn() ? 'Sales Return Received' : 'Sales Return Pending',
    'party' => $credit_note->party,
    'lines' => $credit_note->items,
    'billingAddress' => $credit_note->invoice?->billing_address,
    'shippingAddress' => $credit_note->invoice?->shipping_address,
    'subtotal' => $credit_note->subtotal,
    'discount' => 0,
    'tax' => $credit_note->tax_amount,
    'grandTotal' => $credit_note->grand_total,
    'terms' => 'This Credit Note is issued against Sales Invoice '.$credit_note->invoice?->invoice_no.'. '.($credit_note->reason ?: '').' '.($defaultTerms?->content ?? ''),
    'company' => $credit_note->company,
    'bankAccount' => $bankAccount,
    'accent' => '#0f766e',
    'relatedDocumentNotice' => 'This Credit Note is issued against Sales Invoice '.$credit_note->invoice?->invoice_no.' dated '.$credit_note->invoice?->billing_date?->format('d M Y').'. Keep/download both documents together for complete transaction details.',
    'adjustmentSummary' => [
        'title' => 'Sales Invoice Adjustment Summary',
        'rows' => [
            ['label' => 'Sales Invoice No.', 'value' => $invoice?->invoice_no ?: '-'],
            ['label' => 'Sales Invoice Date', 'value' => $invoice?->billing_date?->format('d M Y') ?: '-'],
            ['label' => 'Original Sales Invoice Amount', 'amount' => $invoiceTotal],
            ['label' => 'Previous Credit Notes Passed', 'amount' => $previousCreditNotesTotal],
            ['label' => 'This Credit Note Amount', 'amount' => $currentCreditNoteTotal],
            ['label' => 'Total Credit Notes Passed', 'amount' => $totalCreditNotesPassed],
            ['label' => 'Invoice Amount After Credit Notes', 'amount' => $invoiceBalanceAfterCreditNotes, 'highlight' => true],
        ],
    ],
])
