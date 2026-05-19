<?php

namespace App\Services\Notifications;

use App\Models\Settings\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SidobeClient
{
    public function __construct(
        private readonly ?string $apiUrl = null,
        private readonly ?string $apiKey = null,
        private readonly ?string $defaultRecipient = null,
    ) {}

    public static function fromSettings(): self
    {
        return new self(
            apiUrl: SystemSetting::get('whatsapp', 'api_url') ?: env('SIDOBE_API_URL'),
            apiKey: SystemSetting::get('whatsapp', 'api_key') ?: env('SIDOBE_API_KEY'),
            defaultRecipient: SystemSetting::get('whatsapp', 'default_recipient'),
        );
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiUrl) && ! empty($this->apiKey);
    }

    public function send(string $to, string $message): array
    {
        if (! $this->isConfigured()) {
            Log::info('[WA MOCK] Send to ' . $to, ['message' => $message]);
            return [
                'success' => true,
                'mock' => true,
                'message_id' => 'mock-' . uniqid(),
                'note' => 'WhatsApp belum dikonfigurasi. Pesan tidak terkirim (mock mode).',
            ];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders(['Authorization' => 'Bearer ' . $this->apiKey])
                ->post(rtrim($this->apiUrl, '/') . '/send', [
                    'to' => $to,
                    'message' => $message,
                ]);

            return [
                'success' => $response->successful(),
                'mock' => false,
                'message_id' => $response->json('id') ?? null,
                'error' => $response->successful() ? null : $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::warning('Sidobe WA send failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'mock' => false, 'error' => $e->getMessage()];
        }
    }
}
