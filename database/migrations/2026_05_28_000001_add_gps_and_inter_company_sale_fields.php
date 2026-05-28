<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchase_bill_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_bill_items', 'selected_units')) {
                $table->json('selected_units')->nullable()->after('line_total');
            }
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_invoices', 'inter_company_transfer')) {
                $table->boolean('inter_company_transfer')->default(false)->after('status');
            }
            if (!Schema::hasColumn('sales_invoices', 'inter_company_target_company_ids')) {
                $table->json('inter_company_target_company_ids')->nullable()->after('inter_company_transfer');
            }
        });

        Schema::table('purchase_bills', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_bills', 'source_sales_invoice_id')) {
                $table->foreignId('source_sales_invoice_id')->nullable()->after('status')->constrained('sales_invoices')->nullOnDelete();
            }
            if (!Schema::hasColumn('purchase_bills', 'inter_company_source_company_id')) {
                $table->foreignId('inter_company_source_company_id')->nullable()->after('source_sales_invoice_id')->constrained('companies')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_bills', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_bills', 'inter_company_source_company_id')) {
                $table->dropConstrainedForeignId('inter_company_source_company_id');
            }
            if (Schema::hasColumn('purchase_bills', 'source_sales_invoice_id')) {
                $table->dropConstrainedForeignId('source_sales_invoice_id');
            }
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('sales_invoices', 'inter_company_target_company_ids')) {
                $table->dropColumn('inter_company_target_company_ids');
            }
            if (Schema::hasColumn('sales_invoices', 'inter_company_transfer')) {
                $table->dropColumn('inter_company_transfer');
            }
        });

        Schema::table('purchase_bill_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_bill_items', 'selected_units')) {
                $table->dropColumn('selected_units');
            }
        });
    }
};
