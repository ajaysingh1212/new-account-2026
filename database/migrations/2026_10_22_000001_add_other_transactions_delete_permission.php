<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->updateOrInsert(
            ['slug' => 'other_transactions.delete'],
            ['name' => 'Delete Other Income Expense', 'module' => 'other_transactions', 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public function down(): void
    {
        DB::table('permissions')->where('slug', 'other_transactions.delete')->delete();
    }
};
