<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('bank_transactions', 'ledger_name')) {
                $table->string('ledger_name')->nullable()->after('party_id');
            }
            if (!Schema::hasColumn('bank_transactions', 'expense_ledger_id')) {
                $table->foreignId('expense_ledger_id')->nullable()->after('party_id')->constrained('expense_ledgers')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('bank_transactions', 'expense_ledger_id')) {
                $table->dropConstrainedForeignId('expense_ledger_id');
            }
            if (Schema::hasColumn('bank_transactions', 'ledger_name')) {
                $table->dropColumn('ledger_name');
            }
        });
    }
};
