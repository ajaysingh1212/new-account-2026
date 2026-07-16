<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Item;
use App\Models\ProductType;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockFloorZeroTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_never_goes_below_zero_and_recovery_starts_from_zero(): void
    {
        [$user, $item] = $this->context();

        $this->actingAs($user);

        app(AccountingService::class)->moveStock($item, [
            'movement_date' => '2026-07-16',
            'movement_type' => 'sale',
            'direction' => 'out',
            'quantity' => 5,
            'unit_price' => 10,
            'total_value' => 50,
            'reference_no' => 'NEG-TEST-1',
        ]);

        $this->assertSame(0.0, (float) $item->fresh()->current_stock);

        $item->fresh()->update(['current_stock' => -3, 'stock_value' => -30]);

        app(AccountingService::class)->moveStock($item, [
            'movement_date' => '2026-07-16',
            'movement_type' => 'production_output',
            'direction' => 'in',
            'quantity' => 3,
            'unit_price' => 10,
            'total_value' => 30,
            'reference_no' => 'NEG-TEST-2',
        ]);

        $this->assertSame(3.0, (float) $item->fresh()->current_stock);
    }

    private function context(): array
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create(['name' => 'Stock Floor Company', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $company->id]);

        $type = ProductType::create([
            'company_id' => $company->id,
            'code' => 'RAW',
            'name' => 'Raw Materials',
            'nature' => 'raw_material',
            'status' => 'active',
        ]);

        $item = Item::create([
            'company_id' => $company->id,
            'product_type_id' => $type->id,
            'item_type' => 'product',
            'item_code' => 'RAW-FLOOR',
            'name' => 'Floor Test Item',
            'unit' => 'PCS',
            'purchase_price' => 10,
            'sale_price' => 15,
            'current_stock' => 0,
            'stock_value' => 0,
            'track_stock' => true,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        return [$user, $item];
    }
}
