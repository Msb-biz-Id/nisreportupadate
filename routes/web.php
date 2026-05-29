<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\BrandSwitchController;
use App\Http\Controllers\ComparisonController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Master\CustomerController;
use App\Http\Controllers\Master\MasterController;
use App\Http\Controllers\Master\RegionController;
use App\Http\Controllers\Order\InvoiceController;
use App\Http\Controllers\Order\OrderController;
use App\Http\Controllers\Order\ProductionController;
use App\Http\Controllers\Order\RefundController;
use App\Http\Controllers\Order\TrackingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Tools\AiToolsController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

// Public tracking PO + invoice (rate-limited)
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/track/{noPo}', [TrackingController::class, 'show'])->name('track.show');
    Route::get('/invoice/{invoiceNumber}', [InvoiceController::class, 'publicShow'])->name('invoice.public');
    Route::get('/invoice/{invoiceNumber}/pdf', [InvoiceController::class, 'publicPdf'])->name('invoice.public.pdf');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::post('/brand/switch/{brandId}', BrandSwitchController::class)->name('brand.switch');

    // Phase 1: Brand & User Management
    Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
    Route::post('/brands', [BrandController::class, 'store'])->name('brands.store');
    Route::put('/brands/{brand}', [BrandController::class, 'update'])->name('brands.update');
    Route::delete('/brands/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');
    Route::post('/brands/{brand}/toggle', [BrandController::class, 'toggle'])->name('brands.toggle');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // Phase 2: Master Data — Customer (dedicated)
    Route::get('/master/pelanggan', [CustomerController::class, 'index'])->name('master.pelanggan.index');
    Route::post('/master/pelanggan', [CustomerController::class, 'store'])->name('master.pelanggan.store');
    Route::put('/master/pelanggan/{customer}', [CustomerController::class, 'update'])->name('master.pelanggan.update');
    Route::delete('/master/pelanggan/{customer}', [CustomerController::class, 'destroy'])->name('master.pelanggan.destroy');

    // Phase 2: Region API
    Route::prefix('api/regions')->group(function () {
        Route::get('/provinces', [RegionController::class, 'provinces'])->name('regions.provinces');
        Route::get('/cities', [RegionController::class, 'cities'])->name('regions.cities');
        Route::get('/districts', [RegionController::class, 'districts'])->name('regions.districts');
        Route::get('/villages', [RegionController::class, 'villages'])->name('regions.villages');
    });

    // Phase 2: Master Data Generic
    Route::get('/master/{slug}', [MasterController::class, 'index'])->name('master.index');
    Route::post('/master/{slug}', [MasterController::class, 'store'])->name('master.store');
    Route::put('/master/{slug}/{id}', [MasterController::class, 'update'])->name('master.update');
    Route::delete('/master/{slug}/{id}', [MasterController::class, 'destroy'])->name('master.destroy');

    // Phase 3: Order Management
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/create', [OrderController::class, 'create'])->name('create');
        Route::post('/', [OrderController::class, 'store'])->name('store');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::get('/{order}/edit', [OrderController::class, 'edit'])->name('edit');
        Route::put('/{order}', [OrderController::class, 'update'])->name('update');
        Route::delete('/{order}', [OrderController::class, 'destroy'])->name('destroy');
        Route::post('/{order}/publish', [OrderController::class, 'publish'])->name('publish');
        Route::post('/{order}/repeat', [OrderController::class, 'repeat'])->name('repeat');
        Route::post('/{order}/unlock', [OrderController::class, 'unlock'])->name('unlock');
        Route::post('/{order}/relock', [OrderController::class, 'relock'])->name('relock');
        Route::post('/{order}/payments', [OrderController::class, 'addPayment'])->name('payments.store');
        Route::patch('/{order}/timeline', [OrderController::class, 'updateTimeline'])->name('timeline.update');
        Route::get('/{order}/spk.pdf', [OrderController::class, 'spkPdf'])->name('spk.pdf');
    });

    // Calendar
    Route::get('/kalender', [CalendarController::class, 'index'])->name('kalender.index');

    // Phase 3: Production
    Route::prefix('produksi')->name('produksi.')->group(function () {
        Route::get('/kanban', [ProductionController::class, 'kanban'])->name('kanban');
        Route::get('/gantt', [ProductionController::class, 'gantt'])->name('gantt');
        Route::get('/{order}/progress', [ProductionController::class, 'progress'])->name('progress');
        Route::put('/{order}/progress/{detail}', [ProductionController::class, 'updateProgress'])->name('progress.update');
        Route::post('/{order}/rijek', [ProductionController::class, 'storeRijek'])->name('rijek.store');
        Route::put('/{order}/move-status', [ProductionController::class, 'moveStatus'])->name('move-status');
    });

    // Phase 3: Finance — Invoice
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])->name('index');
        Route::post('/from-order/{order}', [InvoiceController::class, 'createFromOrder'])->name('create-from-order');
        Route::post('/{invoice}/validate', [InvoiceController::class, 'validateInvoice'])->name('validate');
        Route::post('/{invoice}/publish', [InvoiceController::class, 'publish'])->name('publish');
        Route::get('/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('pdf');
    });

    // Phase 5: Reports
    Route::prefix('laporan')->name('reports.')->group(function () {
        Route::get('/{slug}', [ReportController::class, 'show'])->name('show');
        Route::get('/{slug}/export/excel', [ReportController::class, 'exportExcel'])->name('export.excel');
        Route::get('/{slug}/export/pdf', [ReportController::class, 'exportPdf'])->name('export.pdf');
    });

    // Phase 3: Finance — Refund
    Route::prefix('refunds')->name('refunds.')->group(function () {
        Route::get('/', [RefundController::class, 'index'])->name('index');
        Route::post('/', [RefundController::class, 'store'])->name('store');
        Route::post('/{refund}/publish', [RefundController::class, 'publish'])->name('publish');
        Route::post('/{refund}/reject', [RefundController::class, 'reject'])->name('reject');
    });

    // Phase 6: AI Tools
    Route::prefix('tools/ai')->name('tools.ai.')->group(function () {
        Route::get('/', [AiToolsController::class, 'index'])->name('index');
        Route::get('/{slug}', [AiToolsController::class, 'show'])->name('show');
        Route::post('/{slug}/run', [AiToolsController::class, 'run'])->name('run');
    });

    // Phase 6: Settings & Integrations
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/integrasi', [SettingsController::class, 'index'])->name('integrasi');
        Route::put('/integrasi/ai', [SettingsController::class, 'updateAi'])->name('integrasi.ai');
        Route::put('/integrasi/whatsapp', [SettingsController::class, 'updateWhatsapp'])->name('integrasi.whatsapp');
        Route::put('/integrasi/telegram', [SettingsController::class, 'updateTelegram'])->name('integrasi.telegram');
        Route::put('/integrasi/system', [SettingsController::class, 'updateSystem'])->name('integrasi.system');
        Route::post('/integrasi/test/ai', [SettingsController::class, 'testAi'])->name('integrasi.test.ai');
        Route::post('/integrasi/test/whatsapp', [SettingsController::class, 'testWhatsapp'])->name('integrasi.test.whatsapp');
        Route::post('/integrasi/test/telegram', [SettingsController::class, 'testTelegram'])->name('integrasi.test.telegram');
    });

    // Polish A: Image upload
    Route::post('/uploads/image', [UploadController::class, 'image'])->name('uploads.image');
    Route::delete('/uploads/image', [UploadController::class, 'destroy'])->name('uploads.image.destroy');

    // Phase 5.1: Comparison Report
    Route::get('/laporan-comparison', [ComparisonController::class, 'show'])->name('comparison.show');

    // Phase 6.1: Audit Log
    Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 2FA Management (disabled)
    // Route::get('/two-factor/setup', [TwoFactorController::class, 'setup'])->name('two-factor.setup');
    // Route::post('/two-factor/enable', [TwoFactorController::class, 'enable'])->name('two-factor.enable');
    // Route::post('/two-factor/disable', [TwoFactorController::class, 'disable'])->name('two-factor.disable');
});

require __DIR__.'/auth.php';
