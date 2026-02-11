<?php

use App\Http\Controllers\AccountingDisbursementController;
use App\Http\Controllers\Api\BudgetCheckController;
use App\Http\Controllers\AppItemController;
use App\Http\Controllers\BacMeetingController;
use App\Http\Controllers\BacQuotationController;
use App\Http\Controllers\BudgetEarmarkController;
use App\Http\Controllers\BudgetManagementController;
use App\Http\Controllers\CeoApprovalController;
use App\Http\Controllers\CeoDepartmentController;
use App\Http\Controllers\CeoUserManagementController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\InventoryReceiptController;
use App\Http\Controllers\PpmpController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SupplierCommunicationController;
use App\Http\Controllers\SupplierPOStatusController;
use App\Http\Controllers\SupplierQuotationPublicController;
use App\Http\Controllers\SupplierRegistrationController;
use App\Http\Controllers\SupplyPurchaseRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
});
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'time' => now()->toIso8601String()]);
})->name('health');

// Public file access via controller (helps avoid web server 403 on symlinks)
Route::get('/files/{document}', [DocumentController::class, 'show'])->name('files.show');

// Public Supplier Registration
Route::get('/suppliers/register', [SupplierRegistrationController::class, 'create'])->name('suppliers.register');
Route::post('/suppliers/register', [SupplierRegistrationController::class, 'store'])->name('suppliers.register.store');
Route::get('/suppliers/quotations/submit', [SupplierQuotationPublicController::class, 'create'])->name('suppliers.quotations.submit');
Route::post('/suppliers/quotations/submit', [SupplierQuotationPublicController::class, 'store'])->name('suppliers.quotations.submit.store');
Route::get('/suppliers/po-status', [SupplierPOStatusController::class, 'show'])->name('suppliers.po-status');
Route::get('/suppliers/contact', [SupplierCommunicationController::class, 'create'])->name('suppliers.contact');
Route::post('/suppliers/contact', [SupplierCommunicationController::class, 'store'])->name('suppliers.contact.store');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Purchase Requests
    Route::resource('purchase-requests', PurchaseRequestController::class)
        ->only(['index', 'create', 'store', 'show']);

    // Replacement PR workflow (for returned PRs)
    Route::get('/purchase-requests/{originalPr}/replacement/create', [PurchaseRequestController::class, 'createReplacement'])
        ->name('purchase-requests.replacement.create');
    Route::post('/purchase-requests/{originalPr}/replacement', [PurchaseRequestController::class, 'storeReplacement'])
        ->name('purchase-requests.replacement.store');

    // PPMP Management (Department Users)
    Route::prefix('ppmp')->group(function () {
        Route::get('/', [PpmpController::class, 'index'])->name('ppmp.index');
        Route::get('/create', [PpmpController::class, 'create'])->name('ppmp.create');
        Route::post('/', [PpmpController::class, 'store'])->name('ppmp.store');
        Route::get('/{ppmp}/edit', [PpmpController::class, 'edit'])->name('ppmp.edit');
        Route::put('/{ppmp}', [PpmpController::class, 'update'])->name('ppmp.update');
        Route::post('/{ppmp}/validate', [PpmpController::class, 'validate'])->name('ppmp.validate');
        Route::get('/{ppmp}/summary', [PpmpController::class, 'summary'])->name('ppmp.summary');
    });

    // Supply Officer - Purchase Requests Management
    Route::middleware('can:edit-purchase-request')->group(function () {
        Route::get('/supply/purchase-requests', [SupplyPurchaseRequestController::class, 'index'])->name('supply.purchase-requests.index');
        Route::get('/supply/purchase-requests/{purchaseRequest}', [SupplyPurchaseRequestController::class, 'show'])->name('supply.purchase-requests.show');
        Route::put('/supply/purchase-requests/{purchaseRequest}/status', [SupplyPurchaseRequestController::class, 'updateStatus'])->name('supply.purchase-requests.status');

        // Purchase Orders
        Route::get('/supply/purchase-orders', [PurchaseOrderController::class, 'index'])->name('supply.purchase-orders.index');
        Route::get('/supply/purchase-requests/{purchaseRequest}/purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('supply.purchase-orders.create');
        Route::post('/supply/purchase-requests/{purchaseRequest}/purchase-orders', [PurchaseOrderController::class, 'store'])->name('supply.purchase-orders.store');
        Route::get('/supply/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('supply.purchase-orders.show');
        Route::put('/supply/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->name('supply.purchase-orders.update');
        Route::get('/supply/purchase-orders/{purchaseOrder}/export', [PurchaseOrderController::class, 'export'])->name('supply.purchase-orders.export');

        // PO Signatories Management
        Route::resource('supply/po-signatories', \App\Http\Controllers\PoSignatoryController::class)
            ->except(['show'])
            ->names('supply.po-signatories');

        // Inventory Receipts
        Route::get('/supply/inventory-receipts', [InventoryReceiptController::class, 'index'])->name('supply.inventory-receipts.index');
        Route::get('/supply/purchase-orders/{purchaseOrder}/inventory-receipts/create', [InventoryReceiptController::class, 'create'])->name('supply.inventory-receipts.create');
        Route::post('/supply/purchase-orders/{purchaseOrder}/inventory-receipts', [InventoryReceiptController::class, 'store'])->name('supply.inventory-receipts.store');
        Route::get('/supply/inventory-receipts/{inventoryReceipt}', [InventoryReceiptController::class, 'show'])->name('supply.inventory-receipts.show');

        // APP Management (Annual Procurement Plan)
        Route::get('/supply/app', [AppItemController::class, 'index'])->name('supply.app.index');
        Route::get('/supply/app/import', [AppItemController::class, 'import'])->name('supply.app.import');
        Route::post('/supply/app/import', [AppItemController::class, 'processImport'])->name('supply.app.process');
    });

    // Canvassing Unit - Supplier management
    Route::middleware('can:manage-suppliers')->group(function () {
        Route::get('/supply/suppliers', [SupplierRegistrationController::class, 'indexInternal'])->name('supply.suppliers.index');
        Route::get('/supply/suppliers/create', [SupplierRegistrationController::class, 'createInternal'])->name('supply.suppliers.create');
        Route::post('/supply/suppliers', [SupplierRegistrationController::class, 'storeInternal'])->name('supply.suppliers.store');
        Route::get('/supply/suppliers/{supplier}/edit', [SupplierRegistrationController::class, 'editInternal'])->name('supply.suppliers.edit');
        Route::put('/supply/suppliers/{supplier}', [SupplierRegistrationController::class, 'updateInternal'])->name('supply.suppliers.update');
    });

    // Executive Officer - Supplier approval
    Route::middleware('role:Executive Officer')->group(function () {
        Route::post('/supply/suppliers/{supplier}/approve', [SupplierRegistrationController::class, 'approveInternal'])->name('supply.suppliers.approve');
    });

    // Accounting Office
    Route::middleware('role:Accounting Office')->group(function () {
        Route::get('/accounting/vouchers', [AccountingDisbursementController::class, 'index'])->name('accounting.vouchers.index');
        Route::get('/accounting/purchase-orders/{purchaseOrder}/vouchers/create', [AccountingDisbursementController::class, 'create'])->name('accounting.vouchers.create');
        Route::post('/accounting/purchase-orders/{purchaseOrder}/vouchers', [AccountingDisbursementController::class, 'store'])->name('accounting.vouchers.store');
        Route::get('/accounting/vouchers/{voucher}', [AccountingDisbursementController::class, 'show'])->name('accounting.vouchers.show');
        Route::put('/accounting/vouchers/{voucher}', [AccountingDisbursementController::class, 'update'])->name('accounting.vouchers.update');
    });

    // Reports
    Route::middleware('can:view-reports')->group(function () {
        Route::get('/reports/purchase-requests', [ReportsController::class, 'pr'])->name('reports.pr');
        Route::get('/reports/purchase-requests/export', [ReportsController::class, 'prExport'])->name('reports.pr.export');
        Route::get('/reports/analytics', [ReportsController::class, 'analytics'])->name('reports.analytics');
        Route::get('/reports/suppliers', [ReportsController::class, 'suppliers'])->name('reports.suppliers');
        Route::get('/reports/suppliers/export', [ReportsController::class, 'suppliersExport'])->name('reports.suppliers.export');
        Route::get('/reports/budget', [ReportsController::class, 'budget'])->name('reports.budget');
        Route::get('/reports/budget/export', [ReportsController::class, 'budgetExport'])->name('reports.budget.export');
        Route::get('/reports/custom', [ReportsController::class, 'custom'])->name('reports.custom');
        Route::get('/reports/custom/export', [ReportsController::class, 'customExport'])->name('reports.custom.export');
    });

    // Budget Office
    Route::middleware('role:Budget Office')->group(function () {
        Route::get('/budget/purchase-requests', [BudgetEarmarkController::class, 'index'])->name('budget.purchase-requests.index');
        Route::get('/budget/purchase-requests/{purchaseRequest}/edit', [BudgetEarmarkController::class, 'edit'])->name('budget.purchase-requests.edit');
        Route::put('/budget/purchase-requests/{purchaseRequest}', [BudgetEarmarkController::class, 'update'])->name('budget.purchase-requests.update');
        Route::post('/budget/purchase-requests/{purchaseRequest}/reject', [BudgetEarmarkController::class, 'reject'])->name('budget.purchase-requests.reject');

        // Department Budget Management
        Route::get('/budget/departments', [BudgetManagementController::class, 'index'])->name('budget.index');
        Route::get('/budget/departments/{department}/edit', [BudgetManagementController::class, 'edit'])->name('budget.edit');
        Route::put('/budget/departments/{department}', [BudgetManagementController::class, 'update'])->name('budget.update');
        Route::get('/budget/departments/{department}', [BudgetManagementController::class, 'show'])->name('budget.show');
    });

    // Budget Check API (for all authenticated users)
    Route::prefix('api')->group(function () {
        Route::get('/budget/check', [BudgetCheckController::class, 'check'])->name('api.budget.check');
        Route::post('/budget/validate', [BudgetCheckController::class, 'validate'])->name('api.budget.validate');
    });

    // CEO Approval
    Route::middleware('role:Executive Officer')->group(function () {
        Route::get('/ceo/purchase-requests', [CeoApprovalController::class, 'index'])->name('ceo.purchase-requests.index');
        Route::get('/ceo/purchase-requests/{purchaseRequest}', [CeoApprovalController::class, 'show'])->name('ceo.purchase-requests.show');
        Route::put('/ceo/purchase-requests/{purchaseRequest}', [CeoApprovalController::class, 'update'])->name('ceo.purchase-requests.update');

        // CEO User Management
        Route::get('/ceo/users', [CeoUserManagementController::class, 'index'])->name('ceo.users.index');
        Route::get('/ceo/users/{user}', [CeoUserManagementController::class, 'show'])->name('ceo.users.show');
        Route::post('/ceo/users/{user}/approve', [CeoUserManagementController::class, 'approve'])->name('ceo.users.approve');
        Route::post('/ceo/users/{user}/reject', [CeoUserManagementController::class, 'reject'])->name('ceo.users.reject');

        // CEO Department Management
        Route::get('/ceo/departments', [CeoDepartmentController::class, 'index'])->name('ceo.departments.index');
        Route::get('/ceo/departments/create', [CeoDepartmentController::class, 'create'])->name('ceo.departments.create');
        Route::post('/ceo/departments', [CeoDepartmentController::class, 'store'])->name('ceo.departments.store');
        Route::get('/ceo/departments/{department}/edit', [CeoDepartmentController::class, 'edit'])->name('ceo.departments.edit');
        Route::put('/ceo/departments/{department}', [CeoDepartmentController::class, 'update'])->name('ceo.departments.update');
    });

    // BAC Signatory Management (Admin/BAC Chair only)
    Route::middleware('role:System Admin|BAC Chair')->prefix('bac/signatories')->name('bac.signatories.')->group(function () {
        Route::get('/', [App\Http\Controllers\BacSignatoryController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\BacSignatoryController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\BacSignatoryController::class, 'store'])->name('store');
        Route::get('/{signatory}/edit', [App\Http\Controllers\BacSignatoryController::class, 'edit'])->name('edit');
        Route::put('/{signatory}', [App\Http\Controllers\BacSignatoryController::class, 'update'])->name('update');
        Route::delete('/{signatory}', [App\Http\Controllers\BacSignatoryController::class, 'destroy'])->name('destroy');
    });

    // BAC Quotations & Meetings
    Route::middleware('role:BAC Chair|BAC Members|BAC Secretariat')->group(function () {
        // BAC Item Grouping
        Route::get('/bac/item-groups/{purchaseRequest}/create', [\App\Http\Controllers\BacItemGroupController::class, 'create'])->name('bac.item-groups.create');
        Route::post('/bac/item-groups/{purchaseRequest}', [\App\Http\Controllers\BacItemGroupController::class, 'store'])->name('bac.item-groups.store');
        Route::get('/bac/item-groups/{purchaseRequest}/edit', [\App\Http\Controllers\BacItemGroupController::class, 'edit'])->name('bac.item-groups.edit');
        Route::put('/bac/item-groups/{purchaseRequest}', [\App\Http\Controllers\BacItemGroupController::class, 'update'])->name('bac.item-groups.update');
        Route::delete('/bac/item-groups/{purchaseRequest}', [\App\Http\Controllers\BacItemGroupController::class, 'destroy'])->name('bac.item-groups.destroy');

        Route::get('/bac/quotations', [BacQuotationController::class, 'index'])->name('bac.quotations.index');
        Route::get('/bac/quotations/{purchaseRequest}/manage', [BacQuotationController::class, 'manage'])->name('bac.quotations.manage');
        Route::get('/bac/quotations/{purchaseRequest}/groups/{group}/quotations', [BacQuotationController::class, 'groupQuotationsPartial'])->name('bac.quotations.group-quotations');
        Route::post('/bac/quotations/{purchaseRequest}', [BacQuotationController::class, 'store'])->name('bac.quotations.store');
        Route::put('/bac/quotations/{quotation}/evaluate', [BacQuotationController::class, 'evaluate'])->name('bac.quotations.evaluate');
        Route::put('/bac/quotations/{purchaseRequest}/finalize', [BacQuotationController::class, 'finalize'])->name('bac.quotations.finalize');

        // BAC Resolution
        Route::get('/bac/quotations/{purchaseRequest}/resolution/download', [BacQuotationController::class, 'downloadResolution'])->name('bac.quotations.resolution.download');
        Route::post('/bac/quotations/{purchaseRequest}/resolution/regenerate', [BacQuotationController::class, 'regenerateResolution'])->name('bac.quotations.resolution.regenerate');

        // RFQ (Request for Quotation)
        Route::post('/bac/quotations/{purchaseRequest}/rfq/generate', [BacQuotationController::class, 'generateRfq'])->name('bac.quotations.rfq.generate');
        Route::get('/bac/quotations/{purchaseRequest}/rfq/download', [BacQuotationController::class, 'downloadRfq'])->name('bac.quotations.rfq.download');
        Route::post('/bac/quotations/{purchaseRequest}/rfq/regenerate', [BacQuotationController::class, 'regenerateRfq'])->name('bac.quotations.rfq.regenerate');

        // Group-based RFQ routes
        Route::post('/bac/item-groups/{itemGroup}/rfq/generate', [BacQuotationController::class, 'generateRfqForGroup'])->name('bac.item-groups.rfq.generate');
        Route::get('/bac/item-groups/{itemGroup}/rfq/download', [BacQuotationController::class, 'downloadRfqForGroup'])->name('bac.item-groups.rfq.download');
        Route::post('/bac/item-groups/{itemGroup}/rfq/regenerate', [BacQuotationController::class, 'regenerateRfqForGroup'])->name('bac.item-groups.rfq.regenerate');

        // AOQ (Abstract of Quotations)
        Route::get('/bac/quotations/{purchaseRequest}/aoq', [BacQuotationController::class, 'viewAoq'])->name('bac.quotations.aoq');
        Route::post('/bac/quotations/{purchaseRequest}/aoq/generate', [BacQuotationController::class, 'generateAoq'])->name('bac.quotations.aoq.generate');
        Route::get('/bac/quotations/{purchaseRequest}/aoq/{aoqGeneration}/download', [BacQuotationController::class, 'downloadAoq'])->name('bac.quotations.aoq.download');
        Route::post('/bac/quotations/{purchaseRequest}/aoq/resolve-tie', [BacQuotationController::class, 'resolveTie'])->name('bac.quotations.aoq.resolve-tie');
        Route::post('/bac/quotations/{purchaseRequest}/aoq/bac-override', [BacQuotationController::class, 'applyBacOverride'])->name('bac.quotations.aoq.bac-override');

        // Group-based AOQ routes
        Route::post('/bac/item-groups/{itemGroup}/aoq/generate', [BacQuotationController::class, 'generateAoqForGroup'])->name('bac.item-groups.aoq.generate');
        Route::get('/bac/item-groups/{itemGroup}/aoq/{aoqGeneration}/download', [BacQuotationController::class, 'downloadAoqForGroup'])->name('bac.item-groups.aoq.download');

        // Consolidated AOQ for grouped PRs
        Route::post('/bac/quotations/{purchaseRequest}/aoq/consolidated', [BacQuotationController::class, 'generateConsolidatedAoq'])->name('bac.quotations.aoq.consolidated');

        // Supplier Withdrawal
        Route::post('/bac/quotation-items/{quotationItem}/withdraw', [BacQuotationController::class, 'processWithdrawal'])->name('bac.quotation-items.withdraw');
        Route::get('/bac/quotation-items/{quotationItem}/withdrawal-preview', [BacQuotationController::class, 'withdrawalPreview'])->name('bac.quotation-items.withdrawal-preview');
        Route::get('/bac/quotations/{purchaseRequest}/withdrawal-history', [BacQuotationController::class, 'withdrawalHistory'])->name('bac.quotations.withdrawal-history');

        // Failed Procurement Handling
        Route::post('/bac/pr-items/{prItem}/mark-failed', [BacQuotationController::class, 'markItemFailed'])->name('bac.pr-items.mark-failed');
        Route::post('/bac/quotations/{purchaseRequest}/create-replacement-pr', [BacQuotationController::class, 'createReplacementPr'])->name('bac.quotations.create-replacement-pr');

        // Meetings
        Route::get('/bac/meetings', [BacMeetingController::class, 'index'])->name('bac.meetings.index');
        Route::get('/bac/meetings/create', [BacMeetingController::class, 'create'])->name('bac.meetings.create');
        Route::post('/bac/meetings', [BacMeetingController::class, 'store'])->name('bac.meetings.store');
        Route::get('/bac/meetings/{meeting}', [BacMeetingController::class, 'show'])->name('bac.meetings.show');
    });
});

require __DIR__.'/auth.php';
