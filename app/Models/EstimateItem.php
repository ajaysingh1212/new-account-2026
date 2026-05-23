<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstimateItem extends Model
{
    protected $fillable = [
        'estimate_id','item_id','description','quantity','unit','unit_price',
        'discount_type','discount_value','discount_amount','tax_percent',
        'tax_amount','line_total',
    ];

    public function estimate() { return $this->belongsTo(Estimate::class); }
    public function item() { return $this->belongsTo(Item::class); }
}
