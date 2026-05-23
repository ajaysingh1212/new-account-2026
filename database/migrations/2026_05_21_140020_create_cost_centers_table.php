<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cost_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->string('manager_name')->nullable();
            $table->string('department')->nullable();
            $table->decimal('budget_amount', 15, 2)->nullable();
            $table->date('budget_start_date')->nullable();
            $table->date('budget_end_date')->nullable();
            $table->string('status', 20)->default('active');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('sub_cost_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cost_center_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->string('owner_name')->nullable();
            $table->decimal('budget_amount', 15, 2)->nullable();
            $table->string('status', 20)->default('active');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'cost_center_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_cost_centers');
        Schema::dropIfExists('cost_centers');
    }
};
