<?php

namespace App\Http\Controllers\Hcm;

use App\Http\Controllers\Controller;
use App\Exports\HcmOvertimeBatchExport;
use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmMasterOption;
use App\Models\Hcm\HcmOvertime;
use App\Models\Hcm\HcmOvertimeBatch;
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

class HcmOvertimeController extends Controller
{
    /**
     * Dapatkan tarif lembur dinamis dari system settings.
     */
    public static function getDynamicRates(): array
    {
        return [
            'weekday_hourly_rate' => (float) SystemSetting::get('hcm_overtime', 'weekday_hourly_rate', 10000),
            'weekday_first_half_rate' => (float) SystemSetting::get('hcm_overtime', 'weekday_first_half_rate', 5000),
            'weekend_hourly_rate' => (float) SystemSetting::get('hcm_overtime', 'weekend_hourly_rate', 15000),
            'weekend_first_half_rate' => (float) SystemSetting::get('hcm_overtime', 'weekend_first_half_rate', 10000),
            'coa_code' => (string) SystemSetting::get('hcm_overtime', 'coa_code', '5-50100'),
            'coa_name' => (string) SystemSetting::get('hcm_overtime', 'coa_name', 'Beban Upah Lembur Karyawan Pabrik'),
        ];
    }

    /**
     * Kalkulasi upah lembur berdasarkan formula dinamis.
     */
    public static function calculateAmount(float $hours, string $dayType, array $rates): array
    {
        $isWeekend = ($dayType === 'Lembur Hari Libur');
        $hourlyRate = $isWeekend ? $rates['weekend_hourly_rate'] : $rates['weekday_hourly_rate'];
        $firstHalfRate = $isWeekend ? $rates['weekend_first_half_rate'] : $rates['weekday_first_half_rate'];

        if ($hours < 0.5) {
            // Durasi < 30 menit: tidak dihitung lembur (dibulatkan ke bawah).
            $totalAmount = 0;
        } elseif ($hours < 1) {
            // Durasi 30 s.d. < 60 menit: tarif 30 menit pertama (flat).
            $totalAmount = $firstHalfRate;
        } else {
            // Durasi >= 1 jam: proporsional per jam.
            $totalAmount = round($hours * $hourlyRate, 2);
        }

        return [
            'hourly_rate' => $hourlyRate,
            'first_half_rate' => $firstHalfRate,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * Halaman Utama: Daftar Batch Lembur Mingguan & Statistik.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('hcm.manage-overtime');

        $statusFilter = $request->query('status', 'all');
        $search = $request->query('search', '');
        $monthFilter = $request->query('month', 'all');
        $yearFilter = $request->query('year', 'all');

        $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $search);

        $batchesQuery = HcmOvertimeBatch::with([
            'hcmSigner:id,name',
            'financeSigner:id,name',
            'creator:id,name',
        ])
            ->when($escapedSearch, function ($q, $term) {
                $q->where('batch_code', 'like', "%{$term}%");
            })
            ->when($statusFilter !== 'all', fn ($q) => $q->where('status', $statusFilter))
            ->when($monthFilter !== 'all', function ($q) use ($monthFilter) {
                $q->where(function ($sub) use ($monthFilter) {
                    $sub->whereMonth('period_start', (int) $monthFilter)
                        ->orWhereMonth('period_end', (int) $monthFilter)
                        ->orWhereMonth('payout_date', (int) $monthFilter);
                });
            })
            ->when($yearFilter !== 'all', function ($q) use ($yearFilter) {
                $q->where(function ($sub) use ($yearFilter) {
                    $sub->whereYear('period_start', (int) $yearFilter)
                        ->orWhereYear('payout_date', (int) $yearFilter);
                });
            })
            ->orderBy('period_start', 'desc')
            ->orderBy('id', 'desc');

        $batches = $batchesQuery->paginate(12)->withQueryString();

        $rates = self::getDynamicRates();

        // Metrik Ringkasan
        $metrics = [
            'draft_batches' => HcmOvertimeBatch::where('status', 'DRAFT')->count(),
            'pending_finance_sign' => HcmOvertimeBatch::where('status', 'APPROVED_BY_HCM')->count(),
            'in_finance_process' => HcmOvertimeBatch::where('status', 'PENDING_FINANCE_SIGN')->count(),
            'paid_completed' => HcmOvertimeBatch::where('status', 'PAID_COMPLETED')->count(),
            'total_paid_amount' => HcmOvertimeBatch::where('status', 'PAID_COMPLETED')->sum('total_amount'),
        ];

        // Daftar tahun yang ada pada batch
        $years = HcmOvertimeBatch::select('period_start')
            ->get()
            ->map(fn ($b) => Carbon::parse($b->period_start)->format('Y'))
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        if (empty($years)) {
            $years = [(string) date('Y')];
        }

        return Inertia::render('Hcm/Overtime/Index', [
            'batches' => $batches,
            'filters' => [
                'status' => $statusFilter,
                'search' => $search,
                'month' => $monthFilter,
                'year' => $yearFilter,
            ],
            'availableYears' => $years,
            'rates' => $rates,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Tampilkan Detail Lembar Kerja Batch Lembur Mingguan.
     */
    public function show(HcmOvertimeBatch $batch): Response
    {
        Gate::authorize('hcm.manage-overtime');

        $batch->load([
            'overtimes.employee:id,employee_code,name,nickname,department,division',
            'overtimes.creator:id,name',
            'hcmSigner:id,name',
            'financeSigner:id,name',
            'creator:id,name',
        ]);

        $rates = self::getDynamicRates();

        // Ambil daftar karyawan aktif untuk Bulk Dispatcher
        $employees = HcmEmployee::where('is_active', true)
            ->select('id', 'employee_code', 'name', 'department', 'division')
            ->orderBy('department')
            ->orderBy('division')
            ->orderBy('name')
            ->get();

        // Master departemen
        $departments = HcmMasterOption::getOptions('departemen');
        if (empty($departments)) {
            $departments = HcmEmployee::whereNotNull('department')
                ->where('department', '!=', '')
                ->distinct()
                ->orderBy('department')
                ->pluck('department')
                ->all();
        }

        // Master divisi kerja
        $divisions = HcmMasterOption::getOptions('divisi');
        if (empty($divisions)) {
            $divisions = HcmEmployee::whereNotNull('division')
                ->where('division', '!=', '')
                ->distinct()
                ->orderBy('division')
                ->pluck('division')
                ->all();
        }

        return Inertia::render('Hcm/Overtime/Show', [
            'batch' => $batch,
            'rates' => $rates,
            'employees' => $employees,
            'divisions' => $divisions,
            'departments' => $departments,
        ]);
    }

    /**
     * Buat Batch Mingguan Baru (Cut-Off Sabtu - Jumat, Pencairan Sabtu).
     */
    public function storeBatch(Request $request): RedirectResponse
    {
        Gate::authorize('hcm.manage-overtime');

        $validated = $request->validate([
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date'],
            'payout_date' => ['required', 'date'],
        ]);

        // Cut-off dibakukan: Sabtu 00:00 s.d. Jumat 23:59, pencairan Sabtu berikutnya.
        $payout = Carbon::parse($validated['payout_date'])->startOfDay();
        if (!$payout->isSaturday()) {
            $payout = $payout->next('Saturday');
        }
        $periodEnd = $payout->copy()->subDay();          // Jumat
        $periodStart = $periodEnd->copy()->subDays(6);   // Sabtu pekan sebelumnya

        // Cegah dobel batch untuk periode cut-off yang sama persis
        $existing = HcmOvertimeBatch::where('period_start', $periodStart->toDateString())
            ->where('period_end', $periodEnd->toDateString())
            ->first();

        if ($existing) {
            return redirect()->route('hcm.overtime.show', $existing)->with('warning', "Batch lembur untuk periode {$periodStart->format('d/m/Y')} s.d. {$periodEnd->format('d/m/Y')} sudah ada dengan kode {$existing->batch_code}. Anda diarahkan ke lembar kerja tersebut.");
        }

        $year = $periodStart->format('Y');
        $weekNumber = $periodStart->weekOfYear;

        $batchCode = "OT-{$year}-W" . str_pad($weekNumber, 2, '0', STR_PAD_LEFT);

        // Jika batch code sudah ada, beri suffix sequence
        $count = HcmOvertimeBatch::where('batch_code', 'like', "{$batchCode}%")->count();
        if ($count > 0) {
            $batchCode .= '-' . ($count + 1);
        }

        $rates = self::getDynamicRates();

        $batch = HcmOvertimeBatch::create([
            'batch_code' => $batchCode,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'payout_date' => $payout->toDateString(),
            'status' => 'DRAFT',
            'coa_code' => $rates['coa_code'],
            'created_by' => Auth::id(),
        ]);

        ActivityLogger::log('create', 'hcm', $batch, "Membuat batch lembur baru: {$batchCode} ({$periodStart->toDateString()} s/d {$periodEnd->toDateString()})");

        return redirect()->route('hcm.overtime.show', $batch)->with('success', "Batch lembur mingguan {$batchCode} berhasil dibuat.");
    }

    /**
     * Perbarui Informasi Batch Mingguan (Hanya jika DRAFT).
     */
    public function updateBatch(Request $request, HcmOvertimeBatch $batch): RedirectResponse
    {
        Gate::authorize('hcm.manage-overtime');

        if ($batch->status !== 'DRAFT') {
            return redirect()->back()->with('error', 'Batch yang sudah ditandatangani atau dicairkan tidak dapat diubah.');
        }

        $validated = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'payout_date' => ['required', 'date'],
            'coa_code' => ['nullable', 'string', 'max:50'],
        ]);

        // Cek bentrok dengan batch lain
        $conflict = HcmOvertimeBatch::where('id', '!=', $batch->id)
            ->where('period_start', $validated['period_start'])
            ->where('period_end', $validated['period_end'])
            ->first();

        if ($conflict) {
            return redirect()->back()->with('error', "Periode cut-off tersebut bentrok dengan batch {$conflict->batch_code}.");
        }

        $batch->update([
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
            'payout_date' => $validated['payout_date'],
            'coa_code' => $validated['coa_code'] ?? $batch->coa_code,
        ]);

        ActivityLogger::log('update', 'hcm', $batch, "Memperbarui batch lembur: {$batch->batch_code}");

        return redirect()->back()->with('success', "Informasi batch {$batch->batch_code} berhasil diperbarui.");
    }

    /**
     * Hapus Batch Mingguan (Hanya jika DRAFT).
     */
    public function destroyBatch(HcmOvertimeBatch $batch): RedirectResponse
    {
        Gate::authorize('hcm.manage-overtime');

        if ($batch->status !== 'DRAFT') {
            return redirect()->back()->with('error', 'Hanya batch berstatus DRAF yang dapat dihapus.');
        }

        $batchCode = $batch->batch_code;

        DB::transaction(function () use ($batch) {
            $batch->overtimes()->delete();
            $batch->delete();
        });

        ActivityLogger::log('delete', 'hcm', $batch, "Menghapus batch lembur {$batchCode}");

        return redirect()->route('hcm.overtime.index')->with('success', "Batch lembur {$batchCode} beserta seluruh entri rinciannya berhasil dihapus.");
    }

    /**
     * Input / Dispatch Lembur Karyawan Massal ke dalam Batch (Bulk Overtime Dispatcher).
     */
    public function storeOvertimeItems(Request $request, HcmOvertimeBatch $batch): RedirectResponse
    {
        Gate::authorize('hcm.manage-overtime');

        if ($batch->status !== 'DRAFT') {
            return redirect()->back()->with('error', 'Batch ini telah ditandatangani dan terkunci.');
        }

        $validated = $request->validate([
            'overtime_date' => ['required', 'date'],
            'day_type' => ['required', 'string', 'in:Lembur Hari Kerja,Lembur Hari Libur'],
            'duration_hours' => ['required', 'numeric', 'gt:0', 'max:24'],
            'task_description' => ['nullable', 'string', 'max:500'],
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['required', 'exists:hcm_employees,id'],
        ]);

        $rates = self::getDynamicRates();
        $calc = self::calculateAmount((float) $validated['duration_hours'], $validated['day_type'], $rates);

        DB::transaction(function () use ($batch, $validated, $calc) {
            $employees = HcmEmployee::whereIn('id', $validated['employee_ids'])->get();

            foreach ($employees as $emp) {
                HcmOvertime::create([
                    'batch_id' => $batch->id,
                    'overtime_date' => $validated['overtime_date'],
                    'employee_id' => $emp->id,
                    'position' => $emp->division ?? $emp->department,
                    'day_type' => $validated['day_type'],
                    'duration_hours' => $validated['duration_hours'],
                    'hourly_rate' => $calc['hourly_rate'],
                    'first_half_rate' => $calc['first_half_rate'],
                    'total_amount' => $calc['total_amount'],
                    'task_description' => $validated['task_description'] ?? null,
                    'created_by' => Auth::id(),
                ]);
            }

            // Recalculate totals on batch
            $batch->update([
                'total_hours' => $batch->overtimes()->sum('duration_hours'),
                'total_amount' => $batch->overtimes()->sum('total_amount'),
            ]);
        });

        $totalAdded = count($validated['employee_ids']);
        ActivityLogger::log('create', 'hcm', $batch, "Input lembur massal {$totalAdded} karyawan pada batch {$batch->batch_code}");

        return redirect()->back()->with('success', "Berhasil menambahkan lembur untuk {$totalAdded} karyawan.");
    }

    /**
     * Perbarui satu item rincian lembur karyawan (Hanya jika DRAFT).
     */
    public function updateOvertimeItem(Request $request, HcmOvertimeBatch $batch, HcmOvertime $overtime): RedirectResponse
    {
        Gate::authorize('hcm.manage-overtime');

        if ($batch->status !== 'DRAFT') {
            return redirect()->back()->with('error', 'Batch ini telah dikunci.');
        }

        $validated = $request->validate([
            'overtime_date' => ['required', 'date'],
            'day_type' => ['required', 'string', 'in:Lembur Hari Kerja,Lembur Hari Libur'],
            'duration_hours' => ['required', 'numeric', 'gt:0', 'max:24'],
            'task_description' => ['nullable', 'string', 'max:500'],
        ]);

        $rates = self::getDynamicRates();
        $calc = self::calculateAmount((float) $validated['duration_hours'], $validated['day_type'], $rates);

        $overtime->update([
            'overtime_date' => $validated['overtime_date'],
            'day_type' => $validated['day_type'],
            'duration_hours' => $validated['duration_hours'],
            'hourly_rate' => $calc['hourly_rate'],
            'first_half_rate' => $calc['first_half_rate'],
            'total_amount' => $calc['total_amount'],
            'task_description' => $validated['task_description'] ?? null,
        ]);

        // Recalculate totals on batch
        $batch->update([
            'total_hours' => $batch->overtimes()->sum('duration_hours'),
            'total_amount' => $batch->overtimes()->sum('total_amount'),
        ]);

        ActivityLogger::log('update', 'hcm', $batch, "Memperbarui rincian lembur karyawan {$overtime->employee?->name} pada batch {$batch->batch_code}");

        return redirect()->back()->with('success', "Rincian lembur karyawan berhasil diperbarui.");
    }

    /**
     * Hapus satu item lembur dari batch (Hanya jika DRAFT).
     */
    public function destroyOvertimeItem(HcmOvertimeBatch $batch, HcmOvertime $overtime): RedirectResponse
    {
        Gate::authorize('hcm.manage-overtime');

        if ($batch->status !== 'DRAFT') {
            return redirect()->back()->with('error', 'Batch ini telah dikunci.');
        }

        $overtime->delete();

        // Recalculate
        $batch->update([
            'total_hours' => $batch->overtimes()->sum('duration_hours'),
            'total_amount' => $batch->overtimes()->sum('total_amount'),
        ]);

        return redirect()->back()->with('success', 'Rincian lembur karyawan berhasil dihapus.');
    }

    /**
     * Double Sign-Off Tahap 1: Persetujuan HCM (Gatekeeper Data Jam Lembur).
     */
    public function signHcm(HcmOvertimeBatch $batch): RedirectResponse
    {
        Gate::authorize('hcm.sign-overtime');

        if ($batch->status !== 'DRAFT') {
            return redirect()->back()->with('error', 'Batch ini sudah disetujui sebelumnya.');
        }

        if ($batch->overtimes()->count() === 0) {
            return redirect()->back()->with('error', 'Batch tidak boleh kosong saat disetujui.');
        }

        $batch->update([
            'status' => 'APPROVED_BY_HCM',
            'hcm_signed_by' => Auth::id(),
            'hcm_signed_at' => now(),
        ]);

        ActivityLogger::log('sign-off', 'hcm', $batch, "Verifikasi sign-off HCM batch lembur {$batch->batch_code}");

        IdealNotificationService::dispatch('hcm_overtime_ready_to_pay', [
            'title' => 'Rekap Lembur Siap Dicairkan',
            'body' => "Rekap Lembur {$batch->batch_code} periode " . Carbon::parse($batch->period_start)->isoFormat('D MMM') . ' s/d ' . Carbon::parse($batch->period_end)->isoFormat('D MMM Y') . ' telah disetujui HCM & siap dicairkan.',
            'action_url' => route('hcm.overtime.index'),
            'batch_code' => $batch->batch_code,
            'emoji' => '💸',
            'sound' => 'cash-register',
        ]);

        return redirect()->back()->with('success', 'Batch lembur berhasil ditandatangani oleh HCM dan diteruskan ke Tim Keuangan.');
    }

    /**
     * Double Sign-Off Tahap 3: Keuangan mulai memverifikasi kas (status PENDING_FINANCE_SIGN).
     */
    public function startFinance(HcmOvertimeBatch $batch): RedirectResponse
    {
        Gate::authorize('finance.sign-paid');

        if ($batch->status !== 'APPROVED_BY_HCM') {
            return redirect()->back()->with('error', 'Batch harus disetujui HCM terlebih dahulu.');
        }

        $batch->update(['status' => 'PENDING_FINANCE_SIGN']);

        ActivityLogger::log('sign-off', 'hcm', $batch, "Keuangan mulai memproses pembayaran batch lembur {$batch->batch_code}");

        return redirect()->back()->with('success', "Batch lembur {$batch->batch_code} masuk proses verifikasi kas Keuangan.");
    }

    /**
     * Double Sign-Off Tahap 4: Pembayaran & Eksekusi Keuangan (Gatekeeper Dana).
     */
    public function signFinance(Request $request, HcmOvertimeBatch $batch): RedirectResponse
    {
        Gate::authorize('finance.sign-paid');

        if (!in_array($batch->status, ['APPROVED_BY_HCM', 'PENDING_FINANCE_SIGN'], true)) {
            return redirect()->back()->with('error', 'Batch harus disetujui oleh HCM terlebih dahulu sebelum dibayarkan.');
        }

        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'in:Kas Tunai,Transfer Bank'],
            'coa_code' => ['nullable', 'string', 'max:50'],
            'finance_notes' => ['nullable', 'string', 'max:500'],
            'payout_proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'payout_proof_url' => ['nullable', 'string', 'max:255'],
        ]);

        $payoutProofUrl = $validated['payout_proof_url'] ?? null;
        if ($request->hasFile('payout_proof')) {
            $uploaded = \App\Services\GoogleDriveSyncService::uploadFile(
                $request->file('payout_proof'),
                \App\Services\GoogleDriveSyncService::FOLDER_DOCUMENTS
            );
            $payoutProofUrl = $uploaded['url'];
        }

        $batch->update([
            'status' => 'PAID_COMPLETED',
            'finance_signed_by' => Auth::id(),
            'finance_signed_at' => now(),
            'payment_method' => $validated['payment_method'],
            'coa_code' => $validated['coa_code'] ?? $batch->coa_code,
            'finance_notes' => $validated['finance_notes'] ?? null,
            'payout_proof_url' => $payoutProofUrl ?? $batch->payout_proof_url,
        ]);

        ActivityLogger::log('sign-off', 'hcm', $batch, "Pencairan lunas batch lembur {$batch->batch_code} oleh Keuangan ({$validated['payment_method']})");

        IdealNotificationService::dispatch('hcm_payout_completed', [
            'title' => 'Pencairan Kas Selesai',
            'body' => "Pencairan Lembur {$batch->batch_code} sebesar Rp " . number_format((float) $batch->total_amount, 0, ',', '.') . " telah berstatus Lunas (PAID_COMPLETED).",
            'action_url' => route('hcm.overtime.index'),
            'batch_code' => $batch->batch_code,
            'emoji' => '✅',
            'sound' => 'success-tada',
        ]);

        // Distribusi Slip Lembur Otomatis ke Email Karyawan (dengan Jeda Waktu Setor BRI)
        $autoSend = in_array(SystemSetting::get('hcm_payroll', 'auto_send_slip_email', '0'), ['1', 1, true, 'true'], true);
        $sendOvertime = in_array(SystemSetting::get('hcm_payroll', 'send_overtime_slip_email', '1'), ['1', 1, true, 'true'], true);
        $delayMinutes = (int) SystemSetting::get('hcm_payroll', 'slip_email_delay_minutes', 60);

        if ($autoSend && $sendOvertime) {
            $batch->load(['overtimes.employee']);
            $employeeIds = $batch->overtimes->pluck('employee_id')->filter()->unique();
            $dispatchedCount = 0;
            foreach ($employeeIds as $empId) {
                $employee = $batch->overtimes->firstWhere('employee_id', $empId)?->employee;
                if (!empty($employee?->email)) {
                    SendHcmSlipEmailJob::dispatch('overtime', $batch->id, $empId)->delay(now()->addMinutes($delayMinutes));
                    $dispatchedCount++;
                }
            }
            if ($dispatchedCount > 0) {
                ActivityLogger::log('email', 'hcm', $batch, "Menjadwalkan pengiriman {$dispatchedCount} slip lembur ke email karyawan dengan jeda {$delayMinutes} menit.");
            }
        }

        return redirect()->back()->with('success', "Pencairan lembur {$batch->batch_code} berhasil disetujui & dicatat lunas oleh Keuangan.");
    }

    /**
     * Export Excel rekap batch lembur mingguan.
     */
    public function exportBatchExcel(HcmOvertimeBatch $batch): BinaryFileResponse
    {
        Gate::authorize('hcm.manage-overtime');

        $batch->load(['overtimes.employee:id,employee_code,name,department,division']);

        $filename = 'Rekap_Lembur_' . $batch->batch_code . '_' . now()->format('Ymd') . '.xlsx';

        ActivityLogger::log('export', 'hcm', $batch, "Export Excel rekap lembur {$batch->batch_code}");

        return Excel::download(new HcmOvertimeBatchExport($batch, Auth::user()?->name), $filename);
    }

    /**
     * Perbarui Pengaturan Tarif Lembur Dinamis & Akun Akuntansi / COA.
     * Diselaraskan dan didelegasikan ke HcmSettingController agar terpusat satu pintu dan tidak dobel fungsi.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        Gate::authorize('hcm.manage-overtime');

        return app(HcmSettingController::class)->updateOvertime($request);
    }
}
