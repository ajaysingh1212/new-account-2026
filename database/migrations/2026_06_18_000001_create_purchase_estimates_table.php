<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            if (!Schema::hasColumn('parties', 'district')) {
                $table->string('district', 80)->nullable()->after('city');
            }
        });

        Schema::create('purchase_estimates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sub_cost_center_id')->nullable()->constrained()->nullOnDelete();
            $table->string('estimate_no', 30);
            $table->date('estimate_date');
            $table->date('valid_until')->nullable();
            $table->string('reference_no')->nullable();
            $table->string('phone')->nullable();
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->string('attachment')->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignId('converted_purchase_bill_id')->nullable()->constrained('purchase_bills')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'estimate_no']);
        });

        Schema::create('purchase_estimate_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_estimate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->decimal('quantity', 15, 3);
            $table->string('unit', 20)->nullable();
            $table->decimal('unit_price', 15, 2);
            $table->string('discount_type', 10)->default('percent');
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_estimate_items');
        Schema::dropIfExists('purchase_estimates');
        Schema::table('parties', function (Blueprint $table) {
            if (Schema::hasColumn('parties', 'district')) {
                $table->dropColumn('district');
            }
        });
    }
};
