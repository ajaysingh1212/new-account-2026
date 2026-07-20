<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\DeliveryChallan;
use App\Models\DeliveryChallanItem;
use App\Models\Item;
use App\Models\ProductType;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SerialIndexDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_challan_index_shows_selected_serial_numbers(): void
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create(['name' => 'Delivery Serial Company', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $company->id]);
        $type = ProductType::create([
            'company_id' => $company->id,
            'code' => 'FG',
            'name' => 'Finished Goods',
            'nature' => 'finished_goods',
        ]);
        $item = Item::create([
            'company_id' => $company->id,
            'product_type_id' => $type->id,
            'item_code' => 'ITEM-1',
            'name' => 'Serial Item',
            'unit' => 'PCS',
            'purchase_price' => 100,
            'track_stock' => true,
            'status' => 'active',
        ]);

        $challan = DeliveryChallan::create([
            'company_id' => $company->id,
            'challan_no' => 'DC-00001',
            'challan_date' => '2026-07-10',
            'status' => 'issued',
            'created_by' => $user->id,
        ]);
        DeliveryChallanItem::create([
            'delivery_challan_id' => $challan->id,
            'item_id' => $item->id,
            'quantity' => 1,
            'unit' => 'PCS',
            'unit_price' => 100,
            'line_total' => 100,
            'selected_units' => [['serial_no' => 'DC-SER-001', 'key' => '1-0']],
        ]);

        $this->actingAs($user)
            ->get(route('admin.delivery-challans.index'))
            ->assertOk()
            ->assertSee('DC-SER-001');
    }

    public function test_sales_return_index_shows_selected_serial_numbers(): void
    {
        $user = User::factory()->create(['user_type' => 'super_admin']);
        $company = Company::create(['name' => 'Return Serial Company', 'created_by' => $user->id]);
        $user->update(['current_company_id' => $company->id]);
        $type = ProductType::create([
            'company_id' => $company->id,
            'code' => 'FG',
            'name' => 'Finished Goods',
            'nature' => 'finished_goods',
        ]);
        $item = Item::create([
            'company_id' => $company->id,
            'product_type_id' => $type->id,
            'item_code' => 'ITEM-2',
            'name' => 'Return Item',
            'unit' => 'PCS',
            'purchase_price' => 100,
            'sale_price' => 150,
            'track_stock' => true,
            'status' => 'active',
        ]);

        $invoice = SalesInvoice::create([
            'company_id' => $company->id,
            'sale_type' => 'cash',
            'invoice_no' => 'SI-00001',
            'billing_date' => '2026-07-10',
            'status' => 'posted',
            'created_by' => $user->id,
        ]);
        $invoiceLine = SalesInvoiceItem::create([
            'sales_invoice_id' => $invoice->id,
            'item_id' => $item->id,
            'quantity' => 1,
            'unit' => 'PCS',
            'unit_price' => 150,
            'discount_type' => 'percent',
            'discount_value' => 0,
            'discount_amount' => 0,
            'tax_percent' => 0,
            'tax_amount' => 0,
            'line_total' => 150,
            'selected_units' => [['serial_no' => 'SR-SER-001', 'key' => '1-0']],
        ]);

        $return = SalesReturn::create([
            'company_id' => $company->id,
            'sales_invoice_id' => $invoice->id,
            'return_no' => 'SR-00001',
            'return_date' => '2026-07-11',
            'created_by' => $user->id,
        ]);
        SalesReturnItem::create([
            'sales_return_id' => $return->id,
            'sales_invoice_item_id' => $invoiceLine->id,
            'item_id' => $item->id,
            'quantity' => 1,
            'unit' => 'PCS',
            'unit_price' => 150,
            'tax_percent' => 0,
            'tax_amount' => 0,
            'line_total' => 150,
            'selected_units' => [['serial_no' => 'SR-SER-001', 'key' => '1-0']],
        ]);

        $this->actingAs($user)
            ->get(route('admin.sales-returns.index'))
            ->assertOk()
            ->assertSee('SR-SER-001');
    }
}
