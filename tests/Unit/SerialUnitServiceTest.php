<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Item;
use App\Models\ProductType;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\SerialUnitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SerialUnitServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_auto_selects_only_available_gps_units_with_vts_numbers(): void
    {
        $pool = [
            ['key'=>'sold','serial_no'=>'S-1','vts_sim'=>'V-1','sold'=>true],
            ['key'=>'no-vts','serial_no'=>'S-2','vts_sim'=>'','sold'=>false],
            ['key'=>'valid-1','serial_no'=>'S-3','vts_sim'=>'V-3','sold'=>false],
            ['key'=>'valid-2','serial_no'=>'S-4','vts_sim'=>'V-4','sold'=>false],
        ];

        $units = app(SerialUnitService::class)->reconcile([], $pool, 2, true);

        $this->assertSame(['valid-1','valid-2'], collect($units)->pluck('key')->all());
    }

    #[Test]
    public function it_detects_gps_anywhere_in_the_item_identity(): void
    {
        $item = new Item(['name'=>'Vehicle Controller','description'=>'Inbuilt GPS tracking']);

        $this->assertTrue(app(SerialUnitService::class)->isGpsItem($item));
    }

    #[Test]
    public function it_uses_stable_unit_keys_for_stock_balances(): void
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create(['name' => 'Target Company', 'created_by' => $user->id]);
        $type = ProductType::create([
            'company_id' => $company->id,
            'code' => 'FG',
            'name' => 'Finished Goods',
            'nature' => 'finished_goods',
        ]);
        $item = Item::create([
            'company_id' => $company->id,
            'product_type_id' => $type->id,
            'item_code' => 'GPS-1',
            'name' => 'GPS',
            'unit' => 'PCS',
            'purchase_price' => 100,
            'track_stock' => true,
            'status' => 'active',
        ]);
        $purchase = PurchaseBill::create([
            'company_id' => $company->id,
            'purchase_type' => 'credit',
            'invoice_no' => 'IC-1',
            'billing_date' => '2026-07-01',
        ]);
        PurchaseBillItem::create([
            'purchase_bill_id' => $purchase->id,
            'item_id' => $item->id,
            'quantity' => 1,
            'unit' => 'PCS',
            'unit_price' => 100,
            'line_total' => 100,
            'selected_units' => [['key' => '1-0', 'serial_no' => 'SER-1']],
        ]);

        $pool = app(SerialUnitService::class)->unitPool($company->id);

        $this->assertSame('1-0', $pool[$item->id][0]['key']);

        StockMovement::create([
            'company_id' => $company->id,
            'item_id' => $item->id,
            'movement_date' => '2026-07-01',
            'movement_type' => 'inter_company_purchase',
            'direction' => 'in',
            'quantity' => 1,
            'unit_price' => 100,
            'total_value' => 100,
            'stock_after' => 1,
            'movement_units' => [['key' => '1-0', 'serial_no' => 'SER-1']],
        ]);
        StockMovement::create([
            'company_id' => $company->id,
            'item_id' => $item->id,
            'movement_date' => '2026-07-02',
            'movement_type' => 'sale',
            'direction' => 'out',
            'quantity' => 1,
            'unit_price' => 100,
            'total_value' => 100,
            'stock_after' => 0,
            'movement_units' => [['key' => '1-0', 'serial_no' => 'SER-1']],
        ]);

        $this->assertSame([], app(SerialUnitService::class)->currentStockUnitsByItem($company->id));
    }

    #[Test]
    public function it_hides_serials_whose_latest_movement_is_out_even_if_earlier_inbound_rows_exist(): void
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create(['name' => 'Latest Movement Company', 'created_by' => $user->id]);
        $type = ProductType::create([
            'company_id' => $company->id,
            'code' => 'FG',
            'name' => 'Finished Goods',
            'nature' => 'finished_goods',
        ]);
        $item = Item::create([
            'company_id' => $company->id,
            'product_type_id' => $type->id,
            'item_code' => 'GPS-2',
            'name' => 'GPS Tracker',
            'unit' => 'PCS',
            'purchase_price' => 100,
            'track_stock' => true,
            'status' => 'active',
        ]);

        $unit = ['serial_no' => 'SER-STATE-1', 'key' => '1-0'];
        foreach ([
            ['2026-07-01', 'in', 1],
            ['2026-07-02', 'out', 1],
            ['2026-07-03', 'in', 1],
            ['2026-07-04', 'in', 1],
            ['2026-07-05', 'out', 1],
        ] as [$date, $direction, $qty]) {
            StockMovement::create([
                'company_id' => $company->id,
                'item_id' => $item->id,
                'movement_date' => $date,
                'movement_type' => 'sale_adjustment',
                'direction' => $direction,
                'quantity' => $qty,
                'unit_price' => 100,
                'total_value' => 100,
                'stock_after' => $direction === 'in' ? 1 : 0,
                'movement_units' => [$unit],
            ]);
        }

        $this->assertSame([], app(SerialUnitService::class)->currentStockUnitsByItem($company->id));
    }

    #[Test]
    public function it_tracks_duplicate_visible_serial_numbers_as_distinct_physical_units_by_key(): void
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create(['name' => 'Duplicate Serial Company', 'created_by' => $user->id]);
        $type = ProductType::create([
            'company_id' => $company->id,
            'code' => 'FG',
            'name' => 'Finished Goods',
            'nature' => 'finished_goods',
        ]);
        $item = Item::create([
            'company_id' => $company->id,
            'product_type_id' => $type->id,
            'item_code' => 'GPS-DUP',
            'name' => 'GPS Duplicate Serial',
            'unit' => 'PCS',
            'purchase_price' => 100,
            'track_stock' => true,
            'status' => 'active',
        ]);

        $units = [
            ['key' => 'PUR-A-1', 'serial_no' => 'BC-AUTO-001'],
            ['key' => 'PUR-B-1', 'serial_no' => 'BC-AUTO-001'],
        ];

        foreach ($units as $index => $unit) {
            StockMovement::create([
                'company_id' => $company->id,
                'item_id' => $item->id,
                'movement_date' => '2026-08-01',
                'movement_type' => 'purchase',
                'direction' => 'in',
                'quantity' => 1,
                'unit_price' => 100,
                'total_value' => 100,
                'stock_after' => $index + 1,
                'movement_units' => [$unit],
            ]);
        }

        StockMovement::create([
            'company_id' => $company->id,
            'item_id' => $item->id,
            'movement_date' => '2026-08-02',
            'movement_type' => 'sale',
            'direction' => 'out',
            'quantity' => 1,
            'unit_price' => 100,
            'total_value' => 100,
            'stock_after' => 1,
            'movement_units' => [$units[0]],
        ]);

        $available = app(SerialUnitService::class)->currentStockUnitsByItem($company->id, $item->id);

        $this->assertSame(['PUR-B-1'], collect($available[$item->id] ?? [])->pluck('key')->all());
    }

    #[Test]
    public function it_keeps_stock_units_distinct_across_different_items_with_same_serial_key(): void
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create(['name' => 'Item Scope Company', 'created_by' => $user->id]);
        $type = ProductType::create([
            'company_id' => $company->id,
            'code' => 'FG',
            'name' => 'Finished Goods',
            'nature' => 'finished_goods',
        ]);

        $itemA = Item::create([
            'company_id' => $company->id,
            'product_type_id' => $type->id,
            'item_code' => 'ITEM-A',
            'name' => 'Item A',
            'unit' => 'PCS',
            'purchase_price' => 100,
            'track_stock' => true,
            'status' => 'active',
        ]);

        $itemB = Item::create([
            'company_id' => $company->id,
            'product_type_id' => $type->id,
            'item_code' => 'ITEM-B',
            'name' => 'Item B',
            'unit' => 'PCS',
            'purchase_price' => 100,
            'track_stock' => true,
            'status' => 'active',
        ]);

        $sharedUnit = ['key' => '1-0', 'serial_no' => 'SER-123', 'vts_sim' => 'SIM-123'];

        StockMovement::create([
            'company_id' => $company->id,
            'item_id' => $itemA->id,
            'movement_date' => '2026-08-01',
            'movement_type' => 'purchase',
            'direction' => 'in',
            'quantity' => 1,
            'unit_price' => 100,
            'total_value' => 100,
            'stock_after' => 1,
            'movement_units' => [$sharedUnit],
        ]);

        StockMovement::create([
            'company_id' => $company->id,
            'item_id' => $itemB->id,
            'movement_date' => '2026-08-02',
            'movement_type' => 'purchase',
            'direction' => 'in',
            'quantity' => 1,
            'unit_price' => 100,
            'total_value' => 100,
            'stock_after' => 1,
            'movement_units' => [$sharedUnit],
        ]);

        $stockA = app(SerialUnitService::class)->currentStockUnitsByItem($company->id, $itemA->id);
        $stockB = app(SerialUnitService::class)->currentStockUnitsByItem($company->id, $itemB->id);

        $this->assertSame(['1-0'], collect($stockA[$itemA->id] ?? [])->pluck('key')->all());
        $this->assertSame(['1-0'], collect($stockB[$itemB->id] ?? [])->pluck('key')->all());
        $this->assertArrayHasKey($itemA->id, $stockA);
        $this->assertArrayHasKey($itemB->id, $stockB);
    }
}
