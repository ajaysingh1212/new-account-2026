<?php
// database/migrations/2026_06_27_000001_add_max_discount_percent_to_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->decimal('max_discount_percent', 5, 2)->nullable()->after('sale_gst_percent');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('max_discount_percent');
        });
    }
};
