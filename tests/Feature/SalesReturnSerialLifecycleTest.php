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
use App\Http\Controllers\Admin\Sales\SalesReturnController;
use App\Services\SerialUnitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesReturnSerialLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_inter_company_return_selection_includes_stock_returned_on_multiple_dates(): void
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $source = Company::create(['name' => 'Company A', 'created_by' => $user->id]);
        $target = Company::create(['name' => 'Company B', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $source->id]);
        $items = [];
        foreach ([$source, $target] as $company) {
            $type = ProductType::create(['company_id' => $company->id, 'code' => 'FG', 'name' => 'Finished Goods', 'nature' => 'finished_goods']);
            $items[] = Item::create([
                'company_id' => $company->id, 'product_type_id' => $type->id,
                'item_code' => 'GPS-MULTI', 'name' => 'GPS Device', 'unit' => 'PCS',
                'track_stock' => true, 'status' => 'active', 'current_stock' => 3,
            ]);
        }
        [$sourceItem, $targetItem] = $items;
        $units = collect(range(1, 4))->map(fn($number) => [
            'key' => 'SOURCE-' . $number, 'serial_no' => 'SERIAL-' . $number,
            'item_id' => $sourceItem->id,
        ])->all();
        $invoice = SalesInvoice::create([
            'company_id' => $source->id, 'sale_type' => 'cash', 'invoice_no' => 'SI-MULTI',
            'billing_date' => '2026-09-01', 'inter_company_transfer' => true,
            'inter_company_target_company_ids' => [$target->id], 'created_by' => $user->id,
        ]);
        SalesInvoiceItem::create([
            'sales_invoice_id' => $invoice->id, 'item_id' => $sourceItem->id,
            'quantity' => 4, 'unit' => 'PCS', 'unit_price' => 100, 'line_total' => 400,
            'selected_units' => $units,
        ]);
        $bill = PurchaseBill::create([
            'company_id' => $target->id, 'purchase_type' => 'credit', 'invoice_no' => 'AUTO-MULTI',
            'billing_date' => '2026-09-01', 'source_sales_invoice_id' => $invoice->id,
            'inter_company_source_company_id' => $source->id,
        ]);
        PurchaseBillItem::create([
            'purchase_bill_id' => $bill->id, 'item_id' => $targetItem->id,
            'quantity' => 4, 'unit' => 'PCS', 'unit_price' => 100, 'line_total' => 400,
            'selected_units' => $units,
        ]);
        $movement = [
            'company_id' => $target->id, 'item_id' => $targetItem->id,
            'unit_price' => 100, 'total_value' => 100, 'stock_after' => 3,
        ];
        StockMovement::create(array_merge($movement, [
            'movement_date' => '2026-09-01', 'movement_type' => 'purchase', 'direction' => 'in',
            'quantity' => 4, 'movement_units' => $units,
        ]));
        StockMovement::create(array_merge($movement, [
            'movement_date' => '2026-09-02', 'movement_type' => 'sale', 'direction' => 'out',
            'quantity' => 3, 'movement_units' => array_slice($units, 0, 3),
        ]));
        $this->actingAs($user)->get(route('admin.sales-returns.create'))->assertOk();
        foreach ([0 => '2026-09-03', 1 => '2026-09-09'] as $index => $date) {
            StockMovement::create(array_merge($movement, [
                'movement_date' => $date, 'movement_type' => 'sales_return', 'direction' => 'in',
                'quantity' => 1, 'movement_units' => [$units[$index]],
            ]));
            $response = $this->getJson(route('admin.sales-returns.create', ['sales_invoice_id' => $invoice->id]))
                ->assertOk()
                ->assertHeader('Cache-Control', 'no-store, private')
                ->assertJsonPath('lines.0.remaining_qty', $index + 2);
            $this->assertEqualsCanonicalizing(
                array_merge(array_column(array_slice($units, 0, $index + 1), 'serial_no'), ['SERIAL-4']),
                array_column($response->json('lines.0.available_units'), 'serial_no')
            );
        }

        $this->actingAs($user)
            ->get(route('admin.sales-returns.create'))
            ->assertOk()
            ->assertViewHas('invoiceData', function ($data) use ($invoice) {
                $line = $data[$invoice->id][0];
                $this->assertSame(3, (int) $line['remaining_qty']);
                $this->assertEqualsCanonicalizing(['SERIAL-1', 'SERIAL-2', 'SERIAL-4'], array_column($line['available_units'], 'serial_no'));
                return true;
            });
        $this->getJson(route('admin.sales-returns.create'))->assertUnprocessable();
        $user->update(['current_company_id' => $target->id]);
        $this->getJson(route('admin.sales-returns.create', ['sales_invoice_id' => $invoice->id]))->assertNotFound();
    }

    public function test_inter_company_matching_does_not_treat_shared_buyer_code_as_serial_identity(): void
    {
        $controller = app(SalesReturnController::class);
        $method = new \ReflectionMethod($controller, 'unitsMatch');

        $this->assertFalse($method->invoke($controller,
            ['key' => 'SOURCE-1', 'serial_no' => 'SERIAL-1', 'buyer_code' => 'BUY-1'],
            ['key' => 'TARGET-2', 'serial_no' => 'SERIAL-2', 'buyer_code' => 'BUY-1']
        ));
        $this->assertTrue($method->invoke($controller,
            ['key' => 'SOURCE-1', 'serial_no' => 'SERIAL-1', 'buyer_code' => 'BUY-1'],
            ['key' => 'TARGET-1', 'serial_no' => 'SERIAL-1', 'buyer_code' => 'BUY-1']
        ));
    }

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

        $return = SalesReturn::where('sales_invoice_id', $invoice->id)->firstOrFail();
        $interCompanyMovement = StockMovement::where('reference_type', SalesReturn::class)
            ->where('reference_id', $return->id)
            ->where('movement_type', 'inter_company_sales_return_out')
            ->firstOrFail();
        $interCompanyMovement->update([
            'movement_units' => [['key' => 'OLD-WRONG-KEY', 'serial_no' => 'SER-100', 'item_id' => $sourceItem->id]],
        ]);
        StockMovement::create([
            'company_id' => $target->id, 'item_id' => $targetItem->id, 'movement_date' => '2026-09-25',
            'movement_type' => 'legacy_sales_return_reversal', 'direction' => 'in', 'quantity' => 0,
            'unit_price' => 0, 'total_value' => 0, 'stock_after' => 0, 'movement_units' => [$targetUnit],
        ]);
        $this->assertNotEmpty(app(SerialUnitService::class)->currentStockUnitsByItem($target->id, $targetItem->id));

        $this->actingAs($user)->withoutMiddleware()->put(route('admin.sales-returns.update', $return), [
            'returned_units' => [json_encode([$sourceUnit])],
        ])->assertRedirect(route('admin.sales-returns.show', $return))
            ->assertSessionHas('success', fn($message) => str_contains($message, 'Company B stock se verified and removed'));

        $this->assertSame(0.0, (float) $targetItem->fresh()->current_stock);
        $this->assertEmpty(app(SerialUnitService::class)->currentStockUnitsByItem($target->id, $targetItem->id));
        $this->assertDatabaseHas('stock_movements', [
            'company_id' => $target->id,
            'item_id' => $targetItem->id,
            'movement_type' => 'inter_company_sales_return_serial_repair',
            'direction' => 'out',
            'quantity' => 0,
        ]);

        StockMovement::where('reference_type', SalesReturn::class)
            ->where('reference_id', $return->id)
            ->whereIn('movement_type', ['inter_company_sales_return_out', 'inter_company_sales_return_serial_repair'])
            ->delete();
        $targetItem->refresh()->update(['current_stock' => 1, 'stock_value' => 50]);

        $this->actingAs($user)->withoutMiddleware()->put(route('admin.sales-returns.update', $return), [
            'returned_units' => [json_encode([$sourceUnit])],
        ])->assertRedirect(route('admin.sales-returns.show', $return))
            ->assertSessionHas('success', fn($message) => str_contains($message, 'Company B stock se successfully deducted'));

        $this->assertSame(0.0, (float) $targetItem->fresh()->current_stock);
        $this->assertEmpty(app(SerialUnitService::class)->currentStockUnitsByItem($target->id, $targetItem->id));
    }

    public function test_backdated_resale_hides_returned_serial_and_legacy_repair_aliases(): void
    {
        [$company, $item, $invoiceLine, $units, $user] = $this->serialSaleContext(2);
        $original = $units[0];
        $repair = array_merge($original, ['key' => 'repair-1-1-resale']);
        $return = SalesReturn::create([
            'company_id' => $company->id, 'sales_invoice_id' => $invoiceLine->sales_invoice_id,
            'return_no' => 'SR-RESALE', 'return_date' => '2026-06-19',
        ]);
        SalesReturnItem::create([
            'sales_return_id' => $return->id, 'sales_invoice_item_id' => $invoiceLine->id,
            'item_id' => $item->id, 'quantity' => 1, 'unit' => 'PCS',
            'unit_price' => 100, 'line_total' => 100, 'selected_units' => [$original],
        ]);
        foreach ([
            ['2026-06-01', 'purchase', 'in', [$original]],
            ['2026-06-10', 'sale', 'out', [$original]],
            ['2026-06-19', 'sales_return', 'in', [$original]],
        ] as [$date, $type, $direction, $movementUnits]) {
            StockMovement::create([
                'company_id' => $company->id, 'item_id' => $item->id,
                'movement_date' => $date, 'movement_type' => $type, 'direction' => $direction,
                'quantity' => 1, 'unit_price' => 50, 'total_value' => 50,
                'stock_after' => 1, 'movement_units' => $movementUnits,
            ]);
        }
        $service = app(SerialUnitService::class);
        $this->assertSame(['1-0'], array_column($service->currentStockUnitsByItem($company->id)[$item->id], 'key'));

        $resale = SalesInvoice::create([
            'company_id' => $company->id, 'sale_type' => 'cash', 'invoice_no' => 'SI-RESALE',
            'billing_date' => '2026-06-10', 'subtotal' => 100, 'grand_total' => 100,
        ]);
        $resaleLine = $invoiceLine->replicate();
        $resaleLine->fill([
            'sales_invoice_id' => $resale->id, 'quantity' => 1,
            'line_total' => 100, 'selected_units' => [$original],
        ])->save();
        // A legacy duplicate inbound leaves a positive ledger balance, and
        // the old invoice date sorts the resale before the later return.
        foreach ([['purchase', 'in'], ['sale', 'out']] as [$type, $direction]) {
            StockMovement::create([
                'company_id' => $company->id, 'item_id' => $item->id,
                'movement_date' => '2026-06-10', 'movement_type' => $type, 'direction' => $direction,
                'quantity' => 1, 'unit_price' => 50, 'total_value' => 50,
                'stock_after' => 0, 'movement_units' => [$original],
            ]);
        }
        $this->assertSame([], $service->currentStockUnitsByItem($company->id));

        // The same physical serial may also have an old key left behind by
        // an inter-company repair while the resale uses the regenerated key.
        $resaleLine->update(['selected_units' => [$repair]]);
        $this->assertSame([], $service->currentStockUnitsByItem($company->id));
        $item->update(['current_stock' => 5]);
        $this->actingAs($user)->withoutMiddleware()->get(route('admin.stocks.index'))
            ->assertOk()->assertViewHas('items', fn($items) => $items->isEmpty())
            ->assertViewHas('overallQty', 0);

        $resaleReturn = SalesReturn::create([
            'company_id' => $company->id, 'sales_invoice_id' => $resale->id,
            'return_no' => 'SR-RESALE-2', 'return_date' => '2026-06-20',
        ]);
        SalesReturnItem::create([
            'sales_return_id' => $resaleReturn->id, 'sales_invoice_item_id' => $resaleLine->id,
            'item_id' => $item->id, 'quantity' => 1, 'unit' => 'PCS',
            'unit_price' => 100, 'line_total' => 100, 'selected_units' => [$repair],
        ]);
        $this->assertSame(['1-0'], array_column($service->currentStockUnitsByItem($company->id)[$item->id], 'key'));
    }

    public function test_shared_sku_does_not_match_different_sim_or_unit_keys(): void
    {
        $controller = app(SalesReturnController::class);
        $method = new \ReflectionMethod($controller, 'unitsMatch');
        $this->assertFalse($method->invoke($controller,
            ['key' => 'UNIT-1', 'sku' => 'GPS', 'vts_sim' => 'SIM-1'],
            ['key' => 'UNIT-2', 'sku' => 'GPS', 'vts_sim' => 'SIM-2']
        ));
        $this->assertFalse($method->invoke($controller,
            ['key' => 'UNIT-1', 'sku' => 'GPS'],
            ['key' => 'UNIT-2', 'sku' => 'GPS']
        ));
    }

    public function test_all_partial_customer_returns_are_available_to_source_company_and_leave_buyer_stock_on_return(): void
    {
        [$buyer, $buyerItem, $customerLine, $units, $user] = $this->serialSaleContext(20);
        $units = array_map(fn($unit) => array_merge($unit, ['sku' => 'SHARED-GPS-SKU']), $units);
        $customerLine->update(['selected_units' => $units]);
        $seller = Company::create(['name' => 'Company A', 'created_by' => $user->id]);
        $sellerType = $buyerItem->productType->replicate();
        $sellerType->fill(['company_id' => $seller->id])->save();
        $sellerItem = $buyerItem->replicate();
        $sellerItem->fill(['company_id' => $seller->id, 'product_type_id' => $sellerType->id])->save();
        $sellerUnits = array_map(fn($unit) => array_merge($unit, ['item_id' => $sellerItem->id]), $units);
        $sourceInvoice = $customerLine->salesInvoice->replicate();
        $sourceInvoice->fill([
            'company_id' => $seller->id, 'invoice_no' => 'SI-A-B',
            'inter_company_transfer' => true, 'inter_company_target_company_ids' => [$buyer->id],
        ])->save();
        $sourceLine = $customerLine->replicate();
        $sourceLine->fill([
            'sales_invoice_id' => $sourceInvoice->id, 'item_id' => $sellerItem->id,
            'selected_units' => $sellerUnits,
        ])->save();
        $bill = PurchaseBill::create([
            'company_id' => $buyer->id, 'purchase_type' => 'cash', 'invoice_no' => 'AUTO-A-B',
            'billing_date' => '2026-06-01', 'source_sales_invoice_id' => $sourceInvoice->id,
            'inter_company_source_company_id' => $seller->id,
        ]);
        $purchaseLine = PurchaseBillItem::create([
            'purchase_bill_id' => $bill->id, 'item_id' => $buyerItem->id, 'quantity' => 20,
            'unit' => 'PCS', 'unit_price' => 50, 'line_total' => 1000, 'selected_units' => $units,
        ]);
        foreach ([['purchase', 'in', '2026-06-01'], ['sale', 'out', '2026-06-10']] as [$type, $direction, $date]) {
            StockMovement::create([
                'company_id' => $buyer->id, 'item_id' => $buyerItem->id,
                'movement_date' => $date, 'movement_type' => $type, 'direction' => $direction,
                'quantity' => 20, 'unit_price' => 50, 'total_value' => 1000,
                'stock_after' => $direction === 'in' ? 20 : 0, 'movement_units' => $units,
            ]);
        }
        $service = app(SerialUnitService::class);
        $this->assertSame([], $service->unitPool($buyer->id));
        $offset = 0;
        foreach ([5, 4, 2] as $index => $qty) {
            $this->actingAs($user)->withoutMiddleware()->post(route('admin.sales-returns.store'), [
                'sales_invoice_id' => $customerLine->sales_invoice_id,
                'return_no' => 'SR-B-' . $index, 'return_date' => '2026-06-' . (15 + $index),
                'line_id' => [$customerLine->id], 'quantity' => [$qty],
                'returned_units' => [json_encode(array_slice($units, $offset, $qty))],
            ])->assertSessionHasNoErrors()->assertRedirect(route('admin.sales-returns.index'));
            $offset += $qty;
            $this->assertCount($offset, $service->currentStockUnitsByItem($buyer->id)[$buyerItem->id]);
        }
        $user->update(['current_company_id' => $seller->id]);
        $this->withMiddleware();
        $this->actingAs($user)->get(route('admin.sales-returns.create'))->assertOk()
            ->assertViewHas('invoiceData', function ($data) use ($sourceInvoice, $units) {
                $line = $data[$sourceInvoice->id][0];
                return $line['qty'] === 20.0 && $line['remaining_qty'] === 11
                    && array_column($line['available_units'], 'key') === array_column(array_slice($units, 0, 11), 'key');
            });
        foreach ([5, 4] as $index => $qty) {
            $start = $index === 0 ? 0 : 5;
            $this->post(route('admin.sales-returns.store'), [
                'sales_invoice_id' => $sourceInvoice->id, 'return_no' => 'SR-A-' . $index,
                'return_date' => '2026-06-' . (20 + $index),
                'line_id' => [$sourceLine->id], 'quantity' => [$qty],
                'returned_units' => [json_encode(array_slice($sellerUnits, $start, $qty))],
            ])->assertSessionHasNoErrors()->assertRedirect(route('admin.sales-returns.index'));
        }
        $this->assertSame(array_column(array_slice($units, 9, 2), 'key'),
            array_column($service->currentStockUnitsByItem($buyer->id)[$buyerItem->id], 'key'));
        $this->assertCount(9, $service->currentStockUnitsByItem($seller->id)[$sellerItem->id]);
        $user->update(['current_company_id' => $buyer->id]);
        $this->actingAs($user)->post(route('admin.purchase-returns.store'), [
            'purchase_bill_id' => $bill->id, 'return_no' => 'PR-B-A', 'return_date' => '2026-06-22',
            'line_id' => [$purchaseLine->id], 'quantity' => [2],
            'returned_units' => [json_encode(array_slice($units, 9, 2))],
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.purchase-returns.index'));
        $this->assertSame([], $service->currentStockUnitsByItem($buyer->id));
        $this->assertSame([], $service->unitPool($buyer->id));
        $this->assertCount(11, $service->currentStockUnitsByItem($seller->id)[$sellerItem->id]);
        $user->update(['current_company_id' => $seller->id]);
        $this->actingAs($user)->getJson(route('admin.sales-returns.create', ['sales_invoice_id' => $sourceInvoice->id]))
            ->assertOk()->assertJsonPath('lines.0.already_returned', 11)
            ->assertJsonPath('lines.0.sales_returned_qty', 9)
            ->assertJsonPath('lines.0.purchase_returned_qty', 2)
            ->assertJsonPath('lines.0.purchase_return_refs.0', 'PR-B-A')
            ->assertJsonPath('lines.0.remaining_qty', 0);
        $user->update(['current_company_id' => $buyer->id]);
        $this->actingAs($user);
        $this->get(route('admin.stocks.index'))->assertOk()
            ->assertViewHas('items', fn($items) => $items->isEmpty());

        // Editing the purchase return must cancel its previous allocation
        // release, so only the unit still returned belongs to Company A.
        $purchaseReturn = \App\Models\PurchaseReturn::where('return_no', 'PR-B-A')->firstOrFail();
        $this->put(route('admin.purchase-returns.update', $purchaseReturn), [
            'return_no' => 'PR-B-A', 'return_date' => '2026-06-23',
            'line_id' => [$purchaseLine->id], 'quantity' => [1],
            'returned_units' => [json_encode([$units[9]])],
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.purchase-returns.show', $purchaseReturn));
        $this->assertCount(10, $service->currentStockUnitsByItem($seller->id)[$sellerItem->id]);
        $user->update(['current_company_id' => $seller->id]);
        $this->actingAs($user)->getJson(route('admin.sales-returns.create', ['sales_invoice_id' => $sourceInvoice->id]))
            ->assertOk()->assertJsonPath('lines.0.already_returned', 10)
            ->assertJsonPath('lines.0.purchase_returned_qty', 1)
            ->assertJsonPath('lines.0.purchase_returned_keys.0', $units[9]['key'])
            ->assertJsonPath('lines.0.remaining_qty', 1);
        $user->update(['current_company_id' => $buyer->id]);
        $this->actingAs($user);
        $this->assertSame([$units[10]['key']], array_column($service->currentStockUnitsByItem($buyer->id)[$buyerItem->id], 'key'));

        $resale = $customerLine->salesInvoice->replicate();
        $resale->fill(['invoice_no' => 'SI-B-RESALE', 'billing_date' => '2026-06-24'])->save();
        $resaleLine = $customerLine->replicate();
        $resaleLine->fill(['sales_invoice_id' => $resale->id, 'quantity' => 1, 'selected_units' => [$units[10]]])->save();
        StockMovement::create([
            'company_id' => $buyer->id, 'item_id' => $buyerItem->id, 'movement_date' => '2026-06-24',
            'movement_type' => 'sale', 'direction' => 'out', 'quantity' => 1, 'unit_price' => 100,
            'total_value' => 100, 'stock_after' => 0, 'movement_units' => [$units[10]],
        ]);
        $this->assertSame([], $service->currentStockUnitsByItem($buyer->id));
        $this->assertSame([], $service->unitPool($buyer->id));
        $this->get(route('admin.stocks.index'))->assertOk()
            ->assertViewHas('items', fn($items) => $items->isEmpty());
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
