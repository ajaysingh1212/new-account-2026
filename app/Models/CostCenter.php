<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CostCenter extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id','code','name','manager_name','department','budget_amount',
        'budget_start_date','budget_end_date','status','description','created_by',
    ];

    protected $casts = [
        'budget_amount' => 'decimal:2',
        'budget_start_date' => 'date',
        'budget_end_date' => 'date',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function subCostCenters() { return $this->hasMany(SubCostCenter::class); }
}
