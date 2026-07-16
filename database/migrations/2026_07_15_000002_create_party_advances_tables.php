<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('party_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_payment_id')->constrained()->cascadeOnDelete();
            $table->string('direction', 10);
            $table->date('advance_date');
            $table->decimal('original_amount', 15, 2);
            $table->decimal('remaining_amount', 15, 2);
            $table->string('reference_no')->nullable();
            $table->string('payment_mode', 40)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['company_id', 'party_id', 'direction']);
            $table->index(['company_id', 'advance_date']);
        });

        Schema::create('party_advance_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_advance_id')->constrained()->cascadeOnDelete();
            $table->string('document_type');
            $table->unsignedBigInteger('document_id');
            $table->string('document_no')->nullable();
            $table->decimal('amount', 15, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['document_type', 'document_id']);
            $table->index(['company_id', 'party_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_advance_allocations');
        Schema::dropIfExists('party_advances');
    }
};
