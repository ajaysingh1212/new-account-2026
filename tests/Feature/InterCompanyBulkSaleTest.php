<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyMerge;
use App\Models\Item;
use App\Models\ProductCategory;
use App\Models\ProductionBatch;
use App\Models\ProductType;
use App\Models\PurchaseBill;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterCompanyBulkSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_inter_company_sale_posts_every_line_and_copies_item_master_data(): void
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $sourceCompany = Company::create(['name' => 'Source Company', 'created_by' => $user->id]);
        $targetCompany = Company::create(['name' => 'Target Company', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $sourceCompany->id]);
        CompanyMerge::create([
            'company_id' => $sourceCompany->id,
            'merged_with_company_id' => $targetCompany->id,
            'created_by' => $user->id,
        ]);

        $category = ProductCategory::create([
            'company_id' => $sourceCompany->id,
            'name' => 'Vehicle Devices',
            'status' => 'active',
            'created_by' => $user->id,
        ]);
        $type = ProductType::create([
            'company_id' => $sourceCompany->id,
            'product_category_id' => $category->id,
            'code' => 'TRACKER',
            'name' => 'GPS Tracker',
            'nature' => 'finished_goods',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $first = $this->finishedItem($sourceCompany, $type, $category, $user, 'TRK-001', 'Tracker One');
        $second = $this->finishedItem($sourceCompany, $type, $category, $user, 'TRK-002', 'Tracker Two');
        $this->stockUnit($sourceCompany, $first, $user, 'PB-1', 'SER-1');
        $this->stockUnit($sourceCompany, $second, $user, 'PB-2', 'SER-2');

        $this->actingAs($user)->withoutMiddleware()->post(route('admin.sales.store'), [
            'sale_type' => 'cash',
            'invoice_no' => 'BULK-IC-1',
            'billing_date' => '2026-09-09',
            'item_id' => [$first->id, $second->id],
            'quantity' => [1, 1],
            'unit_price' => [1500, 2500],
            'tax_mode' => ['with_gst', 'with_gst'],
            'tax_percent' => [18, 18],
            'selected_units' => [
                json_encode([['key' => 'PB-1-0', 'serial_no' => 'SER-1']]),
                json_encode([['key' => 'PB-2-0', 'serial_no' => 'SER-2']]),
            ],
            'inter_company_transfer' => true,
            'target_company_ids' => [$targetCompany->id],
        ])->assertRedirect(route('admin.sales.index'));

        $purchase = PurchaseBill::where('company_id', $targetCompany->id)->firstOrFail();
        $this->assertCount(2, $purchase->items);
        $this->assertSame(2, StockMovement::where('company_id', $targetCompany->id)
            ->where('movement_type', 'inter_company_purchase')
            ->count());
        $this->assertSame(2.0, (float) Item::where('company_id', $targetCompany->id)->sum('current_stock'));

        $targetItem = Item::where('company_id', $targetCompany->id)->where('item_code', 'TRK-001')->firstOrFail();
        $this->assertSame('Tracker One', $targetItem->name);
        $this->assertSame('TRACKER', $targetItem->productType->code);
        $this->assertSame('Vehicle Devices', $targetItem->productCategory->name);
    }

    private function finishedItem(Company $company, ProductType $type, ProductCategory $category, User $user, string $code, string $name): Item
    {
        return Item::create([
            'company_id' => $company->id,
            'product_type_id' => $type->id,
            'product_category_id' => $category->id,
            'item_type' => 'product',
            'item_code' => $code,
            'name' => $name,
            'sku' => 'SKU-'.$code,
            'unit' => 'PCS',
            'brand' => 'Eemotrack',
            'purchase_price' => 1000,
            'sale_price' => $code === 'TRK-001' ? 1500 : 2500,
            'current_stock' => 1,
            'track_stock' => true,
            'status' => 'active',
            'created_by' => $user->id,
        ]);
    }

    private function stockUnit(Company $company, Item $item, User $user, string $batchNo, string $serial): void
    {
        $batch = ProductionBatch::create([
            'company_id' => $company->id,
            'finished_item_id' => $item->id,
            'batch_no' => $batchNo,
            'production_date' => '2026-09-01',
            'quantity' => 1,
            'cost_per_unit' => 1000,
            'units_data' => [['serial_no' => $serial]],
            'status' => 'posted',
            'created_by' => $user->id,
        ]);

        StockMovement::create([
            'company_id' => $company->id,
            'item_id' => $item->id,
            'movement_date' => '2026-09-01',
            'movement_type' => 'production_output',
            'direction' => 'in',
            'quantity' => 1,
            'unit_price' => 1000,
            'total_value' => 1000,
            'stock_after' => 1,
            'reference_type' => ProductionBatch::class,
            'reference_id' => $batch->id,
            'reference_no' => $batchNo,
            'movement_units' => [['key' => $batchNo.'-0', 'serial_no' => $serial]],
            'created_by' => $user->id,
        ]);
    }
}
