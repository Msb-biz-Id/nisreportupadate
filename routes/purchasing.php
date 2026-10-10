<?php

use App\Http\Controllers\Purchasing\PurchasingMasterOptionController;
use App\Http\Controllers\Purchasing\PurchasingVendorController;
use Illuminate\Support\Facades\Route;

Route::prefix('purchasing')->name('purchasing.')->group(function () {
    // Default fallback index -> Dashboard
    Route::get('/', fn () => redirect()->route('purchasing.dashboard'))->name('index');

    // Dashboard & 5-Tier Alert Center
    Route::get('/dashboard', [\App\Http\Controllers\Purchasing\PurchasingDashboardController::class, 'index'])->name('dashboard');

    // Pembelian Operasional (Opex/Capex) & Double Sign-Off
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/history-search', [\App\Http\Controllers\Purchasing\PurchasingOrderController::class, 'history'])->name('history');
        Route::get('/', [\App\Http\Controllers\Purchasing\PurchasingOrderController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Purchasing\PurchasingOrderController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Purchasing\PurchasingOrderController::class, 'store'])->name('store');
        Route::get('/{order}', [\App\Http\Controllers\Purchasing\PurchasingOrderController::class, 'show'])->name('show');
        Route::get('/{order}/edit', [\App\Http\Controllers\Purchasing\PurchasingOrderController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{order}', [\App\Http\Controllers\Purchasing\PurchasingOrderController::class, 'update'])->name('update');
        Route::delete('/{order}', [\App\Http\Controllers\Purchasing\PurchasingOrderController::class, 'destroy'])->name('destroy');
        Route::post('/{order}/approve', [\App\Http\Controllers\Purchasing\PurchasingOrderController::class, 'approve'])->name('approve');
        Route::post('/{order}/payment-terms', [\App\Http\Controllers\Purchasing\PurchasingPaymentController::class, 'storeTerms'])->name('terms.store');
    });

    // Pencairan Termin Pembayaran (TOP) oleh Keuangan
    Route::post('/payments/{payment}/pay', [\App\Http\Controllers\Purchasing\PurchasingPaymentController::class, 'pay'])->name('payments.pay');

    // Master Data & Dropdowns Dinamis
    Route::prefix('master-data')->name('master-data.')->group(function () {
        Route::get('/', [PurchasingMasterOptionController::class, 'index'])->name('index');
        Route::post('/', [PurchasingMasterOptionController::class, 'store'])->name('store');
        Route::put('/{option}', [PurchasingMasterOptionController::class, 'update'])->name('update');
        Route::delete('/{option}', [PurchasingMasterOptionController::class, 'destroy'])->name('destroy');
        Route::post('/{option}/toggle', [PurchasingMasterOptionController::class, 'toggleActive'])->name('toggle');
    });

    // Direktori Vendor / Supplier & Multi-PIC
    Route::prefix('vendors')->name('vendors.')->group(function () {
        Route::get('/', [PurchasingVendorController::class, 'index'])->name('index');
        Route::post('/', [PurchasingVendorController::class, 'store'])->name('store');
        Route::put('/{vendor}', [PurchasingVendorController::class, 'update'])->name('update');
        Route::delete('/{vendor}', [PurchasingVendorController::class, 'destroy'])->name('destroy');
        Route::post('/{vendor}/contacts', [PurchasingVendorController::class, 'addContact'])->name('contacts.store');
        Route::delete('/{vendor}/contacts/{contact}', [PurchasingVendorController::class, 'deleteContact'])->name('contacts.destroy');
        Route::post('/{vendor}/contacts/{contact}/set-primary', [PurchasingVendorController::class, 'setPrimaryContact'])->name('contacts.set-primary');
    });

    // Manajemen Aset Tetap, Kodifikasi, Mutasi & Retirement
    Route::prefix('assets')->name('assets.')->group(function () {
        Route::get('/suggest-category', [\App\Http\Controllers\Purchasing\PurchasingAssetController::class, 'suggestCategory'])->name('suggest-category');
        Route::get('/', [\App\Http\Controllers\Purchasing\PurchasingAssetController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Purchasing\PurchasingAssetController::class, 'store'])->name('store');
        Route::get('/{asset}', [\App\Http\Controllers\Purchasing\PurchasingAssetController::class, 'show'])->name('show');
        Route::match(['put', 'post'], '/{asset}', [\App\Http\Controllers\Purchasing\PurchasingAssetController::class, 'update'])->name('update');
        Route::post('/{asset}/mutate', [\App\Http\Controllers\Purchasing\PurchasingAssetController::class, 'mutate'])->name('mutate');
        Route::post('/{asset}/retire', [\App\Http\Controllers\Purchasing\PurchasingAssetController::class, 'retire'])->name('retire');
    });
});
