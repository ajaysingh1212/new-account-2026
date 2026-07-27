<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartyOpeningBalanceAdjustment extends Model
{
    protected $fillable = [
        'company_id',
        'party_id',
        'adjustment_date',
        'previous_amount',
        'adjustment_amount',
        'new_amount',
        'direction',
        'reason',
        'created_by',
        'created_role',
    ];

    protected $casts = [
        'adjustment_date' => 'date',
        'previous_amount' => 'decimal:2',
        'adjustment_amount' => 'decimal:2',
        'new_amount' => 'decimal:2',
    ];

    public function party() { return $this->belongsTo(Party::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
