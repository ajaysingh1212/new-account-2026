<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->string('period_type', 20);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'party_id', 'starts_on', 'ends_on']);
        });

        Schema::create('sales_target_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_target_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_type_id')->constrained()->cascadeOnDelete();
            $table->string('target_type', 20);
            $table->decimal('target_value', 15, 3);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['sales_target_id', 'product_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_target_items');
        Schema::dropIfExists('sales_targets');
    }
};
