<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\SupplyPurchaseRequestController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\BudgetEarmarkController;
use App\Http\Controllers\CeoApprovalController;
use App\Http\Controllers\BacQuotationController;
use App\Http\Controllers\BacMeetingController;
use App\Http\Controllers\PurchaseOrderController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Purchase Requests
    Route::resource('purchase-requests', PurchaseRequestController::class)
        ->only(['index', 'create', 'store']);

    // Supply Officer - Purchase Requests Management
    Route::middleware('can:edit-purchase-request')->group(function () {
        Route::get('/supply/purchase-requests', [SupplyPurchaseRequestController::class, 'index'])->name('supply.purchase-requests.index');
        Route::put('/supply/purchase-requests/{purchaseRequest}/status', [SupplyPurchaseRequestController::class, 'updateStatus'])->name('supply.purchase-requests.status');

        // Purchase Orders
        Route::get('/supply/purchase-orders', [PurchaseOrderController::class, 'index'])->name('supply.purchase-orders.index');
        Route::get('/supply/purchase-requests/{purchaseRequest}/purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('supply.purchase-orders.create');
        Route::post('/supply/purchase-requests/{purchaseRequest}/purchase-orders', [PurchaseOrderController::class, 'store'])->name('supply.purchase-orders.store');
        Route::get('/supply/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('supply.purchase-orders.show');
        Route::put('/supply/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->name('supply.purchase-orders.show');
    });

    // Reports
    Route::middleware('can:view-reports')->group(function () {
        Route::get('/reports/purchase-requests', [ReportsController::class, 'pr'])->name('reports.pr');
        Route::get('/reports/purchase-requests/export', [ReportsController::class, 'prExport'])->name('reports.pr.export');
    });

    // Budget Office
    Route::middleware('role:Budget Office')->group(function () {
        Route::get('/budget/purchase-requests', [BudgetEarmarkController::class, 'index'])->name('budget.purchase-requests.index');
        Route::get('/budget/purchase-requests/{purchaseRequest}/edit', [BudgetEarmarkController::class, 'edit'])->name('budget.purchase-requests.edit');
        Route::put('/budget/purchase-requests/{purchaseRequest}', [BudgetEarmarkController::class, 'update'])->name('budget.purchase-requests.update');
    });

    // CEO Approval
    Route::middleware('role:Executive Officer')->group(function () {
        Route::get('/ceo/purchase-requests', [CeoApprovalController::class, 'index'])->name('ceo.purchase-requests.index');
        Route::get('/ceo/purchase-requests/{purchaseRequest}', [CeoApprovalController::class, 'show'])->name('ceo.purchase-requests.show');
        Route::put('/ceo/purchase-requests/{purchaseRequest}', [CeoApprovalController::class, 'update'])->name('ceo.purchase-requests.update');
    });

    // BAC Quotations & Meetings
    Route::middleware('role:BAC Chair|role:BAC Members|role:BAC Secretariat')->group(function () {
        Route::get('/bac/quotations', [BacQuotationController::class, 'index'])->name('bac.quotations.index');
        Route::get('/bac/quotations/{purchaseRequest}/manage', [BacQuotationController::class, 'manage'])->name('bac.quotations.manage');
        Route::post('/bac/quotations/{purchaseRequest}', [BacQuotationController::class, 'store'])->name('bac.quotations.store');
        Route::put('/bac/quotations/{quotation}/evaluate', [BacQuotationController::class, 'evaluate'])->name('bac.quotations.evaluate');
        Route::put('/bac/quotations/{purchaseRequest}/finalize', [BacQuotationController::class, 'finalize'])->name('bac.quotations.finalize');

        // Meetings
        Route::get('/bac/meetings', [BacMeetingController::class, 'index'])->name('bac.meetings.index');
        Route::get('/bac/meetings/create', [BacMeetingController::class, 'create'])->name('bac.meetings.create');
        Route::post('/bac/meetings', [BacMeetingController::class, 'store'])->name('bac.meetings.store');
        Route::get('/bac/meetings/{meeting}', [BacMeetingController::class, 'show'])->name('bac.meetings.show');
    });
});

require __DIR__.'/auth.php';
