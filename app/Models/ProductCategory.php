<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductCategory extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'name', 'status', 'created_by'];

    public function productTypes() { return $this->hasMany(ProductType::class); }
    public function items() { return $this->hasMany(Item::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
