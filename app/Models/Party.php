<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Party extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id','party_code','party_type','display_name','legal_name','contact_person',
        'email','phone','alternate_phone','whatsapp_number','gstin','pan_number','tan_number',
        'cin_number','tax_type','place_of_supply','billing_address','shipping_address','city',
        'district','state','pincode','country','opening_balance','opening_balance_type','opening_balance_date',
        'current_balance','credit_limit','credit_days','payment_terms','bank_name',
        'account_holder_name','account_number','ifsc_code','branch_name','upi_id','status',
        'notes','created_by','updated_by',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'opening_balance_date' => 'date',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function ledgers() { return $this->hasMany(PartyLedger::class); }
    public function advances() { return $this->hasMany(PartyAdvance::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }

    public function getBalanceLabelAttribute(): string
    {
        $balance = (float) $this->current_balance;
        if ($balance > 0) return 'Payable';
        if ($balance < 0) return 'Receivable';
        return 'Settled';
    }
}
