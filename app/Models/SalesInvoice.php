<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesInvoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id','party_id','cost_center_id','sub_cost_center_id','sale_type','invoice_no',
        'billing_date','po_date','reference_no','phone','billing_address','shipping_address','subtotal',
        'discount_amount','tax_amount','grand_total','total_weight','notes','terms','attachment','status','created_by',
        'inter_company_transfer','inter_company_target_company_ids',
    ];

    protected $casts = [
        'billing_date' => 'date',
        'po_date' => 'date',
        'total_weight' => 'decimal:3',
        'inter_company_transfer' => 'boolean',
        'inter_company_target_company_ids' => 'array',
    ];
    public function party() { return $this->belongsTo(Party::class); }
    public function company() { return $this->belongsTo(Company::class); }
    public function items() { return $this->hasMany(SalesInvoiceItem::class); }
    public function returns() { return $this->hasMany(SalesReturn::class, 'sales_invoice_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
