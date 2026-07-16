<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartyAdvanceAllocation extends Model
{
    protected $fillable = [
        'company_id','party_id','party_advance_id','document_type','document_id',
        'document_no','amount','created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function party() { return $this->belongsTo(Party::class); }
    public function advance() { return $this->belongsTo(PartyAdvance::class, 'party_advance_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
