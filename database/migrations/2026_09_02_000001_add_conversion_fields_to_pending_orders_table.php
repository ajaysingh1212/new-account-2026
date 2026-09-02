<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('pending_orders', 'converted_sales_invoice_id')) {
                $table->foreignId('converted_sales_invoice_id')
                    ->nullable()
                    ->after('status')
                    ->constrained('sales_invoices')
                    ->nullOnDelete();
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
    }
};
