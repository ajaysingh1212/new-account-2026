<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartyLedger extends Model
{
    protected $fillable = [
        'company_id','party_id','entry_date','entry_type','reference_type','reference_id',
        'reference_no','debit','credit','balance_after','description','created_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function party() { return $this->belongsTo(Party::class); }
    public function company() { return $this->belongsTo(Company::class); }
}
