<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_out_challans', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_out_challans', 'party_name')) {
                $table->string('party_name')->nullable()->after('party_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_out_challans', function (Blueprint $table) {
            if (Schema::hasColumn('stock_out_challans', 'party_name')) {
                $table->dropColumn('party_name');
            }
        });
    }
};
