<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductType extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id','code','name','nature','status','description','created_by'];

    public function items() { return $this->hasMany(Item::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
