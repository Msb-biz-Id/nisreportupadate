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
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Tools\AiToolsController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BrandTargetController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::redirect('/home', '/dashboard');

Route::get('/manifest.json', [\App\Http\Controllers\ManifestController::class, 'index'])->name('pwa.manifest');

// Public tracking PO + invoice (rate-limited)
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/track', [TrackingController::class, 'index'])->name('track.index');
    Route::get('/track/{noPo}', [TrackingController::class, 'show'])->name('track.show');
    Route::get('/invoice/{invoiceNumber}', [InvoiceController::class, 'publicShow'])->name('invoice.public');
    Route::get('/invoice/{invoiceNumber}/pdf', [InvoiceController::class, 'publicPdf'])->name('invoice.public.pdf');
    Route::get('/fo/{noPo}', [OrderController::class, 'publicFoPreview'])->name('orders.public.fo.preview');
    Route::get('/fo/{noPo}/pdf', [OrderController::class, 'publicFoPdf'])->name('orders.public.fo.pdf');

    // Portal Karir & Pendaftaran Pelamar Mandiri (Guest Route Publik)
    Route::get('/karir', [\App\Http\Controllers\Hcm\HcmPublicCareerController::class, 'index'])->name('career.index');
    Route::get('/karir/{slug}', [\App\Http\Controllers\Hcm\HcmPublicCareerController::class, 'show'])->name('career.show');
    Route::post('/karir/{slug}/apply', [\App\Http\Controllers\Hcm\HcmPublicCareerController::class, 'apply'])->name('career.apply');
});

// Webhook Sidobe — public endpoint, no auth, CSRF excluded via VerifyCsrfToken
Route::post('/webhooks/sidobe', [\App\Http\Controllers\WebhookController::class, 'sidobe'])
    ->name('webhooks.sidobe')
    ->middleware('throttle:300,1');

