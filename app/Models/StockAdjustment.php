<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    protected $fillable = [
        'company_id',
        'item_id',
        'previous_stock',
        'new_stock',
        'stock_change',
        'unit_rate',
        'previous_stock_value',
        'new_stock_value',
        'user_role',
        'note',
        'adjusted_by',
    ];

    protected $casts = [
        'previous_stock' => 'decimal:3',
        'new_stock' => 'decimal:3',
        'stock_change' => 'decimal:3',
        'unit_rate' => 'decimal:2',
        'previous_stock_value' => 'decimal:2',
        'new_stock_value' => 'decimal:2',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function adjustedBy()
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }
}
