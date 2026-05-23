<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockTransfer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'from_company_id', 'to_company_id', 'transfer_no', 'transfer_date',
        'notes', 'status', 'approved_by', 'approved_at', 'rejection_reason', 'created_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'approved_at'   => 'datetime',
    ];

    public function fromCompany()  { return $this->belongsTo(Company::class, 'from_company_id'); }
    public function toCompany()    { return $this->belongsTo(Company::class, 'to_company_id'); }
    public function creator()      { return $this->belongsTo(User::class, 'created_by'); }
    public function approvedBy()   { return $this->belongsTo(User::class, 'approved_by'); }
    public function items()        { return $this->hasMany(StockTransferItem::class); }

    public function isPending()  { return $this->status === 'pending'; }
    public function isApproved() { return $this->status === 'approved'; }
    public function isRejected() { return $this->status === 'rejected'; }

    public static function nextNumber(): string
    {
        $count = static::withTrashed()->count() + 1;
        return 'ST-' . str_pad((string) $count, 6, '0', STR_PAD_LEFT);
    }
}
