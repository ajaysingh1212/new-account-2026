<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryChallan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id','party_id','cost_center_id','sub_cost_center_id','challan_no',
        'challan_date','reference_no','dispatch_through','vehicle_no','driver_name',
        'driver_phone','lr_no','lr_date','phone','billing_address','shipping_address',
        'subtotal','discount_amount','tax_amount','grand_total','notes','terms',
        'attachment','status','created_by',
    ];

    protected $casts = [
        'challan_date' => 'date',
        'lr_date' => 'date',
    ];

    public function party() { return $this->belongsTo(Party::class); }
    public function costCenter() { return $this->belongsTo(CostCenter::class); }
    public function subCostCenter() { return $this->belongsTo(SubCostCenter::class); }
    public function items() { return $this->hasMany(DeliveryChallanItem::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
