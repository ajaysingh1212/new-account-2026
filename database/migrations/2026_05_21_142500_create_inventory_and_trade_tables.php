<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->string('nature', 30)->default('finished_goods');
            $table->string('status', 20)->default('active');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_type', 20)->default('product');
            $table->string('item_code', 40);
            $table->string('hsn_code', 20)->nullable();
            $table->string('barcode')->nullable();
            $table->string('qr_code')->nullable();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('unit', 20)->default('PCS');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('size')->nullable();
            $table->string('color')->nullable();
            $table->text('description')->nullable();
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->boolean('purchase_tax_inclusive')->default(false);
            $table->decimal('purchase_gst_percent', 5, 2)->default(0);
            $table->decimal('sale_price', 15, 2)->default(0);
            $table->boolean('sale_tax_inclusive')->default(false);
            $table->decimal('sale_gst_percent', 5, 2)->default(0);
            $table->decimal('opening_stock', 15, 3)->default(0);
            $table->decimal('current_stock', 15, 3)->default(0);
            $table->decimal('stock_value', 15, 2)->default(0);
            $table->decimal('low_stock_qty', 15, 3)->nullable();
            $table->boolean('track_stock')->default(true);
            $table->boolean('is_bom_enabled')->default(false);
            $table->string('status', 20)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'item_code']);
            $table->index(['company_id', 'item_type', 'status']);
        });

        Schema::create('item_boms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('finished_item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('raw_item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('qty_per_unit', 15, 3);
            $table->timestamps();
            $table->unique(['finished_item_id', 'raw_item_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
            $table->date('movement_date');
            $table->string('movement_type', 40);
            $table->string('direction', 10);
            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_value', 15, 2)->default(0);
            $table->decimal('stock_after', 15, 3)->default(0);
            $table->decimal('value_after', 15, 2)->default(0);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_no')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'item_id', 'movement_date']);
        });

        Schema::create('purchase_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sub_cost_center_id')->nullable()->constrained()->nullOnDelete();
            $table->string('purchase_type', 20)->default('credit');
            $table->string('invoice_no', 20);
            $table->string('supplier_bill_no')->nullable();
            $table->date('billing_date');
            $table->date('purchase_bill_date')->nullable();
            $table->string('reference_no')->nullable();
            $table->string('docket_no')->nullable();
            $table->string('e_bill_no')->nullable();
            $table->string('phone')->nullable();
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->string('status', 20)->default('posted');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'invoice_no']);
        });

        Schema::create('purchase_bill_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_bill_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->decimal('quantity', 15, 3);
            $table->string('unit', 20)->nullable();
            $table->decimal('unit_price', 15, 2);
            $table->string('discount_type', 10)->default('percent');
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2);
            $table->timestamps();
        });

        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sub_cost_center_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sale_type', 20)->default('credit');
            $table->string('invoice_no', 20);
            $table->date('billing_date');
            $table->string('reference_no')->nullable();
            $table->string('phone')->nullable();
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->string('status', 20)->default('posted');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'invoice_no']);
        });

        Schema::create('sales_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->decimal('quantity', 15, 3);
            $table->string('unit', 20)->nullable();
            $table->decimal('unit_price', 15, 2);
            $table->string('discount_type', 10)->default('percent');
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2);
            $table->timestamps();
        });

        Schema::create('production_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('finished_item_id')->constrained('items')->cascadeOnDelete();
            $table->string('batch_no', 30);
            $table->date('production_date');
            $table->decimal('quantity', 15, 3);
            $table->decimal('raw_material_cost', 15, 2)->default(0);
            $table->decimal('cost_per_unit', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'batch_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_batches');
        Schema::dropIfExists('sales_invoice_items');
        Schema::dropIfExists('sales_invoices');
        Schema::dropIfExists('purchase_bill_items');
        Schema::dropIfExists('purchase_bills');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('item_boms');
        Schema::dropIfExists('items');
        Schema::dropIfExists('product_types');
    }
};
