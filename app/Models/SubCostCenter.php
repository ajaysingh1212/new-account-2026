<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubCostCenter extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id','cost_center_id','code','name','owner_name','budget_amount',
        'status','description','created_by',
    ];

    protected $casts = ['budget_amount' => 'decimal:2'];

    public function company() { return $this->belongsTo(Company::class); }
    public function costCenter() { return $this->belongsTo(CostCenter::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