// Webhook Telegram — public endpoint, no auth, CSRF excluded
Route::post('/webhooks/telegram', [\App\Http\Controllers\WebhookController::class, 'telegram'])
    ->name('webhooks.telegram')
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
        Route::put('/report-visibility', [\App\Http\Controllers\RoleController::class, 'updateReportVisibility'])->name('report-visibility.update');
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
        Route::patch('/{order}/shipping', [OrderController::class, 'updateShipping'])->name('shipping.update');
        Route::post('/{order}/bypass-dp', [OrderController::class, 'bypassDp'])->name('bypass-dp');
        Route::post('/{order}/mark-lunas', [OrderController::class, 'markLunas'])->name('mark-lunas');
        Route::post('/{order}/complete', [OrderController::class, 'complete'])->name('complete');
        Route::post('/pdf-draft', [OrderController::class, 'draftPdf'])->name('pdf-draft');
        Route::get('/{order}/fo.pdf', [OrderController::class, 'foPdf'])->name('fo.pdf');
        Route::get('/{order}/fo/preview', [OrderController::class, 'foPreview'])->name('fo.preview');
        Route::get('/{order}/versions/compare', [OrderController::class, 'getVersionComparison'])->name('versions.compare');
    });

    // Calendar
    Route::get('/kalender', [CalendarController::class, 'index'])->name('kalender.index');

    // Phase 3: Production
    Route::prefix('produksi')->name('produksi.')->group(function () {
        Route::get('/kanban', [ProductionController::class, 'kanban'])->name('kanban');
        Route::get('/gantt', [ProductionController::class, 'gantt'])->name('gantt');
        Route::get('/{order}/progress', [ProductionController::class, 'progress'])->name('progress');
        Route::get('/progress/{order}', [ProductionController::class, 'progress'])->name('progress.legacy-notification-redirect');
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
        Route::get('/export-tsv', [InvoiceController::class, 'exportTsv'])->name('export-tsv');
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
        Route::get('/lookup-order', [RefundController::class, 'lookupOrder'])->name('lookup-order');
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
        Route::post('/integrasi/test/mail', [SettingsController::class, 'testMail'])->name('integrasi.test.mail');
        Route::post('/integrasi/test/reports', [SettingsController::class, 'testReports'])->name('integrasi.test.reports');
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

    // Modul Kepegawaian (HCM)
    Route::prefix('hcm')->name('hcm.')->group(function () {
        // Command Center / Dashboard Kepegawaian (Fase 6)
        Route::get('/', fn () => redirect()->route('hcm.dashboard.index'));
        Route::get('/dashboard', [\App\Http\Controllers\Hcm\HcmDashboardController::class, 'index'])->name('dashboard.index');

        Route::prefix('master-data')->name('master-data.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Hcm\HcmMasterDataController::class, 'index'])->name('index');
            Route::post('/categories/{category}/options', [\App\Http\Controllers\Hcm\HcmMasterDataController::class, 'storeOption'])->name('options.store');
            Route::put('/options/{option}', [\App\Http\Controllers\Hcm\HcmMasterDataController::class, 'updateOption'])->name('options.update');
            Route::post('/options/{option}/toggle', [\App\Http\Controllers\Hcm\HcmMasterDataController::class, 'toggleOption'])->name('options.toggle');
            Route::delete('/options/{option}', [\App\Http\Controllers\Hcm\HcmMasterDataController::class, 'destroyOption'])->name('options.destroy');
        });

        Route::prefix('employees')->name('employees.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Hcm\HcmEmployeeController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Hcm\HcmEmployeeController::class, 'store'])->name('store');
            Route::get('/{employee}', [\App\Http\Controllers\Hcm\HcmEmployeeController::class, 'show'])->name('show');
            Route::put('/{employee}', [\App\Http\Controllers\Hcm\HcmEmployeeController::class, 'update'])->name('update');
            Route::post('/{employee}/toggle', [\App\Http\Controllers\Hcm\HcmEmployeeController::class, 'toggle'])->name('toggle');
            Route::delete('/{employee}', [\App\Http\Controllers\Hcm\HcmEmployeeController::class, 'destroy'])->name('destroy');
            Route::post('/{employee}/contracts', [\App\Http\Controllers\Hcm\HcmEmployeeController::class, 'storeContract'])->name('contracts.store');
            Route::post('/{employee}/compensation-histories', [\App\Http\Controllers\Hcm\HcmEmployeeController::class, 'storeCompensationHistory'])->name('compensation-histories.store');
    Route::put('/{employee}/onboarding', [\App\Http\Controllers\Hcm\HcmEmployeeController::class, 'updateOnboarding'])->name('onboarding.update');
    Route::put('/{employee}/offboarding', [\App\Http\Controllers\Hcm\HcmEmployeeController::class, 'updateOffboarding'])->name('offboarding.update');
    Route::post('/{employee}/offboard', [\App\Http\Controllers\Hcm\HcmEmployeeController::class, 'offboard'])->name('offboard');
            Route::post('/{employee}/photo', [\App\Http\Controllers\Hcm\HcmEmployeeController::class, 'updatePhoto'])->name('photo.update');
            // PDF
            Route::get('/{employee}/pdf/dossier', [\App\Http\Controllers\Hcm\HcmPdfController::class, 'employeeDossier'])->name('pdf.dossier');
            Route::get('/{employee}/pdf/paklaring', [\App\Http\Controllers\Hcm\HcmPdfController::class, 'paklaring'])->name('pdf.paklaring');
        });

        // Kontrak & PKWT (Modul Khusus)
        Route::prefix('contracts')->name('contracts.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Hcm\HcmContractController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Hcm\HcmContractController::class, 'store'])->name('store');
            Route::put('/{contract}', [\App\Http\Controllers\Hcm\HcmContractController::class, 'update'])->name('update');
            Route::delete('/{contract}', [\App\Http\Controllers\Hcm\HcmContractController::class, 'destroy'])->name('destroy');
            Route::post('/{contract}/upload', [\App\Http\Controllers\Hcm\HcmContractController::class, 'uploadFile'])->name('upload');
        });

        // Kompensasi & Gaji (Modul Khusus)
        Route::prefix('compensations')->name('compensations.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Hcm\HcmCompensationController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Hcm\HcmCompensationController::class, 'store'])->name('store');
            Route::put('/{compensation}', [\App\Http\Controllers\Hcm\HcmCompensationController::class, 'update'])->name('update');
            Route::delete('/{compensation}', [\App\Http\Controllers\Hcm\HcmCompensationController::class, 'destroy'])->name('destroy');
            Route::post('/{compensation}/increment', [\App\Http\Controllers\Hcm\HcmCompensationController::class, 'storeIncrement'])->name('increment.store');
        });

        // Presensi & Absensi Harian & Matriks Bulanan
        Route::prefix('attendance')->name('attendance.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Hcm\HcmAttendanceController::class, 'index'])->name('index');
            Route::get('/monthly', [\App\Http\Controllers\Hcm\HcmAttendanceController::class, 'monthlyCalendar'])->name('monthly');
            Route::get('/export-monthly', [\App\Http\Controllers\Hcm\HcmAttendanceController::class, 'exportMonthly'])->name('export-monthly');
            Route::get('/export-daily', [\App\Http\Controllers\Hcm\HcmAttendanceController::class, 'exportDaily'])->name('export-daily');
            Route::get('/wa-summary', [\App\Http\Controllers\Hcm\HcmAttendanceController::class, 'waSummary'])->name('wa-summary');
            Route::post('/batch', [\App\Http\Controllers\Hcm\HcmAttendanceController::class, 'batchStore'])->name('batch-store');
            Route::post('/set-all-present', [\App\Http\Controllers\Hcm\HcmAttendanceController::class, 'setAllPresent'])->name('set-all-present');
            Route::post('/set-bulk', [\App\Http\Controllers\Hcm\HcmAttendanceController::class, 'setBulkAttendance'])->name('set-bulk');
            Route::post('/single-update', [\App\Http\Controllers\Hcm\HcmAttendanceController::class, 'singleUpdate'])->name('single-update');
        });

        // Pengajuan Cuti / Izin / Sakit
        Route::prefix('leaves')->name('leaves.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Hcm\HcmLeaveRequestController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Hcm\HcmLeaveRequestController::class, 'store'])->name('store');
            Route::post('/{leaveRequest}/approve', [\App\Http\Controllers\Hcm\HcmLeaveRequestController::class, 'approve'])->name('approve');
            Route::post('/{leaveRequest}/reject', [\App\Http\Controllers\Hcm\HcmLeaveRequestController::class, 'reject'])->name('reject');
            Route::delete('/{leaveRequest}', [\App\Http\Controllers\Hcm\HcmLeaveRequestController::class, 'destroy'])->name('destroy');
        });

        // Lembur Mingguan & Double Sign-Off Payout
        Route::prefix('overtime')->name('overtime.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Hcm\HcmOvertimeController::class, 'index'])->name('index');
            Route::post('/batches', [\App\Http\Controllers\Hcm\HcmOvertimeController::class, 'storeBatch'])->name('batches.store');
            Route::put('/batches/{batch}', [\App\Http\Controllers\Hcm\HcmOvertimeController::class, 'updateBatch'])->name('batches.update');
            Route::delete('/batches/{batch}', [\App\Http\Controllers\Hcm\HcmOvertimeController::class, 'destroyBatch'])->name('batches.destroy');
            Route::get('/batches/{batch}', [\App\Http\Controllers\Hcm\HcmOvertimeController::class, 'show'])->name('show');
            Route::post('/batches/{batch}/items', [\App\Http\Controllers\Hcm\HcmOvertimeController::class, 'storeOvertimeItems'])->name('items.store');
            Route::put('/batches/{batch}/items/{overtime}', [\App\Http\Controllers\Hcm\HcmOvertimeController::class, 'updateOvertimeItem'])->name('items.update');
            Route::delete('/batches/{batch}/items/{overtime}', [\App\Http\Controllers\Hcm\HcmOvertimeController::class, 'destroyOvertimeItem'])->name('items.destroy');
            Route::post('/batches/{batch}/sign-hcm', [\App\Http\Controllers\Hcm\HcmOvertimeController::class, 'signHcm'])->name('sign-hcm');
            Route::post('/batches/{batch}/start-finance', [\App\Http\Controllers\Hcm\HcmOvertimeController::class, 'startFinance'])->name('start-finance');
            Route::post('/batches/{batch}/sign-finance', [\App\Http\Controllers\Hcm\HcmOvertimeController::class, 'signFinance'])->name('sign-finance');
            Route::post('/settings', [\App\Http\Controllers\Hcm\HcmOvertimeController::class, 'updateSettings'])->name('settings.update');
            // PDF
            Route::get('/batches/{batch}/pdf', [\App\Http\Controllers\Hcm\HcmPdfController::class, 'overtimeVoucher'])->name('pdf');
            Route::get('/batches/{batch}/excel', [\App\Http\Controllers\Hcm\HcmOvertimeController::class, 'exportBatchExcel'])->name('export');
        });

        // Uang Makan Bulanan & Double Sign-Off
        Route::prefix('meal-allowance')->name('meal-allowance.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Hcm\HcmMealAllowanceController::class, 'index'])->name('index');
            Route::post('/generate', [\App\Http\Controllers\Hcm\HcmMealAllowanceController::class, 'generateBatch'])->name('generate');
            Route::get('/batches/{batch}', [\App\Http\Controllers\Hcm\HcmMealAllowanceController::class, 'show'])->name('show');
            Route::post('/batches/{batch}/sign-hcm', [\App\Http\Controllers\Hcm\HcmMealAllowanceController::class, 'signHcm'])->name('sign-hcm');
            Route::post('/batches/{batch}/start-finance', [\App\Http\Controllers\Hcm\HcmMealAllowanceController::class, 'startFinance'])->name('start-finance');
            Route::post('/batches/{batch}/sign-finance', [\App\Http\Controllers\Hcm\HcmMealAllowanceController::class, 'signFinance'])->name('sign-finance');
            Route::post('/settings', [\App\Http\Controllers\Hcm\HcmMealAllowanceController::class, 'updateSettings'])->name('settings.update');
            // PDF
            Route::get('/batches/{batch}/pdf', [\App\Http\Controllers\Hcm\HcmPdfController::class, 'mealAllowanceReport'])->name('pdf');
            Route::get('/batches/{batch}/excel', [\App\Http\Controllers\Hcm\HcmMealAllowanceController::class, 'exportBatchExcel'])->name('export');
        });

        // Apresiasi & Reward Karyawan
        Route::prefix('rewards')->name('rewards.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Hcm\HcmRewardController::class, 'index'])->name('index');
            Route::get('/export', [\App\Http\Controllers\Hcm\HcmRewardController::class, 'exportExcel'])->name('export');
            Route::post('/', [\App\Http\Controllers\Hcm\HcmRewardController::class, 'store'])->name('store');
            Route::put('/{reward}', [\App\Http\Controllers\Hcm\HcmRewardController::class, 'update'])->name('update');
            Route::patch('/{reward}/status', [\App\Http\Controllers\Hcm\HcmRewardController::class, 'updateStatus'])->name('update-status');
            Route::delete('/{reward}', [\App\Http\Controllers\Hcm\HcmRewardController::class, 'destroy'])->name('destroy');
        });

        // Kalender & Agenda Kegiatan Perusahaan (Modul Khusus)
        Route::prefix('events')->name('events.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Hcm\HcmEventController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Hcm\HcmEventController::class, 'store'])->name('store');
            Route::put('/{event}', [\App\Http\Controllers\Hcm\HcmEventController::class, 'update'])->name('update');
            Route::delete('/{event}', [\App\Http\Controllers\Hcm\HcmEventController::class, 'destroy'])->name('destroy');
        });

        // Modul Rekrutmen & Lowongan Kerja (Modul Khusus Terpisah)
        Route::prefix('recruitment')->name('recruitment.')->group(function () {
            Route::get('/jobs', [\App\Http\Controllers\Hcm\HcmRecruitmentController::class, 'jobs'])->name('jobs.index');
            Route::post('/jobs', [\App\Http\Controllers\Hcm\HcmRecruitmentController::class, 'storeJob'])->name('jobs.store');
            Route::put('/jobs/{job}', [\App\Http\Controllers\Hcm\HcmRecruitmentController::class, 'updateJob'])->name('jobs.update');
            Route::delete('/jobs/{job}', [\App\Http\Controllers\Hcm\HcmRecruitmentController::class, 'destroyJob'])->name('jobs.destroy');
            Route::get('/jobs/{job}/qr', [\App\Http\Controllers\Hcm\HcmRecruitmentController::class, 'jobQrCode'])->name('jobs.qr');

            Route::get('/applicants', [\App\Http\Controllers\Hcm\HcmRecruitmentController::class, 'applicants'])->name('applicants.index');
            Route::patch('/applicants/{applicant}/status', [\App\Http\Controllers\Hcm\HcmRecruitmentController::class, 'updateApplicantStatus'])->name('applicants.update-status');
            Route::post('/applicants/{applicant}/convert', [\App\Http\Controllers\Hcm\HcmRecruitmentController::class, 'convertApplicant'])->name('applicants.convert');

            // Modul 11.4: Rekap Hasil Wawancara Kandidat
            Route::post('/applicants/{applicant}/interviews', [\App\Http\Controllers\Hcm\HcmRecruitmentController::class, 'storeInterview'])->name('applicants.interviews.store');
            Route::put('/interviews/{interview}', [\App\Http\Controllers\Hcm\HcmRecruitmentController::class, 'updateInterview'])->name('interviews.update');
            Route::delete('/interviews/{interview}', [\App\Http\Controllers\Hcm\HcmRecruitmentController::class, 'destroyInterview'])->name('interviews.destroy');

            // Laporan Performa Rekrutmen per Loker + cetak PDF
            Route::get('/reports', [\App\Http\Controllers\Hcm\HcmRecruitmentController::class, 'reports'])->name('reports.index');
            Route::get('/reports/pdf', [\App\Http\Controllers\Hcm\HcmPdfController::class, 'recruitmentReport'])->name('reports.pdf');
            Route::get('/reports/excel', [\App\Http\Controllers\Hcm\HcmRecruitmentController::class, 'exportReport'])->name('reports.excel');
            Route::get('/applicants/{applicant}/pdf', [\App\Http\Controllers\Hcm\HcmPdfController::class, 'applicantReport'])->name('applicants.pdf');
            Route::get('/applicants/{applicant}/excel', [\App\Http\Controllers\Hcm\HcmRecruitmentController::class, 'exportApplicantExcel'])->name('applicants.excel');
        });

        // Modul Buku Agenda Persuratan (Modul Khusus Terpisah)
        Route::prefix('letters')->name('letters.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Hcm\HcmLetterController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Hcm\HcmLetterController::class, 'store'])->name('store');
            Route::put('/{letter}', [\App\Http\Controllers\Hcm\HcmLetterController::class, 'update'])->name('update');
            Route::delete('/{letter}', [\App\Http\Controllers\Hcm\HcmLetterController::class, 'destroy'])->name('destroy');
        });

        // Modul Dokumen Internal & SOP (Modul Khusus Terpisah)
        Route::prefix('documents')->name('documents.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Hcm\HcmDocumentController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Hcm\HcmDocumentController::class, 'store'])->name('store');
            Route::put('/{document}', [\App\Http\Controllers\Hcm\HcmDocumentController::class, 'update'])->name('update');
            Route::delete('/{document}', [\App\Http\Controllers\Hcm\HcmDocumentController::class, 'destroy'])->name('destroy');
        });

        // Modul 9: Arsip Korespondensi Eksternal (BPJS/Disnaker/Bank/Mitra)
        Route::prefix('external-letters')->name('external-letters.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Hcm\HcmExternalLetterController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Hcm\HcmExternalLetterController::class, 'store'])->name('store');
            Route::put('/{externalLetter}', [\App\Http\Controllers\Hcm\HcmExternalLetterController::class, 'update'])->name('update');
            Route::delete('/{externalLetter}', [\App\Http\Controllers\Hcm\HcmExternalLetterController::class, 'destroy'])->name('destroy');
        });

        // Modul Pengaturan HCM (Profil Instansi, Kop Surat, Medsos, Lembur Dinamis, & Uang Makan)
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Hcm\HcmSettingController::class, 'index'])->name('index');
            Route::post('/profile', [\App\Http\Controllers\Hcm\HcmSettingController::class, 'updateProfile'])->name('profile.update');
            Route::post('/overtime', [\App\Http\Controllers\Hcm\HcmSettingController::class, 'updateOvertime'])->name('overtime.update');
            Route::post('/meal-allowance', [\App\Http\Controllers\Hcm\HcmSettingController::class, 'updateMealAllowance'])->name('meal-allowance.update');
            Route::post('/storage', [\App\Http\Controllers\Hcm\HcmSettingController::class, 'updateStorage'])->name('storage.update');
            Route::post('/storage/test-connection', [\App\Http\Controllers\Hcm\HcmSettingController::class, 'testStorageConnection'])->name('storage.test-connection');
        });
    });

    // In-App Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/{notification}/verify', [NotificationController::class, 'verify'])->name('verify');
        Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::delete('/clear-all', [NotificationController::class, 'clearAll'])->name('clear-all');
        Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

});

require __DIR__ . '/auth.php';

// Fallback route untuk melayani file dari disk public jika symlink cPanel rusak atau tidak tersedia
Route::get('/storage/{path}', function (string $path) {
    $cleanPath = str_replace(['..', "\0"], '', $path);
    $cleanPath = ltrim($cleanPath, '/');

    /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
    $disk = \Illuminate\Support\Facades\Storage::disk('public');
    if ($disk->exists($cleanPath)) {
        return response()->file($disk->path($cleanPath));
    }

    abort(404);
})->where('path', '.*')->name('storage.fallback');
