<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cheque_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->string('book_no', 40);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->unsignedInteger('leaf_count');
            $table->unsignedInteger('next_leaf_no')->default(1);
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'book_no']);
            $table->index(['company_id', 'bank_account_id', 'status']);
        });

        Schema::create('cheque_leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cheque_book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('party_payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('cheque_no', 60);
            $table->date('cheque_date');
            $table->decimal('amount', 15, 2);
            $table->string('payee_name')->nullable();
            $table->string('amount_words')->nullable();
            $table->string('memo')->nullable();
            $table->unsignedTinyInteger('validity_months')->default(3);
            $table->date('clearance_due_date')->nullable();
            $table->string('status', 30)->default('issued');
            $table->boolean('payment_done')->default(false);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'cheque_no']);
            $table->index(['company_id', 'status', 'clearance_due_date']);
            $table->index(['company_id', 'party_id']);
        });

        Schema::table('party_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('party_payments', 'cheque_leaf_id')) {
                $table->foreignId('cheque_leaf_id')->nullable()->after('bank_account_id')->constrained('cheque_leaves')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('party_payments', function (Blueprint $table) {
            if (Schema::hasColumn('party_payments', 'cheque_leaf_id')) {
                $table->dropForeign(['cheque_leaf_id']);
                $table->dropColumn('cheque_leaf_id');
            }
        });
        Schema::dropIfExists('cheque_leaves');
        Schema::dropIfExists('cheque_books');
    }
};
