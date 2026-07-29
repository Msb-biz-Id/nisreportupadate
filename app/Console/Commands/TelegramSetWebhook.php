<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Settings\SystemSetting;
use Illuminate\Support\Facades\Http;

class TelegramSetWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:set-webhook';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Set the webhook URL for Telegram Bot API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $botToken = SystemSetting::get('telegram', 'bot_token');
        if (empty($botToken)) {
            $this->error('Telegram Bot Token belum dikonfigurasi di pengaturan aplikasi.');
            return self::FAILURE;
        }

        $appUrl = url('/');
        if (str_starts_with($appUrl, 'http://localhost') || str_starts_with($appUrl, 'http://127.0.0.1')) {
            $this->warn('Perhatian: Webhook Telegram memerlukan URL publik HTTPS. Localhost tidak didukung secara langsung oleh Telegram.');
        }

        $webhookUrl = url('/webhooks/telegram');
        $this->info("Menghubungkan webhook Telegram ke: {$webhookUrl}");

        $response = Http::post("https://api.telegram.org/bot{$botToken}/setWebhook", [
            'url' => $webhookUrl,
        ]);

        if ($response->successful() && $response->json('ok')) {
            $this->info('✅ Telegram Webhook berhasil dipasang!');
            $this->line('Deskripsi: ' . $response->json('description'));
            return self::SUCCESS;
        }

        $this->error('❌ Gagal memasang Telegram Webhook.');
        $this->error($response->body());
        return self::FAILURE;
    }
}
