<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('other_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_ledger_id')->constrained('expense_ledgers')->cascadeOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('transaction_no', 30);
            $table->string('transaction_kind', 20);
            $table->date('transaction_date');
            $table->string('reference_no')->nullable();
            $table->string('party_name')->nullable();
            $table->decimal('amount', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->string('payment_mode', 40)->nullable();
            $table->string('status', 30)->default('pending_approval');
            $table->text('description')->nullable();
            $table->string('attachment')->nullable();
            $table->datetime('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approval_note')->nullable();
            $table->datetime('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->decimal('ledger_balance_after', 15, 2)->nullable();
            $table->decimal('bank_balance_after', 15, 2)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'transaction_no']);
            $table->index(['company_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('other_transactions');
    }
};
