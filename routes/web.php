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
use App\Http\Controllers\Order\DesignDepositController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Tools\AiToolsController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\BrandTargetController;
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
    Route::get('/track', [TrackingController::class, 'index'])->name('track.index');
    Route::get('/track/{noPo}', [TrackingController::class, 'show'])->name('track.show');
    Route::get('/invoice/{invoiceNumber}', [InvoiceController::class, 'publicShow'])->name('invoice.public');
    Route::get('/invoice/{invoiceNumber}/pdf', [InvoiceController::class, 'publicPdf'])->name('invoice.public.pdf');
    Route::get('/fo/{noPo}', [OrderController::class, 'publicFoPreview'])->name('orders.public.fo.preview');
    Route::get('/fo/{noPo}/pdf', [OrderController::class, 'publicFoPdf'])->name('orders.public.fo.pdf');
});

// Webhook Sidobe — public endpoint, no auth, CSRF excluded via VerifyCsrfToken
Route::post('/webhooks/sidobe', [\App\Http\Controllers\WebhookController::class, 'sidobe'])
    ->name('webhooks.sidobe')
    ->middleware('throttle:300,1');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::post('/brand/switch/{brandId}', BrandSwitchController::class)->name('brand.switch');

    // Phase 1: Brand & User Management
    Route::get('/brands/import-template', [BrandController::class, 'downloadTemplate'])->name('brands.import-template');
    Route::post('/brands/import', [BrandController::class, 'import'])->name('brands.import');
    Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
    Route::post('/brands', [BrandController::class, 'store'])->name('brands.store');
    Route::put('/brands/{brand}', [BrandController::class, 'update'])->name('brands.update');
    Route::delete('/brands/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');
    Route::post('/brands/{brand}/toggle', [BrandController::class, 'toggle'])->name('brands.toggle');
    Route::post('/brands/{brand}/take-ownership', [BrandController::class, 'takeOwnership'])->name('brands.take-ownership');

    Route::get('/brand-targets', [BrandTargetController::class, 'index'])->name('brand-targets.index');
    Route::post('/brand-targets', [BrandTargetController::class, 'store'])->name('brand-targets.store');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [\App\Http\Controllers\RoleController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\RoleController::class, 'store'])->name('store');
        Route::put('/{role}', [\App\Http\Controllers\RoleController::class, 'update'])->name('update');
        Route::delete('/{role}', [\App\Http\Controllers\RoleController::class, 'destroy'])->name('destroy');
    });

    // Phase 2: Master Data — Customer (dedicated)
    Route::get('/master/pelanggan/import-template', [CustomerController::class, 'downloadTemplate'])->name('master.pelanggan.import-template');
    Route::post('/master/pelanggan/import', [CustomerController::class, 'import'])->name('master.pelanggan.import');
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
        Route::get('/export-comprehensive', [OrderController::class, 'exportComprehensive'])->name('export-comprehensive');
        Route::post('/', [OrderController::class, 'store'])->name('store');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::get('/{order}/edit', [OrderController::class, 'edit'])->name('edit');
        Route::put('/{order}', [OrderController::class, 'update'])->name('update');
        Route::delete('/{order}', [OrderController::class, 'destroy'])->name('destroy');
        Route::post('/{order}/publish', [OrderController::class, 'publish'])->name('publish');
        Route::post('/{order}/repeat', [OrderController::class, 'repeat'])->name('repeat');
        Route::post('/{order}/unlock', [OrderController::class, 'unlock'])->name('unlock');
        Route::post('/{order}/unlock/approve', [OrderController::class, 'approveUnlock'])->name('unlock.approve');
        Route::post('/{order}/unlock/reject', [OrderController::class, 'rejectUnlock'])->name('unlock.reject');
        Route::post('/{order}/relock', [OrderController::class, 'relock'])->name('relock');
        Route::post('/{order}/relock/approve', [OrderController::class, 'approveRelock'])->name('relock.approve');
        Route::post('/{order}/relock/reject', [OrderController::class, 'rejectRelock'])->name('relock.reject');
        Route::post('/{order}/payments', [OrderController::class, 'addPayment'])->name('payments.store');
        Route::patch('/{order}/timeline', [OrderController::class, 'updateTimeline'])->name('timeline.update');
        Route::post('/{order}/bypass-dp', [OrderController::class, 'bypassDp'])->name('bypass-dp');
        Route::post('/{order}/mark-lunas', [OrderController::class, 'markLunas'])->name('mark-lunas');
        Route::post('/{order}/complete', [OrderController::class, 'complete'])->name('complete');
        Route::post('/pdf-draft', [OrderController::class, 'draftPdf'])->name('pdf-draft');
        Route::get('/{order}/fo.pdf', [OrderController::class, 'foPdf'])->name('fo.pdf');
        Route::get('/{order}/fo/preview', [OrderController::class, 'foPreview'])->name('fo.preview');
    });

    // Calendar
    Route::get('/kalender', [CalendarController::class, 'index'])->name('kalender.index');

    // Phase 3: Production
    Route::prefix('produksi')->name('produksi.')->group(function () {
        Route::get('/kanban', [ProductionController::class, 'kanban'])->name('kanban');
        Route::get('/gantt', [ProductionController::class, 'gantt'])->name('gantt');
        Route::get('/{order}/progress', [ProductionController::class, 'progress'])->name('progress');
        Route::put('/{order}/progress/{detail}', [ProductionController::class, 'updateProgress'])->name('progress.update');
        Route::post('/{order}/progress/bulk', [ProductionController::class, 'bulkUpdateProgress'])->name('progress.bulk');
        Route::post('/{order}/rijek', [ProductionController::class, 'storeRijek'])->name('rijek.store');
        Route::put('/{order}/rijek/{rijek}', [ProductionController::class, 'updateRijek'])->name('rijek.update');
        Route::delete('/{order}/rijek/{rijek}', [ProductionController::class, 'destroyRijek'])->name('rijek.destroy');
        Route::put('/{order}/move-status', [ProductionController::class, 'moveStatus'])->name('move-status');
    });

    // Phase 3: Finance — Master Data Pembayaran
    Route::prefix('master-pembayaran')->name('master-pembayaran.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Finance\MasterJenisPembayaranController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Finance\MasterJenisPembayaranController::class, 'store'])->name('store');
        Route::put('/{master}', [\App\Http\Controllers\Finance\MasterJenisPembayaranController::class, 'update'])->name('update');
        Route::delete('/{master}', [\App\Http\Controllers\Finance\MasterJenisPembayaranController::class, 'destroy'])->name('destroy');
    });

    // Phase 3: Finance — Invoice
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])->name('index');
        Route::get('/list', [InvoiceController::class, 'list'])->name('list');
        Route::get('/payments/pending', [InvoiceController::class, 'paymentsPending'])->name('payments.pending');
        Route::post('/payments/{payment}/verify', [InvoiceController::class, 'verifyPayment'])->name('payments.verify');
        Route::put('/payments/{payment}', [InvoiceController::class, 'updatePayment'])->name('payments.update');
        Route::delete('/payments/{payment}', [InvoiceController::class, 'destroyPayment'])->name('payments.destroy');
        Route::post('/from-order/{order}', [InvoiceController::class, 'createFromOrder'])->name('create-from-order');
        Route::post('/{invoice}/validate', [InvoiceController::class, 'validateInvoice'])->name('validate');
        Route::post('/{invoice}/cancel-validation', [InvoiceController::class, 'cancelValidation'])->name('cancel-validation');
        Route::post('/{invoice}/publish', [InvoiceController::class, 'publish'])->name('publish');
        Route::get('/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('pdf');
        Route::post('/{invoice}/send-wa', [InvoiceController::class, 'sendWhatsapp'])->name('send-wa');
    });

    // Phase 3: Finance — Design Deposits (Tanda Jadi)
    Route::prefix('design-deposits')->name('design-deposits.')->group(function () {
        Route::post('/', [DesignDepositController::class, 'store'])->name('store');
        Route::post('/{deposit}/verify', [DesignDepositController::class, 'verify'])->name('verify');
        Route::post('/{deposit}/convert', [DesignDepositController::class, 'convertToOrder'])->name('convert');
        Route::post('/{deposit}/refund', [DesignDepositController::class, 'refund'])->name('refund');
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

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/integrasi', [SettingsController::class, 'index'])->name('integrasi');
        Route::get('/backup', [\App\Http\Controllers\BackupController::class, 'index'])->name('backup');
        Route::get('/backup/download', [\App\Http\Controllers\BackupController::class, 'download'])->name('backup.download');
        Route::post('/backup/cleanup', [\App\Http\Controllers\BackupController::class, 'cleanUp'])->name('backup.cleanup');
        Route::post('/backup/settings', [\App\Http\Controllers\BackupController::class, 'updateSettings'])->name('backup.settings');
        Route::post('/backup/run', [\App\Http\Controllers\BackupController::class, 'runBackup'])->name('backup.run');
        Route::get('/backup/gdrive/redirect', [\App\Http\Controllers\BackupController::class, 'redirectToGoogle'])->name('backup.gdrive.redirect');
        Route::get('/backup/gdrive/callback', [\App\Http\Controllers\BackupController::class, 'handleGoogleCallback'])->name('backup.gdrive.callback');
        Route::post('/backup/gdrive/disconnect', [\App\Http\Controllers\BackupController::class, 'disconnectGoogle'])->name('backup.gdrive.disconnect');
        Route::get('/notifikasi', [SettingsController::class, 'notifications'])->name('notifikasi');
        Route::put('/integrasi/ai', [SettingsController::class, 'updateAi'])->name('integrasi.ai');
        Route::put('/integrasi/whatsapp', [SettingsController::class, 'updateWhatsapp'])->name('integrasi.whatsapp');
        Route::put('/integrasi/telegram', [SettingsController::class, 'updateTelegram'])->name('integrasi.telegram');
        Route::put('/integrasi/system', [SettingsController::class, 'updateSystem'])->name('integrasi.system');
        Route::put('/integrasi/seo', [SettingsController::class, 'updateSeo'])->name('integrasi.seo');
        Route::put('/integrasi/reseller-branding', [SettingsController::class, 'updateResellerBranding'])->name('integrasi.reseller-branding');
        Route::put('/integrasi/mail', [SettingsController::class, 'updateMail'])->name('integrasi.mail');
        Route::put('/integrasi/matrix', [SettingsController::class, 'updateMatrix'])->name('integrasi.matrix');
        Route::post('/integrasi/test/ai', [SettingsController::class, 'testAi'])->name('integrasi.test.ai');
        Route::post('/integrasi/test/whatsapp', [SettingsController::class, 'testWhatsapp'])->name('integrasi.test.whatsapp');
        Route::post('/integrasi/test/telegram', [SettingsController::class, 'testTelegram'])->name('integrasi.test.telegram');
        Route::put('/integrasi/reports', [SettingsController::class, 'updateReports'])->name('integrasi.reports');
    });

    // Polish A: Image upload
    Route::post('/uploads/image', [UploadController::class, 'image'])->name('uploads.image');
    Route::delete('/uploads/image', [UploadController::class, 'destroy'])->name('uploads.image.destroy');

    // Phase 5.1: Comparison Report
    Route::get('/laporan-comparison', [ComparisonController::class, 'show'])->name('comparison.show');
    Route::get('/laporan-comparison/excel', [ComparisonController::class, 'exportExcel'])->name('comparison.export.excel');
    Route::get('/laporan-comparison/pdf', [ComparisonController::class, 'exportPdf'])->name('comparison.export.pdf');

    // Phase 6.1: Audit Log
    Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');

    // In-App Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
    });
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

require __DIR__ . '/auth.php';
