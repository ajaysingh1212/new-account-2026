<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('item_boms', function (Blueprint $table) {
            $table->string('line_type', 20)->default('raw_material')->after('raw_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('item_boms', function (Blueprint $table) {
            $table->dropColumn('line_type');
        });
    }
};
