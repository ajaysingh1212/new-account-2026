<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Item;
use App\Models\MarutiStockOut;
use App\Models\ProductType;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\SerialUnitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarutiStockOutTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_stock_is_archived_and_individual_restores_can_be_estimated_sold_and_dispatched(): void
    {
        [$user, $company, $item, $raw, $units] = $this->context();
        $this->get(route('admin.stocks.index'))->assertOk()->assertSee('Zero Available Stock');
        $rows = $this->getJson(route('admin.stocks.maruti-stock-out.available'))
            ->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('rows');
        $this->assertCount(4, $rows);
        $this->post(route('admin.stocks.maruti-stock-out.store'), [
            'selected_json' => json_encode(array_column($rows, 'selection_key')), 'notes' => 'Physical stock clearance',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.stocks.special-stock-out'));
        $this->assertSame(0.0, (float) $item->fresh()->current_stock);
        $this->assertSame(0.0, (float) $raw->fresh()->current_stock);
        $this->assertCount(4, MarutiStockOut::all());
        $this->assertCount(1, MarutiStockOut::all()->pluck('batch_no')->unique());
        $this->assertSame([], app(SerialUnitService::class)->unitPool($company->id));
        $this->getJson(route('admin.stocks.maruti-stock-out.available'))->assertJsonCount(0, 'rows');
        $this->assertDatabaseCount('stock_out_challans', 0);
        $this->assertDatabaseCount('party_ledgers', 0);
        $this->assertDatabaseCount('sales_invoices', 0);
        $this->get(route('admin.stocks.special-stock-out'))->assertOk()
            ->assertSee('Physical stock clearance')->assertSee('1666320643')->assertSee('Restore to Stock');

        $entry = MarutiStockOut::where('item_id', $item->id)->firstOrFail();
        $this->post(route('admin.stocks.maruti-stock-out.restore', $entry->id))
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertNotNull($entry->fresh()->restored_at);
        $this->assertSame($user->id, $entry->fresh()->restored_by);
        $this->assertSame(1.0, (float) $item->fresh()->current_stock);
        $pool = app(SerialUnitService::class)->unitPool($company->id)[$item->id];
        $this->assertCount(1, $pool);
        $this->assertFalse($pool[0]['sold']);
        $this->assertSame($units[0]['key'], $pool[0]['key']);
        $this->post(route('admin.stocks.maruti-stock-out.restore', $entry->id))->assertSessionHasErrors('restore');
        $this->assertSame(1.0, (float) $item->fresh()->current_stock);

        $line = [
            'item_id' => [$item->id], 'quantity' => [1], 'unit' => ['PCS'], 'unit_price' => [150],
            'tax_percent' => [0], 'tax_mode' => ['without_gst'], 'selected_units' => [json_encode([$pool[0]])],
        ];
        $this->post(route('admin.estimates.store'), $line + ['estimate_date' => now()->toDateString(), 'estimate_no' => 'EST-RESTORED'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('estimate_items', ['item_id' => $item->id, 'quantity' => 1]);
        $this->post(route('admin.sales.store'), $line + ['sale_type' => 'cash', 'billing_date' => now()->toDateString(), 'invoice_no' => 'SALE-RESTORED'])
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.sales.index'));
        $this->assertSame([], app(SerialUnitService::class)->currentStockUnitsByItem($company->id));

        $second = MarutiStockOut::where('item_id', $item->id)->whereNull('restored_at')->firstOrFail();
        $this->post(route('admin.stocks.maruti-stock-out.restore', $second->id))->assertSessionHasNoErrors()->assertRedirect();
        $pool = app(SerialUnitService::class)->unitPool($company->id)[$item->id];
        $line['selected_units'] = [json_encode([$pool[0]])];
        $this->post(route('admin.delivery-challans.store'), $line + ['challan_date' => now()->toDateString(), 'challan_no' => 'DC-RESTORED'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('delivery_challan_items', ['item_id' => $item->id, 'quantity' => 1]);
        $this->assertSame([], app(SerialUnitService::class)->unitPool($company->id));
        $this->assertSame(2, MarutiStockOut::whereNull('restored_at')->count());

        $rawEntry = MarutiStockOut::where('item_id', $raw->id)->firstOrFail();
        $this->post(route('admin.stocks.maruti-stock-out.restore', $rawEntry->id))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(2.5, (float) $raw->fresh()->current_stock);
        $this->get(route('admin.stocks.special-stock-out'))->assertOk()->assertSee('Restored');
    }

    public function test_partial_selection_and_stale_popup_are_handled_atomically(): void
    {
        [, $company, $item, $raw] = $this->context();
        $rows = $this->getJson(route('admin.stocks.maruti-stock-out.available'))->json('rows');
        $key = collect($rows)->firstWhere('item_id', $item->id)['selection_key'];
        $this->post(route('admin.stocks.maruti-stock-out.store'), ['selected_json' => json_encode([$key])])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(2.0, (float) $item->fresh()->current_stock);
        $this->assertSame(2.5, (float) $raw->fresh()->current_stock);
        $this->post(route('admin.stocks.maruti-stock-out.store'), ['selected_json' => json_encode(array_column($rows, 'selection_key'))])
            ->assertSessionHasErrors('selected');
        $this->assertDatabaseCount('maruti_stock_outs', 1);
        $this->assertSame(2.0, (float) $item->fresh()->current_stock);
        $this->assertCount(2, app(SerialUnitService::class)->unitPool($company->id)[$item->id]);
        $this->post(route('admin.stocks.maruti-stock-out.store'), ['selected_json' => '[]'])->assertSessionHasErrors('selected');
        $this->post(route('admin.stocks.maruti-stock-out.store'), ['selected_json' => '"invalid"'])->assertSessionHasErrors('selected');
    }

    public function test_feature_is_restricted_to_maruti_admin_and_other_company_stock_is_unchanged(): void
    {
        [$user, $company, $item] = $this->context();
        $other = Company::create(['name' => 'EEMOTRACK INDIA', 'created_by' => $user->id]);
        $otherItem = Item::create(['company_id' => $other->id, 'item_code' => 'SOURCE', 'name' => 'Source stock', 'unit' => 'PCS', 'current_stock' => 12]);
        $rows = $this->getJson(route('admin.stocks.maruti-stock-out.available'))->json('rows');
        $this->post(route('admin.stocks.maruti-stock-out.store'), ['selected_json' => json_encode(array_column($rows, 'selection_key'))])->assertSessionHasNoErrors();
        $this->assertSame(12.0, (float) $otherItem->fresh()->current_stock);
        $entry = MarutiStockOut::firstOrFail();
        $user->update(['current_company_id' => $other->id]);
        $this->get(route('admin.stocks.index'))->assertOk()->assertDontSee('Zero Available Stock');
        $this->getJson(route('admin.stocks.maruti-stock-out.available'))->assertForbidden();
        $this->post(route('admin.stocks.maruti-stock-out.store'), ['selected_json' => json_encode(array_column($rows, 'selection_key'))])->assertForbidden();
        $this->post(route('admin.stocks.maruti-stock-out.restore', $entry->id))->assertForbidden();
        $this->get(route('admin.stocks.special-stock-out'))->assertOk()->assertDontSee($entry->batch_no);
        $user->update(['current_company_id' => $company->id, 'user_type' => 'user']);
        $this->post(route('admin.stocks.maruti-stock-out.restore', $entry->id))->assertForbidden();
        $this->assertSame(0.0, (float) $item->fresh()->current_stock);
    }

    public function test_serialized_stock_with_stale_quantity_counter_is_zeroed_and_restored_correctly(): void
    {
        [, $company, $item] = $this->context();
        $item->update(['current_stock' => 25]);
        $rows = $this->getJson(route('admin.stocks.maruti-stock-out.available'))->json('rows');
        $keys = collect($rows)->where('item_id', $item->id)->pluck('selection_key')->all();
        $this->assertCount(3, $keys);
        $this->post(route('admin.stocks.maruti-stock-out.store'), ['selected_json' => json_encode($keys)])->assertSessionHasNoErrors();
        $this->assertSame(0.0, (float) $item->fresh()->current_stock);
        $this->assertSame([], app(SerialUnitService::class)->currentStockUnitsByItem($company->id));
        $this->post(route('admin.stocks.maruti-stock-out.restore', MarutiStockOut::firstOrFail()->id))->assertSessionHasNoErrors();
        $this->assertSame(1.0, (float) $item->fresh()->current_stock);
    }

    private function context(): array
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create(['name' => 'MARUTI SUZUKI VENTURES', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $company->id]);
        $this->actingAs($user);
        $type = ProductType::create(['company_id' => $company->id, 'code' => 'FG', 'name' => 'Finished Goods', 'nature' => 'finished_goods']);
        $item = Item::create([
            'company_id' => $company->id, 'product_type_id' => $type->id, 'item_code' => 'GPS-MARUTI',
            'name' => 'GPS Device', 'unit' => 'PCS', 'sku' => 'GPS', 'purchase_price' => 100,
            'current_stock' => 3, 'stock_value' => 300, 'track_stock' => true, 'status' => 'active',
        ]);
        $units = collect(range(1, 3))->map(fn ($n) => [
            'key' => 'UNIT-'.$n, 'serial_no' => (string) (1666320642 + $n), 'vts_sim' => 'SIM-'.$n,
            'sku' => 'SKU-'.$n, 'batch_no' => 'BATCH-1', 'item_id' => $item->id,
        ])->all();
        StockMovement::create([
            'company_id' => $company->id, 'item_id' => $item->id, 'movement_date' => now()->subDay()->toDateString(),
            'movement_type' => 'purchase', 'direction' => 'in', 'quantity' => 3, 'unit_price' => 100,
            'total_value' => 300, 'stock_after' => 3, 'movement_units' => $units,
        ]);
        $rawType = ProductType::create(['company_id' => $company->id, 'code' => 'RM', 'name' => 'Raw', 'nature' => 'raw_material']);
        $raw = Item::create([
            'company_id' => $company->id, 'product_type_id' => $rawType->id, 'item_code' => 'RAW', 'name' => 'Wire',
            'unit' => 'KG', 'purchase_price' => 10, 'current_stock' => 2.5, 'stock_value' => 25, 'track_stock' => true,
        ]);

        return [$user, $company, $item, $raw, $units];
    }
}
