<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserCompany;
use App\Models\UserRole;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name'=>'View Users', 'slug'=>'users.view', 'module'=>'users'],
            ['name'=>'Create Users', 'slug'=>'users.create', 'module'=>'users'],
            ['name'=>'Edit Users', 'slug'=>'users.edit', 'module'=>'users'],
            ['name'=>'Delete Users', 'slug'=>'users.delete', 'module'=>'users'],

            ['name'=>'View Roles', 'slug'=>'roles.view', 'module'=>'roles'],
            ['name'=>'Create Roles', 'slug'=>'roles.create', 'module'=>'roles'],
            ['name'=>'Edit Roles', 'slug'=>'roles.edit', 'module'=>'roles'],
            ['name'=>'Delete Roles', 'slug'=>'roles.delete', 'module'=>'roles'],

            ['name'=>'View Sales', 'slug'=>'sales.view', 'module'=>'sales'],
            ['name'=>'Create Sales', 'slug'=>'sales.create', 'module'=>'sales'],
            ['name'=>'Edit Sales', 'slug'=>'sales.edit', 'module'=>'sales'],
            ['name'=>'Delete Sales', 'slug'=>'sales.delete', 'module'=>'sales'],
            ['name'=>'Print Sales', 'slug'=>'sales.print', 'module'=>'sales'],

            ['name'=>'View Estimates', 'slug'=>'estimates.view', 'module'=>'estimates'],
            ['name'=>'Create Estimates', 'slug'=>'estimates.create', 'module'=>'estimates'],
            ['name'=>'Edit Estimates', 'slug'=>'estimates.edit', 'module'=>'estimates'],
            ['name'=>'Delete Estimates', 'slug'=>'estimates.delete', 'module'=>'estimates'],
            ['name'=>'Convert Estimates', 'slug'=>'estimates.convert', 'module'=>'estimates'],
            ['name'=>'Print Estimates', 'slug'=>'estimates.print', 'module'=>'estimates'],

            ['name'=>'View Delivery Challans', 'slug'=>'delivery_challans.view', 'module'=>'delivery_challans'],
            ['name'=>'Create Delivery Challans', 'slug'=>'delivery_challans.create', 'module'=>'delivery_challans'],
            ['name'=>'Edit Delivery Challans', 'slug'=>'delivery_challans.edit', 'module'=>'delivery_challans'],
            ['name'=>'Delete Delivery Challans', 'slug'=>'delivery_challans.delete', 'module'=>'delivery_challans'],
            ['name'=>'Print Delivery Challans', 'slug'=>'delivery_challans.print', 'module'=>'delivery_challans'],

            ['name'=>'View Purchase', 'slug'=>'purchase.view', 'module'=>'purchase'],
            ['name'=>'Create Purchase', 'slug'=>'purchase.create', 'module'=>'purchase'],
            ['name'=>'Edit Purchase', 'slug'=>'purchase.edit', 'module'=>'purchase'],
            ['name'=>'Delete Purchase', 'slug'=>'purchase.delete', 'module'=>'purchase'],
            ['name'=>'Print Purchase', 'slug'=>'purchase.print', 'module'=>'purchase'],

            ['name'=>'View Stocks', 'slug'=>'stocks.view', 'module'=>'stocks'],
            ['name'=>'Add Stocks', 'slug'=>'stocks.create', 'module'=>'stocks'],
            ['name'=>'Edit Stocks', 'slug'=>'stocks.edit', 'module'=>'stocks'],

            ['name'=>'View Items', 'slug'=>'items.view', 'module'=>'items'],
            ['name'=>'Create Items', 'slug'=>'items.create', 'module'=>'items'],
            ['name'=>'Edit Items', 'slug'=>'items.edit', 'module'=>'items'],
            ['name'=>'Delete Items', 'slug'=>'items.delete', 'module'=>'items'],

            ['name'=>'View Product Types', 'slug'=>'product_types.view', 'module'=>'product_types'],
            ['name'=>'Manage Product Types', 'slug'=>'product_types.manage', 'module'=>'product_types'],

            ['name'=>'View Production', 'slug'=>'production.view', 'module'=>'production'],
            ['name'=>'Create Production', 'slug'=>'production.create', 'module'=>'production'],

            ['name'=>'View Expenses', 'slug'=>'expenses.view', 'module'=>'expenses'],
            ['name'=>'Create Expenses', 'slug'=>'expenses.create', 'module'=>'expenses'],
            ['name'=>'Edit Expenses', 'slug'=>'expenses.edit', 'module'=>'expenses'],
            ['name'=>'Delete Expenses', 'slug'=>'expenses.delete', 'module'=>'expenses'],
            ['name'=>'Approve Expenses', 'slug'=>'expenses.approve', 'module'=>'expenses'],

            ['name'=>'View Parties', 'slug'=>'parties.view', 'module'=>'parties'],
            ['name'=>'Create Parties', 'slug'=>'parties.create', 'module'=>'parties'],
            ['name'=>'Edit Parties', 'slug'=>'parties.edit', 'module'=>'parties'],
            ['name'=>'Delete Parties', 'slug'=>'parties.delete', 'module'=>'parties'],

            ['name'=>'View Banking', 'slug'=>'banking.view', 'module'=>'banking'],
            ['name'=>'Manage Banking', 'slug'=>'banking.manage', 'module'=>'banking'],

            ['name'=>'View Cost Centers', 'slug'=>'cost_centers.view', 'module'=>'cost_centers'],
            ['name'=>'Manage Cost Centers', 'slug'=>'cost_centers.manage', 'module'=>'cost_centers'],

            ['name'=>'View Party Payments', 'slug'=>'party_payments.view', 'module'=>'party_payments'],
            ['name'=>'Create Party Payments', 'slug'=>'party_payments.create', 'module'=>'party_payments'],

            ['name'=>'View Party Reports', 'slug'=>'reports.party', 'module'=>'reports'],
            ['name'=>'View Stock Reports', 'slug'=>'reports.stock', 'module'=>'reports'],
            ['name'=>'View Expense Reports', 'slug'=>'reports.expense', 'module'=>'reports'],
            ['name'=>'View GST Reports', 'slug'=>'reports.gst', 'module'=>'reports'],
            ['name'=>'View Transaction Reports', 'slug'=>'reports.transaction', 'module'=>'reports'],

            ['name'=>'View Audit Logs', 'slug'=>'audit.view', 'module'=>'audit'],
            ['name'=>'Manage Terms', 'slug'=>'terms.manage', 'module'=>'terms'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['slug' => $permission['slug']], $permission);
        }

        $this->backfillCompanyAdminRoles();

        $this->command->info('Permissions seeded: ' . count($permissions));
    }

    private function backfillCompanyAdminRoles(): void
    {
        $permissionIds = Permission::whereNotIn('module', ['permissions','companies'])->pluck('id')->all();

        Company::all()->each(function (Company $company) use ($permissionIds) {
            $role = Role::firstOrCreate(
                ['company_id' => $company->id, 'slug' => 'company-admin'],
                [
                    'name' => 'Company Admin',
                    'description' => 'Default full-access admin role for this company.',
                    'is_active' => true,
                ]
            );

            $role->permissions()->sync($permissionIds);

            User::where('user_type', 'admin')
                ->where('current_company_id', $company->id)
                ->get()
                ->each(function (User $admin) use ($company, $role) {
                    UserCompany::firstOrCreate(['user_id' => $admin->id, 'company_id' => $company->id]);
                    UserRole::firstOrCreate(['user_id' => $admin->id, 'company_id' => $company->id, 'role_id' => $role->id]);
                });
        });
    }
}
