<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Item;
use App\Models\ProductType;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\SerialUnitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseReturnSerialStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_return_bill_items_show_serial_available_stock_not_raw_negative_net(): void
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create(['name' => 'Serial Purchase Company', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $company->id]);
        $type = ProductType::create([
            'company_id' => $company->id,
            'code' => 'FG',
            'name' => 'Finished Goods',
            'nature' => 'finished_goods',
        ]);
        $item = Item::create([
            'company_id' => $company->id,
            'product_type_id' => $type->id,
            'item_code' => 'GPS-PR',
            'name' => 'GPS Purchase Return',
            'unit' => 'PCS',
            'purchase_price' => 100,
            'track_stock' => true,
            'status' => 'active',
        ]);
        $bill = PurchaseBill::create([
            'company_id' => $company->id,
            'purchase_type' => 'credit',
            'invoice_no' => 'PB-001',
            'billing_date' => '2026-08-01',
        ]);
        $units = [
            ['key' => 'PBI-1-0', 'serial_no' => 'SER-AVAILABLE'],
            ['key' => 'PBI-1-1', 'serial_no' => 'SER-SOLD'],
        ];
        PurchaseBillItem::create([
            'purchase_bill_id' => $bill->id,
            'item_id' => $item->id,
            'quantity' => 2,
            'unit' => 'PCS',
            'unit_price' => 100,
            'line_total' => 200,
            'selected_units' => $units,
        ]);

        StockMovement::create([
            'company_id' => $company->id,
            'item_id' => $item->id,
            'movement_date' => '2026-08-01',
            'movement_type' => 'purchase',
            'direction' => 'in',
            'quantity' => 1,
            'unit_price' => 100,
            'total_value' => 100,
            'stock_after' => 1,
            'movement_units' => [$units[0]],
        ]);
        StockMovement::create([
            'company_id' => $company->id,
            'item_id' => $item->id,
            'movement_date' => '2026-08-02',
            'movement_type' => 'sale',
            'direction' => 'out',
            'quantity' => 2,
            'unit_price' => 100,
            'total_value' => 200,
            'stock_after' => 0,
            'movement_units' => [$units[1]],
        ]);

        $response = $this->actingAs($user)
            ->withoutMiddleware()
            ->getJson(route('admin.purchase-returns.bill-items', ['bill_id' => $bill->id]))
            ->assertOk()
            ->json('lines.0');

        $this->assertSame(1, $response['current_stock']);
        $this->assertSame(['PBI-1-0'], collect($response['available_units'])->pluck('key')->all());
    }

    public function test_current_stock_screen_uses_available_serial_count_for_serialised_items(): void
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create(['name' => 'Serial Display Company', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $company->id]);
        $type = ProductType::create([
            'company_id' => $company->id,
            'code' => 'FG',
            'name' => 'Finished Goods',
            'nature' => 'finished_goods',
        ]);
        $item = Item::create([
            'company_id' => $company->id,
            'product_type_id' => $type->id,
            'item_code' => 'ET-1002003',
            'name' => 'ET-1002003- QUARTZ-2 ANDROID',
            'unit' => 'PCS',
            'purchase_price' => 100,
            'current_stock' => 12,
            'track_stock' => true,
            'status' => 'active',
        ]);
        $bill = PurchaseBill::create([
            'company_id' => $company->id,
            'purchase_type' => 'credit',
            'invoice_no' => 'PB-ET-1002003',
            'billing_date' => '2026-08-01',
        ]);
        $units = collect(range(1, 9))->map(fn($index) => [
            'key' => 'ET-UNIT-' . $index,
            'serial_no' => (string) (1946507610 + $index),
        ])->all();
        PurchaseBillItem::create([
            'purchase_bill_id' => $bill->id,
            'item_id' => $item->id,
            'quantity' => 12,
            'unit' => 'PCS',
            'unit_price' => 100,
            'line_total' => 1200,
            'selected_units' => $units,
        ]);
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

        $serials = app(SerialUnitService::class)->currentStockUnitsByItem($company->id, $item->id);

        $this->assertCount(9, $serials[$item->id]);

        $this->actingAs($user)
            ->withoutMiddleware()
            ->get(route('admin.stocks.index', ['q' => 'ET-1002003']))
            ->assertOk()
            ->assertSee('9.000');
    }

    public function test_inter_company_purchase_return_moves_serial_from_buyer_back_to_source_company(): void
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $source = Company::create(['name' => 'Company A', 'created_by' => $user->id]);
        $buyer = Company::create(['name' => 'Company B', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $buyer->id]);
        $sourceType = ProductType::create(['company_id' => $source->id, 'code' => 'FG-A', 'name' => 'Ready Goods', 'nature' => 'finished_goods']);
        $buyerType = ProductType::create(['company_id' => $buyer->id, 'code' => 'FG-B', 'name' => 'Ready Goods', 'nature' => 'finished_goods']);
        $sourceItem = Item::create([
            'company_id' => $source->id, 'product_type_id' => $sourceType->id, 'item_code' => 'READY-IC',
            'name' => 'Ready Item', 'unit' => 'PCS', 'purchase_price' => 100, 'current_stock' => 0,
            'track_stock' => true, 'status' => 'active',
        ]);
        $buyerItem = Item::create([
            'company_id' => $buyer->id, 'product_type_id' => $buyerType->id, 'item_code' => 'READY-IC',
            'name' => 'Ready Item', 'unit' => 'PCS', 'purchase_price' => 100, 'current_stock' => 1,
            'stock_value' => 100, 'track_stock' => true, 'status' => 'active',
        ]);
        $bill = PurchaseBill::create([
            'company_id' => $buyer->id, 'purchase_type' => 'cash', 'invoice_no' => 'IC-PUR-1',
            'billing_date' => '2026-09-20', 'inter_company_source_company_id' => $source->id,
        ]);
        $unit = ['key' => 'IC-UNIT-1', 'serial_no' => 'IC-SERIAL-1', 'item_id' => $buyerItem->id];
        $line = PurchaseBillItem::create([
            'purchase_bill_id' => $bill->id, 'item_id' => $buyerItem->id, 'quantity' => 1,
            'unit' => 'PCS', 'unit_price' => 100, 'line_total' => 100, 'selected_units' => [$unit],
        ]);
        StockMovement::create([
            'company_id' => $buyer->id, 'item_id' => $buyerItem->id, 'movement_date' => '2026-09-20',
            'movement_type' => 'inter_company_purchase', 'direction' => 'in', 'quantity' => 1,
            'unit_price' => 100, 'total_value' => 100, 'stock_after' => 1, 'movement_units' => [$unit],
        ]);

        $this->actingAs($user)->withoutMiddleware()->post(route('admin.purchase-returns.store'), [
            'purchase_bill_id' => $bill->id,
            'return_no' => 'PR-IC-1',
            'return_date' => '2026-09-24',
            'line_id' => [$line->id],
            'quantity' => [1],
            'returned_units' => [json_encode([$unit])],
        ])->assertRedirect(route('admin.purchase-returns.index'));

        $this->assertSame(0.0, (float) $buyerItem->fresh()->current_stock);
        $this->assertSame(1.0, (float) $sourceItem->fresh()->current_stock);
        $this->assertEmpty(app(SerialUnitService::class)->currentStockUnitsByItem($buyer->id, $buyerItem->id));
        $this->assertSame(
            ['IC-SERIAL-1'],
            collect(app(SerialUnitService::class)->currentStockUnitsByItem($source->id, $sourceItem->id)[$sourceItem->id] ?? [])->pluck('serial_no')->all()
        );
    }
}
