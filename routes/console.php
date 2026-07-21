<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Phase 6: Scheduled Reports (BRD 17.2.1)
Schedule::command('reports:send harian')->dailyAt('08:00');
Schedule::command('reports:send mingguan')->weeklyOn(1, '08:00');
Schedule::command('reports:send bulanan')->monthlyOn(1, '08:00');

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


