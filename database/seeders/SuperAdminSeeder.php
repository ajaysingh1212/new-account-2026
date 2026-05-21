<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@bizaccount.com'],
            [
                'name'      => 'Super Administrator',
                'password'  => Hash::make('SuperAdmin@123'),
                'user_type' => 'super_admin',
                'is_active' => true,
            ]
        );

        $this->command->info("✅ Super Admin created:");
        $this->command->info("   Email: superadmin@bizaccount.com");
        $this->command->info("   Password: SuperAdmin@123");
        $this->command->warn("   ⚠️  Please change the password after first login!");
    }
}
