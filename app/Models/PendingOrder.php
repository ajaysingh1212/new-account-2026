<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingOrder extends Model
{
    protected $fillable = [
        'company_id','party_id','delivery_challan_id','delivery_challan_item_id',
        'item_id','pending_date','quantity','unit','unit_price','discount_type',
        'discount_value','discount_amount','tax_percent','tax_amount','line_total',
        'cost_amount','profit_amount','profit_percent','status','converted_sales_invoice_id',
        'converted_at','raw_materials',
    ];

    protected $casts = [
        'pending_date' => 'date',
        'converted_at' => 'datetime',
        'raw_materials' => 'array',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function party() { return $this->belongsTo(Party::class); }
    public function deliveryChallan() { return $this->belongsTo(DeliveryChallan::class); }
    public function deliveryChallanItem() { return $this->belongsTo(DeliveryChallanItem::class); }
    public function convertedInvoice() { return $this->belongsTo(SalesInvoice::class, 'converted_sales_invoice_id'); }
    public function item() { return $this->belongsTo(Item::class); }
}
