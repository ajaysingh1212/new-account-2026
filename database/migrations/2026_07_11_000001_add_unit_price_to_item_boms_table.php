<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('item_boms', function (Blueprint $table) {
            $table->decimal('unit_price', 15, 2)->nullable()->after('qty_per_unit');
        });
    }

    public function down(): void
    {
        Schema::table('item_boms', function (Blueprint $table) {
            $table->dropColumn('unit_price');
        });
    }
};
