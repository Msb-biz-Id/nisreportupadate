<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Phase 6: Scheduled Reports (BRD 17.2.1 & 17.2.2)
try {
    if (Illuminate\Support\Facades\Schema::hasTable('system_settings')) {
        $dailyTime = App\Models\Settings\SystemSetting::get('reports', 'daily_report_time', '08:00');
        
        $weeklyDayStr = App\Models\Settings\SystemSetting::get('reports', 'weekly_report_day', 'monday');
        $weeklyDay = [
            'sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
            'thursday' => 4, 'friday' => 5, 'saturday' => 6
        ][strtolower($weeklyDayStr)] ?? 1;

        $monthlyDateRaw = App\Models\Settings\SystemSetting::get('reports', 'monthly_report_date', '1');

        Schedule::command('reports:send harian')->dailyAt($dailyTime);
        Schedule::command('reports:send mingguan')->weeklyOn($weeklyDay, $dailyTime);

        // Penjadwalan bulanan dinamis (mendukung 'last_day' / tanggal 28-31)
        if ($monthlyDateRaw === 'last_day') {
            Schedule::command('reports:send bulanan')
                ->dailyAt($dailyTime)
                ->when(fn () => now()->isLastOfMonth());
        } else {
            $targetDay = (int) $monthlyDateRaw;
            if ($targetDay >= 1 && $targetDay <= 28) {
                Schedule::command('reports:send bulanan')->monthlyOn($targetDay, $dailyTime);
            } else {
                Schedule::command('reports:send bulanan')
                    ->dailyAt($dailyTime)
                    ->when(function () use ($targetDay) {
                        $daysInMonth = now()->daysInMonth;
                        $effectiveDay = min($targetDay, $daysInMonth);
                        return (int) now()->format('j') === $effectiveDay;
                    });
            }
        }
    } else {
        Schedule::command('reports:send harian')->dailyAt('08:00');
        Schedule::command('reports:send mingguan')->weeklyOn(1, '08:00');
        Schedule::command('reports:send bulanan')->monthlyOn(1, '08:00');
    }
} catch (\Throwable $e) {
    Schedule::command('reports:send harian')->dailyAt('08:00');
    Schedule::command('reports:send mingguan')->weeklyOn(1, '08:00');
    Schedule::command('reports:send bulanan')->monthlyOn(1, '08:00');
}

// BRD 13.5.3: Reminder & overdue invoice WA setiap hari 09:00
Schedule::command('invoices:send-reminders --days=3')->dailyAt('09:00');

// Cloudflare R2 Backup
// Daily backup — retained 30 hari
Schedule::command('backup:r2 --type=daily')->dailyAt('02:00');
// Monthly backup — retained 12 bulan (hari pertama tiap bulan)
Schedule::command('backup:r2 --type=monthly')->monthlyOn(1, '03:00');
// Yearly backup — retained 5 tahun (1 Jan tiap tahun)
Schedule::command('backup:r2 --type=yearly')->yearlyOn(1, 1, '04:00');

// Safe media pruning (run weekly to clean unused image uploads)
Schedule::command('uploads:prune')->weeklyOn(0, '01:00');

// Prune old activity logs older than 30 days daily
Schedule::command('model:prune', ['--model' => [\App\Models\ActivityLog::class]])->daily();

// Hapus log perubahan PO (po_change_logs) yang sudah selesai lebih dari 30 hari
Schedule::command('po:clean-logs --days=30')->dailyAt('02:30');


