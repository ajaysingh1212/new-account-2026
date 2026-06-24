<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Item;
use App\Models\ProductionBatch;
use App\Models\ProductType;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\User;
use App\Services\CrmIdentifierPropagationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmIdentifierPropagationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_checked_locations_receive_identifier_changes(): void
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $sourceCompany = Company::create(['name' => 'Source Company', 'created_by' => $user->id]);
        $targetCompany = Company::create(['name' => 'Merged Company', 'created_by' => $user->id]);
        $type = ProductType::create(['company_id' => $sourceCompany->id, 'code' => 'FINISHED', 'name' => 'Finished', 'nature' => 'finished_goods']);
        $item = Item::create(['company_id' => $sourceCompany->id, 'product_type_id' => $type->id, 'item_code' => 'GPS-1', 'name' => 'GPS', 'sku' => 'SKU-OLD', 'unit' => 'PCS']);
        $oldUnits = [['key' => '1-0', 'serial_no' => 'SER-OLD', 'vts_sim' => 'SIM-OLD']];
        $newUnits = [['key' => '1-0', 'serial_no' => 'SER-NEW', 'vts_sim' => 'SIM-NEW']];
        $batch = ProductionBatch::create(['id' => 1, 'company_id' => $sourceCompany->id, 'finished_item_id' => $item->id, 'batch_no' => 'PB-1', 'production_date' => '2026-06-01', 'quantity' => 1, 'units_data' => $oldUnits, 'status' => 'posted']);

        $sale = SalesInvoice::create(['company_id' => $sourceCompany->id, 'sale_type' => 'cash', 'invoice_no' => 'SI-1', 'billing_date' => '2026-06-02', 'grand_total' => 100]);
        $saleLine = SalesInvoiceItem::create(['sales_invoice_id' => $sale->id, 'item_id' => $item->id, 'quantity' => 1, 'unit_price' => 100, 'line_total' => 100, 'selected_units' => $oldUnits]);

        $purchase = PurchaseBill::create(['company_id' => $targetCompany->id, 'purchase_type' => 'credit', 'invoice_no' => 'PI-1', 'billing_date' => '2026-06-02', 'grand_total' => 50]);
        $purchaseLine = PurchaseBillItem::create(['purchase_bill_id' => $purchase->id, 'item_id' => $item->id, 'quantity' => 1, 'unit_price' => 50, 'line_total' => 50, 'selected_units' => $oldUnits]);

        $service = app(CrmIdentifierPropagationService::class);
        $preview = collect($service->preview($batch->load('finishedItem'), $newUnits, 'SKU-OLD'));

        $this->assertTrue($preview->contains('token', 'sale:' . $sale->id));
        $this->assertTrue($preview->contains('token', 'purchase:' . $purchase->id));

        $service->propagate($batch, $oldUnits, $newUnits, 'SKU-OLD', 'SKU-OLD', ['sale:' . $sale->id]);

        $this->assertSame('SER-NEW', $saleLine->fresh()->selected_units[0]['serial_no']);
        $this->assertSame('SIM-NEW', $saleLine->fresh()->selected_units[0]['vts_sim']);
        $this->assertSame('SER-OLD', $purchaseLine->fresh()->selected_units[0]['serial_no']);
        $this->assertSame('SIM-OLD', $purchaseLine->fresh()->selected_units[0]['vts_sim']);
    }
}
