<?php

namespace Tests\Unit;

use App\Models\Item;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Services\SalesProfitService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SalesProfitServiceTest extends TestCase
{
    public function test_profit_percentage_is_calculated_on_cost(): void
    {
        $service = new SalesProfitService();

        $this->assertSame(25.0, $service->profitPercentage(50, 200));
        $this->assertSame(-25.0, $service->profitPercentage(-50, 200));
        $this->assertSame(0.0, $service->profitPercentage(50, 0));
        $this->assertSame(10.0, $service->profitPercentageOnSale(50, 500));
    }

    public function test_invoice_and_item_details_include_cost_based_profit_percentage(): void
    {
        $item = new Item(['name' => 'Tracker', 'purchase_price' => 200]);
        $line = new SalesInvoiceItem([
            'quantity' => 2,
            'unit_price' => 250,
            'line_total' => 500,
        ]);
        $line->setRelation('item', $item);

        $invoice = new SalesInvoice([
            'invoice_no' => 'INV-1',
            'grand_total' => 500,
            'subtotal' => 500,
            'discount_amount' => 0,
            'tax_amount' => 0,
        ]);
        $invoice->setRelation('items', new Collection([$line]));

        $detail = (new SalesProfitService())->invoiceDetail($invoice);

        $this->assertSame(400.0, $detail['amounts']['cost']);
        $this->assertSame(100.0, $detail['amounts']['profit']);
        $this->assertSame(25.0, $detail['amounts']['profit_percent']);
        $this->assertSame(20.0, $detail['amounts']['profit_percent_on_sale']);
        $this->assertSame(25.0, $detail['items'][0]['profit_percent']);
        $this->assertSame(20.0, $detail['items'][0]['profit_percent_on_sale']);
    }
}
