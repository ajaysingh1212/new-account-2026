<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchase_bills', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_bills', 'attachment')) {
                $table->string('attachment')->nullable()->after('terms');
            }
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_invoices', 'attachment')) {
                $table->string('attachment')->nullable()->after('terms');
            }
        });

        Schema::create('party_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->date('payment_date');
            $table->string('payment_type', 20);
            $table->string('reference_no')->nullable();
            $table->decimal('amount', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->string('payment_mode', 40)->nullable();
            $table->text('description')->nullable();
            $table->string('attachment')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['company_id', 'party_id', 'payment_date']);
            $table->index(['company_id', 'payment_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_payments');
        Schema::table('sales_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('sales_invoices', 'attachment')) {
                $table->dropColumn('attachment');
            }
        });
        Schema::table('purchase_bills', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_bills', 'attachment')) {
                $table->dropColumn('attachment');
            }
        });
    }
};
