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

    public function convertForm(PendingOrder $pendingOrder, SerialUnitService $serialUnits)
    {
        $this->authorizeCompany($pendingOrder);
        abort_if($pendingOrder->status !== 'pending', 422, 'Pending order already converted.');

        $pendingOrder->load(['party', 'item', 'deliveryChallan', 'deliveryChallanItem']);
        $companyId = $pendingOrder->company_id;
        $item = $pendingOrder->item;

        return view('admin.sales.convert', [
            'sourceType' => 'pending_order',
            'sourceLabel' => 'Pending Order',
            'source' => $pendingOrder,
            'parties' => Party::where('company_id', $companyId)->where('status', 'active')->orderBy('display_name')->get(),
            'items' => $item ? collect([$item]) : collect(),
            'lineData' => [[
                'item_id' => $pendingOrder->item_id,
                'name' => $item?->name ?: 'Item',
                'code' => $item?->item_code ?: '-',
                'unit' => $pendingOrder->unit ?: $item?->unit,
                'quantity' => (float) $pendingOrder->quantity,
                'unit_price' => (float) $pendingOrder->unit_price,
                'discount_type' => $pendingOrder->discount_type,
                'discount_value' => (float) $pendingOrder->discount_value,
                'tax_percent' => (float) $pendingOrder->tax_percent,
                'description' => $pendingOrder->deliveryChallanItem?->description ?? $item?->description,
                'selected_units' => [],
                'weight' => (float) ($item?->per_quantity_weight ?? 0),
                'current_stock' => (float) ($item?->current_stock ?? 0),
            ]],
            'unitPool' => $serialUnits->unitPool($companyId),
            'itemMeta' => $item ? [
                $item->id => [
                    'requires_gps' => $serialUnits->isGpsItem($item),
                    'weight' => (float) ($item->per_quantity_weight ?? 0),
                    'current_stock' => (float) ($item->current_stock ?? 0),
                ],
            ] : [],
            'actionRoute' => route('admin.pending-orders.convert', $pendingOrder),
            'backRoute' => route('admin.pending-orders.index'),
            'saleNo' => $this->nextSaleNo($companyId),
        ]);
    }

    public function convert(Request $request, PendingOrder $pendingOrder, AccountingService $accounting, SerialUnitService $serialUnits)
    {
        $this->authorizeCompany($pendingOrder);
        abort_if($pendingOrder->status !== 'pending', 422, 'Pending order already converted.');

        $data = $request->validate([
            'party_id' => ['nullable','exists:parties,id'],
            'sale_type' => ['required','in:credit,cash'],
            'invoice_no' => ['nullable','max:20'],
            'billing_date' => ['required','date'],
            'reference_no' => ['nullable','max:255'],
            'phone' => ['nullable','max:255'],
            'billing_address' => ['nullable','string'],
            'shipping_address' => ['nullable','string'],
            'discount_amount' => ['nullable','numeric','min:0'],
            'notes' => ['nullable','string'],
            'terms' => ['nullable','string'],
            'quantity.0' => ['required','numeric','min:0.001'],
            'unit_price.0' => ['required','numeric','min:0'],
            'discount_type.0' => ['nullable','in:percent,flat'],
            'discount_value.0' => ['nullable','numeric','min:0'],
            'tax_mode.0' => ['nullable','in:with_gst,without_gst'],
            'tax_percent.0' => ['nullable','numeric','min:0'],
            'selected_units.0' => ['nullable','string'],
        ]);

        DB::transaction(function () use ($request, $pendingOrder, $data, $accounting, $serialUnits) {
            $pendingOrder = PendingOrder::whereKey($pendingOrder->id)->lockForUpdate()->firstOrFail();
            $pendingOrder->load(['item.productType', 'party', 'deliveryChallan', 'deliveryChallanItem']);
            $item = Item::whereKey($pendingOrder->item_id)->lockForUpdate()->firstOrFail();
            $qty = (float) $request->input('quantity.0');

            abort_if($item->track_stock && (int) $qty != $qty, 422, "Quantity must be a whole number for {$item->name}.");
            abort_if($item->track_stock && (float) $item->current_stock < $qty, 422, "{$item->name} stock me nahi hai. Available {$item->current_stock}, required {$qty}.");

            $unitPool = $serialUnits->unitPool($pendingOrder->company_id);
            $requestedUnits = json_decode($request->input('selected_units.0', '[]'), true) ?: [];
            $selectedUnits = $item->track_stock
                ? $serialUnits->reconcile($requestedUnits, $unitPool[$item->id] ?? [], (int) $qty, $serialUnits->isGpsItem($item))
                : [];
            abort_if($item->track_stock && count($selectedUnits) !== (int) $qty, 422, "{$item->name} ke {$qty} available serial/VTS units select karein.");

            $invoiceNo = $data['invoice_no'] ?: $this->nextSaleNo($pendingOrder->company_id);
            $invoice = SalesInvoice::create([
                'company_id' => $pendingOrder->company_id,
                'party_id' => $data['party_id'] ?: $pendingOrder->party_id,
                'sale_type' => $data['sale_type'],
                'invoice_no' => $invoiceNo,
                'billing_date' => $data['billing_date'],
                'reference_no' => $data['reference_no'] ?: $pendingOrder->deliveryChallan?->challan_no,
                'phone' => $data['phone'] ?? $pendingOrder->party?->phone,
                'billing_address' => $data['billing_address'] ?? $pendingOrder->party?->billing_address,
                'shipping_address' => $data['shipping_address'] ?? $pendingOrder->party?->shipping_address,
                'notes' => trim((string) ($data['notes'] ?? '') . "\nConverted from pending order of challan " . ($pendingOrder->deliveryChallan?->challan_no ?? '-') . '.'),
                'terms' => $data['terms'] ?? null,
                'status' => 'posted',
                'created_by' => auth()->id(),
            ]);

            $price = (float) $request->input('unit_price.0');
            $base = $qty * $price;
            $discountType = $request->input('discount_type.0', 'percent');
            $discountValue = (float) $request->input('discount_value.0', 0);
            $lineDiscount = $discountType === 'flat' ? $discountValue : $base * $discountValue / 100;
            $taxPercent = $request->input('tax_mode.0', 'with_gst') === 'with_gst' ? (float) $request->input('tax_percent.0', 18) : 0;
            $gross = max(0, $base - $lineDiscount);
            $taxAmount = $taxPercent > 0 ? $gross * $taxPercent / (100 + $taxPercent) : 0;
            $subtotal = $gross - $taxAmount;
            $overallDiscount = (float) ($data['discount_amount'] ?? 0);

            SalesInvoiceItem::create([
                'sales_invoice_id' => $invoice->id,
                'item_id' => $item->id,
                'description' => $pendingOrder->deliveryChallanItem?->description ?? $item->description,
                'quantity' => $qty,
                'unit' => $pendingOrder->unit ?: $item->unit,
                'unit_price' => $price,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'discount_amount' => $lineDiscount,
                'tax_percent' => $taxPercent,
                'tax_amount' => $taxAmount,
                'line_total' => $gross,
                'line_weight' => $qty * (float) ($item->per_quantity_weight ?? 0),
                'selected_units' => $selectedUnits,
            ]);

            $invoice->update([
                'subtotal' => $subtotal,
                'discount_amount' => $lineDiscount + $overallDiscount,
                'tax_amount' => $taxAmount,
                'grand_total' => max(0, $subtotal + $taxAmount - $overallDiscount),
                'total_weight' => $qty * (float) ($item->per_quantity_weight ?? 0),
            ]);

            $accounting->moveStock($item, [
                'party_id' => $invoice->party_id,
                'movement_date' => $invoice->billing_date,
                'movement_type' => 'pending_order_conversion',
                'direction' => 'out',
                'quantity' => $qty,
                'unit_price' => $item->purchase_price,
                'total_value' => $qty * (float) $item->purchase_price,
                'reference_type' => SalesInvoice::class,
                'reference_id' => $invoice->id,
                'reference_no' => $invoice->invoice_no,
                'description' => 'Sales stock out from pending order.',
                'movement_units' => $selectedUnits,
            ]);

            if ($invoice->party_id) {
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
                'status' => 'converted',
                'converted_sales_invoice_id' => $invoice->id,
                'converted_at' => now(),
            ]);
        });

        return redirect()->route('admin.pending-orders.index')->with('success', 'Pending order converted to sale.');
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

    private function authorizeCompany(PendingOrder $pendingOrder): void
    {
        abort_unless($pendingOrder->company_id === auth()->user()->current_company_id || auth()->user()->isSuperAdmin(), 403);
    }

    private function nextSaleNo(int $companyId): string
    {
        return str_pad((string) (SalesInvoice::where('company_id', $companyId)->withTrashed()->count() + 1), 8, '0', STR_PAD_LEFT);
    }
}
