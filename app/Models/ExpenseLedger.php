<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseLedger extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id','ledger_code','name','category','opening_balance','opening_balance_date',
        'current_balance','status','attachment','description','created_by','updated_by',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'opening_balance_date' => 'date',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function expenses() { return $this->hasMany(Expense::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
