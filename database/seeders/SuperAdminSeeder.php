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
            ['email' => 'superadmin@gmail.com'],
            [
                'name'      => 'Super Administrator',
                'password'  => Hash::make('password'),
                'user_type' => 'super_admin',
                'is_active' => true,
            ]
        );

        $this->command->info("✅ Super Admin created:");
        $this->command->info("   Email: superadmin@gamil.com");
        $this->command->info("   Password: password");
        $this->command->warn("   ⚠️  Please change the password after first login!");
    }
}
