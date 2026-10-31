<?php

namespace App\Http\Controllers\Hcm;

use App\Http\Controllers\Controller;
use App\Exports\HcmMealAllowanceBatchExport;
use App\Models\Hcm\HcmAttendance;
use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmMealAllowanceBatch;
use App\Models\Hcm\HcmMealAllowanceItem;
use App\Jobs\Hcm\SendHcmSlipEmailJob;
use App\Models\Settings\SystemSetting;
use App\Services\ActivityLogger;
use App\Services\Notifications\IdealNotificationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class HcmMealAllowanceController extends Controller
{
    /**
     * Dapatkan pengaturan dinamis uang makan.
     */
    public static function getDynamicSettings(): array
    {
        return [
            'monthly_rate' => (float) SystemSetting::get('hcm_meal_allowance', 'monthly_rate', 280000),
            'alpha_deduction_rate' => (float) SystemSetting::get('hcm_meal_allowance', 'alpha_deduction_rate', 14000),
            'half_day_deduction_rate' => (float) SystemSetting::get('hcm_meal_allowance', 'half_day_deduction_rate', 7000),
            'max_late_tolerance' => (int) SystemSetting::get('hcm_meal_allowance', 'max_late_tolerance', 3),
            'max_permit_bonus_limit' => (int) SystemSetting::get('hcm_meal_allowance', 'max_permit_bonus_limit', 2),
            'coa_code' => (string) SystemSetting::get('hcm_meal_allowance', 'coa_code', '5-50110'),
            'coa_name' => (string) SystemSetting::get('hcm_meal_allowance', 'coa_name', 'Beban Uang Makan Karyawan Pabrik'),
        ];
    }

    /**
     * Tampilkan daftar batch uang makan bulanan.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('hcm.manage-meal-allowance');

        $statusFilter = $request->query('status', 'all');
        $search = $request->query('search', '');

        $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $search);

        $batches = HcmMealAllowanceBatch::with([
            'hcmSigner:id,name',
            'financeSigner:id,name',
            'creator:id,name',
        ])
            ->when($escapedSearch, function ($q, $term) {
                $q->where('batch_code', 'like', "%{$term}%");
            })
            ->when($statusFilter !== 'all', fn ($q) => $q->where('status', $statusFilter))
            ->orderBy('period_year', 'desc')
            ->orderBy('period_month', 'desc')
            ->paginate(12)
            ->withQueryString();

        $settings = self::getDynamicSettings();

        $metrics = [
            'total_batches' => HcmMealAllowanceBatch::count(),
            'draft_batches' => HcmMealAllowanceBatch::where('status', 'DRAFT')->count(),
            'pending_finance' => HcmMealAllowanceBatch::where('status', 'APPROVED_BY_HCM')->count(),
            'paid_completed' => HcmMealAllowanceBatch::where('status', 'PAID_COMPLETED')->count(),
            'total_paid_amount' => HcmMealAllowanceBatch::where('status', 'PAID_COMPLETED')->sum('total_amount'),
        ];

        return Inertia::render('Hcm/MealAllowance/Index', [
            'batches' => $batches,
            'filters' => [
                'status' => $statusFilter,
                'search' => $search,
            ],
            'settings' => $settings,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Tampilkan lembar rekapitulasi batch uang makan.
     */
    public function show(HcmMealAllowanceBatch $batch): Response
    {
        Gate::authorize('hcm.manage-meal-allowance');

        $batch->load([
            'items.employee:id,employee_code,name,nickname,department,division',
            'hcmSigner:id,name',
            'financeSigner:id,name',
            'creator:id,name',
        ]);

        $settings = self::getDynamicSettings();

        return Inertia::render('Hcm/MealAllowance/Show', [
            'batch' => $batch,
            'settings' => $settings,
        ]);
    }

    /**
     * Generate rekapitulasi uang makan bulanan otomatis berdasarkan absensi riil.
     */
    public function generateBatch(Request $request): RedirectResponse
    {
        Gate::authorize('hcm.manage-meal-allowance');

        $validated = $request->validate([
            'period_month' => ['required', 'integer', 'between:1,12'],
            'period_year' => ['required', 'integer', 'between:2020,2035'],
            'payout_date' => ['required', 'date'],
        ]);

        $month = (int) $validated['period_month'];
        $year = (int) $validated['period_year'];
        $monthPadded = str_pad($month, 2, '0', STR_PAD_LEFT);
        $batchCode = "MA-{$year}-{$monthPadded}";

        // Cek apakah batch bulan ini sudah ada
        if (HcmMealAllowanceBatch::where('batch_code', $batchCode)->exists()) {
            return redirect()->back()->with('error', "Batch uang makan {$batchCode} sudah pernah dibuat sebelumnya.");
        }

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth()->format('Y-m-d');
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Y-m-d');

        $settings = self::getDynamicSettings();
        $baseAllowance = $settings['monthly_rate'];
        $alphaDeductionRate = $settings['alpha_deduction_rate'];
        $halfDayDeductionRate = $settings['half_day_deduction_rate'];
        $maxLateTolerance = $settings['max_late_tolerance'];
        $maxPermitBonusLimit = $settings['max_permit_bonus_limit'];

        $batch = null;
        DB::transaction(function () use (
            $batchCode, $month, $year, $startDate, $endDate, $validated,
            $baseAllowance, $alphaDeductionRate, $halfDayDeductionRate,
            $maxLateTolerance, $maxPermitBonusLimit, $settings, &$batch
        ) {
            $batch = HcmMealAllowanceBatch::create([
                'batch_code' => $batchCode,
                'period_month' => $month,
                'period_year' => $year,
                'period_start' => $startDate,
                'period_end' => $endDate,
                'payout_date' => $validated['payout_date'],
                'status' => 'DRAFT',
                'coa_code' => $settings['coa_code'],
                'created_by' => Auth::id(),
            ]);

            // Ambil semua karyawan aktif
            $employees = HcmEmployee::where('is_active', true)->get();

            // Ambil seluruh log absensi pada bulan ini dalam 1 indexed query (Zero N+1)
            $attendancesGroup = HcmAttendance::whereBetween('attendance_date', [$startDate, $endDate])
                ->get()
                ->groupBy('employee_id');

            // Cek batch bulan sebelumnya untuk mengambil sisa hold (jika ada)
            $prevMonthDate = Carbon::createFromDate($year, $month, 1)->subMonth();
            $prevBatch = HcmMealAllowanceBatch::where('period_year', $prevMonthDate->year)
                ->where('period_month', $prevMonthDate->month)
                ->first();

            $prevHeldItems = $prevBatch
                ? $prevBatch->items()->where('is_hold', true)->pluck('payable_amount', 'employee_id')
                : collect();

            $totalBatchPayable = 0;
            $totalBatchHeld = 0;

            foreach ($employees as $emp) {
                $logs = $attendancesGroup->get($emp->id, collect());

                $presentCount = $logs->where('attendance_category', 'Hadir')->count();
                $lateCount = $logs->where('attendance_category', 'Terlambat')->count();
                $halfDayCount = $logs->where('attendance_category', 'Pulang Cepat')->count();
                $alphaCount = $logs->where('attendance_category', 'Alpha/Mangkir')->count();
                $leaveCount = $logs->whereIn('attendance_category', ['Cuti', 'Sakit', 'Dinas Luar', 'Libur/Cuti Bersama'])->count();
                $permitCount = $logs->where('attendance_category', 'Izin')->count();

                // Hitung potongan (Hanya Alpha & Setengah Hari yang memotong uang makan)
                $deduction = ($alphaCount * $alphaDeductionRate) + ($halfDayCount * $halfDayDeductionRate);

                // Cek sanksi izin: Izin > 2x dalam sebulan membatalkan hak bonus bulanan
                $bonusEligible = ($permitCount <= $maxPermitBonusLimit);

                // Cek apakah ada dana hold dari bulan lalu
                $prevHold = (float) $prevHeldItems->get($emp->id, 0);

                // Cek Aturan Sanksi Keterlambatan: Telat >= 4x = HOLD
                $isHold = ($lateCount > $maxLateTolerance);

                $calculatedCurrentMonth = max(0, $baseAllowance - $deduction);

                if ($isHold) {
                    // Ditahan bulan ini
                    $payable = 0;
                    $totalBatchHeld += ($calculatedCurrentMonth + $prevHold);
                    $notes = "DITANGGUHKAN (HOLD): Terlambat {$lateCount}x (Toleransi maks {$maxLateTolerance}x). Dicairkan bulan berikutnya jika disiplin.";
                } else {
                    // Dibayarkan (termasuk hold bulan lalu jika ada)
                    $payable = $calculatedCurrentMonth + $prevHold;
                    $totalBatchPayable += $payable;
                    $notes = $prevHold > 0
                        ? "Pencairan dobel (termasuk dana hold bulan lalu Rp " . number_format($prevHold, 0, ',', '.') . ")."
                        : "Normal.";
                }

                if (!$bonusEligible) {
                    $notes .= " [Sanksi Izin > {$maxPermitBonusLimit}x: Tidak berhak bonus bulanan]";
                }

                HcmMealAllowanceItem::create([
                    'batch_id' => $batch->id,
                    'employee_id' => $emp->id,
                    'position' => $emp->division ?: $emp->position,
                    'department' => $emp->department,
                    'base_allowance' => $baseAllowance,
                    'present_days' => $presentCount,
                    'late_days' => $lateCount,
                    'half_days' => $halfDayCount,
                    'alpha_days' => $alphaCount,
                    'leave_days' => $leaveCount,
                    'permit_days' => $permitCount,
                    'deduction_amount' => $deduction,
                    'previous_hold_amount' => $prevHold,
                    'payable_amount' => $isHold ? ($calculatedCurrentMonth + $prevHold) : $payable,
                    'is_hold' => $isHold,
                    'bonus_eligible' => $bonusEligible,
                    'notes' => $notes,
                ]);
            }

            $batch->update([
                'total_employees' => $employees->count(),
                'total_amount' => $totalBatchPayable,
                'total_held_amount' => $totalBatchHeld,
            ]);
        });

        if ($batch) {
            ActivityLogger::log('generate', 'hcm', $batch, "Generate batch uang makan {$batchCode} untuk {$batch->total_employees} karyawan (Total: Rp " . number_format((float) ($batch->total_amount ?? 0), 0, ',', '.') . ")");
        }

        return redirect()->route('hcm.meal-allowance.index')->with('success', "Batch rekapitulasi uang makan {$batchCode} berhasil digenerate.");
    }

    /**
     * Double Sign-Off Tahap 1: Otorisasi HCM (Audit Hari Kehadiran).
     */
    public function signHcm(HcmMealAllowanceBatch $batch): RedirectResponse
    {
        Gate::authorize('hcm.sign-meal-allowance');

        if ($batch->status !== 'DRAFT') {
            return redirect()->back()->with('error', 'Batch ini sudah disetujui sebelumnya.');
        }

        $batch->update([
            'status' => 'APPROVED_BY_HCM',
            'hcm_signed_by' => Auth::id(),
            'hcm_signed_at' => now(),
        ]);

        ActivityLogger::log('sign-off', 'hcm', $batch, "Verifikasi sign-off HCM batch uang makan {$batch->batch_code}");

        IdealNotificationService::dispatch('hcm_meal_allowance_ready_to_pay', [
            'title' => 'Rekap Uang Makan Siap Dibayarkan',
            'body' => "Rekap Uang Makan periode " . Carbon::create()->month((int) $batch->period_month)->isoFormat('MMMM') . " {$batch->period_year} telah disahkan HCM & siap diproses.",
            'action_url' => route('hcm.meal-allowance.index'),
            'batch_code' => $batch->batch_code,
            'emoji' => '🍽️',
            'sound' => 'cash-register',
        ]);

        return redirect()->back()->with('success', 'Batch uang makan berhasil diverifikasi oleh HCM dan diteruskan ke Tim Keuangan.');
    }

    /**
     * Double Sign-Off Tahap 3: Keuangan mulai memverifikasi kas (PENDING_FINANCE_SIGN).
     */
    public function startFinance(HcmMealAllowanceBatch $batch): RedirectResponse
    {
        Gate::authorize('finance.sign-paid');

        if ($batch->status !== 'APPROVED_BY_HCM') {
            return redirect()->back()->with('error', 'Batch harus disetujui HCM terlebih dahulu.');
        }

        $batch->update(['status' => 'PENDING_FINANCE_SIGN']);

        ActivityLogger::log('sign-off', 'hcm', $batch, "Keuangan mulai memproses pembayaran batch uang makan {$batch->batch_code}");

        return redirect()->back()->with('success', "Batch uang makan {$batch->batch_code} masuk proses verifikasi kas Keuangan.");
    }

    /**
     * Double Sign-Off Tahap 4: Otorisasi Pembayaran Keuangan.
     */
    public function signFinance(Request $request, HcmMealAllowanceBatch $batch): RedirectResponse
    {
        Gate::authorize('finance.sign-paid');

        if (!in_array($batch->status, ['APPROVED_BY_HCM', 'PENDING_FINANCE_SIGN'], true)) {
            return redirect()->back()->with('error', 'Batch harus disetujui oleh HCM terlebih dahulu sebelum dicairkan.');
        }

        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'in:Kas Tunai,Transfer Bank'],
            'coa_code' => ['nullable', 'string', 'max:50'],
            'finance_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $batch->update([
            'status' => 'PAID_COMPLETED',
            'finance_signed_by' => Auth::id(),
            'finance_signed_at' => now(),
            'payment_method' => $validated['payment_method'],
            'coa_code' => $validated['coa_code'] ?? $batch->coa_code,
            'finance_notes' => $validated['finance_notes'] ?? null,
        ]);

        ActivityLogger::log('sign-off', 'hcm', $batch, "Pencairan lunas batch uang makan {$batch->batch_code} oleh Keuangan ({$validated['payment_method']})");

        IdealNotificationService::dispatch('hcm_payout_completed', [
            'title' => 'Pencairan Kas Selesai',
            'body' => "Pencairan Uang Makan {$batch->batch_code} sebesar Rp " . number_format((float) $batch->total_amount, 0, ',', '.') . " telah berstatus Lunas (PAID_COMPLETED).",
            'action_url' => route('hcm.meal-allowance.index'),
            'batch_code' => $batch->batch_code,
            'emoji' => '✅',
            'sound' => 'success-tada',
        ]);

        // Distribusi Slip Uang Makan Otomatis ke Email Karyawan (dengan Jeda Waktu Setor BRI)
        $autoSend = in_array(SystemSetting::get('hcm_payroll', 'auto_send_slip_email', '0'), ['1', 1, true, 'true'], true);
        $sendMeal = in_array(SystemSetting::get('hcm_payroll', 'send_meal_slip_email', '1'), ['1', 1, true, 'true'], true);
        $delayMinutes = (int) SystemSetting::get('hcm_payroll', 'slip_email_delay_minutes', 60);

        if ($autoSend && $sendMeal) {
            $batch->load(['items.employee']);
            $dispatchedCount = 0;
            foreach ($batch->items as $item) {
                if (!empty($item->employee?->email)) {
                    SendHcmSlipEmailJob::dispatch('meal_allowance', $item->id)->delay(now()->addMinutes($delayMinutes));
                    $dispatchedCount++;
                }
            }
            if ($dispatchedCount > 0) {
                ActivityLogger::log('email', 'hcm', $batch, "Menjadwalkan pengiriman {$dispatchedCount} slip uang makan ke email karyawan dengan jeda {$delayMinutes} menit.");
            }
        }

        return redirect()->back()->with('success', "Pencairan uang makan {$batch->batch_code} berhasil disetujui & dicatat lunas oleh Keuangan.");
    }

    /**
     * Export Excel rekap batch uang makan bulanan.
     */
    public function exportBatchExcel(HcmMealAllowanceBatch $batch): BinaryFileResponse
    {
        Gate::authorize('hcm.manage-meal-allowance');

        $batch->load(['items.employee:id,employee_code,name,department']);

        $filename = 'Rekap_Uang_Makan_' . $batch->batch_code . '_' . now()->format('Ymd') . '.xlsx';

        ActivityLogger::log('export', 'hcm', $batch, "Export Excel rekap uang makan {$batch->batch_code}");

        return Excel::download(new HcmMealAllowanceBatchExport($batch, Auth::user()?->name), $filename);
    }

    /**
     * Perbarui Pengaturan Uang Makan & COA Akuntansi.
     * Diselaraskan dan didelegasikan ke HcmSettingController agar terpusat satu pintu dan tidak dobel fungsi.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        Gate::authorize('hcm.manage-meal-allowance');

        return app(HcmSettingController::class)->updateMealAllowance($request);
    }
}
