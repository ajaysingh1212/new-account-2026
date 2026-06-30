<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOutChallanItem extends Model
{
    protected $fillable = [
        'stock_out_challan_id','item_id','description','quantity','unit','unit_price','line_total','selected_units',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'selected_units' => 'array',
    ];

    public function stockOutChallan() { return $this->belongsTo(StockOutChallan::class); }
    public function item() { return $this->belongsTo(Item::class); }
}
