<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseEstimateItem extends Model
{
    protected $fillable = [
        'purchase_estimate_id','item_id','description','quantity','unit','unit_price',
        'discount_type','discount_value','discount_amount','tax_percent',
        'tax_amount','line_total',
    ];

    public function purchaseEstimate() { return $this->belongsTo(PurchaseEstimate::class); }
    public function item() { return $this->belongsTo(Item::class); }
}
