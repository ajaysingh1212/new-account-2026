<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    protected $fillable = [
        'purchase_return_id','purchase_bill_item_id','item_id','quantity','unit','unit_price',
        'tax_percent','tax_amount','line_total',
    ];

    public function item() { return $this->belongsTo(Item::class); }
    public function billItem() { return $this->belongsTo(PurchaseBillItem::class, 'purchase_bill_item_id'); }
    public function purchaseReturn() { return $this->belongsTo(PurchaseReturn::class); }
}
