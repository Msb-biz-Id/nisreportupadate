<?php

namespace App\Services\Notifications;

use App\Mail\ReportMail;
use App\Models\Settings\SystemSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
     * @param  array  $recipients  ['whatsapp' => ['081...'], 'telegram' => ['chat_id_1'], 'email' => ['user@example.com']]
     * @param  string  $subject
     */
    public function send(string $message, array $recipients = [], string $subject = 'Laporan Otomatis'): array
    {
        $channel = SystemSetting::get('system', 'notification_channel', 'whatsapp');
        $results = [];

        $waEnabled = (bool) SystemSetting::get('system', 'whatsapp_enabled', true);
        $tgEnabled = (bool) SystemSetting::get('system', 'telegram_enabled', true);
        $mailEnabled = (bool) SystemSetting::get('system', 'email_enabled', true);

        // Auto-fallback/routing: tentukan channel mana saja yang aktif berdasarkan channel preference & master switch
        $waActive = $waEnabled && in_array($channel, ['whatsapp', 'both', 'all'], true);
        $tgActive = $tgEnabled && in_array($channel, ['telegram', 'both', 'all'], true);
        $mailActive = $mailEnabled && in_array($channel, ['email', 'all'], true);

        // Jika channel diset spesifik tapi channel tersebut dimatikan, izinkan pengiriman ke channel lain yang aktif
        if (! $waActive && ! $tgActive && ! $mailActive) {
            $waActive = $waEnabled;
            $tgActive = $tgEnabled;
            $mailActive = $mailEnabled;
        }

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

        if ($mailActive && ! empty($recipients['email'])) {
            $this->configureSmtp();
            foreach ($recipients['email'] as $to) {
                try {
                    Mail::to($to)->send(new ReportMail($subject, $message));
                    $results[] = ['channel' => 'email', 'to' => $to, 'success' => true];
                } catch (\Throwable $e) {
                    Log::error("Failed sending email notification to {$to}: " . $e->getMessage());
                    $results[] = ['channel' => 'email', 'to' => $to, 'success' => false, 'error' => $e->getMessage()];
                }
            }
        }

        Log::info('Notification dispatched', ['channel' => $channel, 'count' => count($results)]);
        return $results;
    }

    private function configureSmtp(): void
    {
        $host = SystemSetting::get('mail', 'mail_host', config('mail.mailers.smtp.host'));
        $port = SystemSetting::get('mail', 'mail_port', config('mail.mailers.smtp.port'));
        $username = SystemSetting::get('mail', 'mail_username', config('mail.mailers.smtp.username'));
        $password = SystemSetting::get('mail', 'mail_password', config('mail.mailers.smtp.password'));
        $encryption = SystemSetting::get('mail', 'mail_encryption', config('mail.mailers.smtp.encryption'));
        $fromAddress = SystemSetting::get('mail', 'mail_from_address', config('mail.from.address'));
        $fromName = SystemSetting::get('mail', 'mail_from_name', config('mail.from.name'));

        if ($host) {
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => $host,
            ]);
        }
        if ($port) config(['mail.mailers.smtp.port' => (int) $port]);
        if ($username) config(['mail.mailers.smtp.username' => $username]);
        if ($password) config(['mail.mailers.smtp.password' => $password]);
        if ($encryption) config(['mail.mailers.smtp.encryption' => $encryption]);
        if ($fromAddress) config(['mail.from.address' => $fromAddress]);
        if ($fromName) config(['mail.from.name' => $fromName]);
    }
}
