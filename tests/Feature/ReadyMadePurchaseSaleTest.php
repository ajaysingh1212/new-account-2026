<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Item;
use App\Models\ProductType;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Services\SerialUnitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReadyMadePurchaseSaleTest extends TestCase
{
    use RefreshDatabase;

    public static function productNatures(): array
    {
        return [
            'finished goods with serials' => ['finished_goods', true],
            'other brand with serials' => ['readymade', true],
            'other brand without serials' => ['readymade', false],
        ];
    }

    #[DataProvider('productNatures')]
    public function test_ready_made_product_can_be_purchased_sold_returned_and_resold(string $nature, bool $withSerials): void
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create(['name' => 'Ready Made Company', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $company->id]);
        $type = ProductType::create([
            'company_id' => $company->id,
            'code' => 'READY',
            'name' => 'Ready-made Goods',
            'nature' => $nature,
            'status' => 'active',
        ]);
        $item = Item::create([
            'company_id' => $company->id,
            'product_type_id' => $type->id,
            'item_code' => 'READY-001',
            'name' => 'Ready-made Speaker Unit',
            'brand' => 'Other Brand',
            'sku' => 'READY-SKU',
            'unit' => 'PCS',
            'purchase_price' => 500,
            'sale_price' => 750,
            'current_stock' => 0,
            'track_stock' => true,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('admin.purchases.create'))
            ->assertOk()
            ->assertSee('Ready-made Speaker Unit')
            ->assertSee("select2({width:'100%'", false);

        $this->actingAs($user)->post(route('admin.purchases.store'), [
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
            'selected_units' => [$withSerials ? 'READY-SERIAL-1' . PHP_EOL . 'READY-SERIAL-2' : ''],
        ])->assertRedirect(route('admin.purchases.index'));

        $this->assertSame(2.0, (float) $item->fresh()->current_stock);
        $available = app(SerialUnitService::class)->currentStockUnitsByItem($company->id, $item->id)[$item->id] ?? [];
        $this->assertCount(2, $available);
        if ($withSerials) {
            $this->assertSame(['READY-SERIAL-1', 'READY-SERIAL-2'], collect($available)->pluck('serial_no')->all());
        }

        foreach (['admin.sales.create', 'admin.estimates.create', 'admin.delivery-challans.create', 'admin.stock-out-challans.create', 'admin.stock-transfers.create'] as $route) {
            $this->get(route($route))->assertOk()->assertSee($item->name);
        }

        $this->actingAs($user)->post(route('admin.sales.store'), [
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
        $invoice = SalesInvoice::where('invoice_no', 'SALE-READY-1')->firstOrFail();
        $line = $invoice->items->first();
        $this->get(route('admin.sales.edit', $invoice))->assertOk()->assertSee($item->name);
        $this->get(route('admin.sales-returns.create'))->assertOk()->assertSee($item->name);
        $this->post(route('admin.sales-returns.store'), [
            'sales_invoice_id' => $invoice->id,
            'return_no' => 'SR-READY-1',
            'return_date' => '2026-09-25',
            'line_id' => [$line->id],
            'quantity' => [1],
            'returned_units' => [json_encode($line->selected_units)],
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.sales-returns.index'));

        $this->assertSame(2.0, (float) $item->fresh()->current_stock);
        $pool = app(SerialUnitService::class)->unitPool($company->id)[$item->id] ?? [];
        $returnedUnit = collect($pool)->firstWhere('key', $available[0]['key']);
        $this->assertNotNull($returnedUnit);
        $this->assertFalse($returnedUnit['sold']);
        $this->post(route('admin.sales.store'), [
            'sale_type' => 'cash',
            'invoice_no' => 'RESALE-READY-1',
            'billing_date' => '2026-09-26',
            'item_id' => [$item->id],
            'quantity' => [1],
            'unit_price' => [750],
            'tax_mode' => ['without_gst'],
            'tax_percent' => [0],
            'selected_units' => [json_encode([$returnedUnit])],
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.sales.index'));
        $this->assertSame(1.0, (float) $item->fresh()->current_stock);
    }
}
