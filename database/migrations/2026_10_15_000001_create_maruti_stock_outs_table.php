<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maruti_stock_outs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('item_id')->constrained();
            $table->string('batch_no', 50)->index();
            $table->string('item_name');
            $table->string('item_code')->nullable();
            $table->string('sku')->nullable();
            $table->string('unit', 20)->nullable();
            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->json('unit_data')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('restored_at')->nullable();
            $table->foreignId('restored_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['company_id', 'restored_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maruti_stock_outs');
    }
};
