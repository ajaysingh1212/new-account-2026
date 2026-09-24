<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Item;
use App\Models\ProductType;
use App\Models\ProductionBatch;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\StockMovement;
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
        StockMovement::create([
            'company_id' => $company->id,
            'item_id' => $item->id,
            'movement_date' => '2026-06-19',
            'movement_type' => 'sales_return',
            'direction' => 'in',
            'quantity' => 2,
            'unit_price' => 50,
            'total_value' => 100,
            'stock_after' => 2,
            'reference_type' => SalesReturn::class,
            'reference_id' => $return->id,
            'reference_no' => $return->return_no,
            'movement_units' => [],
        ]);

        $this->assertCount(5, app(SerialUnitService::class)->activeSoldKeys($company->id));

        $this->actingAs($user)->put(route('admin.sales-returns.update', $return), [
            'returned_units' => [
                json_encode([$units[0], $units[1]]),
            ],
        ])->assertRedirect(route('admin.sales-returns.show', $return));

        $this->assertSame(['1-0', '1-1'], collect($returnLine->fresh()->selected_units)->pluck('key')->all());
        $this->assertSame(
            ['1-0', '1-1'],
            collect(StockMovement::where('reference_type', SalesReturn::class)->where('reference_id', $return->id)->first()->movement_units)->pluck('key')->all()
        );
        $this->assertSame(['1-0', '1-1'], collect(app(SerialUnitService::class)->currentStockUnitsByItem($company->id, $item->id)[$item->id])->pluck('key')->all());
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

    public function test_inter_company_sales_return_removes_serial_from_target_company(): void
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $source = Company::create(['name' => 'Company A', 'created_by' => $user->id]);
        $target = Company::create(['name' => 'Company B', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $source->id]);
        $sourceType = ProductType::create(['company_id' => $source->id, 'code' => 'FG-A', 'name' => 'Finished Goods', 'nature' => 'finished_goods']);
        $targetType = ProductType::create(['company_id' => $target->id, 'code' => 'FG-B', 'name' => 'Finished Goods', 'nature' => 'finished_goods']);
        $sourceItem = Item::create([
            'company_id' => $source->id, 'product_type_id' => $sourceType->id, 'item_code' => 'GPS-1',
            'name' => 'GPS Device', 'unit' => 'PCS', 'purchase_price' => 50, 'sale_price' => 100,
            'current_stock' => 0, 'track_stock' => true, 'status' => 'active',
        ]);
        $targetItem = Item::create([
            'company_id' => $target->id, 'product_type_id' => $targetType->id, 'item_code' => 'GPS-1',
            'name' => 'GPS Device', 'unit' => 'PCS', 'purchase_price' => 50, 'sale_price' => 100,
            'current_stock' => 1, 'stock_value' => 50, 'track_stock' => true, 'status' => 'active',
        ]);
        $sourceUnit = ['key' => 'SRC-1', 'serial_no' => 'SER-100', 'item_id' => $sourceItem->id];
        $targetUnit = ['key' => 'SRC-1', 'serial_no' => 'SER-100', 'item_id' => $targetItem->id];
        $invoice = SalesInvoice::create([
            'company_id' => $source->id, 'sale_type' => 'cash', 'invoice_no' => 'SI-IC-1',
            'billing_date' => '2026-09-20', 'subtotal' => 100, 'grand_total' => 100,
            'inter_company_transfer' => true, 'inter_company_target_company_ids' => [$target->id], 'created_by' => $user->id,
        ]);
        $invoiceLine = SalesInvoiceItem::create([
            'sales_invoice_id' => $invoice->id, 'item_id' => $sourceItem->id, 'quantity' => 1,
            'unit' => 'PCS', 'unit_price' => 100, 'discount_type' => 'percent', 'discount_value' => 0,
            'discount_amount' => 0, 'tax_percent' => 0, 'tax_amount' => 0, 'line_total' => 100,
            'selected_units' => [$sourceUnit],
        ]);
        $bill = PurchaseBill::create([
            'company_id' => $target->id, 'purchase_type' => 'credit', 'invoice_no' => 'AUTO-SI-IC-1',
            'billing_date' => '2026-09-20', 'source_sales_invoice_id' => $invoice->id,
            'inter_company_source_company_id' => $source->id,
        ]);
        PurchaseBillItem::create([
            'purchase_bill_id' => $bill->id, 'item_id' => $targetItem->id, 'quantity' => 1,
            'unit' => 'PCS', 'unit_price' => 50, 'line_total' => 50, 'selected_units' => [$targetUnit],
        ]);
        StockMovement::create([
            'company_id' => $target->id, 'item_id' => $targetItem->id, 'movement_date' => '2026-09-20',
            'movement_type' => 'purchase', 'direction' => 'in', 'quantity' => 1, 'unit_price' => 50,
            'total_value' => 50, 'stock_after' => 1, 'movement_units' => [$targetUnit],
        ]);
        StockMovement::create([
            'company_id' => $target->id, 'item_id' => $targetItem->id, 'movement_date' => '2026-09-21',
            'movement_type' => 'sale', 'direction' => 'out', 'quantity' => 1, 'unit_price' => 100,
            'total_value' => 100, 'stock_after' => 0, 'movement_units' => [$targetUnit],
        ]);
        StockMovement::create([
            'company_id' => $target->id, 'item_id' => $targetItem->id, 'movement_date' => '2026-09-22',
            'movement_type' => 'sales_return', 'direction' => 'in', 'quantity' => 1, 'unit_price' => 50,
            'total_value' => 50, 'stock_after' => 1, 'movement_units' => [$targetUnit],
        ]);

        $this->actingAs($user)->withoutMiddleware()->post(route('admin.sales-returns.store'), [
            'sales_invoice_id' => $invoice->id,
            'return_no' => 'SR-IC-1',
            'return_date' => '2026-09-24',
            'line_id' => [$invoiceLine->id],
            'quantity' => [1],
            'returned_units' => [json_encode([$sourceUnit])],
        ])->assertRedirect(route('admin.sales-returns.index'));

        $this->assertSame(1.0, (float) $sourceItem->fresh()->current_stock);
        $this->assertSame(0.0, (float) $targetItem->fresh()->current_stock);
        $this->assertEmpty(app(SerialUnitService::class)->currentStockUnitsByItem($target->id, $targetItem->id));
        $this->assertDatabaseHas('stock_movements', [
            'company_id' => $target->id,
            'item_id' => $targetItem->id,
            'movement_type' => 'inter_company_sales_return_out',
            'direction' => 'out',
        ]);
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
