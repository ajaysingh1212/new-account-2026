<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionBatch extends Model
{
    protected $fillable = [
        'company_id',
        'finished_item_id',
        'batch_no',
        'production_date',
        'quantity',
        'raw_material_cost',
        'cost_per_unit',
        'notes',
        'units_data',   // JSON: per-unit serial / batch / sale_price details
        'status',
        'created_by',
    ];

    protected $casts = [
        'production_date'   => 'date',
        'raw_material_cost' => 'decimal:2',
        'cost_per_unit'     => 'decimal:2',
        'quantity'          => 'decimal:3',
        'units_data'        => 'array',
    ];

    public function finishedItem() { return $this->belongsTo(Item::class, 'finished_item_id'); }
    public function creator()      { return $this->belongsTo(User::class, 'created_by'); }
}
