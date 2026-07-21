<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Admin\Accounts\BankAccountController;
use App\Http\Controllers\Admin\Accounts\BankTransactionController;
use App\Http\Controllers\Admin\Accounts\CostCenterController;
use App\Http\Controllers\Admin\Accounts\PartyController;
use App\Http\Controllers\Admin\Accounts\PartyAdvanceController;
use App\Http\Controllers\Admin\Accounts\PartyPaymentController;
use App\Http\Controllers\Admin\Accounts\SubCostCenterController;
use App\Http\Controllers\Admin\Inventory\ItemController;
use App\Http\Controllers\Admin\Inventory\BuyerController;
use App\Http\Controllers\Admin\Inventory\ProductTypeController;
use App\Http\Controllers\Admin\Inventory\StockController;
use App\Http\Controllers\Admin\Production\ProductionBatchController;
use App\Http\Controllers\Admin\Purchase\PurchaseBillController;
use App\Http\Controllers\Admin\Purchase\PurchaseEstimateController;
use App\Http\Controllers\Admin\Purchase\PurchaseReturnController;
use App\Http\Controllers\Admin\Purchase\SmartPurchaseController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReplacementController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\ExpenseLedgerController;
use App\Http\Controllers\Admin\OtherTransactionController;
use App\Http\Controllers\Admin\TermsTemplateController;
use App\Http\Controllers\Admin\Sales\DeliveryChallanController;
use App\Http\Controllers\Admin\Sales\EstimateController;
use App\Http\Controllers\Admin\Sales\SalesInvoiceController;
use App\Http\Controllers\Admin\Sales\SalesReturnController;
use App\Http\Controllers\Admin\Sales\StockOutChallanController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// ── Auth Routes (Breeze) ──────────────────────────────────
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__ . '/auth.php';

