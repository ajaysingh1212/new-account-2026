<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseBill extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id','party_id','cost_center_id','sub_cost_center_id','purchase_type',
        'invoice_no','supplier_bill_no','billing_date','purchase_bill_date','reference_no',
        'docket_no','e_bill_no','phone','billing_address','shipping_address','subtotal',
        'discount_amount','tax_amount','grand_total','notes','terms','attachment','status','created_by',
        'source_sales_invoice_id','inter_company_source_company_id',
    ];

    protected $casts = ['billing_date' => 'date', 'purchase_bill_date' => 'date'];
    public function party() { return $this->belongsTo(Party::class); }
    public function company() { return $this->belongsTo(Company::class); }
    public function items() { return $this->hasMany(PurchaseBillItem::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function sourceSalesInvoice() { return $this->belongsTo(SalesInvoice::class, 'source_sales_invoice_id'); }
    public function interCompanySourceCompany() { return $this->belongsTo(Company::class, 'inter_company_source_company_id'); }
}
