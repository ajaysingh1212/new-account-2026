<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    protected $fillable = ['stock_transfer_id', 'item_id', 'quantity', 'stock_before', 'unit_price'];

    protected $casts = [
        'quantity'     => 'decimal:3',
        'stock_before' => 'decimal:3',
        'unit_price'   => 'decimal:2',
    ];

    public function transfer() { return $this->belongsTo(StockTransfer::class, 'stock_transfer_id'); }
    public function item()     { return $this->belongsTo(Item::class); }
}
