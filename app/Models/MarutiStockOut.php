<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarutiStockOut extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'unit_data' => 'array',
        'restored_at' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function restorer()
    {
        return $this->belongsTo(User::class, 'restored_by');
    }
}
