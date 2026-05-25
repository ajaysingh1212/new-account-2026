<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesReturn extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id','sales_invoice_id','party_id','return_no','return_date','reason',
        'subtotal','tax_amount','grand_total','status','created_by',
    ];

    protected $casts = ['return_date' => 'date'];

    public function invoice() { return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id'); }
    public function party() { return $this->belongsTo(Party::class); }
    public function items() { return $this->hasMany(SalesReturnItem::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
