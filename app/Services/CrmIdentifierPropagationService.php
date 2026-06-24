<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Item;
use App\Models\ProductionBatch;
use App\Models\PurchaseBillItem;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesReturnItem;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CrmIdentifierPropagationService
{
    public function preview(ProductionBatch $batch, array $newUnits, ?string $newSku): array
    {
        return $this->targets($batch, $newUnits, $newSku)
            ->map(fn(array $target) => collect($target)->except('records')->all())
            ->values()
            ->all();
    }

    public function propagate(ProductionBatch $batch, array $oldUnits, array $newUnits, ?string $oldSku, ?string $newSku, array $selectedTokens): void
    {
        $selected = array_flip($selectedTokens);
        $changes = $this->unitChanges($batch, $oldUnits, $newUnits);

        foreach ($this->targetsFromChanges($batch, $changes, $oldSku, $newSku) as $token => $target) {
            if (!isset($selected[$token])) {
                continue;
            }

            foreach ($target['records'] as $record) {
                if ($record['kind'] === 'sku') {
                    $record['model']->update(['sku' => $newSku]);
                    continue;
                }

                /** @var Model $model */
                $model = $record['model'];
                $attribute = $record['attribute'];
                $updated = $this->replaceIdentifiers($model->{$attribute} ?? [], $changes);
                $model->update([$attribute => $updated]);
            }
        }
    }

    private function targets(ProductionBatch $batch, array $newUnits, ?string $newSku): Collection
    {
        return $this->targetsFromChanges(
            $batch,
            $this->unitChanges($batch, $batch->units_data ?? [], $newUnits),
            $batch->finishedItem?->sku,
            $newSku
        );
    }

    private function targetsFromChanges(ProductionBatch $batch, array $changes, ?string $oldSku, ?string $newSku): Collection
    {
        $targets = collect();
        $needles = collect($changes)->flatMap(fn($change) => array_filter([
            $change['key'],
            $change['old']['serial_no'] ?? null,
            $change['old']['vts_sim'] ?? null,
            $change['old']['sku'] ?? null,
        ]))->unique()->values()->all();

        if ($changes) {
            $this->matchingJsonRecords(ProductionBatch::class, 'units_data', $needles)
                ->where('id', '<>', $batch->id)
                ->get()->each(function (ProductionBatch $model) use ($targets, $changes) {
                    if ($this->wouldChange($model->units_data ?? [], $changes)) {
                        $this->addTarget($targets, 'production:' . $model->id, 'Production / CRM Assembly',
                            $this->companyName($model->company_id) . ' · Batch ' . $model->batch_no,
                            ['kind' => 'json', 'model' => $model, 'attribute' => 'units_data']);
                    }
                });

            $this->matchingJsonRecords(SalesInvoiceItem::class, 'selected_units', $needles)
                ->with(['salesInvoice.party','salesInvoice.company'])->get()
                ->each(function (SalesInvoiceItem $model) use ($targets, $changes) {
                    if (!$this->wouldChange($model->selected_units ?? [], $changes) || !$model->salesInvoice) return;
                    $invoice = $model->salesInvoice;
                    $merged = $invoice->inter_company_transfer ? 'Merged-company sale' : 'Customer sale';
                    $party = $invoice->party?->display_name ?: 'Cash / Walk-in';
                    $this->addTarget($targets, 'sale:' . $invoice->id, $merged,
                        ($invoice->company?->name ?: $this->companyName($invoice->company_id)) . " · {$invoice->invoice_no} · {$party}",
                        ['kind' => 'json', 'model' => $model, 'attribute' => 'selected_units']);
                });

            $this->matchingJsonRecords(PurchaseBillItem::class, 'selected_units', $needles)
                ->with(['purchaseBill.party','purchaseBill.company'])->get()
                ->each(function (PurchaseBillItem $model) use ($targets, $changes) {
                    if (!$this->wouldChange($model->selected_units ?? [], $changes) || !$model->purchaseBill) return;
                    $bill = $model->purchaseBill;
                    $type = $bill->source_sales_invoice_id ? 'Merged-company stock / purchase' : 'Purchase';
                    $this->addTarget($targets, 'purchase:' . $bill->id, $type,
                        ($bill->company?->name ?: $this->companyName($bill->company_id)) . " · {$bill->invoice_no}",
                        ['kind' => 'json', 'model' => $model, 'attribute' => 'selected_units']);
                });

            $this->matchingJsonRecords(SalesReturnItem::class, 'selected_units', $needles)
                ->with(['salesReturn.party'])->get()
                ->each(function (SalesReturnItem $model) use ($targets, $changes) {
                    if (!$this->wouldChange($model->selected_units ?? [], $changes) || !$model->salesReturn) return;
                    $return = $model->salesReturn;
                    $this->addTarget($targets, 'return:' . $return->id, 'Sales return',
                        $this->companyName($return->company_id) . " · {$return->return_no} · " . ($return->party?->display_name ?: 'Cash / Walk-in'),
                        ['kind' => 'json', 'model' => $model, 'attribute' => 'selected_units']);
                });

            $this->matchingJsonRecords(StockMovement::class, 'movement_units', $needles)
                ->get()->each(function (StockMovement $model) use ($targets, $changes, $batch) {
                    if (!$this->wouldChange($model->movement_units ?? [], $changes)) return;
                    if ($model->reference_type === ProductionBatch::class && (int) $model->reference_id === (int) $batch->id) return;
                    [$token, $type, $detail] = $this->movementTarget($model);
                    $this->addTarget($targets, $token, $type, $detail,
                        ['kind' => 'json', 'model' => $model, 'attribute' => 'movement_units']);
                });
        }

        if ($oldSku !== $newSku && $batch->finishedItem) {
            Item::where('item_code', $batch->finishedItem->item_code)
                ->whereKeyNot($batch->finished_item_id)
                ->get()->each(function (Item $item) use ($targets, $newSku) {
                    if ((string) $item->sku === (string) $newSku) return;
                    $this->addTarget($targets, 'item:' . $item->id, 'Item SKU',
                        $this->companyName($item->company_id) . ' · ' . $item->name,
                        ['kind' => 'sku', 'model' => $item]);
                });
        }

        return $targets;
    }

    private function movementTarget(StockMovement $movement): array
    {
        if ($movement->reference_type === SalesInvoice::class && $movement->reference_id) {
            return ['sale:' . $movement->reference_id, 'Sale stock movement', $this->companyName($movement->company_id) . ' · ' . $movement->reference_no];
        }
        if ($movement->reference_type === \App\Models\PurchaseBill::class && $movement->reference_id) {
            return ['purchase:' . $movement->reference_id, 'Merged-company stock movement', $this->companyName($movement->company_id) . ' · ' . $movement->reference_no];
        }

        return ['stock:' . $movement->company_id, 'Stock', $this->companyName($movement->company_id) . ' · current/history stock records'];
    }

    private function addTarget(Collection $targets, string $token, string $type, string $detail, array $record): void
    {
        $target = $targets->get($token, ['token' => $token, 'type' => $type, 'detail' => $detail, 'records' => []]);
        $target['records'][] = $record;
        $targets->put($token, $target);
    }

    private function matchingJsonRecords(string $model, string $attribute, array $needles)
    {
        return $model::query()->where(function ($query) use ($attribute, $needles) {
            foreach ($needles as $needle) {
                $query->orWhere($attribute, 'like', '%' . addcslashes((string) $needle, '%_\\') . '%');
            }
        });
    }

    private function unitChanges(ProductionBatch $batch, array $oldUnits, array $newUnits): array
    {
        return collect($oldUnits)->map(function ($old, $index) use ($newUnits, $batch) {
            $new = $newUnits[$index] ?? [];
            $fields = ['serial_no', 'vts_sim', 'sku'];
            $changed = collect($fields)->contains(fn($field) => (string) ($old[$field] ?? '') !== (string) ($new[$field] ?? ''));
            return $changed ? ['key' => $batch->id . '-' . $index, 'old' => $old, 'new' => $new] : null;
        })->filter()->values()->all();
    }

    private function wouldChange(array $units, array $changes): bool
    {
        return $this->replaceIdentifiers($units, $changes) !== array_values($units);
    }

    private function replaceIdentifiers(array $units, array $changes): array
    {
        return collect($units)->map(function ($unit) use ($changes) {
            if (!is_array($unit)) return $unit;
            foreach ($changes as $change) {
                if (!$this->unitMatches($unit, $change)) continue;
                foreach (['serial_no', 'vts_sim', 'sku'] as $field) {
                    if ((string) ($change['old'][$field] ?? '') !== (string) ($change['new'][$field] ?? '')) {
                        $unit[$field] = $change['new'][$field] ?? null;
                    }
                }
                break;
            }
            return $unit;
        })->values()->all();
    }

    private function unitMatches(array $unit, array $change): bool
    {
        if (!empty($unit['key']) && (string) $unit['key'] === (string) $change['key']) return true;
        foreach (['serial_no', 'vts_sim', 'sku'] as $field) {
            $old = trim((string) ($change['old'][$field] ?? ''));
            if ($old !== '' && trim((string) ($unit[$field] ?? '')) === $old) return true;
        }
        return false;
    }

    private function companyName(?int $companyId): string
    {
        static $companies = [];
        if (!$companyId) return 'Unknown company';
        return $companies[$companyId] ??= (Company::find($companyId)?->name ?: "Company #{$companyId}");
    }
}
