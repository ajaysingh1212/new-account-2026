<?php

namespace App\Services;

use App\Models\SalesInvoiceItem;
use App\Models\SalesReturnItem;

class SerialUnitService
{
    public function activeSoldKeys(int $companyId, ?int $excludeInvoiceId = null): array
    {
        $soldCounts = SalesInvoiceItem::whereHas('salesInvoice', function ($query) use ($companyId, $excludeInvoiceId) {
                $query->where('company_id', $companyId);
                if ($excludeInvoiceId) {
                    $query->where('id', '<>', $excludeInvoiceId);
                }
            })
            ->get()
            ->flatMap(fn($line) => collect($line->selected_units ?? [])->pluck('key'))
            ->filter()
            ->countBy();

        $returnedCounts = SalesReturnItem::whereHas(
                'salesReturn',
                fn($query) => $query->where('company_id', $companyId)
            )
            ->when(
                $excludeInvoiceId,
                fn($query) => $query->whereHas(
                    'salesReturn',
                    fn($returnQuery) => $returnQuery->where('sales_invoice_id', '<>', $excludeInvoiceId)
                )
            )
            ->get()
            ->flatMap(fn($line) => collect($line->selected_units ?? [])->pluck('key'))
            ->filter()
            ->countBy();

        return $soldCounts
            ->filter(fn($count, $key) => $count > (int) $returnedCounts->get($key, 0))
            ->keys()
            ->values()
            ->all();
    }

    public function returnedKeysForInvoiceLine(int $invoiceLineId, ?int $excludeReturnId = null): array
    {
        return SalesReturnItem::where('sales_invoice_item_id', $invoiceLineId)
            ->when($excludeReturnId, fn($query) => $query->where('sales_return_id', '<>', $excludeReturnId))
            ->get()
            ->flatMap(fn($line) => collect($line->selected_units ?? [])->pluck('key'))
            ->filter()
            ->values()
            ->all();
    }
}
