<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('replacements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sales_invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sales_invoice_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->string('replacement_no', 30);
            $table->date('request_date');
            $table->json('returned_unit')->nullable();
            $table->json('issued_unit')->nullable();
            $table->foreignId('issued_item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->foreignId('stock_movement_id')->nullable()->constrained('stock_movements')->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('customer_address')->nullable();
            $table->text('request_reason');
            $table->json('product_images');
            $table->string('status', 20)->default('pending');
            $table->text('admin_reason')->nullable();
            $table->string('admin_attachment')->nullable();
            $table->text('issue_narration')->nullable();
            $table->string('issue_attachment')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'replacement_no']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('replacements');
    }
};
