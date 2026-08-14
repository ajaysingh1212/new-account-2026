<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Item;
use App\Models\ItemBom;
use App\Models\ProductType;
use App\Models\ProductionBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionBatchStockMovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_batch_consumes_raw_material_and_increases_finished_goods_stock(): void
    {
        [$user, $raw, $finished] = $this->productionContext();

        $this->actingAs($user)->withoutMiddleware()->post(route('admin.production-batches.store'), [
            'finished_item_id' => $finished->id,
            'batch_no' => 'PB-TEST-001',
            'production_date' => '2026-07-16',
            'quantity' => 2,
            'notes' => 'Regression test batch',
        ])->assertRedirect(route('admin.production-batches.index'));

        $this->assertSame(6.0, (float) $raw->fresh()->current_stock);
        $this->assertSame(2.0, (float) $finished->fresh()->current_stock);

        $this->assertDatabaseHas('stock_movements', [
            'company_id' => $user->current_company_id,
            'item_id' => $raw->id,
            'movement_type' => 'production_consumption',
            'direction' => 'out',
            'quantity' => 4,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'company_id' => $user->current_company_id,
            'item_id' => $finished->id,
            'movement_type' => 'production_output',
            'direction' => 'in',
            'quantity' => 2,
            'stock_after' => 2,
        ]);

        $this->assertDatabaseHas('production_batches', [
            'company_id' => $user->current_company_id,
            'finished_item_id' => $finished->id,
            'batch_no' => 'PB-TEST-001',
            'quantity' => 2,
            'status' => 'posted',
        ]);
    }

    public function test_metadata_only_update_with_same_quantity_does_not_trigger_stock_validation(): void
    {
        [$user, $raw, $finished] = $this->productionContext();

        $batch = ProductionBatch::create([
            'company_id' => $user->current_company_id,
            'finished_item_id' => $finished->id,
            'batch_no' => 'PB-TEST-002',
            'production_date' => '2026-07-16',
            'quantity' => 2,
            'raw_material_cost' => 40,
            'cost_per_unit' => 20,
            'status' => 'posted',
            'units_data' => [
                ['serial_no' => 'SER-OLD-1', 'buyer_code' => 'BC-1', 'vts_sim' => 'VTS-OLD-1'],
                ['serial_no' => 'SER-OLD-2', 'buyer_code' => 'BC-2', 'vts_sim' => 'VTS-OLD-2'],
            ],
            'created_by' => $user->id,
        ]);

        $finished->update(['current_stock' => 1, 'stock_value' => 20]);

        $this->actingAs($user);

        $request = new \Illuminate\Http\Request([
            'batch_no' => 'PB-TEST-002',
            'production_date' => '2026-07-16',
            'quantity' => 2,
            'notes' => 'Updated serial metadata only',
            'finished_item_sku' => 'SKU-NEW',
            'unit_serial' => ['SER-NEW-1', 'SER-NEW-2'],
            'unit_vts_sim' => ['VTS-NEW-1', 'VTS-NEW-2'],
        ]);

        $response = app(\App\Http\Controllers\Admin\Production\ProductionBatchController::class)
            ->update(
                $request,
                $batch,
                app(\App\Services\AccountingService::class),
                app(\App\Services\EntryVisibilityService::class),
                app(\App\Services\CrmIdentifierPropagationService::class)
            );

        $this->assertNotNull($response);
        $batch->refresh();
        $this->assertSame('SKU-NEW', $finished->fresh()->sku);
        $this->assertSame('SER-NEW-1', $batch->units_data[0]['serial_no']);
        $this->assertSame('VTS-NEW-1', $batch->units_data[0]['vts_sim']);
        $this->assertSame(1.0, (float) $finished->fresh()->current_stock);
    }

    private function productionContext(): array
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create(['name' => 'Prod Test Company', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $company->id]);

        $finishedType = ProductType::create([
            'company_id' => $company->id,
            'code' => 'FINISHED',
            'name' => 'Finished Goods',
            'nature' => 'finished_goods',
            'status' => 'active',
        ]);

        $rawType = ProductType::create([
            'company_id' => $company->id,
            'code' => 'RAW',
            'name' => 'Raw Materials',
            'nature' => 'raw_material',
            'status' => 'active',
        ]);

        $raw = Item::create([
            'company_id' => $company->id,
            'product_type_id' => $rawType->id,
            'item_type' => 'product',
            'item_code' => 'RAW-001',
            'name' => 'Raw Input',
            'unit' => 'PCS',
            'purchase_price' => 10,
            'sale_price' => 15,
            'current_stock' => 10,
            'stock_value' => 100,
            'track_stock' => true,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $finished = Item::create([
            'company_id' => $company->id,
            'product_type_id' => $finishedType->id,
            'item_type' => 'product',
            'item_code' => 'FIN-001',
            'name' => 'Finished Item',
            'unit' => 'PCS',
            'purchase_price' => 25,
            'sale_price' => 40,
            'current_stock' => 0,
            'stock_value' => 0,
            'track_stock' => true,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        ItemBom::create([
            'company_id' => $company->id,
            'finished_item_id' => $finished->id,
            'raw_item_id' => $raw->id,
            'line_type' => 'raw_material',
            'qty_per_unit' => 2,
        ]);

        return [$user, $raw, $finished];
    }
}
