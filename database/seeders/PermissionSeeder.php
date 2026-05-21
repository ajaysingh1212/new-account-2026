<?php
namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Users
            ['name'=>'View Users',    'slug'=>'users.view',    'module'=>'users'],
            ['name'=>'Create Users',  'slug'=>'users.create',  'module'=>'users'],
            ['name'=>'Edit Users',    'slug'=>'users.edit',    'module'=>'users'],
            ['name'=>'Delete Users',  'slug'=>'users.delete',  'module'=>'users'],

            // Roles
            ['name'=>'View Roles',    'slug'=>'roles.view',    'module'=>'roles'],
            ['name'=>'Create Roles',  'slug'=>'roles.create',  'module'=>'roles'],
            ['name'=>'Edit Roles',    'slug'=>'roles.edit',    'module'=>'roles'],
            ['name'=>'Delete Roles',  'slug'=>'roles.delete',  'module'=>'roles'],

            // Sales
            ['name'=>'View Sales',    'slug'=>'sales.view',    'module'=>'sales'],
            ['name'=>'Create Sales',  'slug'=>'sales.create',  'module'=>'sales'],
            ['name'=>'Edit Sales',    'slug'=>'sales.edit',    'module'=>'sales'],
            ['name'=>'Delete Sales',  'slug'=>'sales.delete',  'module'=>'sales'],

            // Purchase
            ['name'=>'View Purchase',   'slug'=>'purchase.view',   'module'=>'purchase'],
            ['name'=>'Create Purchase', 'slug'=>'purchase.create', 'module'=>'purchase'],
            ['name'=>'Edit Purchase',   'slug'=>'purchase.edit',   'module'=>'purchase'],
            ['name'=>'Delete Purchase', 'slug'=>'purchase.delete', 'module'=>'purchase'],

            // Stocks
            ['name'=>'View Stocks',   'slug'=>'stocks.view',   'module'=>'stocks'],
            ['name'=>'Add Stocks',    'slug'=>'stocks.create', 'module'=>'stocks'],
            ['name'=>'Edit Stocks',   'slug'=>'stocks.edit',   'module'=>'stocks'],

            // Expenses
            ['name'=>'View Expenses',   'slug'=>'expenses.view',   'module'=>'expenses'],
            ['name'=>'Create Expenses', 'slug'=>'expenses.create', 'module'=>'expenses'],
            ['name'=>'Edit Expenses',   'slug'=>'expenses.edit',   'module'=>'expenses'],
            ['name'=>'Delete Expenses', 'slug'=>'expenses.delete', 'module'=>'expenses'],

            // Parties
            ['name'=>'View Parties',   'slug'=>'parties.view',   'module'=>'parties'],
            ['name'=>'Create Parties', 'slug'=>'parties.create', 'module'=>'parties'],
            ['name'=>'Edit Parties',   'slug'=>'parties.edit',   'module'=>'parties'],
            ['name'=>'Delete Parties', 'slug'=>'parties.delete', 'module'=>'parties'],

            // Banking
            ['name'=>'View Banking',   'slug'=>'banking.view',   'module'=>'banking'],
            ['name'=>'Manage Banking', 'slug'=>'banking.manage', 'module'=>'banking'],

            // Reports
            ['name'=>'View Party Reports',   'slug'=>'reports.party',   'module'=>'reports'],
            ['name'=>'View Stock Reports',   'slug'=>'reports.stock',   'module'=>'reports'],
            ['name'=>'View Expense Reports', 'slug'=>'reports.expense', 'module'=>'reports'],
            ['name'=>'View GST Reports',     'slug'=>'reports.gst',     'module'=>'reports'],

            // Audit Logs
            ['name'=>'View Audit Logs', 'slug'=>'audit.view', 'module'=>'audit'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['slug' => $perm['slug']], $perm);
        }

        $this->command->info('✅ ' . count($permissions) . ' permissions seeded.');
    }
}
