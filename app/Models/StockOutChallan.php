<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockOutChallan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id','party_id','party_name','challan_no','challan_date','reference_no',
        'phone','billing_address','shipping_address','subtotal','grand_total','notes',
        'status','ip_address','user_role','created_by',
    ];

    protected $casts = [
        'challan_date' => 'date',
        'subtotal' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function party() { return $this->belongsTo(Party::class); }
    public function items() { return $this->hasMany(StockOutChallanItem::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }

    public function getDisplayPartyAttribute(): string
    {
        return $this->party?->display_name ?: ($this->party_name ?: 'Manual / Walk-in');
    }
}
