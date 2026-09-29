<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CreditNote;
use App\Models\Item;
use App\Models\ProductType;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_credit_note_create_screen_renders(): void
    {
        [$user] = $this->invoiceContext();

        $this->actingAs($user)->withoutMiddleware()
            ->get(route('admin.credit-notes.create'))
            ->assertOk()
            ->assertSee('New Credit Note');
    }

    public function test_credit_note_requires_confirmation_when_sales_return_is_missing(): void
    {
        [$user, $invoice, $line] = $this->invoiceContext();

        $response = $this->actingAs($user)->withoutMiddleware()->post(route('admin.credit-notes.store'), [
            'sales_invoice_id' => $invoice->id,
            'credit_note_no' => 'CN-00001',
            'credit_note_date' => '2026-09-29',
            'line_id' => [$line->id],
            'quantity' => [1],
            'selected_units' => [json_encode([$line->selected_units[0]])],
        ]);

        $response->assertSessionHasErrors('return_confirmation');
        $this->assertDatabaseCount('credit_notes', 0);
    }

    public function test_confirmed_credit_note_is_created_without_changing_invoice_total_or_stock(): void
    {
        [$user, $invoice, $line, $item] = $this->invoiceContext();
        $stockBefore = $item->current_stock;

        $this->actingAs($user)->withoutMiddleware()->post(route('admin.credit-notes.store'), [
            'sales_invoice_id' => $invoice->id,
            'credit_note_no' => 'CN-00001',
            'credit_note_date' => '2026-09-29',
            'line_id' => [$line->id],
            'quantity' => [1],
            'selected_units' => [json_encode([$line->selected_units[0]])],
            'allow_without_return' => 1,
        ])->assertRedirect();

        $note = CreditNote::firstOrFail();
        $this->assertSame(100.0, (float) $note->grand_total);
        $this->assertFalse($note->hasCompleteSalesReturn());
        $this->assertSame(200.0, (float) $invoice->fresh()->grand_total);
        $this->assertSame((float) $stockBefore, (float) $item->fresh()->current_stock);
    }

    public function test_returned_serial_marks_credit_note_as_return_received(): void
    {
        [$user, $invoice, $line, $item] = $this->invoiceContext();
        $return = SalesReturn::create([
            'company_id' => $invoice->company_id, 'sales_invoice_id' => $invoice->id,
            'return_no' => 'SR-00001', 'return_date' => '2026-09-29',
        ]);
        SalesReturnItem::create([
            'sales_return_id' => $return->id, 'sales_invoice_item_id' => $line->id, 'item_id' => $item->id,
            'quantity' => 1, 'unit' => 'PCS', 'unit_price' => 100, 'line_total' => 100,
            'selected_units' => [$line->selected_units[0]],
        ]);

        $this->actingAs($user)->withoutMiddleware()->post(route('admin.credit-notes.store'), [
            'sales_invoice_id' => $invoice->id, 'credit_note_no' => 'CN-00001',
            'credit_note_date' => '2026-09-29', 'line_id' => [$line->id], 'quantity' => [1],
            'selected_units' => [json_encode([$line->selected_units[0]])],
        ])->assertRedirect();

        $this->assertTrue(CreditNote::firstOrFail()->hasCompleteSalesReturn());
    }

    public function test_gst1_deducts_credit_note_tax_in_credit_note_month(): void
    {
        [$user, $invoice, $line] = $this->invoiceContext();
        $invoice->update(['billing_date' => '2026-08-15', 'subtotal' => 200, 'tax_amount' => 36, 'grand_total' => 236]);
        $line->update(['unit_price' => 118, 'tax_percent' => 18, 'tax_amount' => 36, 'line_total' => 236]);

        $this->actingAs($user)->withoutMiddleware()->post(route('admin.credit-notes.store'), [
            'sales_invoice_id' => $invoice->id, 'credit_note_no' => 'CN-GST-1',
            'credit_note_date' => '2026-09-10', 'line_id' => [$line->id], 'quantity' => [1],
            'selected_units' => [json_encode([$line->selected_units[0]])], 'allow_without_return' => 1,
        ])->assertRedirect();

        $this->actingAs($user)->withoutMiddleware()
            ->get(route('admin.reports.gst1', ['month' => '2026-09']))
            ->assertOk()
            ->assertViewHas('creditNoteTotals', fn($totals) => (float) $totals['taxable'] === 100.0
                && (float) $totals['gst'] === 18.0
                && (float) $totals['total'] === 118.0)
            ->assertViewHas('totals', fn($totals) => (float) $totals['taxable'] === -100.0
                && (float) $totals['gst'] === -18.0
                && (float) $totals['total'] === -118.0)
            ->assertSee('CN-GST-1');
    }

    public function test_dashboard_cards_use_credit_note_date_filter_and_show_details(): void
    {
        [$user, $invoice, $line] = $this->invoiceContext();
        $invoice->update(['tax_amount' => 36, 'grand_total' => 236]);
        $line->update(['unit_price' => 118, 'tax_percent' => 18, 'tax_amount' => 36, 'line_total' => 236]);
        $this->actingAs($user)->withoutMiddleware()->post(route('admin.credit-notes.store'), [
            'sales_invoice_id' => $invoice->id, 'credit_note_no' => 'CN-DASH-1',
            'credit_note_date' => '2026-09-10', 'line_id' => [$line->id], 'quantity' => [1],
            'selected_units' => [json_encode([$line->selected_units[0]])], 'allow_without_return' => 1,
        ]);

        $this->actingAs($user)->withoutMiddleware()
            ->get(route('admin.dashboard', ['period' => 'custom', 'from_date' => '2026-09-01', 'to_date' => '2026-09-30']))
            ->assertOk()
            ->assertViewHas('stats', fn($stats) => (float) $stats['credit_notes_total'] === 118.0
                && (float) $stats['credit_notes_tax'] === 18.0)
            ->assertSee('Credit Notes Passed')
            ->assertSee('GST Tax Minus')
            ->assertSee('CN-DASH-1');
    }

    public function test_credit_note_print_shows_sales_invoice_adjustment_summary(): void
    {
        [$user, $invoice, $line, $item] = $this->invoiceContext();

        $note = CreditNote::create([
            'company_id' => $invoice->company_id,
            'sales_invoice_id' => $invoice->id,
            'party_id' => $invoice->party_id,
            'credit_note_no' => 'CN-PRINT-1',
            'credit_note_date' => '2026-09-29',
            'subtotal' => 100,
            'tax_amount' => 0,
            'grand_total' => 100,
            'return_status' => 'pending',
            'created_by' => $user->id,
        ]);
        $note->items()->create([
            'sales_invoice_item_id' => $line->id,
            'item_id' => $item->id,
            'description' => 'SKU: GPS-1',
            'quantity' => 1,
            'unit' => 'PCS',
            'unit_price' => 100,
            'tax_percent' => 0,
            'tax_amount' => 0,
            'line_total' => 100,
            'selected_units' => [$line->selected_units[0]],
        ]);

        $this->actingAs($user)
            ->get(route('admin.credit-notes.print', $note))
            ->assertOk()
            ->assertSee('Sales Invoice Adjustment Summary')
            ->assertSee('Original Sales Invoice Amount')
            ->assertSee('This Credit Note Amount')
            ->assertSee('Invoice Amount After Credit Notes')
            ->assertSee('SI-00001')
            ->assertSee('200.00')
            ->assertSee('100.00');
    }

    private function invoiceContext(): array
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create(['name' => 'Credit Note Company', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $company->id]);
        $type = ProductType::create(['company_id' => $company->id, 'code' => 'FG', 'name' => 'Finished Goods', 'nature' => 'finished_goods']);
        $item = Item::create([
            'company_id' => $company->id, 'product_type_id' => $type->id, 'item_code' => 'GPS-1',
            'name' => 'GPS Device', 'unit' => 'PCS', 'purchase_price' => 50, 'sale_price' => 100,
            'current_stock' => 0, 'track_stock' => true, 'status' => 'active',
        ]);
        $invoice = SalesInvoice::create([
            'company_id' => $company->id, 'sale_type' => 'cash', 'invoice_no' => 'SI-00001',
            'billing_date' => '2026-09-20', 'subtotal' => 200, 'grand_total' => 200, 'created_by' => $user->id,
        ]);
        $units = [
            ['key' => 'GPS-1-A', 'serial_no' => 'SER-A'],
            ['key' => 'GPS-1-B', 'serial_no' => 'SER-B'],
        ];
        $line = SalesInvoiceItem::create([
            'sales_invoice_id' => $invoice->id, 'item_id' => $item->id, 'quantity' => 2,
            'unit' => 'PCS', 'unit_price' => 100, 'tax_percent' => 0, 'tax_amount' => 0,
            'line_total' => 200, 'selected_units' => $units,
        ]);

        return [$user, $invoice, $line, $item];
    }
}
