<?php

use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$company = (object) [
    'name' => 'Northstar Mobility Private Limited',
    'address' => '42 Industrial Estate, Jaipur, Rajasthan 302001',
    'gst_number' => '08ABCDE1234F1Z5',
];
$invoice = (object) ['invoice_no' => 'PI-2026-0042'];
$detail = [
    'invoice' => 'PI-2026-0042',
    'date' => '02 Jul 2026',
    'sale_type' => 'Credit',
    'reference' => 'PO-7812',
    'phone' => '+91 98765 43210',
    'billing_address' => 'Vaishali Nagar, Jaipur, Rajasthan',
    'shipping_address' => 'Sitapura Industrial Area, Jaipur, Rajasthan',
    'party' => [
        'name' => 'Apex Logistics', 'legal_name' => 'Apex Logistics LLP',
        'phone' => '+91 90000 12345', 'email' => 'accounts@apex.example',
        'gstin' => '08AAEFA1234A1Z2', 'city' => 'Jaipur, Rajasthan, 302022',
    ],
    'amounts' => [
        'total' => 177000, 'cost' => 120000, 'profit' => 57000,
        'profit_percent' => 47.5, 'tax' => 27000,
    ],
    'items' => collect([
        [
            'name' => 'GPS Fleet Tracker Pro', 'description' => '4G vehicle tracking device',
            'hsn' => '85269190', 'qty' => 100, 'unit' => 'Nos', 'amount' => 150000,
            'cost' => 100000, 'profit' => 50000, 'profit_percent' => 50,
            'bom' => collect([['name' => 'Tracker PCB', 'qty_per_unit' => 1, 'unit' => 'Nos', 'purchase_price' => 620]]),
            'units' => collect([['serial_no' => 'GPS-260701', 'vts_sim' => '899100001234', 'batch_no' => 'B-0726', 'buyer_code' => 'APX-01']]),
        ],
        [
            'name' => 'Wiring Harness', 'description' => 'Heavy duty installation harness',
            'hsn' => '85444299', 'qty' => 100, 'unit' => 'Nos', 'amount' => 27000,
            'cost' => 20000, 'profit' => 7000, 'profit_percent' => 35,
            'bom' => collect(), 'units' => collect(),
        ],
    ]),
];

file_put_contents(__DIR__.'/profit-invoice.html', view('admin.sales.detail-pdf', compact('company', 'invoice', 'detail'))->render());
