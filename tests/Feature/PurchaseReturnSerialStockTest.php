<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Item;
use App\Models\ProductType;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\StockMovement;
use App\Models\User;
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
}
