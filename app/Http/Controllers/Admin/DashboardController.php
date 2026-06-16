<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\DeliveryChallan;
use App\Models\Estimate;
use App\Models\Expense;
use App\Models\Item;
use App\Models\Party;
use App\Models\PartyPaymentAllocation;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\Role;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\EntryVisibilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, EntryVisibilityService $visibility)
    {
        $user = auth()->user();
        $companyId = $user->isSuperAdmin() ? $request->integer('company_id') : $user->current_company_id;
        [$period, $from, $to] = $this->dateRange($request);
        $companiesFilter = $user->isSuperAdmin() ? Company::orderBy('name')->get() : collect();

        if ($user->isSuperAdmin()) {
            $stats = [
                'companies'   => Company::count(),
                'users'       => User::count(),
                'roles'       => Role::count(),
                'admins'      => User::where('user_type', 'admin')->count(),
                'active_companies' => Company::where('is_active', true)->count(),
                'sales' => $this->scope(SalesInvoice::query(), $companyId)->whereBetween('billing_date', [$from, $to])->sum('grand_total'),
                'purchases' => $this->scope(PurchaseBill::query(), $companyId)->whereBetween('billing_date', [$from, $to])->sum('grand_total'),
                'estimates' => $this->scope(Estimate::query(), $companyId)->whereBetween('estimate_date', [$from, $to])->count(),
                'challans' => $this->scope(DeliveryChallan::query(), $companyId)->whereBetween('challan_date', [$from, $to])->count(),
                'pending_expenses' => $this->scope(Expense::query(), $companyId)->where('status', 'pending_approval')->count(),
            ];
            $recentLogs = AuditLog::with('user','company')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->whereBetween('created_at', [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()])
                ->latest('created_at')
                ->paginate(5, ['*'], 'activity_page');
            $companies = Company::withCount(['users','roles'])->latest()->take(6)->get();
        } else {
            $partyQuery = $visibility->scopeForUser(Party::query(), Party::class);
            $bankQuery = $visibility->scopeForUser(BankAccount::query(), BankAccount::class);
            $itemQuery = $visibility->scopeForUser(Item::query(), Item::class);
            $stats = [
                'users'  => User::whereHas('userRoles', fn($q) => $q->where('company_id', $companyId))->count(),
                'roles'  => Role::where('company_id', $companyId)->count(),
                'active' => User::whereHas('userRoles', fn($q) => $q->where('company_id', $companyId))
                                ->where('is_active', true)->count(),
                'parties' => (clone $partyQuery)->count(),
                'party_payable' => (clone $partyQuery)->where('current_balance', '>', 0)->sum('current_balance'),
                'party_receivable' => abs((clone $partyQuery)->where('current_balance', '<', 0)->sum('current_balance')),
                'cost_centers' => $visibility->scopeForUser(CostCenter::query(), CostCenter::class)->count(),
                'bank_balance' => (clone $bankQuery)->where('account_type', 'bank')->sum('current_balance'),
                'cash_balance' => (clone $bankQuery)->where('account_type', 'cash')->sum('current_balance'),
                'sales' => $visibility->scopeForUser(SalesInvoice::query(), SalesInvoice::class)->whereBetween('billing_date', [$from, $to])->sum('grand_total'),
                'purchases' => $visibility->scopeForUser(PurchaseBill::query(), PurchaseBill::class)->whereBetween('billing_date', [$from, $to])->sum('grand_total'),
                'items' => (clone $itemQuery)->count(),
                'low_stock' => (clone $itemQuery)->whereNotNull('low_stock_qty')->whereColumn('current_stock', '<=', 'low_stock_qty')->count(),
                'estimates' => $visibility->scopeForUser(Estimate::query(), Estimate::class)->whereBetween('estimate_date', [$from, $to])->count(),
                'challans' => $visibility->scopeForUser(DeliveryChallan::query(), DeliveryChallan::class)->whereBetween('challan_date', [$from, $to])->count(),
                'pending_expenses' => $visibility->scopeForUser(Expense::query(), Expense::class)->where('status', 'pending_approval')->count(),
            ];
            $recentLogs = AuditLog::with('user')
                ->where('company_id', $companyId)
                ->whereBetween('created_at', [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()])
                ->latest('created_at')
                ->paginate(5, ['*'], 'activity_page');
            $companies = collect();
        }

        $salesDueRows = $this->dueRows(SalesInvoice::class, 'sale_type', $companyId, $visibility, $user, $from, $to);
        $purchaseDueRows = $this->dueRows(PurchaseBill::class, 'purchase_type', $companyId, $visibility, $user, $from, $to);
        $stats['sales_due'] = $salesDueRows->sum('due');
        $stats['purchase_due'] = $purchaseDueRows->sum('due');
        $ageingRows = $salesDueRows->merge($purchaseDueRows)->sortByDesc('date')->values();
        $ageingPage = max(1, $request->integer('ageing_page', 1));
        $ageingPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $ageingRows->forPage($ageingPage, 5)->values(),
            $ageingRows->count(),
            5,
            $ageingPage,
            ['pageName' => 'ageing_page', 'path' => $request->url(), 'query' => $request->query()]
        );
        $salesProducts = $this->productSummary(SalesInvoiceItem::class, 'salesInvoice', 'billing_date', $companyId, $visibility, $user, $from, $to);
        $purchaseProducts = $this->productSummary(PurchaseBillItem::class, 'purchaseBill', 'billing_date', $companyId, $visibility, $user, $from, $to);
        $topSellingItemIds = $salesProducts->take(3)->pluck('item_id')->filter()->all();
        $lowStockProducts = $this->lowStockProducts($companyId, $visibility, $user, $topSellingItemIds);
        $monthly = $this->monthlySeries($companyId, $visibility, $user, $from, $to);
        $mix = [
            'Sales' => (float) ($stats['sales'] ?? 0),
            'Purchase' => (float) ($stats['purchases'] ?? 0),
            'Bank' => (float) ($stats['bank_balance'] ?? 0),
            'Cash' => (float) ($stats['cash_balance'] ?? 0),
        ];
        $quickActions = $this->quickActions($user);

        return view('admin.dashboard', compact('stats','recentLogs','companies','companiesFilter','companyId','from','to','period','monthly','mix','quickActions','salesDueRows','purchaseDueRows','ageingPaginated','salesProducts','purchaseProducts','lowStockProducts'));
    }

    private function dateRange(Request $request): array
    {
        $period = $request->input('period', 'this_week');
        $today = now();

        return match ($period) {
            'today' => [$period, $today->toDateString(), $today->toDateString()],
            'yesterday' => [$period, $today->copy()->subDay()->toDateString(), $today->copy()->subDay()->toDateString()],
            'week', 'this_week' => ['week', $today->copy()->startOfWeek()->toDateString(), $today->copy()->endOfWeek()->toDateString()],
            'month', 'this_month' => ['month', $today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()],
            'three_months', 'last_3_months' => ['three_months', $today->copy()->subMonths(3)->startOfDay()->toDateString(), $today->toDateString()],
            'six_months' => [$period, $today->copy()->subMonths(6)->startOfDay()->toDateString(), $today->toDateString()],
            'nine_months' => [$period, $today->copy()->subMonths(9)->startOfDay()->toDateString(), $today->toDateString()],
            'one_year', 'year' => ['year', $today->copy()->subYear()->startOfDay()->toDateString(), $today->toDateString()],
            'all' => [$period, '1970-01-01', $today->toDateString()],
            'custom' => [
                $period,
                $request->date('from_date')?->toDateString() ?? $today->copy()->startOfMonth()->toDateString(),
                $request->date('to_date')?->toDateString() ?? $today->toDateString(),
            ],
            default => ['week', $today->copy()->startOfWeek()->toDateString(), $today->copy()->endOfWeek()->toDateString()],
        };
    }

    private function scope($query, ?int $companyId)
    {
        return $companyId ? $query->where('company_id', $companyId) : $query;
    }

    private function monthlySeries(?int $companyId, EntryVisibilityService $visibility, User $user, string $from, string $to): array
{
    $start = Carbon::parse($from)->startOfMonth();
    $end = Carbon::parse($to)->startOfMonth();
    if ($start->diffInMonths($end) > 11) {
        $start = $end->copy()->subMonths(11);
    }
    $months = collect();
    while ($start <= $end && $months->count() < 12) {
        $months->push($start->copy());
        $start->addMonth();
    }
    if ($months->isEmpty()) {
        $months->push(now()->startOfMonth());
    }

    $labels = $months->map(fn($date) => $date->format('M y'))->values();

    $sales = $months->map(function ($date) use ($companyId, $visibility, $user) {


        $query = $user->isSuperAdmin()
            ? $this->scope(SalesInvoice::query(), $companyId)
            : $visibility->scopeForUser(
                SalesInvoice::query(),
                SalesInvoice::class
            );

        return (float) $query
            ->whereYear('billing_date', $date->year)
            ->whereMonth('billing_date', $date->month)
            ->sum('grand_total');

    })->values();

    $purchases = $months->map(function ($date) use ($companyId, $visibility, $user) {

        $query = $user->isSuperAdmin()
            ? $this->scope(PurchaseBill::query(), $companyId)
            : $visibility->scopeForUser(
                PurchaseBill::query(),
                PurchaseBill::class
            );

        return (float) $query
            ->whereYear('billing_date', $date->year)
            ->whereMonth('billing_date', $date->month)
            ->sum('grand_total');

    })->values();

    return compact('labels', 'sales', 'purchases');
}
    private function quickActions(User $user): array
    {
        $actions = [
            ['can' => 'parties.create', 'route' => 'admin.parties.create', 'icon' => 'fa-id-card', 'label' => 'Add Party'],
            ['can' => 'sales.create', 'route' => 'admin.sales.create', 'icon' => 'fa-file-invoice-dollar', 'label' => 'New Sale'],
            ['can' => 'estimates.create', 'route' => 'admin.estimates.create', 'icon' => 'fa-file-contract', 'label' => 'New Estimate'],
            ['can' => 'delivery_challans.create', 'route' => 'admin.delivery-challans.create', 'icon' => 'fa-truck', 'label' => 'New Challan'],
            ['can' => 'purchase.create', 'route' => 'admin.purchases.create', 'icon' => 'fa-shopping-cart', 'label' => 'New Purchase'],
            ['can' => 'expenses.create', 'route' => 'admin.expenses.create', 'icon' => 'fa-receipt', 'label' => 'New Expense'],
            ['can' => 'items.create', 'route' => 'admin.items.create', 'icon' => 'fa-box', 'label' => 'Add Item'],
            ['can' => 'banking.manage', 'route' => 'admin.bank-transactions.create', 'icon' => 'fa-exchange-alt', 'label' => 'Bank Transfer'],
            ['can' => 'users.create', 'route' => 'admin.users.create', 'icon' => 'fa-user-plus', 'label' => 'Add User'],
            ['can' => 'roles.create', 'route' => 'admin.roles.create', 'icon' => 'fa-briefcase', 'label' => 'Add Role'],
        ];

        if ($user->isSuperAdmin()) {
            array_unshift($actions, ['can' => 'companies.create', 'route' => 'admin.companies.create', 'icon' => 'fa-building', 'label' => 'Add Company']);
        }

        return collect($actions)->filter(fn($action) => $user->can($action['can']) || ($user->isSuperAdmin() && $action['route'] === 'admin.companies.create'))->values()->all();
    }

    private function dueRows(string $model, string $typeColumn, ?int $companyId, EntryVisibilityService $visibility, User $user, string $from, string $to)
    {
        $query = $model::with(['party', 'items.item'])->where($typeColumn, 'credit')->whereBetween('billing_date', [$from, $to]);
        $query = $user->isSuperAdmin() ? $this->scope($query, $companyId) : $visibility->scopeForUser($query, $model);

        return $query->get()
            ->map(function ($bill) use ($model) {
                $allocations = PartyPaymentAllocation::with('payment.bankAccount')
                    ->where('bill_model', $model)
                    ->where('bill_id', $bill->id)
                    ->get();
                $paid = (float) $allocations->sum('amount');
                $due = max(0, (float) $bill->grand_total - $paid);
                return [
                    'kind' => $model === SalesInvoice::class ? 'receivable' : 'payable',
                    'bill_id' => $bill->id,
                    'party_id' => $bill->party_id,
                    'party' => $bill->party?->display_name ?: 'Cash / Walk-in',
                    'invoice' => $bill->invoice_no,
                    'date' => $bill->billing_date,
                    'age' => $bill->billing_date ? $bill->billing_date->diffInDays(now()) : 0,
                    'total' => (float) $bill->grand_total,
                    'paid' => $paid,
                    'due' => $due,
                    'items' => $bill->items->map(fn($line) => [
                        'name' => $line->item?->name ?: 'Item',
                        'qty' => (float) $line->quantity,
                        'unit' => $line->unit,
                        'rate' => (float) $line->unit_price,
                        'amount' => (float) $line->line_total,
                    ])->values(),
                    'payments' => $allocations->map(fn($allocation) => [
                        'date' => $allocation->payment?->payment_date?->format('d M Y') ?: '-',
                        'amount' => (float) $allocation->amount,
                        'mode' => $allocation->payment?->payment_mode ?: '-',
                        'bank' => $allocation->payment?->bankAccount?->account_name ?: $allocation->payment?->bankAccount?->bank_name ?: '-',
                        'reference' => $allocation->payment?->reference_no ?: '-',
                    ])->values(),
                ];
            })
            ->filter(fn($row) => $row['due'] > 0)
            ->values();
    }

    private function productSummary(string $lineModel, string $invoiceRelation, string $dateColumn, ?int $companyId, EntryVisibilityService $visibility, User $user, string $from, string $to)
    {
        $query = $lineModel::with(['item', $invoiceRelation])
            ->whereHas($invoiceRelation, fn($q) => $q->whereBetween($dateColumn, [$from, $to]));

        if ($user->isSuperAdmin()) {
            $query->whereHas($invoiceRelation, fn($q) => $this->scope($q, $companyId));
        } else {
            $invoiceModel = $lineModel === SalesInvoiceItem::class ? SalesInvoice::class : PurchaseBill::class;
            $visibleIds = $visibility->scopeForUser($invoiceModel::query(), $invoiceModel)->pluck('id');
            $foreignKey = $lineModel === SalesInvoiceItem::class ? 'sales_invoice_id' : 'purchase_bill_id';
            $query->whereIn($foreignKey, $visibleIds);
        }

        return $query->get()
            ->groupBy('item_id')
            ->map(function ($rows, $itemId) {
                $first = $rows->first();
                return [
                    'item_id' => $itemId,
                    'name' => $first->item?->name ?: 'Item',
                    'qty' => (float) $rows->sum('quantity'),
                    'amount' => (float) $rows->sum('line_total'),
                    'unit' => $first->unit ?: $first->item?->unit,
                ];
            })
            ->sortByDesc('qty')
            ->values();
    }

    private function lowStockProducts(?int $companyId, EntryVisibilityService $visibility, User $user, array $topSellingItemIds)
    {
        $query = Item::with('productType')
            ->whereNotNull('low_stock_qty')
            ->whereColumn('current_stock', '<=', 'low_stock_qty')
            ->orderBy('current_stock');
        $query = $user->isSuperAdmin() ? $this->scope($query, $companyId) : $visibility->scopeForUser($query, Item::class);

        return $query->take(10)->get()->map(fn(Item $item) => [
            'id' => $item->id,
            'name' => $item->name,
            'stock' => (float) $item->current_stock,
            'low' => (float) $item->low_stock_qty,
            'unit' => $item->unit,
            'most_selling' => in_array($item->id, $topSellingItemIds, true),
        ]);
    }
}
