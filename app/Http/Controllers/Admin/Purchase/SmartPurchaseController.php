<?php

namespace App\Http\Controllers\Admin\Purchase;

use App\Http\Controllers\Controller;
use App\Models\CostCenter;
use App\Models\Item;
use App\Models\Party;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\SalesInvoice;
use App\Models\SubCostCenter;
use App\Services\AccountingService;
use App\Services\EntryVisibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SmartPurchaseController extends Controller
{
    public function index(Request $request, EntryVisibilityService $visibility)
    {
        $period = $this->period($request);
        $analysis = $this->analysisRows($period['from'], $period['to'], $visibility);
        $companyId = auth()->user()->current_company_id;

        return view('admin.smart-purchases.index', [
            'analysis' => $analysis,
            'period' => $period,
            'parties' => Party::where('company_id', $companyId)->where('status', 'active')->orderBy('display_name')->get(),
            'costCenters' => CostCenter::where('company_id', $companyId)->where('status', 'active')->get(),
            'subCostCenters' => SubCostCenter::where('company_id', $companyId)->where('status', 'active')->get(),
            'invoiceNo' => $this->nextNo(),
        ]);
    }

    public function store(Request $request, AccountingService $accounting, EntryVisibilityService $visibility)
    {
        $data = $request->validate([
            'party_id' => ['nullable', 'exists:parties,id'],
            'purchase_type' => ['required', 'in:credit,cash'],
            'invoice_no' => ['nullable', 'max:20'],
            'supplier_bill_no' => ['nullable', 'max:255'],
            'billing_date' => ['required', 'date'],
            'purchase_bill_date' => ['nullable', 'date'],
            'reference_no' => ['nullable', 'max:255'],
            'cost_center_id' => ['nullable', 'exists:cost_centers,id'],
            'sub_cost_center_id' => ['nullable', 'exists:sub_cost_centers,id'],
            'notes' => ['nullable', 'string'],
            'item_id' => ['required', 'array'],
            'item_id.*' => ['required', 'exists:items,id'],
            'quantity.*' => ['required', 'numeric', 'min:0.001'],
            'unit_price.*' => ['required', 'numeric', 'min:0'],
            'tax_percent.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $companyId = auth()->user()->current_company_id;

        DB::transaction(function () use ($request, $data, $companyId, $accounting, $visibility) {
            $bill = PurchaseBill::create([
                'company_id' => $companyId,
                'party_id' => $data['party_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'sub_cost_center_id' => $data['sub_cost_center_id'] ?? null,
                'purchase_type' => $data['purchase_type'],
                'invoice_no' => $data['invoice_no'] ?: $this->nextNo(),
                'supplier_bill_no' => $data['supplier_bill_no'] ?? null,
                'billing_date' => $data['billing_date'],
                'purchase_bill_date' => $data['purchase_bill_date'] ?? null,
                'reference_no' => $data['reference_no'] ?? null,
                'notes' => trim('Smart Purchase. ' . ($data['notes'] ?? '')),
                'created_by' => auth()->id(),
            ]);

            $subtotal = $tax = 0;
            foreach ($request->item_id as $i => $itemId) {
                $item = Item::where('company_id', $companyId)->findOrFail($itemId);
                $qty = (float) $request->quantity[$i];
                $price = (float) $request->unit_price[$i];
                $taxPercent = (float) ($request->tax_percent[$i] ?? 0);
                $base = $qty * $price;
                $taxAmount = $base * $taxPercent / 100;
                $lineTotal = $base + $taxAmount;

                $line = PurchaseBillItem::create([
                    'purchase_bill_id' => $bill->id,
                    'item_id' => $item->id,
                    'description' => 'Smart Purchase raw material',
                    'quantity' => $qty,
                    'unit' => $item->unit,
                    'unit_price' => $price,
                    'discount_type' => 'percent',
                    'discount_value' => 0,
                    'discount_amount' => 0,
                    'tax_percent' => $taxPercent,
                    'tax_amount' => $taxAmount,
                    'line_total' => $lineTotal,
                    'selected_units' => [],
                ]);

                $accounting->moveStock($item, [
                    'party_id' => $bill->party_id,
                    'movement_date' => $bill->billing_date,
                    'movement_type' => 'smart_purchase',
                    'direction' => 'in',
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'total_value' => $lineTotal,
                    'reference_type' => PurchaseBill::class,
                    'reference_id' => $bill->id,
                    'reference_no' => $bill->invoice_no,
                    'description' => 'Smart Purchase stock in: ' . $line->description,
                ]);

                $subtotal += $base;
                $tax += $taxAmount;
            }

            $bill->update([
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'tax_amount' => $tax,
                'grand_total' => $subtotal + $tax,
            ]);

            if ($bill->purchase_type === 'credit' && $bill->party_id) {
                $accounting->postPartyLedger($bill->party, [
                    'entry_date' => $bill->billing_date,
                    'entry_type' => 'smart_purchase',
                    'reference_type' => PurchaseBill::class,
                    'reference_id' => $bill->id,
                    'reference_no' => $bill->invoice_no,
                    'credit' => $bill->grand_total,
                    'debit' => 0,
                    'description' => 'Smart Purchase payable.',
                ]);
            }

            $visibility->syncFromRequest($request, $bill);
        });

        return redirect()->route('admin.purchases.index')->with('success', 'Smart Purchase posted with stock and payable ledger.');
    }

    private function analysisRows(Carbon $from, Carbon $to, EntryVisibilityService $visibility): array
    {
        $invoices = $visibility->scopeForUser(
            SalesInvoice::with(['items.item.bomMaterials.rawItem'])->whereBetween('billing_date', [$from->toDateString(), $to->toDateString()]),
            SalesInvoice::class
        )->get();

        $rawRows = collect();
        $invoiceTotal = 0;

        foreach ($invoices as $invoice) {
            foreach ($invoice->items as $line) {
                $bom = $line->item?->bomMaterials ?? collect();
                if ($bom->isEmpty()) {
                    continue;
                }

                $invoiceTotal += (float) $line->line_total;
                foreach ($bom as $row) {
                    if (!$row->rawItem || ($row->line_type ?? 'raw_material') === 'service' || $row->rawItem->item_type === 'service') {
                        continue;
                    }

                    $need = (float) $line->quantity * (float) $row->qty_per_unit;
                    $rawRows->push([
                        'item' => $row->rawItem,
                        'required_qty' => $need,
                        'valuation' => $need * (float) $row->rawItem->purchase_price,
                        'finished' => $line->item?->name,
                        'sold_qty' => (float) $line->quantity,
                    ]);
                }
            }
        }

        $materials = $rawRows->groupBy(fn($row) => $row['item']->id)->map(function ($rows) {
            $item = $rows->first()['item'];
            return [
                'item' => $item,
                'required_qty' => $rows->sum('required_qty'),
                'valuation' => $rows->sum('valuation'),
                'sources' => $rows->pluck('finished')->unique()->join(', '),
            ];
        })->values();

        return [
            'materials' => $materials,
            'invoice_total' => $invoiceTotal,
            'raw_total' => $materials->sum('valuation'),
            'difference' => $invoiceTotal - $materials->sum('valuation'),
        ];
    }

    private function period(Request $request): array
    {
        $preset = $request->input('preset', '30');
        $to = $request->filled('to_date') ? Carbon::parse($request->to_date) : today();
        $from = match ($preset) {
            '60' => $to->copy()->subDays(59),
            '90' => $to->copy()->subDays(89),
            'all' => Carbon::create(2000, 1, 1),
            'custom' => $request->filled('from_date') ? Carbon::parse($request->from_date) : $to->copy()->subDays(29),
            default => $to->copy()->subDays(29),
        };

        return ['preset' => $preset, 'from' => $from, 'to' => $to];
    }

    private function nextNo(): string
    {
        return str_pad((string) (PurchaseBill::where('company_id', auth()->user()->current_company_id)->withTrashed()->count() + 1), 8, '0', STR_PAD_LEFT);
    }
}
