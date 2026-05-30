<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('expense_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('ledger_code', 30);
            $table->string('name');
            $table->string('category', 80)->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->date('opening_balance_date')->nullable();
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->string('status', 20)->default('active');
            $table->string('attachment')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'ledger_code']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_ledger_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->date('expense_date');
            $table->string('expense_no', 30);
            $table->string('reference_no')->nullable();
            $table->string('vendor_name')->nullable();
            $table->decimal('amount', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->string('payment_mode', 40)->nullable();
            $table->string('status', 30)->default('pending_approval');
            $table->text('description')->nullable();
            $table->string('attachment')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approval_note')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['company_id', 'expense_no']);
            $table->index(['company_id', 'status', 'expense_date']);
        });

        Schema::create('terms_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('document_type', 30)->default('all');
            $table->text('content');
            $table->string('status', 20)->default('active');
            $table->boolean('is_default')->default(false);
            $table->string('attachment')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'document_type', 'status']);
        });

        Schema::table('bank_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('bank_accounts', 'upi_qr_code')) {
                $table->string('upi_qr_code')->nullable()->after('upi_id');
            }
        });

        foreach ([
            ['name' => 'Approve Expenses', 'slug' => 'expenses.approve', 'module' => 'expenses'],
            ['name' => 'Manage Terms', 'slug' => 'terms.manage', 'module' => 'terms'],
        ] as $permission) {
            DB::table('permissions')->updateOrInsert(['slug' => $permission['slug']], $permission + ['created_at' => now(), 'updated_at' => now()]);
        }

        $permissionIds = DB::table('permissions')->whereIn('slug', ['expenses.approve', 'terms.manage'])->pluck('id');
        $roleIds = DB::table('roles')->where('slug', 'company-admin')->pluck('id');
        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permission')->updateOrInsert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ], []);
            }
        }
    }

    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('bank_accounts', 'upi_qr_code')) {
                $table->dropColumn('upi_qr_code');
            }
        });
        Schema::dropIfExists('terms_templates');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_ledgers');
    }
};
