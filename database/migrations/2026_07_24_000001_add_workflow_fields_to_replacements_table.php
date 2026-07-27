<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('replacements', function (Blueprint $table) {
            $table->foreignId('target_party_id')->nullable()->after('party_id')->constrained('parties')->nullOnDelete();
            $table->boolean('ledger_enabled')->default(true)->after('status');
            $table->decimal('ledger_amount', 15, 2)->nullable()->after('ledger_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('replacements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('target_party_id');
            $table->dropColumn(['ledger_enabled', 'ledger_amount']);
        });
    }
};
