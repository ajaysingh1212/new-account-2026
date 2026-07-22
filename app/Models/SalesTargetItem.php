<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesTargetItem extends Model
{
    protected $fillable = ['sales_target_id','product_category_id','target_type','target_value','notes'];
    protected $casts = ['target_value' => 'decimal:3'];

    public function target() { return $this->belongsTo(SalesTarget::class, 'sales_target_id'); }
    public function productCategory() { return $this->belongsTo(ProductCategory::class); }
}
