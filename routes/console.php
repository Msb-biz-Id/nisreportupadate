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
