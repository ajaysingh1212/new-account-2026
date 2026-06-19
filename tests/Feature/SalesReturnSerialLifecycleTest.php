<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Item;
use App\Models\ProductType;
use App\Models\ProductionBatch;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\User;
use App\Services\SerialUnitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesReturnSerialLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_returned_serials_are_removed_from_active_sold_keys_and_become_resellable(): void
    {
        [$company, $item, $invoiceLine, $units] = $this->serialSaleContext(5);

        $return = SalesReturn::create([
            'company_id' => $company->id,
            'sales_invoice_id' => $invoiceLine->sales_invoice_id,
            'return_no' => 'SR-00001',
            'return_date' => '2026-06-19',
        ]);
        SalesReturnItem::create([
            'sales_return_id' => $return->id,
            'sales_invoice_item_id' => $invoiceLine->id,
            'item_id' => $item->id,
            'quantity' => 5,
            'unit' => 'PCS',
            'unit_price' => 100,
            'line_total' => 500,
            'selected_units' => $units,
        ]);

        $this->assertSame([], app(SerialUnitService::class)->activeSoldKeys($company->id));
    }

    public function test_old_sales_return_without_serials_can_be_updated_with_returned_serials(): void
    {
        [$company, $item, $invoiceLine, $units, $user] = $this->serialSaleContext(5);

        $return = SalesReturn::create([
            'company_id' => $company->id,
            'sales_invoice_id' => $invoiceLine->sales_invoice_id,
            'return_no' => 'SR-00001',
            'return_date' => '2026-06-19',
            'created_by' => $user->id,
        ]);
        $returnLine = SalesReturnItem::create([
            'sales_return_id' => $return->id,
            'sales_invoice_item_id' => $invoiceLine->id,
            'item_id' => $item->id,
            'quantity' => 2,
            'unit' => 'PCS',
            'unit_price' => 100,
            'line_total' => 200,
            'selected_units' => [],
        ]);

        $this->assertCount(5, app(SerialUnitService::class)->activeSoldKeys($company->id));

        $this->actingAs($user)->put(route('admin.sales-returns.update', $return), [
            'returned_units' => [
                json_encode([$units[0], $units[1]]),
            ],
        ])->assertRedirect(route('admin.sales-returns.show', $return));

        $this->assertSame(['1-0', '1-1'], collect($returnLine->fresh()->selected_units)->pluck('key')->all());
        $this->assertSame(['1-2', '1-3', '1-4'], app(SerialUnitService::class)->activeSoldKeys($company->id));
    }

    public function test_sales_return_edit_screen_loads_serial_lines(): void
    {
        [$company, $item, $invoiceLine, $units, $user] = $this->serialSaleContext(1);

        $return = SalesReturn::create([
            'company_id' => $company->id,
            'sales_invoice_id' => $invoiceLine->sales_invoice_id,
            'return_no' => 'SR-00001',
            'return_date' => '2026-06-19',
            'created_by' => $user->id,
        ]);
        SalesReturnItem::create([
            'sales_return_id' => $return->id,
            'sales_invoice_item_id' => $invoiceLine->id,
            'item_id' => $item->id,
            'quantity' => 1,
            'unit' => 'PCS',
            'unit_price' => 100,
            'line_total' => 100,
            'selected_units' => [],
        ]);

        $this->actingAs($user)
            ->get(route('admin.sales-returns.edit', $return))
            ->assertOk()
            ->assertSee('Update Returned Serials')
            ->assertSee('GPS Device');
    }

    private function serialSaleContext(int $qty): array
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create(['name' => 'Serial Company', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $company->id]);
        $type = ProductType::create([
            'company_id' => $company->id,
            'code' => 'FINISHED',
            'name' => 'Finished Goods',
            'nature' => 'finished_goods',
        ]);
        $item = Item::create([
            'company_id' => $company->id,
            'product_type_id' => $type->id,
            'item_code' => 'FG-001',
            'name' => 'GPS Device',
            'unit' => 'PCS',
            'purchase_price' => 50,
            'sale_price' => 100,
            'current_stock' => 0,
            'track_stock' => true,
            'status' => 'active',
        ]);
        $units = collect(range(0, $qty - 1))->map(fn($index) => [
            'key' => '1-' . $index,
            'serial_no' => 'SER-' . ($index + 1),
            'vts_sim' => 'SIM-' . ($index + 1),
            'batch_no' => 'PB-1',
            'buyer_code' => 'B-' . ($index + 1),
        ])->all();
        ProductionBatch::create([
            'id' => 1,
            'company_id' => $company->id,
            'finished_item_id' => $item->id,
            'batch_no' => 'PROD-001',
            'production_date' => '2026-06-01',
            'quantity' => $qty,
            'cost_per_unit' => 50,
            'units_data' => $units,
            'status' => 'posted',
            'created_by' => $user->id,
        ]);
        $invoice = SalesInvoice::create([
            'company_id' => $company->id,
            'sale_type' => 'cash',
            'invoice_no' => 'SI-001',
            'billing_date' => '2026-06-10',
            'subtotal' => $qty * 100,
            'grand_total' => $qty * 100,
            'created_by' => $user->id,
        ]);
        $invoiceLine = SalesInvoiceItem::create([
            'sales_invoice_id' => $invoice->id,
            'item_id' => $item->id,
            'quantity' => $qty,
            'unit' => 'PCS',
            'unit_price' => 100,
            'discount_type' => 'percent',
            'discount_value' => 0,
            'discount_amount' => 0,
            'tax_percent' => 0,
            'tax_amount' => 0,
            'line_total' => $qty * 100,
            'selected_units' => $units,
        ]);

        return [$company, $item, $invoiceLine, $units, $user];
    }
}
