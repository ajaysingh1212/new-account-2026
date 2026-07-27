<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('party_opening_balance_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->date('adjustment_date');
            $table->decimal('previous_amount', 15, 2);
            $table->decimal('adjustment_amount', 15, 2);
            $table->decimal('new_amount', 15, 2);
            $table->string('direction', 20);
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('created_role')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'party_id', 'adjustment_date'], 'poba_company_party_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_opening_balance_adjustments');
    }
};
