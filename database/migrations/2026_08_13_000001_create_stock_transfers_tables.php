<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('stock_transfers')) {
            Schema::create('stock_transfers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('from_company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('to_company_id')->constrained('companies')->cascadeOnDelete();
                $table->string('transfer_no', 30)->unique();
                $table->date('transfer_date');
                $table->text('notes')->nullable();
                $table->string('status', 20)->default('pending');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['to_company_id', 'status']);
                $table->index(['from_company_id', 'status']);
            });
        }

        if (!Schema::hasTable('stock_transfer_items')) {
            Schema::create('stock_transfer_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
                $table->foreignId('item_id')->constrained()->cascadeOnDelete();
                $table->decimal('quantity', 15, 3);
                $table->decimal('stock_before', 15, 3)->default(0);
                $table->decimal('unit_price', 15, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
    }
};
