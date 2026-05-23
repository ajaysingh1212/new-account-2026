<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('bank_transactions')) {
            return;
        }

        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
            $table->date('transaction_date');
            $table->string('transaction_type', 40);
            $table->string('direction', 10);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2)->default(0);
            $table->string('reference_no')->nullable();
            $table->string('payment_mode', 40)->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->string('attachment')->nullable();
            $table->string('transfer_group', 80)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'bank_account_id', 'transaction_date'], 'bank_txn_account_date_idx');
            $table->index(['company_id', 'transaction_type'], 'bank_txn_type_idx');
            $table->index('transfer_group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
