<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryChallanItem extends Model
{
    protected $fillable = [
        'delivery_challan_id','item_id','description','quantity','unit','unit_price',
        'discount_type','discount_value','discount_amount','tax_percent',
        'tax_amount','line_total','selected_units',
    ];

    protected $casts = ['selected_units' => 'array'];

    public function deliveryChallan() { return $this->belongsTo(DeliveryChallan::class); }
    public function item() { return $this->belongsTo(Item::class); }
}
