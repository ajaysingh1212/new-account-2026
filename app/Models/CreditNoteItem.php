<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditNoteItem extends Model
{
    protected $fillable = [
        'credit_note_id', 'sales_invoice_item_id', 'item_id', 'description', 'quantity', 'unit',
        'unit_price', 'tax_percent', 'tax_amount', 'line_total', 'selected_units',
    ];

    protected $casts = ['selected_units' => 'array'];

    public function creditNote() { return $this->belongsTo(CreditNote::class); }
    public function invoiceItem() { return $this->belongsTo(SalesInvoiceItem::class, 'sales_invoice_item_id'); }
    public function item() { return $this->belongsTo(Item::class); }
}
