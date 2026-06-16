<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id','product_type_id','item_type','item_code','hsn_code','barcode','qr_code',
        'name','sku','unit','brand','model','size','color','description','purchase_price',
        'purchase_tax_inclusive','purchase_gst_percent','sale_price','sale_tax_inclusive',
        'sale_gst_percent','opening_stock','current_stock','stock_value','low_stock_qty','per_quantity_weight',
        'track_stock','is_bom_enabled','status','created_by',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'purchase_tax_inclusive' => 'boolean',
        'sale_tax_inclusive' => 'boolean',
        'opening_stock' => 'decimal:3',
        'current_stock' => 'decimal:3',
        'stock_value' => 'decimal:2',
        'low_stock_qty' => 'decimal:3',
        'per_quantity_weight' => 'decimal:3',
        'track_stock' => 'boolean',
        'is_bom_enabled' => 'boolean',
    ];

    public function productType() { return $this->belongsTo(ProductType::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function bomMaterials() { return $this->hasMany(ItemBom::class, 'finished_item_id'); }
    public function stockMovements() { return $this->hasMany(StockMovement::class); }
}
