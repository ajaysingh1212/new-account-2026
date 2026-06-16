<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesInvoiceItem extends Model
{
    protected $fillable = [
        'sales_invoice_id','item_id','description','quantity','unit','unit_price',
        'discount_type','discount_value','discount_amount','tax_percent','tax_amount','line_total','line_weight',
        'selected_units',
    ];

    protected $casts = [
        'selected_units' => 'array',
        'line_weight' => 'decimal:3',
    ];

    public function salesInvoice() { return $this->belongsTo(SalesInvoice::class); }
    public function item() { return $this->belongsTo(Item::class); }
}
