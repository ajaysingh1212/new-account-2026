<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemBom extends Model
{
    protected $fillable = ['company_id','finished_item_id','raw_item_id','line_type','qty_per_unit','unit_price'];
    protected $casts = ['qty_per_unit' => 'decimal:3', 'unit_price' => 'decimal:2'];

    public function finishedItem() { return $this->belongsTo(Item::class, 'finished_item_id'); }
    public function rawItem() { return $this->belongsTo(Item::class, 'raw_item_id'); }

    public function effectiveUnitPrice(): float
    {
        if (($this->line_type ?? 'raw_material') === 'service' && $this->unit_price !== null) {
            return (float) $this->unit_price;
        }

        return (float) ($this->rawItem?->purchase_price ?? $this->unit_price ?? 0);
    }
}
