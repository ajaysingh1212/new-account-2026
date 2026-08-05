<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChequeLeaf extends Model
{
    protected $fillable = [
        'company_id','cheque_book_id','bank_account_id','party_id','party_payment_id',
        'cheque_no','cheque_date','amount','payee_name','amount_words','memo',
        'validity_months','clearance_due_date','status','payment_done','description','created_by',
    ];

    protected $casts = [
        'cheque_date' => 'date',
        'clearance_due_date' => 'date',
        'amount' => 'decimal:2',
        'payment_done' => 'boolean',
    ];

    public function chequeBook() { return $this->belongsTo(ChequeBook::class); }
    public function bankAccount() { return $this->belongsTo(BankAccount::class); }
    public function party() { return $this->belongsTo(Party::class); }
    public function payment() { return $this->belongsTo(PartyPayment::class, 'party_payment_id'); }

    public function getIsValidAttribute(): bool
    {
        return $this->status === 'issued'
            && !$this->payment_done
            && (!$this->clearance_due_date || $this->clearance_due_date->endOfDay()->isFuture());
    }
}
