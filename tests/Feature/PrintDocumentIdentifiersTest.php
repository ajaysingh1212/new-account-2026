<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\DeliveryChallan;
use App\Models\DeliveryChallanItem;
use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\Item;
use App\Models\ProductType;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\SerialUnitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PrintDocumentIdentifiersTest extends TestCase
{
    use RefreshDatabase;

    public static function documents(): array
    {
        return [
            'sales' => ['admin.sales.print', 'invoice', SalesInvoice::class, SalesInvoiceItem::class],
            'quotation' => ['admin.estimates.print', 'estimate', Estimate::class, EstimateItem::class],
            'challan' => ['admin.delivery-challans.print', 'deliveryChallan', DeliveryChallan::class, DeliveryChallanItem::class],
        ];
    }

    #[DataProvider('documents')]
    public function test_prints_item_code_and_only_the_saved_unit_identifiers(string $view, string $key, string $documentClass, string $lineClass): void
    {
        $item = new Item(['name' => 'Device', 'item_code' => 'ITEM-CODE-007', 'hsn_code' => 'HSN-DO-NOT-PRINT']);
        $lines = collect([
            new $lineClass(['quantity' => 1, 'selected_units' => [['serial_no' => 'SERIAL-ONLY-001']]]),
            new $lineClass(['quantity' => 1, 'selected_units' => [['serial_no' => '', 'vts_sim' => 'SIM-ONLY-002']]]),
            new $lineClass(['quantity' => 1, 'selected_units' => [['serial_no' => 'SERIAL-BOTH-003', 'vts_sim' => 'VTS-BOTH-003'], ['serial_no' => 'SECOND-UNIT-004']]]),
            new $lineClass(['quantity' => 1, 'selected_units' => null]),
        ])->each(fn($line) => $line->setRelation('item', $item));
        $document = (new $documentClass(['status' => 'draft']))
            ->setRelation('items', $lines)
            ->setRelation('party', null)
            ->setRelation('company', new Company(['name' => 'Print Company']));

        $html = view($view, [$key => $document, 'defaultTerms' => null, 'company' => $document->company])->render();

        $this->assertStringContainsString('Item Code', $html);
        $this->assertStringContainsString('ITEM-CODE-007', $html);
        $this->assertStringNotContainsString('HSN-DO-NOT-PRINT', $html);
        $this->assertStringNotContainsString('HSN/SAC', $html);
        $this->assertStringContainsString('Serial No.: SERIAL-ONLY-001', $html);
        $this->assertStringContainsString('SIM / VTS No.: SIM-ONLY-002', $html);
        $this->assertStringContainsString('Serial No.: SERIAL-BOTH-003 | SIM / VTS No.: VTS-BOTH-003', $html);
        $this->assertStringContainsString('Serial No.: SECOND-UNIT-004', $html);
        $this->assertSame(4, substr_count($html, '<div class="item-identifiers">'));
    }

    public function test_quotation_saves_canonical_identifiers_without_moving_or_reserving_stock(): void
    {
        [$user, $item, $unit] = $this->quotationStock();
        $requestUnit = array_merge($unit, ['serial_no' => 'FORGED-SERIAL']);
        $payload = $this->quotationPayload($item, [$requestUnit]);

        $this->actingAs($user)->post(route('admin.estimates.store'), $payload)
            ->assertRedirect(route('admin.estimates.index'));

        $estimate = Estimate::firstOrFail();
        $this->assertSame('STOCK-SERIAL-001', $estimate->items->first()->selected_units[0]['serial_no']);
        $this->assertSame(2.0, (float) $item->fresh()->current_stock);
        $this->assertSame(0, StockMovement::count());
        $this->assertSame([], app(SerialUnitService::class)->allocatedKeys($item->company_id));
        $this->get(route('admin.estimates.print', $estimate))->assertOk()->assertSee('STOCK-SERIAL-001');
        $this->get(route('admin.estimates.edit', $estimate))->assertOk()->assertSee('selected-units-json');

        $this->put(route('admin.estimates.update', $estimate), $payload)
            ->assertRedirect(route('admin.estimates.show', $estimate));
        $this->assertSame('STOCK-SERIAL-001', $estimate->fresh()->items->first()->selected_units[0]['serial_no']);
    }

    public function test_quotation_rejects_unavailable_identifiers_and_allows_no_identifiers(): void
    {
        [$user, $item] = $this->quotationStock();
        $this->actingAs($user)->post(route('admin.estimates.store'), $this->quotationPayload($item, [['key' => 'unknown']]))
            ->assertStatus(422);
        $this->assertSame(0, Estimate::count());
        $this->post(route('admin.estimates.store'), $this->quotationPayload($item, []))
            ->assertRedirect(route('admin.estimates.index'));
        $this->assertSame([], Estimate::firstOrFail()->items->first()->selected_units);
    }

    private function quotationStock(): array
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create(['name' => 'Quote Company', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $company->id]);
        $type = ProductType::create(['company_id' => $company->id, 'code' => 'FG', 'name' => 'Finished Goods', 'nature' => 'finished_goods']);
        $item = Item::create(['company_id' => $company->id, 'product_type_id' => $type->id, 'item_code' => 'QUOTE-ITEM', 'name' => 'Device', 'unit' => 'PCS', 'current_stock' => 2, 'status' => 'active']);
        $unit = ['key' => 'stock-unit-1', 'item_id' => $item->id, 'serial_no' => 'STOCK-SERIAL-001', 'vts_sim' => 'SIM-001', 'sold' => false];
        $service = Mockery::mock(SerialUnitService::class)->makePartial();
        $service->shouldReceive('unitPool')->andReturn([$item->id => [$unit]]);
        $this->app->instance(SerialUnitService::class, $service);

        return [$user, $item, $unit];
    }

    private function quotationPayload(Item $item, array $units): array
    {
        return ['estimate_no' => 'EST-TEST', 'estimate_date' => '2026-10-13', 'item_id' => [$item->id], 'quantity' => [1], 'unit_price' => [100], 'selected_units' => [json_encode($units)]];
    }
}
