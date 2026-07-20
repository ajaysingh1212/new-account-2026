<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Item;
use App\Models\Party;
use App\Models\ProductType;
use App\Models\ProductionBatch;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\PartyLedger;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesInvoicePoDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_gst_report_uses_po_date_for_sales_invoices(): void
    {
        [$user, $company, $invoice] = $this->seedSalesInvoice('2026-07-05', '2026-08-12');

        $this->actingAs($user)
            ->get(route('admin.reports.gst1', ['month' => '2026-07']))
            ->assertOk()
            ->assertDontSee($invoice->invoice_no);

        $this->actingAs($user)
            ->get(route('admin.reports.gst1', ['month' => '2026-08']))
            ->assertOk()
            ->assertSee($invoice->invoice_no);

        $this->actingAs($user)
            ->get(route('admin.reports.gst3', ['month' => '2026-08']))
            ->assertOk()
            ->assertSee('GST-3 Summary');
    }

    public function test_updating_po_date_does_not_change_stock_or_party_ledger(): void
    {
        [$user, $company, $invoice] = $this->seedSalesInvoice('2026-07-05', '2026-08-12');

        $stockCountBefore = StockMovement::count();
        $ledgerCountBefore = PartyLedger::count();
        $currentStockBefore = (float) $invoice->items->first()->item->fresh()->current_stock;

        $payload = [
            'party_id' => $invoice->party_id,
            'cost_center_id' => $invoice->cost_center_id,
            'sub_cost_center_id' => $invoice->sub_cost_center_id,
            'sale_type' => $invoice->sale_type,
            'invoice_no' => $invoice->invoice_no,
            'billing_date' => $invoice->billing_date?->format('Y-m-d'),
            'po_date' => '2026-08-25',
            'reference_no' => $invoice->reference_no,
            'phone' => $invoice->phone,
            'billing_address' => $invoice->billing_address,
            'shipping_address' => $invoice->shipping_address,
            'discount_amount' => 0,
            'notes' => $invoice->notes,
            'terms' => $invoice->terms,
            'item_id' => [$invoice->items->first()->item_id],
            'quantity' => ['1'],
            'unit_price' => ['150'],
            'tax_mode' => ['with_gst'],
            'tax_percent' => ['18'],
            'selected_units' => [json_encode([['key' => '1-0', 'serial_no' => 'SER-PO-1', 'vts_sim' => 'SIM-PO-1']])],
        ];

        $this->actingAs($user)
            ->put(route('admin.sales.update', $invoice), $payload)
            ->assertRedirect(route('admin.sales.show', $invoice));

        $invoice->refresh()->load('items.item');

        $this->assertSame('2026-08-25', $invoice->po_date?->format('Y-m-d'));
        $this->assertSame($stockCountBefore, StockMovement::count());
        $this->assertSame($ledgerCountBefore, PartyLedger::count());
        $this->assertSame($currentStockBefore, (float) $invoice->items->first()->item->fresh()->current_stock);
    }

    private function seedSalesInvoice(string $billingDate, string $poDate): array
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create(['name' => 'PO Date Company', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $company->id]);

        $party = Party::create([
            'company_id' => $company->id,
            'party_code' => 'P-PO-1',
            'party_type' => 'customer',
            'display_name' => 'PO Date Party',
            'status' => 'active',
        ]);

        $type = ProductType::create([
            'company_id' => $company->id,
            'code' => 'FG',
            'name' => 'Finished Goods',
            'nature' => 'finished_goods',
        ]);

        $item = Item::create([
            'company_id' => $company->id,
            'product_type_id' => $type->id,
            'item_code' => 'PO-ITEM',
            'name' => 'PO Item',
            'unit' => 'PCS',
            'purchase_price' => 100,
            'sale_price' => 150,
            'current_stock' => 1,
            'track_stock' => true,
            'status' => 'active',
        ]);

        ProductionBatch::create([
            'company_id' => $company->id,
            'finished_item_id' => $item->id,
            'batch_no' => 'PB-PO-1',
            'production_date' => '2026-07-01',
            'quantity' => 1,
            'cost_per_unit' => 100,
            'units_data' => [[
                'key' => '1-0',
                'serial_no' => 'SER-PO-1',
                'vts_sim' => 'SIM-PO-1',
                'batch_no' => 'PB-PO-1',
            ]],
            'status' => 'posted',
            'created_by' => $user->id,
        ]);

        $invoice = SalesInvoice::create([
            'company_id' => $company->id,
            'party_id' => $party->id,
            'sale_type' => 'credit',
            'invoice_no' => 'SI-PO-1',
            'billing_date' => $billingDate,
            'po_date' => $poDate,
            'subtotal' => 150,
            'discount_amount' => 0,
            'tax_amount' => 22.88,
            'grand_total' => 150,
            'status' => 'posted',
            'created_by' => $user->id,
        ]);

        SalesInvoiceItem::create([
            'sales_invoice_id' => $invoice->id,
            'item_id' => $item->id,
            'quantity' => 1,
            'unit' => 'PCS',
            'unit_price' => 150,
            'discount_type' => 'percent',
            'discount_value' => 0,
            'discount_amount' => 0,
            'tax_percent' => 18,
            'tax_amount' => 22.88,
            'line_total' => 150,
            'selected_units' => [[
                'key' => '1-0',
                'serial_no' => 'SER-PO-1',
                'vts_sim' => 'SIM-PO-1',
            ]],
        ]);

        // The live store flow would also post stock and ledger entries. This
        // fixture only needs the invoice shape for report/update regression.
        PartyLedger::create([
            'company_id' => $company->id,
            'party_id' => $party->id,
            'entry_date' => $billingDate,
            'entry_type' => 'sale',
            'reference_type' => SalesInvoice::class,
            'reference_id' => $invoice->id,
            'reference_no' => $invoice->invoice_no,
            'debit' => 150,
            'credit' => 0,
            'balance_after' => 150,
            'description' => 'Seed ledger',
            'created_by' => $user->id,
        ]);

        StockMovement::create([
            'company_id' => $company->id,
            'item_id' => $item->id,
            'movement_date' => $billingDate,
            'movement_type' => 'sale',
            'direction' => 'out',
            'quantity' => 1,
            'unit_price' => 150,
            'total_value' => 150,
            'stock_after' => 0,
            'movement_units' => [[
                'key' => '1-0',
                'serial_no' => 'SER-PO-1',
                'vts_sim' => 'SIM-PO-1',
            ]],
        ]);

        $invoice->setRelation('items', collect([
            SalesInvoiceItem::where('sales_invoice_id', $invoice->id)->with('item')->first(),
        ]));

        return [$user, $company, $invoice];
    }
}
