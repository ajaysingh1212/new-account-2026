<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyMerge;
use App\Models\DeliveryChallan;
use App\Models\Item;
use App\Models\Party;
use App\Models\ProductType;
use App\Models\PurchaseBill;
use App\Models\SalesInvoice;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\SerialUnitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class InterCompanyReceiptLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_auto_purchase_is_available_despite_legacy_serial_deficit_and_supports_all_downstream_documents(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 12:00:00'));
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $source = Company::create(['name' => 'EEMOTRACK INDIA', 'created_by' => $user->id]);
        $target = Company::create(['name' => 'MARUTI SUZUKI VENTURES', 'created_by' => $user->id]);
        CompanyMerge::create(['company_id' => $source->id, 'merged_with_company_id' => $target->id, 'created_by' => $user->id]);
        $user->update(['current_company_id' => $source->id]);
        $items = [];
        foreach ([$source, $target] as $company) {
            $type = ProductType::create(['company_id' => $company->id, 'code' => 'FG', 'name' => 'Finished Goods', 'nature' => 'finished_goods']);
            $items[] = Item::create([
                'company_id' => $company->id, 'product_type_id' => $type->id, 'item_code' => 'ET-ANDROID',
                'name' => 'Android Device', 'unit' => 'PCS', 'purchase_price' => 100,
                'current_stock' => $company->id === $source->id ? 1 : 0,
                'stock_value' => $company->id === $source->id ? 100 : 0, 'track_stock' => true, 'status' => 'active',
            ]);
        }
        [$sourceItem, $targetItem] = $items;
        $unit = ['key' => '85-6', 'serial_no' => 'SKU-1894172860-AAA', 'batch_no' => 'ZNDX4487', 'item_id' => $sourceItem->id, 'scope_key' => $sourceItem->id.':85-6'];
        StockMovement::create([
            'company_id' => $source->id, 'item_id' => $sourceItem->id, 'movement_date' => '2026-10-14',
            'movement_type' => 'production_output', 'direction' => 'in', 'quantity' => 1,
            'unit_price' => 100, 'total_value' => 100, 'stock_after' => 1, 'movement_units' => [$unit],
        ]);
        // A legacy reversal with a regenerated key plus two unmatched outflows
        // leaves a negative identity balance, even though the physical unit is
        // back at the source and is being sold to the target again today.
        foreach ([
            ['sale_reversal', 'in', array_merge($unit, ['key' => 'PBI-689-1'])],
            ['purchase_return', 'out', $unit],
            ['inter_company_purchase_reversal', 'out', $unit],
        ] as [$type, $direction, $oldUnit]) {
            StockMovement::create([
                'company_id' => $target->id, 'item_id' => $targetItem->id, 'movement_date' => '2026-08-13',
                'movement_type' => $type, 'direction' => $direction, 'quantity' => 1,
                'unit_price' => 100, 'total_value' => 100, 'stock_after' => 0, 'movement_units' => [$oldUnit],
            ]);
        }
        // This unrelated historical identity already had a zero balance.
        // Treating every old purchase as a fresh receipt resurrects it.
        $oldItem = $targetItem->replicate();
        $oldItem->fill(['item_code' => 'OLD-ZERO-STOCK', 'name' => 'Old zero stock'])->save();
        foreach ([['2026-08-10', 'out', 'inter_company_purchase_reversal'], ['2026-08-11', 'in', 'inter_company_purchase']] as [$date, $direction, $type]) {
            StockMovement::create([
                'company_id' => $target->id, 'item_id' => $oldItem->id, 'movement_date' => $date,
                'movement_type' => $type, 'direction' => $direction, 'quantity' => 1,
                'unit_price' => 100, 'total_value' => 100, 'stock_after' => 0,
                'movement_units' => [['key' => 'OLD-UNIT', 'serial_no' => 'OLD-ZERO-SERIAL']],
            ]);
        }
        $serials = app(SerialUnitService::class);
        $this->assertSame([], $serials->currentStockUnitsByItem($target->id));
        $this->actingAs($user)->post(route('admin.sales.store'), [
            'sale_type' => 'cash', 'invoice_no' => 'NEW-TRANSFER', 'billing_date' => '2026-10-15',
            'item_id' => [$sourceItem->id], 'quantity' => [1], 'unit_price' => [150],
            'tax_mode' => ['without_gst'], 'tax_percent' => [0], 'selected_units' => [json_encode([$unit])],
            'inter_company_transfer' => true, 'target_company_ids' => [$target->id],
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.sales.index'));
        $sourceSale = SalesInvoice::where('invoice_no', 'NEW-TRANSFER')->firstOrFail();
        $bill = PurchaseBill::where('source_sales_invoice_id', $sourceSale->id)->firstOrFail();
        $billLine = $bill->items->first();
        $this->assertSame(1.0, (float) $targetItem->fresh()->current_stock);
        $this->assertSame([$targetItem->id], array_keys($serials->currentStockUnitsByItem($target->id)));
        $this->assertTrue(StockMovement::where('reference_type', PurchaseBill::class)->where('reference_id', $bill->id)->firstOrFail()->starts_new_receipt);
        $this->assertSame([], $serials->currentStockUnitsByItem($source->id));
        $pool = $serials->unitPool($target->id)[$targetItem->id] ?? [];
        $this->assertCount(1, $pool);
        $this->assertSame('SKU-1894172860-AAA', $pool[0]['serial_no']);
        $this->assertFalse($pool[0]['sold']);
        $this->assertSame($targetItem->id.':85-6', $billLine->selected_units[0]['scope_key']);
        $this->assertSame($targetItem->id.':85-6', $pool[0]['scope_key']);
        $user->update(['current_company_id' => $target->id]);
        $this->get(route('admin.stocks.index', ['q' => 'SKU-1894172860-AAA']))->assertOk()->assertSee('SKU-1894172860-AAA');
        $this->get(route('admin.stocks.index'))->assertOk()->assertViewHas('overallQty', 1.0)
            ->assertDontSee('OLD-ZERO-SERIAL');
        $this->getJson(route('admin.purchase-returns.bill-items', ['bill_id' => $bill->id]))->assertOk()
            ->assertJsonPath('lines.0.current_stock', 1)->assertJsonPath('lines.0.available_units.0.key', '85-6');

        $party = Party::create(['company_id' => $target->id, 'party_code' => 'CUSTOMER', 'display_name' => 'Customer', 'party_type' => 'customer', 'status' => 'active']);
        $line = [
            'party_id' => $party->id, 'item_id' => [$targetItem->id], 'quantity' => [1], 'unit_price' => [200],
            'tax_mode' => ['without_gst'], 'tax_percent' => [0], 'selected_units' => [json_encode([$pool[0]])],
        ];
        $this->post(route('admin.estimates.store'), $line + ['estimate_no' => 'EST-RECEIPT', 'estimate_date' => '2026-10-15'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(1.0, (float) $targetItem->fresh()->current_stock);
        $this->post(route('admin.sales.store'), $line + ['invoice_no' => 'RESALE-RECEIPT', 'billing_date' => '2026-10-15', 'sale_type' => 'credit'])
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.sales.index'));
        $this->assertSame([], $serials->currentStockUnitsByItem($target->id));
        $sale = SalesInvoice::where('invoice_no', 'RESALE-RECEIPT')->firstOrFail();
        $sold = $sale->items->first();
        $this->post(route('admin.sales-returns.store'), [
            'sales_invoice_id' => $sale->id, 'return_no' => 'CUSTOMER-RETURN', 'return_date' => '2026-10-15',
            'line_id' => [$sold->id], 'quantity' => [1], 'returned_units' => [json_encode($sold->selected_units)],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertCount(1, $serials->unitPool($target->id)[$targetItem->id]);

        $this->post(route('admin.delivery-challans.store'), $line + ['challan_no' => 'DC-RECEIPT', 'challan_date' => '2026-10-15'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame([], $serials->unitPool($target->id));
        $challan = DeliveryChallan::where('challan_no', 'DC-RECEIPT')->firstOrFail();
        $this->patch(route('admin.delivery-challans.cancel', $challan))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertCount(1, $serials->unitPool($target->id)[$targetItem->id]);
        $this->post(route('admin.purchase-returns.store'), [
            'purchase_bill_id' => $bill->id, 'return_no' => 'RETURN-TO-EEMOTRACK', 'return_date' => '2026-10-15',
            'line_id' => [$billLine->id], 'quantity' => [1], 'returned_units' => [json_encode($billLine->selected_units)],
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.purchase-returns.index'));
        $this->assertSame([], $serials->unitPool($target->id));
        $this->assertCount(1, $serials->unitPool($source->id)[$sourceItem->id]);
        $this->assertSame(0.0, (float) $targetItem->fresh()->current_stock);
        $this->assertSame(1.0, (float) $sourceItem->fresh()->current_stock);
        $this->assertSame(1, StockMovement::where('company_id', $target->id)->where('reference_type', PurchaseBill::class)
            ->where('reference_id', $bill->id)->where('movement_type', 'inter_company_purchase')->count());
    }
}
