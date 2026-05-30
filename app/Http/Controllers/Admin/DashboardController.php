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
use App\Models\Role;
use App\Models\SalesInvoice;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\EntryVisibilityService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, EntryVisibilityService $visibility)
    {
        $user = auth()->user();
        $companyId = $user->isSuperAdmin() ? $request->integer('company_id') : $user->current_company_id;
        $from = $request->date('from_date')?->toDateString() ?? now()->startOfMonth()->toDateString();
        $to = $request->date('to_date')?->toDateString() ?? now()->toDateString();
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
                ->latest('created_at')->take(15)->get();
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
                ->latest('created_at')->take(10)->get();
            $companies = collect();
        }

        $salesDueRows = $this->dueRows(SalesInvoice::class, 'sale_type', $companyId, $visibility, $user);
        $purchaseDueRows = $this->dueRows(PurchaseBill::class, 'purchase_type', $companyId, $visibility, $user);
        $stats['sales_due'] = $salesDueRows->sum('due');
        $stats['purchase_due'] = $purchaseDueRows->sum('due');
        $monthly = $this->monthlySeries($companyId, $visibility, $user);
        $mix = [
            'Sales' => (float) ($stats['sales'] ?? 0),
            'Purchase' => (float) ($stats['purchases'] ?? 0),
            'Bank' => (float) ($stats['bank_balance'] ?? 0),
            'Cash' => (float) ($stats['cash_balance'] ?? 0),
        ];
        $quickActions = $this->quickActions($user);

        return view('admin.dashboard', compact('stats','recentLogs','companies','companiesFilter','companyId','from','to','monthly','mix','quickActions','salesDueRows','purchaseDueRows'));
    }

    private function scope($query, ?int $companyId)
    {
        return $companyId ? $query->where('company_id', $companyId) : $query;
    }

    private function monthlySeries(?int $companyId, EntryVisibilityService $visibility, User $user): array
{
    $labels = collect(range(5, 0))
        ->map(fn($i) => now()->subMonths($i)->format('M'))
        ->values();

    $sales = collect(range(5, 0))->map(function ($i) use ($companyId, $visibility, $user) {

        $date = now()->subMonths($i);

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

    $purchases = collect(range(5, 0))->map(function ($i) use ($companyId, $visibility, $user) {

        $date = now()->subMonths($i);

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

    private function dueRows(string $model, string $typeColumn, ?int $companyId, EntryVisibilityService $visibility, User $user)
    {
        $query = $model::with('party')->where($typeColumn, 'credit');
        $query = $user->isSuperAdmin() ? $this->scope($query, $companyId) : $visibility->scopeForUser($query, $model);

        return $query->get()
            ->map(function ($bill) use ($model) {
                $paid = (float) PartyPaymentAllocation::where('bill_model', $model)->where('bill_id', $bill->id)->sum('amount');
                $due = max(0, (float) $bill->grand_total - $paid);
                return ['party' => $bill->party?->display_name ?: 'Cash / Walk-in', 'invoice' => $bill->invoice_no, 'date' => $bill->billing_date, 'total' => (float) $bill->grand_total, 'paid' => $paid, 'due' => $due];
            })
            ->filter(fn($row) => $row['due'] > 0)
            ->values();
    }
}
