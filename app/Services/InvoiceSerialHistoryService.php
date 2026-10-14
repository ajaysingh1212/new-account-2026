<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PurchaseBill;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\StockMovement;

class InvoiceSerialHistoryService
{
    public function forInvoice(SalesInvoice $invoice): array
    {
        $invoice->loadMissing('items.item');
        $serials = app(SerialUnitService::class);
        $historyLines = $invoice->items->values();
        $originalMovements = StockMovement::with('item')->where('company_id', $invoice->company_id)
            ->where('reference_type', SalesInvoice::class)->where('reference_id', $invoice->id)
            ->where('movement_type', 'sale')->orderBy('id')->get();
        // Invoice edits can remove a line after its goods have circulated.
        // Retain those serials in history without making them returnable.
        foreach ($originalMovements->groupBy('item_id') as $itemId => $rows) {
            $currentUnits = $invoice->items->where('item_id', $itemId)->flatMap(fn($line) => $line->selected_units ?? []);
            $removedUnits = $rows->flatMap(fn($movement) => $serials->movementUnits($movement))
                ->reject(fn($unit) => $currentUnits->contains(fn($current) => $this->matches($unit, $current)))
                ->unique(fn($unit) => $serials->unitIdentity($unit, (int) $itemId))->values();
            if ($removedUnits->isNotEmpty() && $rows->first()->item) {
                $historicalLine = new SalesInvoiceItem(['item_id' => $itemId, 'selected_units' => $removedUnits->all()]);
                $historicalLine->id = -(int) $itemId;
                $historicalLine->setRelation('item', $rows->first()->item);
                $historyLines->push($historicalLine);
            }
        }
        $bills = PurchaseBill::with('items.item')->where('source_sales_invoice_id', $invoice->id)->get();
        $companyIds = $bills->pluck('company_id')->push($invoice->company_id)->unique()->values();
        $companyNames = Company::whereIn('id', $companyIds)->pluck('name', 'id');
        $items = $historyLines->pluck('item')->concat($bills->flatMap(fn($bill) => $bill->items->pluck('item')))->filter()->unique('id');
        // Include matching buyer items even if an edited auto purchase removed
        // its old line. Company scope still comes from this invoice's bills.
        $items = $items->concat(\App\Models\Item::whereIn('company_id', $companyIds)
            ->whereIn('item_code', $items->pluck('item_code'))->get())->unique('id');
        $itemCodes = $items->pluck('item_code', 'id');
        $itemIds = $items->pluck('id');
        $sales = SalesInvoiceItem::with(['salesInvoice.party', 'salesInvoice.returns.items'])
            ->whereIn('item_id', $itemIds)
            ->whereHas('salesInvoice', fn($query) => $query->whereIn('company_id', $companyIds))->get();
        $movements = StockMovement::with('party')->whereIn('company_id', $companyIds)->whereIn('item_id', $itemIds)
            ->whereIn('movement_type', ['inter_company_purchase', 'purchase_return', 'inter_company_purchase_return_in',
                'inter_company_sales_return_out', 'purchase_return_reversal', 'inter_company_purchase_return_reversal',
                'stock_transfer_out', 'stock_transfer_in'])
            ->orderBy('movement_date')->orderBy('id')->get();
        $movementUnits = $movements->mapWithKeys(fn($movement) => [$movement->id => $serials->movementUnits($movement)]);
        $stock = $companyIds->mapWithKeys(fn($id) => [$id => $serials->currentStockUnitsByItem((int) $id)]);

        return $historyLines->mapWithKeys(function ($line) use ($invoice, $companyNames, $itemCodes, $items, $sales, $movements, $movementUnits, $stock, $originalMovements, $serials) {
            $code = $line->item?->item_code;
            $history = collect($line->selected_units ?? [])->map(function ($unit) use ($line, $invoice, $code, $companyNames, $itemCodes, $items, $sales, $movements, $movementUnits, $stock, $originalMovements, $serials) {
                $events = collect();
                $activeSales = collect();
                if ($line->id < 0) {
                    $original = $originalMovements->first(fn($movement) => (int) $movement->item_id === (int) $line->item_id
                        && collect($serials->movementUnits($movement))->contains(fn($candidate) => $this->matches($unit, $candidate)));
                    if ($original) {
                        $events->push($this->event($original->movement_date, $invoice->company_id, $companyNames,
                            'Original invoice sale (line later edited)', $invoice->invoice_no, $invoice->party?->display_name));
                    }
                }
                foreach ($sales as $sale) {
                    if ($itemCodes->get($sale->item_id) !== $code || !collect($sale->selected_units ?? [])->contains(fn($candidate) => $this->matches($unit, $candidate))) {
                        continue;
                    }
                    $document = $sale->salesInvoice;
                    $returned = $document->returns->flatMap(fn($return) => $return->items)
                        ->where('sales_invoice_item_id', $sale->id)
                        ->contains(fn($row) => collect($row->selected_units ?? [])->contains(fn($candidate) => $this->matches($unit, $candidate)));
                    $event = $this->event($document->billing_date, $document->company_id, $companyNames,
                        $document->inter_company_transfer ? 'Company transfer sale' : 'Customer sale', $document->invoice_no, $document->party?->display_name);
                    $events->push($event);
                    if (!$returned && !$document->inter_company_transfer) {
                        $activeSales->push($event);
                    }
                    foreach ($document->returns as $return) {
                        if ($return->items->where('sales_invoice_item_id', $sale->id)
                            ->contains(fn($row) => collect($row->selected_units ?? [])->contains(fn($candidate) => $this->matches($unit, $candidate)))) {
                            $events->push($this->event($return->return_date, $document->company_id, $companyNames,
                                $document->inter_company_transfer ? 'Received sales return from buyer company' : 'Customer sales return', $return->return_no, $document->party?->display_name));
                        }
                    }
                }
                foreach ($movements as $movement) {
                    if ($itemCodes->get($movement->item_id) !== $code
                        || !collect($movementUnits->get($movement->id))->contains(fn($candidate) => $this->matches($unit, $candidate))) {
                        continue;
                    }
                    $action = match ($movement->movement_type) {
                        'inter_company_purchase' => 'Received from source company',
                        'purchase_return' => 'Purchase return to source company',
                        'inter_company_purchase_return_in' => 'Received purchase return from buyer company',
                        'inter_company_sales_return_out' => 'Sent to source through sales return',
                        'purchase_return_reversal' => 'Purchase return cancelled for update',
                        'inter_company_purchase_return_reversal' => 'Purchase return receipt cancelled for update',
                        'stock_transfer_out' => 'Stock transferred out',
                        'stock_transfer_in' => 'Stock transfer received',
                        default => null,
                    };
                    if ($action) {
                        $events->push($this->event($movement->movement_date, $movement->company_id, $companyNames,
                            $action, $movement->reference_no, $movement->party?->display_name));
                    }
                }
                $locations = $items->filter(fn($item) => $item->item_code === $code
                    && collect($stock->get($item->company_id)[$item->id] ?? [])->contains(fn($candidate) => $this->matches($unit, $candidate)))
                    ->map(fn($item) => ['company_id' => $item->company_id, 'company' => $companyNames->get($item->company_id)])
                    ->unique('company_id')->values();
                $latestSale = $activeSales->sortBy('date')->last();
                $status = $locations->isNotEmpty()
                    ? 'In stock: '.$locations->pluck('company')->join(', ')
                    : ($latestSale ? 'Sold by '.$latestSale['company'].' to '.($latestSale['party'] ?: 'Cash customer') : 'Not in available stock; check history');

                return [
                    'source_company_id' => $invoice->company_id,
                    'item_name' => $line->item?->name, 'historical_only' => $line->id < 0,
                    'key' => $unit['key'] ?? null, 'serial_no' => $unit['serial_no'] ?? null,
                    'vts_sim' => $unit['vts_sim'] ?? null, 'sku' => $unit['sku'] ?? null,
                    'status' => $status, 'stock_locations' => $locations->all(),
                    'active_customer_sale' => $latestSale,
                    'events' => $events->unique(fn($event) => json_encode($event))->sortBy('date')->values()->all(),
                ];
            })->values()->all();

            return [$line->id => $history];
        })->all();
    }

    private function event($date, int $companyId, $names, string $action, ?string $reference, ?string $party): array
    {
        return ['date' => $date?->format('Y-m-d'), 'company_id' => $companyId,
            'company' => $names->get($companyId), 'action' => $action, 'reference' => $reference, 'party' => $party];
    }

    private function matches(array $left, array $right): bool
    {
        if (!empty($left['key']) && !empty($right['key'])) {
            if ((string) $left['key'] === (string) $right['key']) {
                return true;
            }
            if (!str_starts_with((string) $left['key'], 'repair-') && !str_starts_with((string) $right['key'], 'repair-')) {
                return false;
            }
        }
        foreach (['serial_no', 'vts_sim'] as $field) {
            if (!empty($left[$field]) && !empty($right[$field])) {
                return strcasecmp(trim((string) $left[$field]), trim((string) $right[$field])) === 0;
            }
        }
        return false;
    }
}
