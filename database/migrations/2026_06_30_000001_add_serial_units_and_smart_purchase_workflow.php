<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('delivery_challan_items', function (Blueprint $table) {
            $table->json('selected_units')->nullable()->after('line_total');
        });
        Schema::table('stock_out_challan_items', function (Blueprint $table) {
            $table->json('selected_units')->nullable()->after('line_total');
        });
        Schema::table('purchase_estimates', function (Blueprint $table) {
            $table->boolean('is_smart_purchase')->default(false)->after('status');
            $table->date('analysis_from')->nullable()->after('is_smart_purchase');
            $table->date('analysis_to')->nullable()->after('analysis_from');
            $table->timestamp('transit_at')->nullable()->after('converted_at');
            $table->boolean('payment_completed')->nullable()->after('transit_at');
            $table->foreignId('payment_bank_account_id')->nullable()->after('payment_completed')->constrained('bank_accounts')->nullOnDelete();
            $table->string('payment_mode', 30)->nullable()->after('payment_bank_account_id');
            $table->string('payment_reference')->nullable()->after('payment_mode');
        });
        Schema::table('purchase_estimate_items', function (Blueprint $table) {
            $table->decimal('received_quantity', 15, 3)->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_estimate_items', fn(Blueprint $table) => $table->dropColumn('received_quantity'));
        Schema::table('purchase_estimates', function (Blueprint $table) {
            $table->dropForeign(['payment_bank_account_id']);
            $table->dropColumn(['is_smart_purchase','analysis_from','analysis_to','transit_at','payment_completed','payment_bank_account_id','payment_mode','payment_reference']);
        });
        Schema::table('stock_out_challan_items', fn(Blueprint $table) => $table->dropColumn('selected_units'));
        Schema::table('delivery_challan_items', fn(Blueprint $table) => $table->dropColumn('selected_units'));
    }
};
