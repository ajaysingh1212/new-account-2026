<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesInvoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id','party_id','cost_center_id','sub_cost_center_id','sale_type','invoice_no',
        'billing_date','reference_no','phone','billing_address','shipping_address','subtotal',
        'discount_amount','tax_amount','grand_total','notes','terms','attachment','status','created_by',
        'inter_company_transfer','inter_company_target_company_ids',
    ];

    protected $casts = [
        'billing_date' => 'date',
        'inter_company_transfer' => 'boolean',
        'inter_company_target_company_ids' => 'array',
    ];
    public function party() { return $this->belongsTo(Party::class); }
    public function items() { return $this->hasMany(SalesInvoiceItem::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
