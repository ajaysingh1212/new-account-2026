<?php

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Party;
use App\Models\PendingOrder;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class PendingOrderController extends Controller
{
    public function index(Request $request)
    {
        $companyId = auth()->user()->current_company_id;
        $from = $request->input('from_date', now()->startOfMonth()->toDateString());
        $to = $request->input('to_date', now()->toDateString());

        $orders = PendingOrder::with(['party','item.productCategory','item.productType','deliveryChallan'])
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
}
