<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChequeBook extends Model
{
    protected $fillable = [
        'company_id','bank_account_id','book_no','valid_from','valid_to','leaf_count',
        'next_leaf_no','status','notes','created_by',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    public function bankAccount() { return $this->belongsTo(BankAccount::class); }
    public function leaves() { return $this->hasMany(ChequeLeaf::class); }

    public function getRemainingLeavesAttribute(): int
    {
        return max(0, (int) $this->leaf_count - $this->leaves()->count());
    }
}
