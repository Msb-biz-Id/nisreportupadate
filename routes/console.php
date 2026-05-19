<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Phase 6: Scheduled Reports
// Default jadwal — bisa di-override via settings nanti (BRD 17.2.1)
Schedule::command('reports:send harian')->dailyAt('08:00');
Schedule::command('reports:send mingguan')->weeklyOn(1, '08:00'); // Senin 08:00
Schedule::command('reports:send bulanan')->monthlyOn(1, '08:00'); // Tanggal 1 jam 08:00
