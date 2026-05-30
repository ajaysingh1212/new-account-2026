<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('party_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->string('bill_type', 20);
            $table->string('bill_model');
            $table->unsignedBigInteger('bill_id');
            $table->string('bill_no')->nullable();
            $table->date('bill_date')->nullable();
            $table->decimal('bill_total', 15, 2)->default(0);
            $table->decimal('amount', 15, 2);
            $table->timestamps();
            $table->index(['company_id', 'party_id', 'bill_type']);
            $table->index(['bill_model', 'bill_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'screen_pin')) {
                $table->string('screen_pin')->nullable()->after('password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'screen_pin')) {
                $table->dropColumn('screen_pin');
            }
        });
        Schema::dropIfExists('party_payment_allocations');
    }
};
