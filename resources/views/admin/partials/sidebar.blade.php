@php
    $user = auth()->user();
    $canAnySale = $user->can('sales.view') || $user->can('estimates.view') || $user->can('delivery_challans.view') || $user->can('party_payments.view');
    $canAnyPurchase = $user->can('purchase.view') || $user->can('party_payments.view');
    $canAnyInventory = $user->can('items.view') || $user->can('product_types.view') || $user->can('stocks.view') || $user->can('production.view');
    $canAnyBanking = $user->can('banking.view') || $user->can('cost_centers.view');
    $canAnyReport = $user->can('reports.party') || $user->can('reports.stock') || $user->can('reports.expense') || $user->can('reports.gst') || $user->can('reports.transaction');
    $canManagement = $user->isSuperAdmin() || $user->isAdmin() || $user->can('users.view') || $user->can('roles.view') || $user->can('audit.view');
@endphp

<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="{{ route('admin.dashboard') }}" class="brand-link">
        <span class="brand-logo-icon" style="font-size:22px;margin-right:8px;">

            @if(auth()->user()->currentCompany && auth()->user()->currentCompany->logo)

                <img
                    src="{{ Storage::url(auth()->user()->currentCompany->logo) }}"
                    alt="Company Logo"
                    style="height:60px;width:auto;"
                >

            @else

                <i class="fas fa-building me-1" style="color:#7C3AED"></i>

            @endif

        </span>
        <span class="brand-text font-weight-bold"><span><b>Account's </b></span></span>
    </a>

    <div class="sidebar">
        <div class="user-panel mt-3 pb-3 d-flex">
            <div class="image">
                @if($user->profile_pic)
                    <img src="{{ $user->profile_pic_url }}" alt="User" class="elevation-2">
                @else
                    <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#2563eb,#14b8a6);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:14px;">
                        {{ substr($user->name,0,1) }}
                    </div>
                @endif
            </div>
            <div class="info">
                <a href="{{ route('admin.profile.edit') }}" class="d-block">
                    {{ $user->name }}<br>
                    <small style="opacity:.65;font-size:11px;">{{ $user->isSuperAdmin() ? 'Super Admin' : ($user->isAdmin() ? 'Company Admin' : 'Role User') }}</small>
                </a>
            </div>
        </div>

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i><p>Dashboard</p>
                    </a>
                </li>
                @if($user->isSuperAdmin())
                <li class="nav-item">
                    <a href="{{ route('admin.company-merges.index') }}"
                    class="nav-link {{ request()->routeIs('admin.company-merges*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-link"></i>
                        <p>Company Merges</p>
                    </a>
                </li>
                @endif
                @if($canAnySale)
                    <li class="nav-header">SALE</li>
                    <li class="nav-item has-treeview {{ request()->routeIs('admin.sales*','admin.estimates*','admin.delivery-challans*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs('admin.sales*','admin.estimates*','admin.delivery-challans*') ? 'active' : '' }}"><i class="nav-icon fas fa-file-invoice-dollar" style="color:#06B6D4"></i><p>Sale <i class="right fas fa-angle-left"></i></p></a>
                        <ul class="nav nav-treeview">
                            @can('sales.view')<li class="nav-item"><a href="{{ route('admin.sales.index') }}" class="nav-link {{ request()->routeIs('admin.sales*') ? 'active' : '' }}"><i class="fas fa-file-alt nav-icon"></i><p>Sale Invoice</p></a></li>@endcan
                            @can('party_payments.create')<li class="nav-item"><a href="{{ route('admin.party-payments.create', ['type' => 'payment_in']) }}" class="nav-link"><i class="fas fa-money-bill-wave nav-icon"></i><p>Payment In</p></a></li>@endcan
                            @can('estimates.view')<li class="nav-item"><a href="{{ route('admin.estimates.index') }}" class="nav-link {{ request()->routeIs('admin.estimates*') ? 'active' : '' }}"><i class="fas fa-file-contract nav-icon"></i><p>Estimate Quotation</p></a></li>@endcan
                            @can('delivery_challans.view')<li class="nav-item"><a href="{{ route('admin.delivery-challans.index') }}" class="nav-link {{ request()->routeIs('admin.delivery-challans*') ? 'active' : '' }}"><i class="fas fa-truck nav-icon"></i><p>Delivery Challan</p></a></li>@endcan
                        </ul>
                    </li>
                @endif

                @if($canAnyPurchase)
                    <li class="nav-header">PURCHASE</li>
                    <li class="nav-item has-treeview {{ request()->routeIs('admin.purchases*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs('admin.purchases*') ? 'active' : '' }}"><i class="nav-icon fas fa-shopping-cart" style="color:#EC4899"></i><p>Purchase <i class="right fas fa-angle-left"></i></p></a>
                        <ul class="nav nav-treeview">
                            @can('purchase.view')<li class="nav-item"><a href="{{ route('admin.purchases.index') }}" class="nav-link {{ request()->routeIs('admin.purchases*') ? 'active' : '' }}"><i class="fas fa-file-invoice nav-icon"></i><p>Purchase Bill</p></a></li>@endcan
                            @can('party_payments.create')<li class="nav-item"><a href="{{ route('admin.party-payments.create', ['type' => 'payment_out']) }}" class="nav-link"><i class="fas fa-hand-holding-usd nav-icon"></i><p>Payment Out</p></a></li>@endcan
                        </ul>
                    </li>
                @endif

                @if($canAnyInventory)
                    <li class="nav-header">INVENTORY</li>
                    <li class="nav-item has-treeview {{ request()->routeIs('admin.items*','admin.product-types*','admin.stocks*','admin.production-batches*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs('admin.items*','admin.product-types*','admin.stocks*','admin.production-batches*') ? 'active' : '' }}"><i class="nav-icon fas fa-boxes" style="color:#F59E0B"></i><p>Inventory <i class="right fas fa-angle-left"></i></p></a>
                        <ul class="nav nav-treeview">
                            @can('items.view')<li class="nav-item"><a href="{{ route('admin.items.index') }}" class="nav-link {{ request()->routeIs('admin.items*') ? 'active' : '' }}"><i class="fas fa-box nav-icon"></i><p>Item Master</p></a></li>@endcan
                            @can('product_types.view')<li class="nav-item"><a href="{{ route('admin.product-types.index') }}" class="nav-link {{ request()->routeIs('admin.product-types*') ? 'active' : '' }}"><i class="fas fa-tags nav-icon"></i><p>Product Types</p></a></li>@endcan
                            @can('stocks.view')<li class="nav-item"><a href="{{ route('admin.stocks.index') }}" class="nav-link {{ request()->routeIs('admin.stocks.index') ? 'active' : '' }}"><i class="fas fa-layer-group nav-icon"></i><p>Current Stocks</p></a></li>@endcan
                            @can('stocks.view')<li class="nav-item"><a href="{{ route('admin.stocks.history') }}" class="nav-link {{ request()->routeIs('admin.stocks.history') ? 'active' : '' }}"><i class="fas fa-history nav-icon"></i><p>Stock History</p></a></li>@endcan
                            @can('stocks.view')
                                <li class="nav-item">
                                    <a href="{{ route('admin.stock-transfers.index') }}"
                                    class="nav-link {{ request()->routeIs('admin.stock-transfers*') ? 'active' : '' }}">
                                        <i class="fas fa-exchange-alt nav-icon" style="color:#10B981"></i>
                                        <p>
                                            Stock Transfer
                                            {{-- Pending badge for receiving company --}}
                                            @php
                                                $pendingCount = \App\Models\StockTransfer::where('to_company_id', auth()->user()->current_company_id)
                                                    ->where('status', 'pending')->count();
                                            @endphp
                                            @if($pendingCount > 0)
                                                <span class="right badge badge-warning">{{ $pendingCount }}</span>
                                            @endif
                                        </p>
                                    </a>
                                </li>
                            @endcan
                            @can('production.view')<li class="nav-item"><a href="{{ route('admin.production-batches.index') }}" class="nav-link {{ request()->routeIs('admin.production-batches*') ? 'active' : '' }}"><i class="fas fa-industry nav-icon"></i><p>CRM Assembly</p></a></li>@endcan
                        </ul>
                    </li>
                @endif

                @can('parties.view')
                    <li class="nav-header">PARTIES</li>
                    <li class="nav-item"><a href="{{ route('admin.parties.index') }}" class="nav-link {{ request()->routeIs('admin.parties*') ? 'active' : '' }}"><i class="nav-icon fas fa-users" style="color:#8B5CF6"></i><p>Party Details</p></a></li>
                @endcan

                @if($canAnyBanking)
                    <li class="nav-header">BANKING & COSTING</li>
                    @can('banking.view')<li class="nav-item"><a href="{{ route('admin.bank-accounts.index') }}" class="nav-link {{ request()->routeIs('admin.bank-accounts*') ? 'active' : '' }}"><i class="nav-icon fas fa-university" style="color:#06B6D4"></i><p>Bank Accounts</p></a></li>@endcan
                    @can('banking.view')<li class="nav-item"><a href="{{ route('admin.bank-transactions.index') }}" class="nav-link {{ request()->routeIs('admin.bank-transactions*') ? 'active' : '' }}"><i class="nav-icon fas fa-exchange-alt"></i><p>Bank Transactions</p></a></li>@endcan
                    @can('banking.view')<li class="nav-item"><a href="{{ route('admin.bank-reports.statement') }}" class="nav-link {{ request()->routeIs('admin.bank-reports*') ? 'active' : '' }}"><i class="nav-icon fas fa-chart-line"></i><p>Bank Report</p></a></li>@endcan
                    @can('cost_centers.view')<li class="nav-item"><a href="{{ route('admin.cost-centers.index') }}" class="nav-link {{ request()->routeIs('admin.cost-centers*') ? 'active' : '' }}"><i class="nav-icon fas fa-sitemap" style="color:#EC4899"></i><p>Cost Centers</p></a></li>@endcan
                    @can('cost_centers.view')<li class="nav-item"><a href="{{ route('admin.sub-cost-centers.index') }}" class="nav-link {{ request()->routeIs('admin.sub-cost-centers*') ? 'active' : '' }}"><i class="nav-icon fas fa-project-diagram"></i><p>Sub Cost Centers</p></a></li>@endcan
                @endif

                @if($canAnyReport)
                    <li class="nav-header">REPORTS</li>
                    @can('reports.gst')
                        <li class="nav-item has-treeview {{ request()->routeIs('admin.reports.gst*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->routeIs('admin.reports.gst*') ? 'active' : '' }}"><i class="nav-icon fas fa-receipt"></i><p>GST Reports <i class="right fas fa-angle-left"></i></p></a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item"><a href="{{ route('admin.reports.gst1') }}" class="nav-link"><i class="fas fa-file-invoice nav-icon"></i><p>GST-1 Sales</p></a></li>
                                <li class="nav-item"><a href="{{ route('admin.reports.gst2') }}" class="nav-link"><i class="fas fa-shopping-cart nav-icon"></i><p>GST-2 Purchase</p></a></li>
                                <li class="nav-item"><a href="{{ route('admin.reports.gst3') }}" class="nav-link"><i class="fas fa-balance-scale nav-icon"></i><p>GST-3 Summary</p></a></li>
                            </ul>
                        </li>
                    @endcan
                    @can('reports.party')
                        <li class="nav-item has-treeview {{ request()->routeIs('admin.reports.party*','admin.reports.all-parties','admin.reports.sale-purchase-by-party') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link"><i class="nav-icon fas fa-user-friends"></i><p>Party Report <i class="right fas fa-angle-left"></i></p></a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item"><a href="{{ route('admin.reports.party-statement') }}" class="nav-link"><i class="fas fa-file-alt nav-icon"></i><p>Party Statement</p></a></li>
                                <li class="nav-item"><a href="{{ route('admin.reports.party-profit-loss') }}" class="nav-link"><i class="fas fa-chart-line nav-icon"></i><p>Party Wise Profit And Loss</p></a></li>
                                <li class="nav-item"><a href="{{ route('admin.reports.all-parties') }}" class="nav-link"><i class="fas fa-users nav-icon"></i><p>All Parties</p></a></li>
                                <li class="nav-item"><a href="{{ route('admin.reports.party-by-item') }}" class="nav-link"><i class="fas fa-boxes nav-icon"></i><p>Party Report By Item</p></a></li>
                                <li class="nav-item"><a href="{{ route('admin.reports.sale-purchase-by-party') }}" class="nav-link"><i class="fas fa-random nav-icon"></i><p>Sale Purchase By Party</p></a></li>
                            </ul>
                        </li>
                    @endcan
                    @can('reports.transaction')
                        <li class="nav-item has-treeview {{ request()->routeIs('admin.reports.sales','admin.reports.purchases','admin.reports.day-book','admin.reports.all-transactions','admin.reports.profit-loss','admin.reports.bill-wise-profit','admin.reports.balance-sheet') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link"><i class="nav-icon fas fa-exchange-alt"></i><p>Transaction Report <i class="right fas fa-angle-left"></i></p></a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item"><a href="{{ route('admin.reports.sales') }}" class="nav-link"><i class="fas fa-bookmark nav-icon"></i><p>Sale Report</p></a></li>
                                <li class="nav-item"><a href="{{ route('admin.reports.purchases') }}" class="nav-link"><i class="fas fa-shopping-cart nav-icon"></i><p>Purchase Report</p></a></li>
                                <li class="nav-item"><a href="{{ route('admin.reports.day-book') }}" class="nav-link"><i class="fas fa-book nav-icon"></i><p>Day Book</p></a></li>
                                <li class="nav-item"><a href="{{ route('admin.reports.all-transactions') }}" class="nav-link"><i class="fas fa-random nav-icon"></i><p>All Transactions</p></a></li>
                                <li class="nav-item"><a href="{{ route('admin.reports.profit-loss') }}" class="nav-link"><i class="fas fa-heart nav-icon"></i><p>Profit And Loss</p></a></li>
                                <li class="nav-item"><a href="{{ route('admin.reports.bill-wise-profit') }}" class="nav-link"><i class="fas fa-file-invoice nav-icon"></i><p>Bill Wise Profit</p></a></li>
                                <li class="nav-item"><a href="{{ route('admin.reports.balance-sheet') }}" class="nav-link"><i class="fas fa-balance-scale nav-icon"></i><p>Balance Sheet</p></a></li>
                            </ul>
                        </li>
                    @endcan
                    @can('reports.stock')<li class="nav-item"><a href="{{ route('admin.stocks.index') }}" class="nav-link"><i class="nav-icon fas fa-box-open"></i><p>Stock Reports</p></a></li>@endcan
                @endif

                @if($canManagement)
                    <li class="nav-header">MANAGEMENT</li>
                    @can('roles.view')<li class="nav-item"><a href="{{ route('admin.roles.index') }}" class="nav-link {{ request()->routeIs('admin.roles*') ? 'active' : '' }}"><i class="nav-icon fas fa-briefcase"></i><p>Roles</p></a></li>@endcan
                    @can('users.view')<li class="nav-item"><a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}"><i class="nav-icon fas fa-user"></i><p>Users</p></a></li>@endcan
                    @if($user->isSuperAdmin())<li class="nav-item"><a href="{{ route('admin.permissions.index') }}" class="nav-link {{ request()->routeIs('admin.permissions*') ? 'active' : '' }}"><i class="nav-icon fas fa-lock"></i><p>Permissions</p></a></li>@endif
                    @if($user->isSuperAdmin())<li class="nav-item"><a href="{{ route('admin.companies.index') }}" class="nav-link {{ request()->routeIs('admin.companies*') ? 'active' : '' }}"><i class="nav-icon fas fa-building"></i><p>Businesses</p></a></li>@endif
                    @can('audit.view')<li class="nav-item"><a href="{{ route('admin.audit-logs.index') }}" class="nav-link {{ request()->routeIs('admin.audit*') ? 'active' : '' }}"><i class="nav-icon fas fa-file-alt"></i><p>Audit Logs</p></a></li>@endcan
                @endif
            </ul>
        </nav>
    </div>
</aside>
