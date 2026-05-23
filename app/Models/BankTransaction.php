<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankTransaction extends Model
{
    protected $fillable = [
        'company_id','bank_account_id','related_bank_account_id','party_id','transaction_date',
        'transaction_type','direction','amount','balance_after','reference_no','payment_mode',
        'reference_type','reference_id','description','attachment','transfer_group','created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function bankAccount() { return $this->belongsTo(BankAccount::class); }
    public function relatedBankAccount() { return $this->belongsTo(BankAccount::class, 'related_bank_account_id'); }
    public function party() { return $this->belongsTo(Party::class); }
    public function company() { return $this->belongsTo(Company::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
