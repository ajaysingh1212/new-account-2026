<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReturnItem extends Model
{
    protected $fillable = [
        'sales_return_id','sales_invoice_item_id','item_id','quantity','unit','unit_price',
        'tax_percent','tax_amount','line_total','selected_units',
    ];

    protected $casts = ['selected_units' => 'array'];

    public function item() { return $this->belongsTo(Item::class); }
    public function invoiceItem() { return $this->belongsTo(SalesInvoiceItem::class, 'sales_invoice_item_id'); }
    public function salesReturn() { return $this->belongsTo(SalesReturn::class); }
}
