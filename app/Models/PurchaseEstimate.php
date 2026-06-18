<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseEstimate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id','party_id','cost_center_id','sub_cost_center_id','estimate_no',
        'estimate_date','valid_until','reference_no','phone','billing_address',
        'shipping_address','subtotal','discount_amount','tax_amount','grand_total',
        'notes','terms','attachment','status','converted_purchase_bill_id',
        'converted_at','created_by',
    ];

    protected $casts = [
        'estimate_date' => 'date',
        'valid_until' => 'date',
        'converted_at' => 'datetime',
    ];

    public function party() { return $this->belongsTo(Party::class); }
    public function company() { return $this->belongsTo(Company::class); }
    public function costCenter() { return $this->belongsTo(CostCenter::class); }
    public function subCostCenter() { return $this->belongsTo(SubCostCenter::class); }
    public function items() { return $this->hasMany(PurchaseEstimateItem::class); }
    public function convertedBill() { return $this->belongsTo(PurchaseBill::class, 'converted_purchase_bill_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
