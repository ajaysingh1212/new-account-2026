<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('party_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('party_payments', 'outsource_expense_amount')) {
                $table->decimal('outsource_expense_amount', 15, 2)->default(0)->after('discount_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('party_payments', function (Blueprint $table) {
            if (Schema::hasColumn('party_payments', 'outsource_expense_amount')) {
                $table->dropColumn('outsource_expense_amount');
            }
        });
    }
};
