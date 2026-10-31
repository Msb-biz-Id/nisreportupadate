<?php

namespace App\Console\Commands;

use App\Models\Hcm\HcmCompanyEvent;
use App\Services\Notifications\IdealNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class HcmEventReminders extends Command
{
    protected $signature = 'hcm:event-reminders';

    protected $description = 'Pengingat agenda/event perusahaan (H-N, H-1, Hari-H) + generate event tahunan berulang';

    public function handle(): int
    {
        $this->generateRecurring();
        $this->sendReminders();

        return self::SUCCESS;
    }

    /**
     * Buat otomatis kejadian tahun berikutnya untuk event berulang tahunan.
     */
    private function generateRecurring(): void
    {
        $today = now()->startOfDay();
        $created = 0;

        $recurring = HcmCompanyEvent::where('is_annual_recurring', true)->get();

        foreach ($recurring as $ev) {
            if (!$ev->start_date) {
                continue;
            }

            $candidate = Carbon::create($today->year, $ev->start_date->month, $ev->start_date->day)->startOfDay();
            if ($candidate->lt($today)) {
                $candidate = $candidate->copy()->addYear();
            }

            $durationDays = $ev->end_date ? $ev->start_date->diffInDays($ev->end_date) : 0;
            $candidateEnd = $candidate->copy()->addDays($durationDays);

            $exists = HcmCompanyEvent::where('is_annual_recurring', true)
                ->where('title', $ev->title)
                ->whereDate('start_date', $candidate->toDateString())
                ->exists();

            if ($exists) {
                continue;
            }

            HcmCompanyEvent::create([
                'title' => $ev->title,
                'event_type' => $ev->event_type,
                'organizer_name' => $ev->organizer_name,
                'start_date' => $candidate->toDateString(),
                'end_date' => $candidateEnd->toDateString(),
                'start_time' => $ev->start_time,
                'end_time' => $ev->end_time,
                'location' => $ev->location,
                'target_audience' => $ev->target_audience,
                'reminder_days' => $ev->reminder_days,
                'is_annual_recurring' => true,
                'invitation_file_url' => $ev->invitation_file_url,
                'description' => $ev->description,
                'color_code' => $ev->color_code,
                'is_public' => $ev->is_public,
                'created_by' => $ev->created_by,
            ]);

            $created++;
        }

        $this->info("Event tahunan dibuat otomatis: {$created}");
    }

    /**
     * Kirim pengingat untuk event yang jatuh pada H-N (reminder_days), H-1, dan Hari-H.
     */
    private function sendReminders(): void
    {
        $today = now()->startOfDay();
        $sent = 0;

        $events = HcmCompanyEvent::whereDate('start_date', '>=', $today->toDateString())->get();

        foreach ($events as $ev) {
            $daysUntil = (int) $today->diffInDays($ev->start_date->startOfDay(), false);
            $reminderDays = (int) ($ev->reminder_days ?? 3);

            if (!in_array($daysUntil, [$reminderDays, 1, 0], true)) {
                continue;
            }

            $when = match (true) {
                $daysUntil === 0 => 'hari ini',
                $daysUntil === 1 => 'besok',
                default => "dalam {$daysUntil} hari",
            };

            IdealNotificationService::dispatch('hcm_event_reminder', [
                'title' => 'Pengingat Agenda / Event',
                'body' => "Agenda \"{$ev->title}\" ({$ev->event_type}) berlangsung {$when}, "
                    . $ev->start_date->isoFormat('D MMMM Y')
                    . ($ev->location ? " di {$ev->location}" : '') . '.',
                'action_url' => route('hcm.events.index'),
                'action_label' => 'Buka Kalender',
                'emoji' => '📅',
                'event_id' => $ev->id,
            ]);

            $sent++;
        }

        $this->info("Pengingat event terkirim: {$sent}");
    }
}
