<?php

namespace App\Services;

use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;

class SalesProfitService
{
    public function lineCost(SalesInvoiceItem $line): float
    {
        $qty = (float) $line->quantity;
        $itemPurchaseCost = (float) ($line->item?->purchase_price ?? 0);

        if ($itemPurchaseCost > 0) {
            return round($qty * $itemPurchaseCost, 2);
        }

        $unitCosts = collect($line->selected_units ?? [])
            ->map(fn($unit) => (float) ($unit['cost_per_unit'] ?? 0))
            ->filter(fn($cost) => $cost > 0);

        if ($unitCosts->isEmpty()) {
            return 0.0;
        }

        $knownCost = (float) $unitCosts->sum();
        $missingQty = max(0, $qty - $unitCosts->count());

        return round($knownCost + ($missingQty * (float) $unitCosts->avg()), 2);
    }

    public function invoiceCost(SalesInvoice $invoice): float
    {
        return round((float) $invoice->items->sum(fn(SalesInvoiceItem $line) => $this->lineCost($line)), 2);
    }

    public function invoiceSale(SalesInvoice $invoice): float
    {
        return (float) $invoice->grand_total;
    }

    public function invoiceProfit(SalesInvoice $invoice): float
    {
        return round($this->invoiceSale($invoice) - $this->invoiceCost($invoice), 2);
    }

    public function invoiceDetail(SalesInvoice $invoice): array
    {
        $cost = $this->invoiceCost($invoice);
        $sale = $this->invoiceSale($invoice);

        return [
            'invoice' => $invoice->invoice_no,
            'date' => $invoice->billing_date?->format('d M Y'),
            'sale_type' => ucfirst((string) $invoice->sale_type),
            'reference' => $invoice->reference_no ?: '-',
            'phone' => $invoice->phone ?: ($invoice->party?->phone ?: '-'),
            'billing_address' => $invoice->billing_address ?: ($invoice->party?->billing_address ?: '-'),
            'shipping_address' => $invoice->shipping_address ?: ($invoice->party?->shipping_address ?: '-'),
            'party' => [
                'name' => $invoice->party?->display_name ?: 'Cash / Walk-in',
                'legal_name' => $invoice->party?->legal_name ?: '-',
                'phone' => $invoice->party?->phone ?: '-',
                'email' => $invoice->party?->email ?: '-',
                'gstin' => $invoice->party?->gstin ?: '-',
                'city' => trim(collect([$invoice->party?->city, $invoice->party?->state, $invoice->party?->pincode])->filter()->implode(', ')) ?: '-',
            ],
            'amounts' => [
                'subtotal' => (float) $invoice->subtotal,
                'discount' => (float) $invoice->discount_amount,
                'tax' => (float) $invoice->tax_amount,
                'total' => $sale,
                'cost' => $cost,
                'profit' => $sale - $cost,
            ],
            'items' => $invoice->items->map(function (SalesInvoiceItem $line) {
                $units = collect($line->selected_units ?? [])->values();

                return [
                    'name' => $line->item?->name ?: 'Item',
                    'description' => $line->description ?: '-',
                    'hsn' => $line->item?->hsn_code ?: '-',
                    'qty' => (float) $line->quantity,
                    'unit' => $line->unit ?: $line->item?->unit,
                    'rate' => (float) $line->unit_price,
                    'purchase_cost' => (float) ($line->item?->purchase_price ?? 0),
                    'tax' => (float) $line->tax_amount,
                    'amount' => (float) $line->line_total,
                    'cost' => $this->lineCost($line),
                    'profit' => (float) $line->line_total - $this->lineCost($line),
                    'bom' => $line->item?->bomMaterials?->map(fn($bom) => [
                        'name' => $bom->rawItem?->name ?: 'Raw material',
                        'qty_per_unit' => (float) $bom->qty_per_unit,
                        'unit' => $bom->rawItem?->unit,
                        'purchase_price' => (float) ($bom->rawItem?->purchase_price ?? 0),
                    ])->values() ?? collect(),
                    'units' => $units->map(fn($unit) => [
                        'key' => $unit['key'] ?? '-',
                        'serial_no' => $unit['serial_no'] ?? '-',
                        'vts_sim' => $unit['vts_sim'] ?? '-',
                        'buyer_code' => $unit['buyer_code'] ?? '-',
                        'batch_no' => $unit['batch_no'] ?? '-',
                        'production_batch_no' => $unit['production_batch_no'] ?? '-',
                        'production_date' => $unit['production_date'] ?? '-',
                        'cost_per_unit' => (float) ($unit['cost_per_unit'] ?? 0),
                        'sale_price' => (float) ($unit['sale_price'] ?? 0),
                        'gst' => (float) ($unit['gst'] ?? 0),
                        'warehouse' => $unit['warehouse'] ?? '-',
                    ])->values(),
                ];
            })->values(),
        ];
    }
}