// ── Admin Routes ──────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'screen_unlocked'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [Admin\ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [Admin\ProfileController::class, 'update'])->name('profile.update');

    Route::middleware('permission:parties.view')->group(function () {
        Route::resource('parties', PartyController::class)->only(['create','store'])->middleware('permission:parties.create');
        Route::resource('parties', PartyController::class)->only(['edit','update'])->middleware('permission:parties.edit');
        Route::resource('parties', PartyController::class)->only(['destroy'])->middleware('permission:parties.delete');
        Route::resource('parties', PartyController::class)->only(['index','show']);
    });
    Route::middleware('permission:cost_centers.view')->group(function () {
        Route::resource('cost-centers', CostCenterController::class)->only(['index']);
        Route::resource('cost-centers', CostCenterController::class)->only(['create','store','edit','update','destroy'])->middleware('permission:cost_centers.manage');
        Route::resource('sub-cost-centers', SubCostCenterController::class)->only(['index']);
        Route::resource('sub-cost-centers', SubCostCenterController::class)->only(['create','store','edit','update','destroy'])->middleware('permission:cost_centers.manage');
    });
    Route::middleware('permission:banking.view')->group(function () {
        Route::resource('bank-accounts', BankAccountController::class)->only(['create','store','edit','update','destroy'])->middleware('permission:banking.manage');
        Route::resource('bank-accounts', BankAccountController::class)->only(['index','show']);
        Route::resource('bank-transactions', BankTransactionController::class)->only(['create','store'])->middleware('permission:banking.manage');
        Route::resource('bank-transactions', BankTransactionController::class)->only(['index']);
        Route::get('bank-reports/statement', [BankTransactionController::class, 'report'])->name('bank-reports.statement');
    });
    Route::middleware('permission:expenses.view')->group(function () {
        Route::resource('expense-ledgers', ExpenseLedgerController::class)->only(['index']);
        Route::resource('expense-ledgers', ExpenseLedgerController::class)->only(['create','store','edit','update'])->middleware('permission:expenses.create');
        Route::post('expenses/{expense}/approve', [ExpenseController::class, 'approve'])->middleware('permission:expenses.approve')->name('expenses.approve');
        Route::post('expenses/{expense}/reject', [ExpenseController::class, 'reject'])->middleware('permission:expenses.approve')->name('expenses.reject');
        Route::resource('expenses', ExpenseController::class)->only(['create','store'])->middleware('permission:expenses.create');
        Route::resource('expenses', ExpenseController::class)->only(['edit','update'])->middleware('permission:expenses.edit');
        Route::resource('expenses', ExpenseController::class)->only(['index','show']);
    });
    Route::middleware('permission:other_transactions.view')->group(function () {
        Route::post('other-transactions/{otherTransaction}/approve', [OtherTransactionController::class, 'approve'])->middleware('permission:other_transactions.approve')->name('other-transactions.approve');
        Route::post('other-transactions/{otherTransaction}/reject', [OtherTransactionController::class, 'reject'])->middleware('permission:other_transactions.approve')->name('other-transactions.reject');
        Route::resource('other-transactions', OtherTransactionController::class)->only(['create','store'])->middleware('permission:other_transactions.create');
        Route::resource('other-transactions', OtherTransactionController::class)->only(['edit','update'])->middleware('permission:other_transactions.edit');
        Route::resource('other-transactions', OtherTransactionController::class)->only(['index','show']);
    });
    Route::middleware('permission:terms.manage')->group(function () {
        Route::resource('terms', TermsTemplateController::class)->except(['show','destroy']);
    });
    Route::middleware('permission:party_payments.view')->group(function () {
        Route::get('party-payments/open-bills', [PartyPaymentController::class, 'openBills'])->name('party-payments.open-bills');
        Route::get('party-advances/available', [PartyAdvanceController::class, 'available'])->name('party-advances.available');
        Route::resource('party-payments', PartyPaymentController::class)->only(['index','create','store']);
    });
    Route::middleware('permission:product_types.view')->group(function () {
        Route::resource('product-types', ProductTypeController::class)->only(['index']);
        Route::post('product-types/categories', [ProductTypeController::class, 'storeCategory'])->middleware('permission:product_types.manage')->name('product-types.categories.store');
        Route::resource('product-types', ProductTypeController::class)->only(['create','store','edit','update','destroy'])->middleware('permission:product_types.manage');
    });
    Route::middleware('permission:items.view')->group(function () {
        Route::resource('items', ItemController::class)->only(['index']);
        Route::resource('items', ItemController::class)->only(['create','store'])->middleware('permission:items.create');
        Route::resource('items', ItemController::class)->only(['edit','update'])->middleware('permission:items.edit');
        Route::resource('items', ItemController::class)->only(['destroy'])->middleware('permission:items.delete');
    });
    Route::middleware('permission:stocks.view')->group(function () {
        Route::get('stocks', [StockController::class, 'index'])->name('stocks.index');
        Route::get('stocks/history', [StockController::class, 'history'])->name('stocks.history');
        Route::post('stocks/{item}/adjust', [StockController::class, 'adjustRawMaterial'])->middleware('company_admin')->name('stocks.adjust');
        Route::get('stocks/special-stock-out', [StockController::class, 'specialStockOut'])->name('stocks.special-stock-out');
    });
    Route::middleware('permission:replacements.view')->group(function () {
        Route::get('replacements/lookup', [ReplacementController::class, 'lookup'])->middleware('permission:replacements.create')->name('replacements.lookup');
        Route::post('replacements/{replacement}/approve', [ReplacementController::class, 'approve'])->middleware('permission:replacements.approve')->name('replacements.approve');
        Route::post('replacements/{replacement}/reject', [ReplacementController::class, 'reject'])->middleware('permission:replacements.approve')->name('replacements.reject');
        Route::post('replacements/{replacement}/issue', [ReplacementController::class, 'issue'])->middleware('permission:replacements.approve')->name('replacements.issue');
        Route::resource('replacements', ReplacementController::class)->only(['index']);
        Route::resource('replacements', ReplacementController::class)->only(['create','store'])->middleware('permission:replacements.create');
        Route::resource('replacements', ReplacementController::class)->only(['edit','update'])->middleware('permission:replacements.edit');
        Route::resource('replacements', ReplacementController::class)->only(['show']);
        Route::resource('replacements', ReplacementController::class)->only(['destroy'])->middleware('permission:replacements.delete');
    });
    Route::middleware(['permission:production.view', 'crm_access'])->group(function () {
        Route::resource('buyers', BuyerController::class)->only(['index','create','store','edit','update']);
        Route::get('production-reverts', [ProductionBatchController::class, 'revertTool'])->middleware('permission:production_reverts.view')->name('production-reverts.index');
        Route::post('production-reverts', [ProductionBatchController::class, 'revertSelected'])->middleware('permission:production_reverts.manage')->name('production-reverts.store');
        Route::resource('production-batches', ProductionBatchController::class)->only(['create','store'])->middleware('permission:production.create');
        Route::resource('production-batches', ProductionBatchController::class)->only(['edit','update'])->middleware('permission:production.create');
        Route::post('production-batches/{productionBatch}/identifier-impact', [ProductionBatchController::class, 'identifierImpact'])->middleware('permission:production.create')->name('production-batches.identifier-impact');
        Route::post('production-batches/{productionBatch}/revert', [ProductionBatchController::class, 'revert'])->middleware('permission:production.create')->name('production-batches.revert');
        Route::resource('production-batches', ProductionBatchController::class)->only(['index','show']);
    });
    Route::middleware('permission:smart_purchase.view')->group(function () {
        Route::get('smart-purchases', [SmartPurchaseController::class, 'index'])->middleware('permission:smart_purchase.view')->name('smart-purchases.index');
        Route::post('smart-purchases', [SmartPurchaseController::class, 'store'])->middleware('permission:smart_purchase.create')->name('smart-purchases.store');
        Route::post('smart-purchases/parties', [PartyController::class, 'store'])->middleware('permission:smart_purchase.create')->name('smart-purchases.parties.store');
    });
    Route::middleware('permission:purchase.view')->group(function () {
        Route::get('purchases/{purchase}/print', [PurchaseBillController::class, 'print'])->middleware('permission:purchase.print')->name('purchases.print');
        Route::resource('purchases', PurchaseBillController::class)->only(['create','store'])->middleware('permission:purchase.create');
        Route::resource('purchases', PurchaseBillController::class)->only(['edit','update'])->middleware('permission:purchase.edit');
        Route::resource('purchases', PurchaseBillController::class)->only(['index','show']);
        // web.php → Route::prefix('admin')->name('admin.')->group(function() {

        // Admin group ke ANDAR — baaki routes ke saath

        // ─── Purchase Returns ───────────────────────────────────────────
        // RULE: Har static/named route resource se PEHLE aana chahiye

        Route::get('purchase-returns/bill-items', [PurchaseReturnController::class, 'billItems'])
            ->name('purchase-returns.bill-items');

        Route::get('purchase-returns', [PurchaseReturnController::class, 'index'])
            ->name('purchase-returns.index');

        Route::get('purchase-returns/create', [PurchaseReturnController::class, 'create'])
            ->middleware('permission:purchase.create')
            ->name('purchase-returns.create');

        Route::post('purchase-returns', [PurchaseReturnController::class, 'store'])
            ->middleware('permission:purchase.create')
            ->name('purchase-returns.store');

        Route::get('purchase-returns/{purchaseReturn}', [PurchaseReturnController::class, 'show'])
            ->name('purchase-returns.show');

        Route::get('purchase-returns/{purchaseReturn}/edit', [PurchaseReturnController::class, 'edit'])
            ->middleware('permission:purchase.edit')
            ->name('purchase-returns.edit');

        Route::put('purchase-returns/{purchaseReturn}', [PurchaseReturnController::class, 'update'])
            ->middleware('permission:purchase.edit')
            ->name('purchase-returns.update');
    });
    Route::middleware('permission:purchase_estimates.view')->group(function () {
        Route::get('purchase-estimates/{purchaseEstimate}/print', [PurchaseEstimateController::class, 'print'])->middleware('permission:purchase_estimates.print')->name('purchase-estimates.print');
        Route::post('purchase-estimates/{purchaseEstimate}/convert', [PurchaseEstimateController::class, 'convert'])->middleware('permission:purchase_estimates.convert')->name('purchase-estimates.convert');
        Route::post('purchase-estimates/{purchaseEstimate}/transit', [PurchaseEstimateController::class, 'transit'])->middleware('permission:purchase_estimates.convert')->name('purchase-estimates.transit');
        Route::patch('purchase-estimates/{purchaseEstimate}/cancel', [PurchaseEstimateController::class, 'cancel'])->middleware('permission:purchase_estimates.edit')->name('purchase-estimates.cancel');
        Route::resource('purchase-estimates', PurchaseEstimateController::class)->only(['create','store'])->middleware('permission:purchase_estimates.create');
        Route::resource('purchase-estimates', PurchaseEstimateController::class)->only(['edit','update'])->middleware('permission:purchase_estimates.edit');
        Route::resource('purchase-estimates', PurchaseEstimateController::class)->only(['destroy'])->middleware('permission:purchase_estimates.delete');
        Route::resource('purchase-estimates', PurchaseEstimateController::class)->only(['index','show']);
    });
    Route::middleware('permission:sales.view')->group(function () {
        Route::get('sales/{sale}/print', [SalesInvoiceController::class, 'print'])->middleware('permission:sales.print')->name('sales.print');
        Route::get('sales/{sale}/detail-pdf', [SalesInvoiceController::class, 'detailPdf'])->middleware('permission:sales.print')->name('sales.detail-pdf');
        Route::resource('sales', SalesInvoiceController::class)->only(['create','store'])->middleware('permission:sales.create');
        Route::resource('sales', SalesInvoiceController::class)->only(['edit','update'])->middleware('permission:sales.edit');
        Route::resource('sales', SalesInvoiceController::class)->only(['index','show']);
        Route::resource('sales-returns', SalesReturnController::class)->only(['create','store'])->middleware('permission:sales.create');
        Route::resource('sales-returns', SalesReturnController::class)->only(['edit','update'])->middleware('permission:sales.edit');
        Route::resource('sales-returns', SalesReturnController::class)->only(['index','show']);
    });
    Route::middleware('permission:estimates.view')->group(function () {
        Route::get('estimates/{estimate}/print', [EstimateController::class, 'print'])->middleware('permission:estimates.print')->name('estimates.print');
        Route::get('estimates/{estimate}/convert', [EstimateController::class, 'convertForm'])->middleware('permission:estimates.convert')->name('estimates.convert-form');
        Route::post('estimates/{estimate}/convert', [EstimateController::class, 'convert'])->middleware('permission:estimates.convert')->name('estimates.convert');
        Route::patch('estimates/{estimate}/cancel', [EstimateController::class, 'cancel'])->middleware('permission:estimates.edit')->name('estimates.cancel');
        Route::resource('estimates', EstimateController::class)->only(['create','store'])->middleware('permission:estimates.create');
        Route::resource('estimates', EstimateController::class)->only(['edit','update'])->middleware('permission:estimates.edit');
        Route::resource('estimates', EstimateController::class)->only(['destroy'])->middleware('permission:estimates.delete');
        Route::resource('estimates', EstimateController::class)->only(['index','show']);
    });
    Route::middleware('permission:delivery_challans.view')->group(function () {
        Route::get('delivery-challans/{deliveryChallan}/print', [DeliveryChallanController::class, 'print'])->middleware('permission:delivery_challans.print')->name('delivery-challans.print');
        Route::post('delivery-challans/{deliveryChallan}/convert', [DeliveryChallanController::class, 'convert'])->middleware('permission:delivery_challans.edit')->name('delivery-challans.convert');
        Route::patch('delivery-challans/{deliveryChallan}/cancel', [DeliveryChallanController::class, 'cancel'])->middleware('permission:delivery_challans.edit')->name('delivery-challans.cancel');
        Route::resource('delivery-challans', DeliveryChallanController::class)->only(['create','store'])->middleware('permission:delivery_challans.create');
        Route::resource('delivery-challans', DeliveryChallanController::class)->only(['edit','update'])->middleware('permission:delivery_challans.edit');
        Route::resource('delivery-challans', DeliveryChallanController::class)->only(['destroy'])->middleware('permission:delivery_challans.delete');
        Route::resource('delivery-challans', DeliveryChallanController::class)->only(['index','show']);
    });
    Route::middleware('permission:stock_out_challans.view')->group(function () {
        Route::get('stock-out-challans/{stockOutChallan}/print', [StockOutChallanController::class, 'print'])->middleware('permission:stock_out_challans.print')->name('stock-out-challans.print');
        Route::patch('stock-out-challans/{stockOutChallan}/cancel', [StockOutChallanController::class, 'cancel'])->middleware('permission:stock_out_challans.edit')->name('stock-out-challans.cancel');
        Route::resource('stock-out-challans', StockOutChallanController::class)->parameters(['stock-out-challans' => 'stockOutChallan'])->only(['create','store'])->middleware('permission:stock_out_challans.create');
        Route::resource('stock-out-challans', StockOutChallanController::class)->parameters(['stock-out-challans' => 'stockOutChallan'])->only(['edit','update'])->middleware('permission:stock_out_challans.edit');
        Route::resource('stock-out-challans', StockOutChallanController::class)->parameters(['stock-out-challans' => 'stockOutChallan'])->only(['destroy'])->middleware('permission:stock_out_challans.delete');
        Route::resource('stock-out-challans', StockOutChallanController::class)->parameters(['stock-out-challans' => 'stockOutChallan'])->only(['index','show']);
    });

    Route::middleware('permission:reports.gst')->group(function () {
        Route::get('reports/gst-1', [ReportController::class, 'gst1'])->name('reports.gst1');
        Route::get('reports/gst-2', [ReportController::class, 'gst2'])->name('reports.gst2');
        Route::get('reports/gst-3', [ReportController::class, 'gst3'])->name('reports.gst3');
    });
    Route::middleware('permission:reports.party')->group(function () {
        Route::get('reports/party-statement', [ReportController::class, 'partyStatement'])->name('reports.party-statement');
        Route::get('reports/party-profit-loss', [ReportController::class, 'partyProfitLoss'])->name('reports.party-profit-loss');
        Route::get('reports/all-parties', [ReportController::class, 'allParties'])->name('reports.all-parties');
        Route::get('reports/party-by-item', [ReportController::class, 'partyByItem'])->name('reports.party-by-item');
        Route::get('reports/sale-purchase-by-party', [ReportController::class, 'salePurchaseByParty'])->name('reports.sale-purchase-by-party');
    });
    Route::middleware('permission:reports.transaction')->group(function () {
        Route::get('reports/sales', [ReportController::class, 'salesReport'])->name('reports.sales');
        Route::get('reports/purchases', [ReportController::class, 'purchaseReport'])->name('reports.purchases');
        Route::get('reports/day-book', [ReportController::class, 'dayBook'])->name('reports.day-book');
        Route::get('reports/all-transactions', [ReportController::class, 'allTransactions'])->name('reports.all-transactions');
        Route::get('reports/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.profit-loss');
        Route::get('reports/bill-wise-profit', [ReportController::class, 'billWiseProfit'])->name('reports.bill-wise-profit');
        Route::get('reports/ageing', [ReportController::class, 'ageing'])->name('reports.ageing');
        Route::get('reports/ageing/party/{party}/print', [ReportController::class, 'ageingPartyPrint'])->name('reports.ageing.party-print');
        Route::get('reports/ageing/party/{party}/diagnosis', [ReportController::class, 'ageingPartyDiagnosis'])->name('reports.ageing.party-diagnosis');
        Route::get('reports/ageing/{kind}/{bill}/print', [ReportController::class, 'ageingBillPrint'])->name('reports.ageing.print');
        Route::get('reports/ageing/{kind}/{bill}/diagnosis', [ReportController::class, 'ageingBillDiagnosis'])->name('reports.ageing.diagnosis');
        Route::get('reports/balance-sheet', [ReportController::class, 'balanceSheet'])->name('reports.balance-sheet');
        Route::get('reports/item-trace', [ReportController::class, 'itemTrace'])->name('reports.item-trace');
    });
    // Stock Transfers (Inventory ke andar)
    Route::middleware('permission:stocks.view')->group(function () {
        Route::get('stock-transfers', [\App\Http\Controllers\Admin\Inventory\StockTransferController::class, 'index'])->name('stock-transfers.index');
        Route::get('stock-transfers/create', [\App\Http\Controllers\Admin\Inventory\StockTransferController::class, 'create'])->name('stock-transfers.create');
        Route::post('stock-transfers', [\App\Http\Controllers\Admin\Inventory\StockTransferController::class, 'store'])->name('stock-transfers.store');
        Route::get('stock-transfers/{stockTransfer}', [\App\Http\Controllers\Admin\Inventory\StockTransferController::class, 'show'])->name('stock-transfers.show');
        Route::post('stock-transfers/{stockTransfer}/approve', [\App\Http\Controllers\Admin\Inventory\StockTransferController::class, 'approve'])->name('stock-transfers.approve');
        Route::post('stock-transfers/{stockTransfer}/reject', [\App\Http\Controllers\Admin\Inventory\StockTransferController::class, 'reject'])->name('stock-transfers.reject');
        Route::get('stock-transfers-item-stock', [\App\Http\Controllers\Admin\Inventory\StockTransferController::class, 'itemStock'])->name('stock-transfers.item-stock');
    });

    // Company Merges (SuperAdmin only - existing companies group ke andar daalein)
    Route::middleware('super_admin')->group(function () {
        Route::get('company-merges', [\App\Http\Controllers\Admin\CompanyMergeController::class, 'index'])->name('company-merges.index');
        Route::post('company-merges', [\App\Http\Controllers\Admin\CompanyMergeController::class, 'store'])->name('company-merges.store');
        Route::delete('company-merges/{companyMerge}', [\App\Http\Controllers\Admin\CompanyMergeController::class, 'destroy'])->name('company-merges.destroy');
    });
    // ── User Management (admin + super_admin) ─────────────
    Route::middleware('company_admin')->group(function () {

        Route::resource('users', Admin\UserController::class)->only(['index'])->middleware('permission:users.view');
        Route::resource('users', Admin\UserController::class)->only(['create','store'])->middleware('permission:users.create');
        Route::resource('users', Admin\UserController::class)->only(['edit','update'])->middleware('permission:users.edit');
        Route::resource('users', Admin\UserController::class)->only(['destroy'])->middleware('permission:users.delete');
        Route::patch('users/{user}/toggle-status', [Admin\UserController::class, 'toggleStatus'])->middleware('permission:users.edit')->name('users.toggle-status');

        Route::resource('roles', Admin\RoleController::class)->only(['index'])->middleware('permission:roles.view');
        Route::resource('roles', Admin\RoleController::class)->only(['create','store'])->middleware('permission:roles.create');
        Route::resource('roles', Admin\RoleController::class)->only(['edit','update'])->middleware('permission:roles.edit');
        Route::resource('roles', Admin\RoleController::class)->only(['destroy'])->middleware('permission:roles.delete');

        // Permissions (super_admin only)
        Route::middleware('super_admin')->group(function () {
            Route::resource('permissions', Admin\PermissionController::class)->only(['index','create','store','destroy']);

            // Companies
            Route::resource('companies', Admin\CompanyController::class);
        });

        // Audit Logs
        Route::get('audit-logs', [Admin\AuditLogController::class, 'index'])->middleware('permission:audit.view')->name('audit-logs.index');
    });
});

// Old Breeze profile (keep for compatibility)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
