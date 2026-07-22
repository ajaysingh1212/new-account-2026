<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('sales_target_items', 'product_category_id')) {
            Schema::table('sales_target_items', function (Blueprint $table) {
                $table->foreignId('product_category_id')->nullable()->after('sales_target_id')->constrained('product_categories')->nullOnDelete();
            });
        }

        DB::statement('UPDATE sales_target_items sti INNER JOIN product_types pt ON pt.id = sti.product_type_id SET sti.product_category_id = pt.product_category_id WHERE sti.product_category_id IS NULL');

        if (Schema::hasColumn('sales_target_items', 'product_type_id')) {
            $foreignKeys = collect(Schema::getForeignKeys('sales_target_items'));
            if ($foreignKeys->contains(fn ($key) => in_array('product_type_id', $key['columns'] ?? [], true))) {
                Schema::table('sales_target_items', fn (Blueprint $table) => $table->dropForeign(['product_type_id']));
            }
            $indexes = collect(Schema::getIndexes('sales_target_items'));
            if (!$indexes->contains(fn ($index) => $index['name'] === 'sales_target_items_sales_target_id_index')) {
                Schema::table('sales_target_items', fn (Blueprint $table) => $table->index('sales_target_id'));
            }
            Schema::table('sales_target_items', function (Blueprint $table) {
                $table->dropUnique(['sales_target_id', 'product_type_id']);
                $table->dropColumn('product_type_id');
            });
        }
        $indexes = collect(Schema::getIndexes('sales_target_items'));
        if (!$indexes->contains(fn ($index) => $index['name'] === 'sales_target_items_sales_target_id_product_category_id_unique')) {
            Schema::table('sales_target_items', fn (Blueprint $table) => $table->unique(['sales_target_id', 'product_category_id']));
        }
    }

    public function down(): void
    {
        Schema::table('sales_target_items', function (Blueprint $table) {
            $table->foreignId('product_type_id')->nullable()->after('sales_target_id')->constrained('product_types')->nullOnDelete();
        });
        Schema::table('sales_target_items', function (Blueprint $table) {
            $table->dropUnique(['sales_target_id', 'product_category_id']);
            $table->dropForeign(['product_category_id']);
            $table->dropColumn('product_category_id');
            $table->unique(['sales_target_id', 'product_type_id']);
        });
    }
};
