<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Party;
use App\Models\PendingOrder;
use App\Models\ProductCategory;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Services\AccountingService;
use App\Services\SerialUnitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PendingOrderController extends Controller
{
    public function index(Request $request)
    {
        $companyId = auth()->user()->current_company_id;
        $from = $request->input('from_date', now()->startOfMonth()->toDateString());
        $to = $request->input('to_date', now()->toDateString());

        $orders = PendingOrder::with(['party','item.productCategory','item.productType','deliveryChallan','convertedInvoice'])
            ->where('company_id', $companyId)
            ->whereBetween('pending_date', [$from, $to])
            ->when($request->filled('month'), fn($q) => $q->whereMonth('pending_date', substr($request->month, 5, 2))->whereYear('pending_date', substr($request->month, 0, 4)))
            ->when($request->filled('party_id'), fn($q) => $q->where('party_id', $request->party_id))
            ->when($request->filled('product_category_id'), fn($q) => $q->whereHas('item', fn($item) => $item->where('product_category_id', $request->product_category_id)))
            ->latest('pending_date')
            ->get();

        $summary = [
            'sales' => (float) $orders->sum('line_total'),
            'cost' => (float) $orders->sum('cost_amount'),
            'profit' => (float) $orders->sum('profit_amount'),
            'profit_percent' => (float) ($orders->sum('cost_amount') > 0 ? round($orders->sum('profit_amount') / $orders->sum('cost_amount') * 100, 2) : 0),
        ];

        $stockByItem = Item::where('company_id', $companyId)->whereIn('id', $orders->pluck('item_id')->unique())->pluck('current_stock', 'id');
        $partyWise = $orders->groupBy(fn($o) => $o->party?->display_name ?: 'Walk-in / No Party');
        $categoryWise = $orders->groupBy(fn($o) => $o->item?->productCategory?->name ?: 'Uncategorized')
            ->map(fn($rows, $name) => ['name' => $name, 'qty' => (float) $rows->sum('quantity'), 'sales' => (float) $rows->sum('line_total')])
            ->values();

        $categorySegments = $this->buildSegments($orders, fn(PendingOrder $order) => $order->item?->productCategory?->name ?: 'Uncategorized');
        $partySegments = $this->buildSegments($orders, fn(PendingOrder $order) => $order->party?->display_name ?: 'Walk-in / No Party');

        return view('admin.pending-orders.index', [
            'orders' => $orders,
            'summary' => $summary,
            'stockByItem' => $stockByItem,
            'partyWise' => $partyWise,
            'categoryWise' => $categoryWise,
            'categorySegments' => $categorySegments,
            'partySegments' => $partySegments,
            'from' => $from,
            'to' => $to,
            'parties' => Party::where('company_id', $companyId)->orderBy('display_name')->get(),
            'categories' => ProductCategory::where('company_id', $companyId)->orderBy('name')->get(),
            'filters' => $request->all() + ['from_date' => $from, 'to_date' => $to],
        ]);
    }

    public function convert(Request $request, PendingOrder $pendingOrder, AccountingService $accounting, SerialUnitService $serialUnits)
    {
        abort_unless($pendingOrder->company_id === auth()->user()->current_company_id || auth()->user()->isSuperAdmin(), 403);
        if ($pendingOrder->converted_sales_invoice_id) {
            return redirect()->route('admin.sales.show', $pendingOrder->converted_sales_invoice_id)
                ->with('info', 'Pending order already converted to this sale.');
        }

        $data = $request->validate([
            'party_id' => ['nullable','exists:parties,id'],
            'billing_date' => ['required','date'],
            'invoice_no' => ['nullable','max:20'],
            'unit_price' => ['required','numeric','min:0'],
            'discount_value' => ['nullable','numeric','min:0'],
            'tax_percent' => ['nullable','numeric','min:0'],
        ]);

        $invoice = DB::transaction(function () use ($pendingOrder, $data, $accounting, $serialUnits) {
            $pendingOrder->refresh()->load(['item.productType', 'party', 'deliveryChallan']);
            abort_if($pendingOrder->converted_sales_invoice_id, 422, 'Pending order already converted.');
            $item = Item::with('productType')->lockForUpdate()->findOrFail($pendingOrder->item_id);
            abort_if($item->track_stock && (float) $item->current_stock < (float) $pendingOrder->quantity, 422, "{$item->name} stock available nahi hai.");

            $unitPool = $serialUnits->unitPool($pendingOrder->company_id);
            $selectedUnits = $item->track_stock
                ? $serialUnits->reconcile([], $unitPool[$item->id] ?? [], (int) $pendingOrder->quantity, $serialUnits->isGpsItem($item))
                : [];
            abort_if($item->track_stock && count($selectedUnits) < (int) $pendingOrder->quantity, 422, "{$item->name} ke required serial stock mein available nahi hain.");

            $qty = (float) $pendingOrder->quantity;
            $base = $qty * (float) $data['unit_price'];
            $discount = (($pendingOrder->discount_type ?? 'percent') === 'flat') ? (float) ($data['discount_value'] ?? 0) : $base * (float) ($data['discount_value'] ?? 0) / 100;
            $gross = max(0, $base - $discount);
            $taxPercent = (float) ($data['tax_percent'] ?? $pendingOrder->tax_percent);
            $taxAmount = $taxPercent > 0 ? $gross * $taxPercent / (100 + $taxPercent) : 0;
            $subtotal = $gross - $taxAmount;

            $invoice = SalesInvoice::create([
                'company_id' => $pendingOrder->company_id,
                'party_id' => $data['party_id'] ?: $pendingOrder->party_id,
                'sale_type' => 'credit',
                'invoice_no' => $data['invoice_no'] ?: $this->nextSaleNo($pendingOrder->company_id),
                'billing_date' => $data['billing_date'],
                'reference_no' => $pendingOrder->deliveryChallan?->challan_no,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $taxAmount,
                'grand_total' => $gross,
                'notes' => 'Converted from pending order.',
                'status' => 'posted',
                'created_by' => auth()->id(),
                'source_pending_order_id' => $pendingOrder->id,
            ]);

            SalesInvoiceItem::create([
                'sales_invoice_id' => $invoice->id,
                'item_id' => $item->id,
                'description' => $item->description,
                'quantity' => $qty,
                'unit' => $pendingOrder->unit ?: $item->unit,
                'unit_price' => $data['unit_price'],
                'discount_type' => $pendingOrder->discount_type,
                'discount_value' => $data['discount_value'] ?? $pendingOrder->discount_value,
                'discount_amount' => $discount,
                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,
                'line_total' => $gross,
                'selected_units' => $selectedUnits,
            ]);

            $accounting->moveStock($item, [
                'party_id' => $invoice->party_id,
                'movement_date' => $invoice->billing_date,
                'movement_type' => 'pending_order_sale',
                'direction' => 'out',
                'quantity' => $qty,
                'unit_price' => $item->purchase_price,
                'total_value' => $qty * (float) $item->purchase_price,
                'reference_type' => SalesInvoice::class,
                'reference_id' => $invoice->id,
                'reference_no' => $invoice->invoice_no,
                'description' => 'Stock out from pending order conversion.',
                'movement_units' => $selectedUnits,
            ]);

            if ($invoice->party) {
                $accounting->postPartyLedger($invoice->party, [
                    'entry_date' => $invoice->billing_date,
                    'entry_type' => 'sale',
                    'reference_type' => SalesInvoice::class,
                    'reference_id' => $invoice->id,
                    'reference_no' => $invoice->invoice_no,
                    'debit' => $invoice->grand_total,
                    'credit' => 0,
                    'description' => 'Sales invoice converted from pending order.',
                ]);
            }

            $pendingOrder->update([
                'party_id' => $invoice->party_id,
                'unit_price' => $data['unit_price'],
                'discount_value' => $data['discount_value'] ?? $pendingOrder->discount_value,
                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,
                'line_total' => $gross,
                'status' => 'converted',
                'converted_sales_invoice_id' => $invoice->id,
                'converted_at' => now(),
            ]);

            return $invoice;
        });

        return redirect()->route('admin.sales.show', $invoice)->with('success', 'Pending order converted to sale.');
    }

    /**
     * Group pending orders into the {label,icon,color,qty,amount,percent,items[]} shape
     * the shared segment-viz chart partial (used on the dashboard) expects.
     */
    private function buildSegments($orders, \Closure $groupKey)
    {
        $palette = ['#2563eb','#14b8a6','#f59e0b','#ec4899','#7c3aed','#22c55e','#ef4444','#0f766e'];
        $iconMap = [
            'gps' => 'fa-map-marker-alt',
            'android' => 'fa-mobile-alt',
            'led' => 'fa-lightbulb',
            'horn' => 'fa-bullhorn',
            'speaker' => 'fa-volume-up',
        ];

        $groups = $orders->groupBy($groupKey);
        $segments = $groups->values()->map(function ($rows, $index) use ($groups, $palette, $iconMap) {
            $label = $groups->keys()[$index];
            $lower = strtolower($label);
            $icon = collect($iconMap)->first(fn($class, $needle) => str_contains($lower, $needle)) ?: 'fa-boxes';

            return [
                'label' => $label,
                'icon' => $icon,
                'color' => $palette[$index % count($palette)],
                'qty' => (float) $rows->sum('quantity'),
                'amount' => (float) $rows->sum('line_total'),
                'percent' => 0.0,
                'items' => $rows->map(fn(PendingOrder $order) => [
                    'name' => $order->item?->name ?: 'Item',
                    'invoice' => $order->deliveryChallan?->challan_no,
                    'date' => $order->pending_date?->format('d M Y'),
                    'party' => $order->party?->display_name ?: 'Walk-in / No Party',
                    'state' => $order->party?->state ?: 'Unknown',
                    'district' => $order->party?->district ?: 'Unknown',
                    'city' => $order->party?->city ?: 'Unknown',
                    'product_type' => $order->item?->productType?->name ?: '-',
                    'category' => $order->item?->productCategory?->name ?: 'Uncategorized',
                    'qty' => (float) $order->quantity,
                    'amount' => (float) $order->line_total,
                ])->values(),
            ];
        });

        $total = max(0.01, abs((float) $segments->sum('amount')));

        return $segments->map(function ($segment) use ($total) {
            $segment['percent'] = round((abs((float) $segment['amount']) / $total) * 100, 2);
            return $segment;
        })->sortByDesc('amount')->values();
    }

    private function nextSaleNo(int $companyId): string
    {
        return str_pad((string) (SalesInvoice::where('company_id', $companyId)->withTrashed()->count() + 1), 8, '0', STR_PAD_LEFT);
    }
}
