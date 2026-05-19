<?php

namespace App\Services\Notifications;

use App\Models\Settings\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramClient
{
    public function __construct(
        private readonly ?string $botToken = null,
        private readonly ?string $defaultChatId = null,
    ) {}

    public static function fromSettings(): self
    {
        return new self(
            botToken: SystemSetting::get('telegram', 'bot_token') ?: env('TELEGRAM_BOT_TOKEN'),
            defaultChatId: SystemSetting::get('telegram', 'default_chat_id'),
        );
    }

    public function isConfigured(): bool
    {
        return ! empty($this->botToken);
    }

    public function send(string $chatId, string $message): array
    {
        if (! $this->isConfigured()) {
            Log::info('[Telegram MOCK] chat=' . $chatId, ['message' => $message]);
            return [
                'success' => true, 'mock' => true,
                'note' => 'Telegram belum dikonfigurasi (mock mode).',
            ];
        }

        try {
            $response = Http::timeout(15)
                ->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'Markdown',
                ]);

            return [
                'success' => $response->successful(),
                'mock' => false,
                'message_id' => $response->json('result.message_id') ?? null,
                'error' => $response->successful() ? null : $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::warning('Telegram send failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'mock' => false, 'error' => $e->getMessage()];
        }
    }
}
