<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id','account_code','account_type','account_name','bank_name','branch_name',
        'account_holder_name','account_number','ifsc_code','swift_code','upi_id','phone',
        'email','address','opening_balance','opening_balance_date','current_balance',
        'is_primary','print_on_invoice','status','notes','created_by','updated_by',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'opening_balance_date' => 'date',
        'is_primary' => 'boolean',
        'print_on_invoice' => 'boolean',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function transactions() { return $this->hasMany(BankTransaction::class); }

    public function getMaskedAccountNumberAttribute(): string
    {
        if (!$this->account_number) return '—';
        return str_repeat('•', max(strlen($this->account_number) - 4, 0)) . substr($this->account_number, -4);
    }
}
