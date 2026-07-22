<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Party;
use App\Models\ProductCategory;
use App\Models\SalesInvoice;
use App\Models\SalesTarget;
use App\Models\SalesTargetItem;
use App\Services\EntryVisibilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesTargetController extends Controller
{
    public function index(EntryVisibilityService $visibility)
    {
        $targets = $visibility->scopeForUser(SalesTarget::with(['party','items.productCategory'])->latest(), SalesTarget::class)->get();
        return view('admin.sales-targets.index', compact('targets'));
    }

    public function create(EntryVisibilityService $visibility)
    {
        return view('admin.sales-targets.create', $this->formData($visibility));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $target = SalesTarget::create(array_intersect_key($data, array_flip(['company_id','party_id','period_type','starts_on','ends_on','status','notes'])) + ['created_by' => auth()->id()]);
        $this->saveItems($target, $data);
        return redirect()->route('admin.sales-targets.index')->with('success', 'Sales target created successfully.');
    }

    public function edit(SalesTarget $salesTarget, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($salesTarget);
        $salesTarget->load('items');
        return view('admin.sales-targets.edit', $this->formData($visibility) + ['target' => $salesTarget]);
    }

    public function update(Request $request, SalesTarget $salesTarget, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($salesTarget);
        $data = $this->validateData($request);
        $salesTarget->update(array_intersect_key($data, array_flip(['company_id','party_id','period_type','starts_on','ends_on','status','notes'])));
        $salesTarget->items()->delete();
        $this->saveItems($salesTarget, $data);
        return redirect()->route('admin.sales-targets.index')->with('success', 'Sales target updated successfully.');
    }

    public function destroy(SalesTarget $salesTarget, EntryVisibilityService $visibility)
    {
        $visibility->authorizeView($salesTarget);
        $salesTarget->delete();
        return back()->with('success', 'Sales target deleted successfully.');
    }

    public function report(Request $request, EntryVisibilityService $visibility)
    {
        [$from, $to, $quickPeriod] = $this->reportDates($request);
        $partyId = $request->integer('party_id') ?: null;
        $categoryId = $request->integer('product_category_id') ?: null;
        $parties = $visibility->scopeForUser(Party::orderBy('display_name'), Party::class)->get();
        $categories = ProductCategory::where('company_id', auth()->user()->current_company_id)->orderBy('name')->get();

        $targets = $visibility->scopeForUser(SalesTarget::with(['party','items.productCategory'])
            ->where('starts_on', '<=', $to->toDateString())->where('ends_on', '>=', $from->toDateString()), SalesTarget::class)
            ->when($partyId, fn ($q) => $q->where('party_id', $partyId))->get();
        $sales = $visibility->scopeForUser(SalesInvoice::with(['party','items.item.productCategory'])
            ->whereBetween('billing_date', [$from->toDateString(), $to->toDateString()]), SalesInvoice::class)
            ->when($partyId, fn ($q) => $q->where('party_id', $partyId))->get();

        $actuals = [];
        $partyTotals = [];
        foreach ($sales as $invoice) {
            foreach ($invoice->items as $line) {
                $catId = $line->item?->product_category_id;
                $partyTotals[$invoice->party_id] = ($partyTotals[$invoice->party_id] ?? 0) + (float) $line->line_total;
                if (!$catId || ($categoryId && $catId !== $categoryId)) continue;
                $key = $invoice->party_id.'-'.$catId;
                $actuals[$key]['amount'] = ($actuals[$key]['amount'] ?? 0) + (float) $line->line_total;
                $actuals[$key]['quantity'] = ($actuals[$key]['quantity'] ?? 0) + (float) $line->quantity;
            }
        }

        $rows = $targets->flatMap(function (SalesTarget $target) use ($actuals, $partyTotals, $categoryId) {
            return $target->items->filter(fn ($item) => !$categoryId || $item->product_category_id === $categoryId)->map(function (SalesTargetItem $item) use ($target, $actuals, $partyTotals) {
                $key = $target->party_id.'-'.$item->product_category_id;
                $amount = (float) ($actuals[$key]['amount'] ?? 0);
                $quantity = (float) ($actuals[$key]['quantity'] ?? 0);
                $actual = $item->target_type === 'amount' ? $amount : ($item->target_type === 'quantity' ? $quantity : (($partyTotals[$target->party_id] ?? 0) > 0 ? ($amount / $partyTotals[$target->party_id]) * 100 : 0));
                $targetValue = (float) $item->target_value;
                return ['party' => $target->party?->display_name ?? 'Cash / Walk-in', 'party_id' => $target->party_id, 'category' => $item->productCategory?->name ?? '-', 'category_id' => $item->product_category_id, 'period' => ucfirst(str_replace('_', ' ', $target->period_type)), 'target_type' => $item->target_type, 'target' => $targetValue, 'actual' => $actual, 'actual_amount' => $amount, 'actual_quantity' => $quantity, 'achievement' => $targetValue > 0 ? ($actual / $targetValue) * 100 : 0, 'starts_on' => $target->starts_on->format('d M Y'), 'ends_on' => $target->ends_on->format('d M Y')];
            });
        })->values();
        $summary = ['target' => $rows->sum('target'), 'actual' => $rows->sum('actual'), 'amount' => $rows->sum('actual_amount'), 'quantity' => $rows->sum('actual_quantity')];
        $charts = ['labels' => $rows->pluck('category')->values(), 'target' => $rows->pluck('target')->values(), 'actual' => $rows->pluck('actual')->values(), 'achievement' => $rows->pluck('achievement')->values()];
        $filters = ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'party_id' => $partyId, 'product_category_id' => $categoryId, 'quick_period' => $quickPeriod];
        return view('admin.sales-targets.report', compact('rows','summary','charts','filters','parties','categories'));
    }

    public function export(Request $request, EntryVisibilityService $visibility): StreamedResponse
    {
        $response = $this->report($request, $visibility);
        $rows = $response->getData()['rows'];
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Party','Product Category','Period','Target Type','Target','Actual','Achievement %','Actual Amount','Actual Quantity','Target Dates']);
            foreach ($rows as $row) fputcsv($out, [$row['party'],$row['category'],$row['period'],$row['target_type'],$row['target'],$row['actual'],round($row['achievement'],2),$row['actual_amount'],$row['actual_quantity'],$row['starts_on'].' - '.$row['ends_on']]);
            fclose($out);
        }, 'sales-target-report.csv', ['Content-Type' => 'text/csv']);
    }

    public function print(Request $request, EntryVisibilityService $visibility)
    {
        $data = (array) $this->report($request, $visibility)->getData();
        return view('admin.sales-targets.print', $data);
    }

    private function formData(EntryVisibilityService $visibility): array
    {
        return ['parties' => $visibility->scopeForUser(Party::orderBy('display_name'), Party::class)->get(), 'categories' => ProductCategory::where('company_id', auth()->user()->current_company_id)->orderBy('name')->get()];
    }

    private function validateData(Request $request): array
    {
        return $request->validate(['party_id' => ['required','exists:parties,id'], 'period_type' => ['required','in:daily,weekly,monthly,quarterly_3,quarterly_6,yearly'], 'starts_on' => ['required','date'], 'ends_on' => ['required','date','after_or_equal:starts_on'], 'status' => ['required','in:active,inactive'], 'notes' => ['nullable','string'], 'product_category_ids' => ['required','array','min:1'], 'product_category_ids.*' => ['required','distinct','exists:product_categories,id'], 'target_types' => ['required','array'], 'target_types.*' => ['required','in:amount,quantity,percent'], 'target_values' => ['required','array'], 'target_values.*' => ['required','numeric','min:0'], 'item_notes' => ['nullable','array']]) + ['company_id' => auth()->user()->current_company_id];
    }

    private function saveItems(SalesTarget $target, array $data): void
    {
        foreach ($data['product_category_ids'] as $index => $category) $target->items()->create(['product_category_id' => $category, 'target_type' => $data['target_types'][$index] ?? 'percent', 'target_value' => $data['target_values'][$index] ?? 0, 'notes' => $data['item_notes'][$index] ?? null]);
    }

    private function reportDates(Request $request): array
    {
        $quick = $request->input('quick_period');
        $today = now()->startOfDay();
        if ($quick === 'last_month') $from = $today->copy()->subMonth()->startOfMonth();
        elseif ($quick === 'this_month') $from = $today->copy()->startOfMonth();
        elseif ($quick === 'last_3_months') $from = $today->copy()->subMonths(2)->startOfMonth();
        elseif ($quick === 'last_6_months') $from = $today->copy()->subMonths(5)->startOfMonth();
        elseif ($quick === 'last_9_months') $from = $today->copy()->subMonths(8)->startOfMonth();
        elseif ($quick === 'this_year') $from = $today->copy()->startOfYear();
        else $from = Carbon::parse($request->input('from_date', $today->copy()->startOfMonth()->toDateString()))->startOfDay();
        $to = in_array($quick, ['last_month','this_month','last_3_months','last_6_months','last_9_months','this_year'], true) ? ($quick === 'last_month' ? $today->copy()->subMonth()->endOfMonth() : $today->copy()->endOfDay()) : Carbon::parse($request->input('to_date', $today->toDateString()))->endOfDay();
        return [$from, $to, $quick ?: 'custom'];
    }
}
