<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemBom extends Model
{
    protected $fillable = ['company_id','finished_item_id','raw_item_id','qty_per_unit'];
    protected $casts = ['qty_per_unit' => 'decimal:3'];

    public function finishedItem() { return $this->belongsTo(Item::class, 'finished_item_id'); }
    public function rawItem() { return $this->belongsTo(Item::class, 'raw_item_id'); }
}
