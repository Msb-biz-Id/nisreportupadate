<?php

namespace App\Console\Commands;

use App\Models\Hcm\HcmAttendance;
use App\Models\Hcm\HcmContract;
use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmIntern;
use App\Models\Hcm\HcmLeaveRequest;
use App\Services\Notifications\IdealNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class HcmDailyAlerts extends Command
{
    protected $signature = 'hcm:daily-alerts';

    protected $description = 'Kirim alert HCM harian: probation H-7..H-3, kontrak PKWT H-60/H-30, dan mangkir (unexcused absence)';

    public function handle(): int
    {
        $this->probationWarnings();
        $this->contractWarnings();
        $this->unexcusedAbsence();

        return self::SUCCESS;
    }

    /**
     * Masa training/probation berakhir H-7 s.d. H-3.
     */
    private function probationWarnings(): void
    {
        $from = now()->addDays(3)->startOfDay();
        $to = now()->addDays(7)->endOfDay();

        $names = collect();

        // Peserta magang PKL yang periode berakhir sebentar lagi.
        HcmIntern::with('employee:id,name,employee_code,is_active')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [$from, $to])
            ->get()
            ->each(function ($intern) use ($names) {
                if ($intern->employee && $intern->employee->is_active) {
                    $names->push($intern->employee->name);
                }
            });

        // Karyawan probation/trainee dengan kontrak berakhir sebentar lagi.
        HcmContract::with('employee:id,name,employee_code,is_active,employment_status')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [$from, $to])
            ->get()
            ->each(function ($contract) use ($names) {
                $emp = $contract->employee;
                if (!$emp || !$emp->is_active) {
                    return;
                }
                $status = strtolower((string) $emp->employment_status);
                if (str_contains($status, 'trainee') || str_contains($status, 'probation') || str_contains($status, 'magang')) {
                    $names->push($emp->name . ' — masa training usai dalam ' . now()->startOfDay()->diffInDays($contract->end_date->startOfDay()) . ' hari');
                }
            });

        if ($names->isEmpty()) {
            $this->info('Probation: tidak ada.');
            return;
        }

        IdealNotificationService::dispatch('hcm_probation_warning', [
            'title' => 'Evaluasi Probation Karyawan',
            'body' => $names->count() . ' karyawan memasuki akhir masa training: ' . $names->implode('; ') . '.',
            'action_url' => route('hcm.contracts.index'),
            'action_label' => 'Buka Kontrak',
            'emoji' => '⏳',
        ]);

        $this->info('Probation terkirim: ' . $names->count());
    }

    /**
     * Kontrak PKWT berakhir H-60 dan H-30.
     */
    private function contractWarnings(): void
    {
        foreach ([60, 30] as $threshold) {
            $target = now()->addDays($threshold)->toDateString();

            $contracts = HcmContract::with('employee:id,name,employee_code,is_active')
                ->whereDate('end_date', $target)
                ->whereHas('employee', fn ($q) => $q->where('is_active', true))
                ->get();

            if ($contracts->isEmpty()) {
                continue;
            }

            $lines = $contracts->map(fn ($c) => ($c->employee->name ?? '-') . " ({$c->contract_number})")->implode('; ');

            IdealNotificationService::dispatch('hcm_contract_expired_warning', [
                'title' => 'Peringatan Berakhirnya Kontrak PKWT',
                'body' => "Kontrak habis dalam {$threshold} hari: {$lines}. Perlu keputusan perpanjang/putus.",
                'action_url' => route('hcm.contracts.index'),
                'action_label' => 'Buka Kontrak',
                'emoji' => '📄',
            ]);

            $this->info("Kontrak H-{$threshold} terkirim: {$contracts->count()}");
        }
    }

    /**
     * Mangkir: karyawan aktif tanpa absensi hari ini dan tanpa izin/cuti disetujui.
     */
    private function unexcusedAbsence(): void
    {
        $today = now()->toDateString();

        // Auto-tandai Alpha untuk karyawan yang pengajuan izin/cuti hari ini DITOLAK namun tidak hadir.
        // Ini mengaitkan tiket REJECTED -> Alpha/Mangkir -> potong uang makan.
        $rejectedIds = HcmLeaveRequest::where('status', 'REJECTED')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->pluck('employee_id')
            ->all();

        foreach ($rejectedIds as $empId) {
            HcmAttendance::firstOrCreate(
                ['employee_id' => $empId, 'attendance_date' => $today],
                [
                    'attendance_category' => 'Alpha/Mangkir',
                    'notes' => 'Otomatis: izin/cuti ditolak & tidak hadir.',
                ]
            );
        }

        $presentIds = HcmAttendance::whereDate('attendance_date', $today)->pluck('employee_id')->all();

        $onLeaveIds = HcmLeaveRequest::where('status', 'APPROVED')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->pluck('employee_id')
            ->all();

        $count = HcmEmployee::where('is_active', true)
            ->whereNotIn('id', $presentIds)
            ->whereNotIn('id', $onLeaveIds)
            ->count();

        if ($count <= 0) {
            $this->info('Mangkir: tidak ada.');
            return;
        }

        IdealNotificationService::dispatch('hcm_unexcused_absence', [
            'title' => 'Peringatan Mangkir (Unexcused Absence)',
            'body' => "{$count} karyawan belum melakukan konfirmasi ketidakhadiran hari ini tanpa keterangan.",
            'action_url' => route('hcm.attendance.index'),
            'action_label' => 'Buka Presensi',
            'emoji' => '🚨',
        ]);

        $this->info("Mangkir terkirim: {$count}");
    }
}
