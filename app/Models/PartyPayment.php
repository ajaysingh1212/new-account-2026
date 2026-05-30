<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartyPayment extends Model
{
    protected $fillable = [
        'company_id','party_id','bank_account_id','payment_date','payment_type','reference_no',
        'amount','discount_amount','total_amount','payment_mode','description','attachment','created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function party() { return $this->belongsTo(Party::class); }
    public function bankAccount() { return $this->belongsTo(BankAccount::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function allocations() { return $this->hasMany(PartyPaymentAllocation::class); }
}
