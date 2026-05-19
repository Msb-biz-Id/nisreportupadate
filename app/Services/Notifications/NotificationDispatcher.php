<?php

namespace App\Services\Notifications;

use App\Models\Settings\SystemSetting;
use Illuminate\Support\Facades\Log;

class NotificationDispatcher
{
    public function __construct(
        private readonly SidobeClient $whatsapp,
        private readonly TelegramClient $telegram,
    ) {}

    /**
     * Send notification ke channel(s) berdasarkan konfigurasi global.
     *
     * @param  string  $message
     * @param  array  $recipients  ['whatsapp' => ['081...'], 'telegram' => ['chat_id_1']]
     */
    public function send(string $message, array $recipients = []): array
    {
        $channel = SystemSetting::get('system', 'notification_channel', 'whatsapp');
        $results = [];

        $waActive = in_array($channel, ['whatsapp', 'both'], true);
        $tgActive = in_array($channel, ['telegram', 'both'], true);

        if ($waActive && ! empty($recipients['whatsapp'])) {
            foreach ($recipients['whatsapp'] as $to) {
                $results[] = ['channel' => 'whatsapp', 'to' => $to, ...$this->whatsapp->send($to, $message)];
            }
        }
        if ($tgActive && ! empty($recipients['telegram'])) {
            foreach ($recipients['telegram'] as $to) {
                $results[] = ['channel' => 'telegram', 'to' => $to, ...$this->telegram->send($to, $message)];
            }
        }

        Log::info('Notification dispatched', ['channel' => $channel, 'count' => count($results)]);
        return $results;
    }
}
