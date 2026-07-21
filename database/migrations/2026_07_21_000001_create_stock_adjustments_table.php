<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->decimal('previous_stock', 15, 3);
            $table->decimal('new_stock', 15, 3);
            $table->decimal('stock_change', 15, 3);
            $table->decimal('unit_rate', 15, 2)->default(0);
            $table->decimal('previous_stock_value', 15, 2)->default(0);
            $table->decimal('new_stock_value', 15, 2)->default(0);
            $table->string('user_role', 100)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('adjusted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'item_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
