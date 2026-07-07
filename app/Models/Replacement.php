<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Replacement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id','party_id','sales_invoice_id','sales_invoice_item_id','item_id',
        'replacement_no','request_date','returned_unit','issued_unit','issued_item_id',
        'stock_movement_id','customer_name','customer_email','customer_phone',
        'customer_address','request_reason','product_images','status','admin_reason',
        'admin_attachment','issue_narration','issue_attachment','created_by',
        'approved_by','approved_at','issued_at',
    ];

    protected $casts = [
        'request_date' => 'date',
        'returned_unit' => 'array',
        'issued_unit' => 'array',
        'product_images' => 'array',
        'approved_at' => 'datetime',
        'issued_at' => 'datetime',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function party() { return $this->belongsTo(Party::class); }
    public function invoice() { return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id'); }
    public function invoiceItem() { return $this->belongsTo(SalesInvoiceItem::class, 'sales_invoice_item_id'); }
    public function item() { return $this->belongsTo(Item::class); }
    public function issuedItem() { return $this->belongsTo(Item::class, 'issued_item_id'); }
    public function stockMovement() { return $this->belongsTo(StockMovement::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
}
