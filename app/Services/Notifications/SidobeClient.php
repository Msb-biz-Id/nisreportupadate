<?php

namespace App\Services\Notifications;

use App\Models\Settings\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SidobeClient — WhatsApp gateway via Sidobe API v1
 * Docs: https://docs.sidobe.com
 *
 * Base URL : https://api.sidobe.com/wa/v1
 * Auth     : header X-Secret-Key: {secret}
 */
class SidobeClient
{
    public function __construct(
        private readonly ?string $apiUrl          = null,
        private readonly ?string $apiKey          = null,
        private readonly ?string $defaultRecipient = null,
        private readonly ?string $senderPhone      = null,
    ) {}

    public static function fromSettings(): self
    {
        return new self(
            apiUrl:          SystemSetting::get('whatsapp', 'api_url')   ?: 'https://api.sidobe.com/wa/v1',
            apiKey:          SystemSetting::get('whatsapp', 'api_key')   ?: env('SIDOBE_API_KEY'),
            defaultRecipient: SystemSetting::get('whatsapp', 'default_recipient'),
            senderPhone:     SystemSetting::get('whatsapp', 'sender_phone'),
        );
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function baseHeaders(): array
    {
        return [
            'X-Secret-Key' => $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];
    }

    private function baseBody(string $phone): array
    {
        $body = ['phone' => $phone];
        if ($this->senderPhone) {
            $body['sender_phone'] = $this->senderPhone;
        }
        return $body;
    }

    private function endpoint(string $path): string
    {
        return rtrim($this->apiUrl ?? 'https://api.sidobe.com/wa/v1', '/') . '/' . ltrim($path, '/');
    }

    private function mockResponse(string $type = 'send'): array
    {
        return [
            'success'    => true,
            'mock'       => true,
            'message_id' => 'mock-' . uniqid(),
            'note'       => "Sidobe API key belum dikonfigurasi — {$type} di-mock.",
        ];
    }

    // ── Text Message ─────────────────────────────────────────────────────────

    /**
     * Kirim pesan teks ke nomor HP atau group.
     *
     * @param  string  $to       Nomor HP (628xxx) atau group_id
     * @param  string  $message  Teks pesan (bold: *teks*, italic: _teks_)
     * @param  bool    $isGroup  true jika $to adalah group_id
     * @param  bool    $async    Kirim async (lebih cepat, status via webhook)
     */
    public function send(string $to, string $message, bool $isGroup = false, bool $async = false): array
    {
        if (! $this->isConfigured()) {
            Log::info('[WA MOCK] send', ['to' => $to, 'message' => substr($message, 0, 80)]);
            return $this->mockResponse('send');
        }

        $body = array_merge(
            $isGroup ? ['group_id' => $to] : $this->baseBody($to),
            ['message' => $message],
        );
        if ($async) $body['is_async'] = true;

        return $this->post('send-message', $body);
    }

    // ── Image Message ─────────────────────────────────────────────────────────

    /**
     * Kirim pesan dengan gambar (JPG/PNG, maks 10 MB).
     *
     * @param  string  $to        Nomor HP atau group_id
     * @param  string  $imageUrl  URL publik gambar (JPG/PNG)
     * @param  string  $caption   Caption opsional
     * @param  bool    $isGroup
     */
    public function sendImage(string $to, string $imageUrl, string $caption = '', bool $isGroup = false): array
    {
        if (! $this->isConfigured()) {
            Log::info('[WA MOCK] sendImage', ['to' => $to, 'imageUrl' => $imageUrl]);
            return $this->mockResponse('sendImage');
        }

        $body = array_merge(
            $isGroup ? ['group_id' => $to] : $this->baseBody($to),
            ['image_url' => $imageUrl],
        );
        if ($caption) $body['message'] = $caption;

        return $this->post('send-message-image', $body);
    }

    // ── Document Message ──────────────────────────────────────────────────────

    /**
     * Kirim pesan dengan dokumen (PDF, DOC, XLSX, dll — maks 10 MB).
     */
    public function sendDocument(string $to, string $documentUrl, string $documentName, string $caption = ''): array
    {
        if (! $this->isConfigured()) {
            Log::info('[WA MOCK] sendDocument', ['to' => $to, 'url' => $documentUrl]);
            return $this->mockResponse('sendDocument');
        }

        $body = array_merge($this->baseBody($to), [
            'document_url'  => $documentUrl,
            'document_name' => $documentName,
        ]);
        if ($caption) $body['message'] = $caption;

        return $this->post('send-message-doc', $body);
    }

    // ── Check Number ─────────────────────────────────────────────────────────

    /**
     * Cek apakah nomor HP terdaftar di WhatsApp.
     *
     * @return bool|null  true = terdaftar, false = tidak, null = API error
     */
    public function checkNumber(string $phone): ?bool
    {
        if (! $this->isConfigured()) return null;

        $result = $this->post('utilities/check-number', ['phone' => $phone]);
        if (! $result['success']) return null;

        return (bool) ($result['data']['is_registered'] ?? false);
    }

    // ── Message Status ────────────────────────────────────────────────────────

    /**
     * Ambil status pesan berdasar message ID.
     *
     * @return array  ['success' => bool, 'status' => 'PENDING|SUCCESS|FAILED', ...]
     */
    public function getMessageStatus(string $messageId): array
    {
        if (! $this->isConfigured()) return $this->mockResponse('status');

        try {
            $response = Http::timeout(10)
                ->withHeaders($this->baseHeaders())
                ->get($this->endpoint("whatsapp-messages/{$messageId}"));

            if ($response->successful()) {
                return ['success' => true, 'mock' => false, 'data' => $response->json()];
            }

            return ['success' => false, 'mock' => false, 'error' => $response->body()];
        } catch (\Throwable $e) {
            Log::warning('Sidobe getMessageStatus failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'mock' => false, 'error' => $e->getMessage()];
        }
    }

    // ── List Groups ───────────────────────────────────────────────────────────

    /**
     * Ambil daftar grup WhatsApp.
     */
    public function listGroups(): array
    {
        if (! $this->isConfigured()) return [];

        try {
            $response = Http::timeout(10)
                ->withHeaders($this->baseHeaders())
                ->get($this->endpoint('whatsapp-groups'));

            return $response->successful() ? ($response->json('data') ?? []) : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    // ── Webhook Signature Verification ────────────────────────────────────────

    /**
     * Verifikasi signature webhook Sidobe.
     *
     * Formula: sha256($secretKey . '|' . $webhookId)
     *
     * @param  string  $incomingSignature  Nilai header X-Webhook-Signature
     * @param  string  $webhookId          Nilai field `id` dari payload webhook
     */
    public function verifyWebhookSignature(string $incomingSignature, string $webhookId): bool
    {
        if (! $this->apiKey) return false;
        $expected = hash('sha256', $this->apiKey . '|' . $webhookId);
        return hash_equals(strtolower($expected), strtolower($incomingSignature));
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    private function post(string $path, array $body): array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders($this->baseHeaders())
                ->post($this->endpoint($path), $body);

            if ($response->successful()) {
                $json = $response->json();
                return [
                    'success'    => true,
                    'mock'       => false,
                    'message_id' => $json['id'] ?? $json['data']['id'] ?? null,
                    'data'       => $json,
                ];
            }

            Log::warning("Sidobe {$path} failed", [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return [
                'success' => false,
                'mock'    => false,
                'error'   => $response->json('message') ?? $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::warning("Sidobe {$path} exception", ['error' => $e->getMessage()]);
            return ['success' => false, 'mock' => false, 'error' => $e->getMessage()];
        }
    }
}
