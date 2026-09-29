<?php

namespace App\Console\Commands;

use App\Models\Hcm\HcmOvertimeBatch;
use App\Services\Notifications\IdealNotificationService;
use Illuminate\Console\Command;

class HcmOvertimeReminder extends Command
{
    protected $signature = 'hcm:overtime-reminder';

    protected $description = 'Pengingat validasi lembur mingguan menjelang cut-off (Jumat sore / Sabtu pagi)';

    public function handle(): int
    {
        $draftCount = HcmOvertimeBatch::where('status', 'DRAFT')->count();

        if ($draftCount <= 0) {
            $this->info('Tidak ada batch lembur draft.');
            return self::SUCCESS;
        }

        IdealNotificationService::dispatch('hcm_overtime_validation_reminder', [
            'title' => 'Pengingat Validasi Lembur Mingguan',
            'body' => "Ada {$draftCount} batch lembur menunggu validasi. Mohon validasi total jam lembur minggu ini sebelum diteruskan ke Keuangan.",
            'action_url' => route('hcm.overtime.index'),
            'action_label' => 'Buka Lembur',
            'emoji' => '⏰',
        ]);

        $this->info("Pengingat lembur terkirim: {$draftCount} batch draft.");

        return self::SUCCESS;
    }
}
