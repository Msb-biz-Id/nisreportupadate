<?php

namespace App\Http\Controllers\Hcm;

use App\Http\Controllers\Controller;
use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmMasterOption;
use App\Models\Hcm\HcmOvertime;
use App\Models\Hcm\HcmOvertimeBatch;
use App\Models\Settings\SystemSetting;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

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
            $totalAmount = 0;
        } elseif ($hours == 0.5) {
            $totalAmount = $firstHalfRate;
        } else {
            // Proporsional per jam
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
            ->orderBy('id', 'desc');

        $batches = $batchesQuery->paginate(12)->withQueryString();

        $rates = self::getDynamicRates();

        // Metrik Ringkasan
        $metrics = [
            'draft_batches' => HcmOvertimeBatch::where('status', 'DRAFT')->count(),
            'pending_finance_sign' => HcmOvertimeBatch::where('status', 'APPROVED_BY_HCM')->count(),
            'paid_completed' => HcmOvertimeBatch::where('status', 'PAID_COMPLETED')->count(),
            'total_paid_amount' => HcmOvertimeBatch::where('status', 'PAID_COMPLETED')->sum('total_amount'),
        ];

        return Inertia::render('Hcm/Overtime/Index', [
            'batches' => $batches,
            'filters' => [
                'status' => $statusFilter,
                'search' => $search,
            ],
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
            'overtimes.employee:id,employee_code,name,nickname,department,position',
            'overtimes.creator:id,name',
            'hcmSigner:id,name',
            'financeSigner:id,name',
            'creator:id,name',
        ]);

        $rates = self::getDynamicRates();

        // Ambil daftar karyawan aktif untuk Bulk Dispatcher
        $employees = HcmEmployee::where('is_active', true)
            ->select('id', 'employee_code', 'name', 'department', 'position')
            ->orderBy('department')
            ->orderBy('name')
            ->get();

        // Master divisi kerja
        $departments = HcmMasterOption::select('hcm_master_options.name')
            ->join('hcm_master_categories', 'hcm_master_options.category_id', '=', 'hcm_master_categories.id')
            ->where('hcm_master_categories.code', 'department')
            ->where('hcm_master_options.is_active', true)
            ->orderBy('hcm_master_options.order_index')
            ->pluck('name');

        return Inertia::render('Hcm/Overtime/Show', [
            'batch' => $batch,
            'rates' => $rates,
            'employees' => $employees,
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
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'payout_date' => ['required', 'date'],
        ]);

        $start = Carbon::parse($validated['period_start']);
        $year = $start->format('Y');
        $weekNumber = $start->weekOfYear;

        $batchCode = "OT-{$year}-W" . str_pad($weekNumber, 2, '0', STR_PAD_LEFT);

        // Jika batch code sudah ada, beri suffix sequence
        $count = HcmOvertimeBatch::where('batch_code', 'like', "{$batchCode}%")->count();
        if ($count > 0) {
            $batchCode .= '-' . ($count + 1);
        }

        $rates = self::getDynamicRates();

        $batch = HcmOvertimeBatch::create([
            'batch_code' => $batchCode,
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
            'payout_date' => $validated['payout_date'],
            'status' => 'DRAFT',
            'coa_code' => $rates['coa_code'],
            'created_by' => Auth::id(),
        ]);

        ActivityLogger::log('create', 'hcm', $batch, "Membuat batch lembur baru: {$batchCode}");

        return redirect()->route('hcm.overtime.show', $batch)->with('success', "Batch lembur mingguan {$batchCode} berhasil dibuat.");
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
            'duration_hours' => ['required', 'numeric', 'min:0.5', 'max:24'],
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
                    'position' => $emp->position,
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

        return redirect()->back()->with('success', 'Batch lembur berhasil ditandatangani oleh HCM dan diteruskan ke Tim Keuangan.');
    }

    /**
     * Double Sign-Off Tahap 2: Pembayaran & Eksekusi Keuangan (Gatekeeper Dana).
     */
    public function signFinance(Request $request, HcmOvertimeBatch $batch): RedirectResponse
    {
        Gate::authorize('finance.sign-paid');

        if ($batch->status !== 'APPROVED_BY_HCM') {
            return redirect()->back()->with('error', 'Batch harus disetujui oleh HCM terlebih dahulu sebelum dibayarkan.');
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

        ActivityLogger::log('sign-off', 'hcm', $batch, "Pencairan lunas batch lembur {$batch->batch_code} oleh Keuangan ({$validated['payment_method']})");

        return redirect()->back()->with('success', "Pencairan lembur {$batch->batch_code} berhasil disetujui & dicatat lunas oleh Keuangan.");
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
