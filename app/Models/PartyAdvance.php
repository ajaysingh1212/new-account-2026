<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartyAdvance extends Model
{
    protected $fillable = [
        'company_id','party_id','party_payment_id','direction','advance_date',
        'original_amount','remaining_amount','reference_no','payment_mode',
        'description','created_by',
    ];

    protected $casts = [
        'advance_date' => 'date',
        'original_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    public function party() { return $this->belongsTo(Party::class); }
    public function payment() { return $this->belongsTo(PartyPayment::class, 'party_payment_id'); }
    public function allocations() { return $this->hasMany(PartyAdvanceAllocation::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
