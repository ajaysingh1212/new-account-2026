<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseBillItem extends Model
{
    protected $fillable = [
        'purchase_bill_id','item_id','description','quantity','unit','unit_price',
        'discount_type','discount_value','discount_amount','tax_percent','tax_amount','line_total',
    ];

    public function purchaseBill() { return $this->belongsTo(PurchaseBill::class); }
    public function item() { return $this->belongsTo(Item::class); }
}
