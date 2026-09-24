<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Item;
use App\Models\ProductType;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\SerialUnitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadyMadePurchaseSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_ready_made_finished_good_can_be_purchased_and_sold(): void
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create(['name' => 'Ready Made Company', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $company->id]);
        $type = ProductType::create([
            'company_id' => $company->id,
            'code' => 'READY',
            'name' => 'Ready-made Goods',
            'nature' => 'finished_goods',
            'status' => 'active',
        ]);
        $item = Item::create([
            'company_id' => $company->id,
            'product_type_id' => $type->id,
            'item_code' => 'READY-001',
            'name' => 'Ready-made Speaker Unit',
            'sku' => 'READY-SKU',
            'unit' => 'PCS',
            'purchase_price' => 500,
            'sale_price' => 750,
            'current_stock' => 0,
            'track_stock' => true,
            'status' => 'active',
        ]);

        $this->actingAs($user)->withoutMiddleware()
            ->get(route('admin.purchases.create'))
            ->assertOk()
            ->assertSee('Ready-made Speaker Unit')
            ->assertSee("select2({width:'100%'", false);

        $this->actingAs($user)->withoutMiddleware()->post(route('admin.purchases.store'), [
            'purchase_type' => 'cash',
            'invoice_no' => 'PUR-READY-1',
            'billing_date' => '2026-09-24',
            'item_id' => [$item->id],
            'description' => ['Ready-made purchase'],
            'quantity' => [2],
            'unit' => ['PCS'],
            'unit_price' => [500],
            'discount_type' => ['percent'],
            'discount_value' => [0],
            'tax_percent' => [0],
            'selected_units' => ['READY-SERIAL-1' . PHP_EOL . 'READY-SERIAL-2'],
        ])->assertRedirect(route('admin.purchases.index'));

        $this->assertSame(2.0, (float) $item->fresh()->current_stock);
        $available = app(SerialUnitService::class)->currentStockUnitsByItem($company->id, $item->id)[$item->id] ?? [];
        $this->assertSame(['READY-SERIAL-1', 'READY-SERIAL-2'], collect($available)->pluck('serial_no')->all());

        $this->actingAs($user)->withoutMiddleware()->post(route('admin.sales.store'), [
            'sale_type' => 'cash',
            'invoice_no' => 'SALE-READY-1',
            'billing_date' => '2026-09-24',
            'item_id' => [$item->id],
            'quantity' => [1],
            'unit_price' => [750],
            'discount_type' => ['percent'],
            'discount_value' => [0],
            'tax_mode' => ['without_gst'],
            'tax_percent' => [0],
            'selected_units' => [json_encode([$available[0]])],
        ])->assertRedirect(route('admin.sales.index'));

        $this->assertSame(1.0, (float) $item->fresh()->current_stock);
        $this->assertDatabaseHas('stock_movements', [
            'company_id' => $company->id,
            'item_id' => $item->id,
            'movement_type' => 'sale',
            'direction' => 'out',
        ]);
    }
}
