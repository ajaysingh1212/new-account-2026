<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Item;
use App\Models\ProductType;
use App\Models\Role;
use App\Models\StockAdjustment;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_can_adjust_raw_material_stock_and_history_is_saved(): void
    {
        $user = User::factory()->create([
            'name' => 'Admin User',
            'user_type' => 'admin',
        ]);

        $company = Company::create([
            'name' => 'Test Company',
            'created_by' => $user->id,
        ]);

        $user->update(['current_company_id' => $company->id]);
        $role = Role::create([
            'name' => 'Company Admin',
            'slug' => 'company-admin',
            'company_id' => $company->id,
            'is_active' => true,
        ]);
        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'company_id' => $company->id,
        ]);

        $type = ProductType::create([
            'company_id' => $company->id,
            'code' => 'RM-001',
            'name' => 'Raw Materials',
            'nature' => 'raw_material',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $item = Item::create([
            'company_id' => $company->id,
            'product_type_id' => $type->id,
            'item_type' => 'product',
            'item_code' => 'RM-100',
            'name' => 'Copper Wire',
            'unit' => 'KG',
            'purchase_price' => 100,
            'sale_price' => 120,
            'current_stock' => 10,
            'stock_value' => 1000,
            'track_stock' => true,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->withoutMiddleware()->post(route('admin.stocks.adjust', $item), [
            'target_stock' => 15.5,
            'note' => 'Physical count update',
        ])->assertRedirect();

        $item->refresh();

        $this->assertSame('15.500', (string) $item->current_stock);
        $this->assertSame('1550.00', (string) $item->stock_value);

        $this->assertDatabaseHas('stock_adjustments', [
            'company_id' => $company->id,
            'item_id' => $item->id,
            'previous_stock' => '10.000',
            'new_stock' => '15.500',
            'stock_change' => '5.500',
            'user_role' => 'Company Admin',
            'adjusted_by' => $user->id,
        ]);

        $this->actingAs($user)->withoutMiddleware()
            ->get(route('admin.stocks.history', ['item_id' => $item->id]))
            ->assertOk()
            ->assertSee('Manual Stock Adjustment')
            ->assertSee('Physical count update');

        $this->assertCount(1, StockAdjustment::all());
    }

    public function test_non_admin_cannot_adjust_raw_material_stock(): void
    {
        $user = User::factory()->create([
            'name' => 'Regular User',
            'user_type' => 'user',
        ]);

        $company = Company::create([
            'name' => 'Test Company 2',
            'created_by' => $user->id,
        ]);

        $user->update(['current_company_id' => $company->id]);

        $type = ProductType::create([
            'company_id' => $company->id,
            'code' => 'RM-002',
            'name' => 'Raw Materials',
            'nature' => 'raw_material',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $item = Item::create([
            'company_id' => $company->id,
            'product_type_id' => $type->id,
            'item_type' => 'product',
            'item_code' => 'RM-200',
            'name' => 'Aluminium Sheet',
            'unit' => 'KG',
            'purchase_price' => 50,
            'sale_price' => 60,
            'current_stock' => 5,
            'stock_value' => 250,
            'track_stock' => true,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->withoutMiddleware()
            ->post(route('admin.stocks.adjust', $item), [
                'target_stock' => 8,
            ])->assertForbidden();
    }
}
