<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'company_id','item_id','party_id','movement_date','movement_type','direction',
        'quantity','unit_price','total_value','stock_after','value_after','reference_type',
        'reference_id','reference_no','description','movement_units','created_by',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total_value' => 'decimal:2',
        'stock_after' => 'decimal:3',
        'value_after' => 'decimal:2',
        'movement_units' => 'array',
    ];

    public function item() { return $this->belongsTo(Item::class); }
    public function party() { return $this->belongsTo(Party::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
