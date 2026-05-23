<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesInvoiceItem extends Model
{
    protected $fillable = [
        'sales_invoice_id','item_id','description','quantity','unit','unit_price',
        'discount_type','discount_value','discount_amount','tax_percent','tax_amount','line_total',
    ];

    public function item() { return $this->belongsTo(Item::class); }
}
