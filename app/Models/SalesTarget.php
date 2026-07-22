<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesTarget extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id','party_id','period_type','starts_on','ends_on','status','notes','created_by'];
    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date'];

    public function party() { return $this->belongsTo(Party::class); }
    public function items() { return $this->hasMany(SalesTargetItem::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
