<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id','expense_ledger_id','bank_account_id','expense_date','expense_no','reference_no',
        'vendor_name','amount','tax_amount','total_amount','payment_mode','status','description',
        'attachment','approved_at','approved_by','approval_note','rejected_at','rejected_by',
        'rejection_reason','created_by','updated_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function ledger() { return $this->belongsTo(ExpenseLedger::class, 'expense_ledger_id'); }
    public function bankAccount() { return $this->belongsTo(BankAccount::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
}
