<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $permissionId = DB::table('permissions')->insertGetId([
            'name' => 'Use AI Assistant', 'slug' => 'ai_assistant.use', 'module' => 'ai_assistant',
            'description' => 'Use company-scoped AI data search and workflow links.',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $roleIds = DB::table('roles')->where('slug', 'company-admin')->pluck('id');
        foreach ($roleIds as $roleId) {
            DB::table('role_permission')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId]);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('slug', 'ai_assistant.use')->value('id');
        if ($permissionId) {
            DB::table('role_permission')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
