<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('replacements')) {
            return;
        }

        Schema::table('replacements', function (Blueprint $table) {
            if (!Schema::hasColumn('replacements', 'target_party_id')) {
                $table->foreignId('target_party_id')->nullable()->after('party_id')->constrained('parties')->nullOnDelete();
            }
            if (!Schema::hasColumn('replacements', 'ledger_enabled')) {
                $table->boolean('ledger_enabled')->default(true)->after('status');
            }
            if (!Schema::hasColumn('replacements', 'ledger_amount')) {
                $table->decimal('ledger_amount', 15, 2)->nullable()->after('ledger_enabled');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('replacements')) {
            return;
        }

        Schema::table('replacements', function (Blueprint $table) {
            if (Schema::hasColumn('replacements', 'target_party_id')) {
                $table->dropConstrainedForeignId('target_party_id');
            }
            if (Schema::hasColumn('replacements', 'ledger_enabled')) {
                $table->dropColumn('ledger_enabled');
            }
            if (Schema::hasColumn('replacements', 'ledger_amount')) {
                $table->dropColumn('ledger_amount');
            }
        });
    }
};
