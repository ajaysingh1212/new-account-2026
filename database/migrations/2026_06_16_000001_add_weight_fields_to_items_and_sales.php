<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            if (!Schema::hasColumn('items', 'per_quantity_weight')) {
                $table->decimal('per_quantity_weight', 15, 3)->nullable()->after('low_stock_qty');
            }
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_invoices', 'total_weight')) {
                $table->decimal('total_weight', 15, 3)->default(0)->after('grand_total');
            }
        });

        Schema::table('sales_invoice_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_invoice_items', 'line_weight')) {
                $table->decimal('line_weight', 15, 3)->default(0)->after('line_total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoice_items', function (Blueprint $table) {
            if (Schema::hasColumn('sales_invoice_items', 'line_weight')) {
                $table->dropColumn('line_weight');
            }
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('sales_invoices', 'total_weight')) {
                $table->dropColumn('total_weight');
            }
        });

        Schema::table('items', function (Blueprint $table) {
            if (Schema::hasColumn('items', 'per_quantity_weight')) {
                $table->dropColumn('per_quantity_weight');
            }
        });
    }
};
