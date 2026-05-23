<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('party_code', 30);
            $table->string('party_type', 20)->default('both');
            $table->string('display_name');
            $table->string('legal_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('alternate_phone', 30)->nullable();
            $table->string('whatsapp_number', 30)->nullable();
            $table->string('gstin', 20)->nullable();
            $table->string('pan_number', 20)->nullable();
            $table->string('tan_number', 20)->nullable();
            $table->string('cin_number', 30)->nullable();
            $table->string('tax_type', 20)->default('registered');
            $table->string('place_of_supply')->nullable();
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('city', 80)->nullable();
            $table->string('state', 80)->nullable();
            $table->string('pincode', 15)->nullable();
            $table->string('country', 80)->default('India');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->string('opening_balance_type', 20)->default('payable');
            $table->date('opening_balance_date')->nullable();
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->unsignedInteger('credit_days')->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_holder_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('ifsc_code', 20)->nullable();
            $table->string('branch_name')->nullable();
            $table->string('upi_id')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'party_code']);
            $table->index(['company_id', 'party_type', 'status']);
            $table->index(['company_id', 'display_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parties');
    }
};
