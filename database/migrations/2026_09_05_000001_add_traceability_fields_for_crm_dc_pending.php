<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('production_batches', function (Blueprint $table) {
            if (!Schema::hasColumn('production_batches', 'reverted_at')) {
                $table->timestamp('reverted_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('production_batches', 'reverted_by')) {
                $table->foreignId('reverted_by')->nullable()->after('reverted_at')->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_invoices', 'source_delivery_challan_id')) {
                $table->foreignId('source_delivery_challan_id')->nullable()->after('created_by')->constrained('delivery_challans')->nullOnDelete();
            }
            if (!Schema::hasColumn('sales_invoices', 'source_pending_order_id')) {
                $table->foreignId('source_pending_order_id')->nullable()->after('source_delivery_challan_id')->constrained('pending_orders')->nullOnDelete();
            }
        });

        Schema::table('pending_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('pending_orders', 'converted_sales_invoice_id')) {
                $table->foreignId('converted_sales_invoice_id')->nullable()->after('raw_materials')->constrained('sales_invoices')->nullOnDelete();
            }
            if (!Schema::hasColumn('pending_orders', 'converted_at')) {
                $table->timestamp('converted_at')->nullable()->after('converted_sales_invoice_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pending_orders', function (Blueprint $table) {
            if (Schema::hasColumn('pending_orders', 'converted_at')) {
                $table->dropColumn('converted_at');
            }
            if (Schema::hasColumn('pending_orders', 'converted_sales_invoice_id')) {
                $table->dropConstrainedForeignId('converted_sales_invoice_id');
            }
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('sales_invoices', 'source_pending_order_id')) {
                $table->dropConstrainedForeignId('source_pending_order_id');
            }
            if (Schema::hasColumn('sales_invoices', 'source_delivery_challan_id')) {
                $table->dropConstrainedForeignId('source_delivery_challan_id');
            }
        });

        Schema::table('production_batches', function (Blueprint $table) {
            if (Schema::hasColumn('production_batches', 'reverted_by')) {
                $table->dropConstrainedForeignId('reverted_by');
            }
            if (Schema::hasColumn('production_batches', 'reverted_at')) {
                $table->dropColumn('reverted_at');
            }
        });
    }
};
