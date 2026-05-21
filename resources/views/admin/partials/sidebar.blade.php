<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{ route('admin.dashboard') }}" class="brand-link">
        <span class="brand-logo-icon" style="font-size:22px;margin-right:8px;">💼</span>
        <span class="brand-text font-weight-bold">Biz<span>Account</span></span>
    </a>

    <div class="sidebar">
        <!-- User Panel -->
        <div class="user-panel mt-3 pb-3 d-flex">
            <div class="image">
                @if(auth()->user()->profile_pic)
                    <img src="{{ auth()->user()->profile_pic_url }}" alt="User" class="elevation-2">
                @else
                    <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#7C3AED,#06B6D4);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:14px;">
                        {{ substr(auth()->user()->name,0,1) }}
                    </div>
                @endif
            </div>
            <div class="info">
                <a href="{{ route('admin.profile.edit') }}" class="d-block">
                    {{ auth()->user()->name }}
                    <br>
                    <small style="opacity:.6;font-size:11px;">
                        @if(auth()->user()->isSuperAdmin()) ⭐ Super Admin
                        @elseif(auth()->user()->isAdmin()) 🏢 Admin
                        @else 👤 User @endif
                    </small>
                </a>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview" role="menu" data-accordion="false">

                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <!-- ─── SALE ──────────────────────────── -->
                <li class="nav-header">SALE</li>
                <li class="nav-item has-treeview {{ request()->routeIs('admin.sale*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->routeIs('admin.sale*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-file-invoice-dollar" style="color:#06B6D4"></i>
                        <p>Sale <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-file-alt nav-icon"></i><p>Sale Invoice</p></a></li>
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-money-bill-wave nav-icon"></i><p>Payment In</p></a></li>
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-file-contract nav-icon"></i><p>Estimate Quotation</p></a></li>
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-truck nav-icon"></i><p>Delivery Challan</p></a></li>
                    </ul>
                </li>

                <!-- ─── PURCHASE ──────────────────────── -->
                <li class="nav-header">PURCHASE</li>
                <li class="nav-item has-treeview {{ request()->routeIs('admin.purchase*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-shopping-cart" style="color:#EC4899"></i>
                        <p>Purchase & Expense <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-file-invoice nav-icon"></i><p>Purchase Bill</p></a></li>
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-hand-holding-usd nav-icon"></i><p>Payment Out</p></a></li>
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-clipboard-list nav-icon"></i><p>Purchase Order</p></a></li>
                    </ul>
                </li>

                <!-- ─── INVENTORY ─────────────────────── -->
                <li class="nav-header">INVENTORY</li>
                <li class="nav-item has-treeview">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-boxes" style="color:#F59E0B"></i>
                        <p>Items <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-plus-circle nav-icon"></i><p>Add Item</p></a></li>
                    </ul>
                </li>

                <li class="nav-item has-treeview">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-warehouse" style="color:#10B981"></i>
                        <p>Stocks <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-layer-group nav-icon"></i><p>Current Stocks</p></a></li>
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-chart-bar nav-icon"></i><p>Stocks Report</p></a></li>
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-history nav-icon"></i><p>Stock History</p></a></li>
                    </ul>
                </li>

                <!-- ─── BANKING ────────────────────────── -->
                <li class="nav-header">BANK - CASH & ASSETS</li>
                <li class="nav-item has-treeview">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-university" style="color:#06B6D4"></i>
                        <p>Bank - Cash & Assets <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-piggy-bank nav-icon"></i><p>Bank Account</p></a></li>
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-exchange-alt nav-icon"></i><p>Bank Transactions</p></a></li>
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-hand-holding nav-icon"></i><p>Cash In Hand</p></a></li>
                        <li class="nav-item has-treeview">
                            <a href="#" class="nav-link"><i class="fas fa-cogs nav-icon"></i><p>Deposit Withdraw <i class="right fas fa-angle-left"></i></p></a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-arrow-left nav-icon"></i><p>Bank To Cash</p></a></li>
                                <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-arrow-right nav-icon"></i><p>Cash To Bank</p></a></li>
                                <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-arrows-alt-h nav-icon"></i><p>Bank To Bank</p></a></li>
                                <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-balance-scale nav-icon"></i><p>Adjust Bank Balance</p></a></li>
                            </ul>
                        </li>
                    </ul>
                </li>

                <!-- ─── EXPENSES ───────────────────────── -->
                <li class="nav-header">EXPENSES</li>
                <li class="nav-item has-treeview">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-receipt" style="color:#F59E0B"></i>
                        <p>Expense <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-tags nav-icon"></i><p>Expense Category</p></a></li>
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-list nav-icon"></i><p>Expense List</p></a></li>
                    </ul>
                </li>

                <!-- ─── PARTIES ────────────────────────── -->
                <li class="nav-header">PARTIES</li>
                <li class="nav-item has-treeview">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-users" style="color:#8B5CF6"></i>
                        <p>Parties <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-id-card nav-icon"></i><p>Party Details</p></a></li>
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-star nav-icon"></i><p>Loyalty Point</p></a></li>
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fab fa-whatsapp nav-icon"></i><p>Whatsapp Connect</p></a></li>
                    </ul>
                </li>

                <!-- ─── COST CENTER ────────────────────── -->
                <li class="nav-item has-treeview">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-sitemap" style="color:#EC4899"></i>
                        <p>Cost Center <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-home nav-icon"></i><p>Main Cost Center</p></a></li>
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-exchange-alt nav-icon"></i><p>Sub Cost Centers</p></a></li>
                    </ul>
                </li>

                <!-- ─── REPORTS ───────────────────────── -->
                <li class="nav-header">REPORTS</li>
                <li class="nav-item has-treeview">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-chart-line" style="color:#06B6D4"></i>
                        <p>Party Report <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-file-alt nav-icon"></i><p>Party Statement</p></a></li>
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-chart-pie nav-icon"></i><p>Party Wise P&L</p></a></li>
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-users nav-icon"></i><p>All Parties</p></a></li>
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-boxes nav-icon"></i><p>Party Report By Item</p></a></li>
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-random nav-icon"></i><p>Sale Purchase By Party</p></a></li>
                    </ul>
                </li>

                <li class="nav-item has-treeview">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-box-open" style="color:#10B981"></i>
                        <p>Stock Report <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-chart-pie nav-icon"></i><p>Stocks Summary</p></a></li>
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-users nav-icon"></i><p>Item Report By Party</p></a></li>
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-dollar-sign nav-icon"></i><p>Item Wise P&L</p></a></li>
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-exclamation-triangle nav-icon"></i><p>Low Stock Summary</p></a></li>
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-clipboard nav-icon"></i><p>Stock Detail</p></a></li>
                    </ul>
                </li>

                <li class="nav-item has-treeview">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-receipt" style="color:#F59E0B"></i>
                        <p>Expense Report <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-list-alt nav-icon"></i><p>Expense Report</p></a></li>
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-tag nav-icon"></i><p>Expense Category Report</p></a></li>
                        <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-clipboard-list nav-icon"></i><p>Expense Item Report</p></a></li>
                    </ul>
                </li>

                <!-- ─── MANAGEMENT ─────────────────────── -->
                @if(auth()->user()->isSuperAdmin() || auth()->user()->isAdmin())
                <li class="nav-header">MANAGEMENT</li>
                <li class="nav-item has-treeview {{ request()->routeIs('admin.users*','admin.roles*','admin.permissions*','admin.companies*','admin.audit*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->routeIs('admin.users*','admin.roles*','admin.permissions*','admin.companies*','admin.audit*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-users-cog" style="color:#7C3AED"></i>
                        <p>User Management <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        @if(auth()->user()->isSuperAdmin())
                        <li class="nav-item">
                            <a href="{{ route('admin.permissions.index') }}" class="nav-link {{ request()->routeIs('admin.permissions*') ? 'active' : '' }}">
                                <i class="fas fa-lock nav-icon"></i><p>Permissions</p>
                            </a>
                        </li>
                        @endif

                        <li class="nav-item">
                            <a href="{{ route('admin.roles.index') }}" class="nav-link {{ request()->routeIs('admin.roles*') ? 'active' : '' }}">
                                <i class="fas fa-briefcase nav-icon"></i><p>Roles</p>
                            </a>
                        </li>

                        @if(auth()->user()->isSuperAdmin())
                        <li class="nav-item">
                            <a href="{{ route('admin.companies.index') }}" class="nav-link {{ request()->routeIs('admin.companies*') ? 'active' : '' }}">
                                <i class="fas fa-building nav-icon"></i><p>Add Business</p>
                            </a>
                        </li>
                        @endif

                        <li class="nav-item">
                            <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                                <i class="fas fa-user nav-icon"></i><p>Users</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.audit-logs.index') }}" class="nav-link {{ request()->routeIs('admin.audit*') ? 'active' : '' }}">
                                <i class="fas fa-file-alt nav-icon"></i><p>Audit Logs</p>
                            </a>
                        </li>
                    </ul>
                </li>
                @endif

            </ul>
        </nav>
    </div>
</aside>
