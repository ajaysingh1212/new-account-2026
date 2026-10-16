<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\DeliveryChallan;
use App\Models\Item;
use App\Models\Party;
use App\Models\PartyLedger;
use App\Models\SalesInvoice;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\EntryVisibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryChallanConversionTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversion_creates_invoice_and_redirects_to_it_without_dispatching_stock_again(): void
    {
        [$user, $dc] = $this->challan();
        $this->actingAs($user)->post(route('admin.delivery-challans.convert', $dc), [
            'sale_type' => 'credit', 'billing_date' => '2026-10-16',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.sales.show', SalesInvoice::firstOrFail()));

        $invoice = $dc->fresh()->convertedInvoice;
        $this->assertSame('converted', $dc->fresh()->status);
        $this->assertSame($dc->id, $invoice->source_delivery_challan_id);
        $this->assertEquals(200, $invoice->grand_total);
        $this->assertEquals(2, $invoice->items->first()->quantity);
        $this->assertSame(1, PartyLedger::count());
        $this->assertSame(0, StockMovement::count());
        $this->get(route('admin.sales.index'))->assertOk()->assertSee($invoice->invoice_no);
    }

    public function test_empty_challan_does_not_claim_conversion_succeeded(): void
    {
        [$user, $dc] = $this->challan(false);
        $this->actingAs($user)->post(route('admin.delivery-challans.convert', $dc), [
            'sale_type' => 'credit', 'billing_date' => '2026-10-16',
        ])->assertSessionHasErrors('conversion');
        $this->assertSame('issued', $dc->fresh()->status);
        $this->assertNull($dc->fresh()->converted_at);
        $this->assertSame(0, SalesInvoice::withTrashed()->count());
        $this->assertSame(0, PartyLedger::count());
    }

    public function test_retry_repairs_missing_invoice_and_repeated_clicks_do_not_duplicate_it(): void
    {
        [$user, $dc] = $this->challan();
        $dc->update(['status' => 'converted', 'converted_at' => now()]);
        $this->actingAs($user)->get(route('admin.delivery-challans.index'))
            ->assertOk()->assertSee('Sales invoice missing')->assertSee(route('admin.delivery-challans.retry-conversion', $dc));
        $this->get(route('admin.delivery-challans.show', $dc))->assertOk()->assertSee('Sales invoice missing');
        $retry = route('admin.delivery-challans.retry-conversion', $dc);
        $this->post($retry)->assertSessionHasNoErrors()->assertRedirect();
        $invoice = $dc->fresh()->convertedInvoice;
        $this->assertNotNull($invoice);
        $this->post($retry)->assertRedirect(route('admin.sales.show', $invoice));
        $this->assertSame(1, SalesInvoice::count());
        $this->assertSame(1, PartyLedger::count());
        $this->assertSame(0, StockMovement::count());
        $this->get(route('admin.delivery-challans.index'))->assertDontSee('Sales invoice missing');
    }

    public function test_retry_relinks_existing_source_invoice_instead_of_creating_another(): void
    {
        [$user, $dc] = $this->challan();
        $this->actingAs($user)->post(route('admin.delivery-challans.convert', $dc), [
            'sale_type' => 'credit', 'billing_date' => '2026-10-16',
        ])->assertRedirect();
        $invoice = $dc->fresh()->convertedInvoice;
        $dc->update(['converted_sales_invoice_id' => null]);
        $this->post(route('admin.delivery-challans.retry-conversion', $dc))->assertRedirect(route('admin.sales.show', $invoice));
        $this->assertSame($invoice->id, $dc->fresh()->converted_sales_invoice_id);
        $this->assertSame(1, SalesInvoice::count());
        $this->assertSame(1, PartyLedger::count());
    }

    public function test_dc_creator_can_see_invoice_created_by_another_user(): void
    {
        [$admin, $dc] = $this->challan();
        $owner = User::factory()->create(['current_company_id' => $dc->company_id]);
        $outsider = User::factory()->create(['current_company_id' => $dc->company_id]);
        $dc->update(['created_by' => $owner->id]);
        $this->actingAs($admin)->post(route('admin.delivery-challans.convert', $dc), [
            'sale_type' => 'credit', 'billing_date' => '2026-10-16',
        ])->assertRedirect();
        $invoice = $dc->fresh()->convertedInvoice;
        $visibility = app(EntryVisibilityService::class);
        $this->actingAs($owner);
        $this->assertTrue($visibility->canView($owner, $invoice));
        $this->assertTrue($visibility->scopeForUser(SalesInvoice::query(), SalesInvoice::class)->whereKey($invoice->id)->exists());
        $this->actingAs($outsider);
        $this->assertFalse($visibility->canView($outsider, $invoice));
        $this->assertFalse($visibility->scopeForUser(SalesInvoice::query(), SalesInvoice::class)->whereKey($invoice->id)->exists());
    }

    private function challan(bool $withLines = true): array
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create(['name' => 'DC Conversion', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $company->id]);
        $party = Party::create(['company_id' => $company->id, 'party_code' => 'DC-P1', 'party_type' => 'customer', 'display_name' => 'DC Customer', 'status' => 'active']);
        $dc = DeliveryChallan::create([
            'company_id' => $company->id, 'party_id' => $party->id, 'challan_no' => 'DC-TEST',
            'challan_date' => '2026-10-15', 'status' => 'issued', 'created_by' => $user->id,
        ]);
        if ($withLines) {
            $item = Item::create(['company_id' => $company->id, 'item_code' => 'DC-I1', 'name' => 'DC Item', 'unit' => 'PCS', 'track_stock' => true, 'current_stock' => 0, 'status' => 'active']);
            $dc->items()->create([
                'item_id' => $item->id, 'quantity' => 2, 'unit' => 'PCS', 'unit_price' => 100,
                'discount_type' => 'percent', 'discount_value' => 0, 'discount_amount' => 0,
                'tax_percent' => 0, 'tax_amount' => 0, 'line_total' => 200,
                'selected_units' => [['key' => 'dc-1', 'serial_no' => 'DC-S1'], ['key' => 'dc-2', 'serial_no' => 'DC-S2']],
            ]);
        }
        return [$user, $dc];
    }
}
