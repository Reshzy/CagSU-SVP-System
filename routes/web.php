<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\SupplyPurchaseRequestController;

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
    });
});

require __DIR__.'/auth.php';
