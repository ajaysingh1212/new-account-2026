<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartyPaymentAllocation extends Model
{
    protected $fillable = [
        'party_payment_id','company_id','party_id','bill_type','bill_model','bill_id','bill_no',
        'bill_date','bill_total','amount',
    ];

    protected $casts = [
        'bill_date' => 'date',
        'bill_total' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function payment() { return $this->belongsTo(PartyPayment::class, 'party_payment_id'); }
    public function party() { return $this->belongsTo(Party::class); }
}
