<?php

namespace App\Services\Ai;

use App\Models\Settings\SystemSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiClient
{
    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct(
        private readonly array $apiKeys = [],
        private readonly string $model = 'gemini-2.5-flash',
        private readonly float $temperature = 0.7,
        private readonly int $maxOutputTokens = 2048,
    ) {}

    public static function fromSettings(): self
    {
        $rawKeys = SystemSetting::get('ai', 'gemini_api_keys') ?: env('GEMINI_API_KEYS', '');
        $keys = array_filter(array_map('trim', explode(',', $rawKeys)));
        $model = SystemSetting::get('ai', 'model', 'gemini-2.5-flash');
        $temp = (float) SystemSetting::get('ai', 'temperature', 0.7);
        $maxTokens = (int) SystemSetting::get('ai', 'max_tokens', 2048);

        return new self($keys, $model, $temp, $maxTokens);
    }

    public function isConfigured(): bool
    {
        return count($this->apiKeys) > 0;
    }

    public function generate(string $prompt): array
    {
        if (! $this->isConfigured()) {
            return $this->mockResponse($prompt);
        }

        // Load balancing: random key, fallback on failure
        $keys = $this->apiKeys;
        shuffle($keys);

        $lastError  = 'Semua API key gagal merespons.';
        $totalKeys  = count($keys);

        foreach ($keys as $index => $key) {
            try {
                $response = Http::timeout(30)
                    ->withHeaders([
                        'x-goog-api-key' => $key,
                    ])
                    ->post(self::BASE_URL . "/models/{$this->model}:generateContent", [
                        'contents'         => [['parts' => [['text' => $prompt]]]],
                        'generationConfig' => [
                            'temperature'     => $this->temperature,
                            'maxOutputTokens' => $this->maxOutputTokens,
                        ],
                        'safetySettings' => [
                            ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_ONLY_HIGH'],
                            ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_ONLY_HIGH'],
                        ],
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                    if (trim($text) === '') {
                        // Kemungkinan diblokir safety filter — coba key berikutnya
                        $lastError = 'Respons kosong (kemungkinan diblokir safety filter).';
                        continue;
                    }

                    return [
                        'success' => true,
                        'text'    => trim($text),
                        'model'   => $this->model,
                        'tokens'  => $data['usageMetadata']['totalTokenCount'] ?? null,
                        'mock'    => false,
                    ];
                }

                $status = $response->status();

                if ($status === 429) {
                    // Rate limit — coba key berikutnya (normal behavior, bukan error)
                    Log::info("Gemini key #{$index} rate limited (429), coba key berikutnya.");
                    $lastError = 'Rate limit tercapai.';
                    continue;
                }

                if ($status === 400) {
                    // Bad request = masalah di prompt, bukan di key — tidak perlu coba key lain
                    $errMsg = $response->json('error.message') ?? $response->body();
                    Log::warning('Gemini 400 Bad Request (prompt issue)', ['error' => $errMsg]);
                    return [
                        'success' => false,
                        'text'    => '',
                        'error'   => "Prompt ditolak Gemini: {$errMsg}",
                        'mock'    => false,
                    ];
                }

                // Error lain (5xx, dll) — log dan coba key berikutnya
                Log::warning("Gemini key #{$index} error {$status}", ['body' => $response->body()]);
                $lastError = "HTTP {$status}: " . ($response->json('error.message') ?? 'Unknown error');

            } catch (ConnectionException $e) {
                Log::warning("Gemini key #{$index} timeout", ['error' => $e->getMessage()]);
                $lastError = 'Connection timeout.';
            } catch (\Throwable $e) {
                Log::warning("Gemini key #{$index} exception", ['error' => $e->getMessage()]);
                $lastError = $e->getMessage();
            }
        }

        return [
            'success' => false,
            'text'    => '',
            'error'   => "Semua {$totalKeys} API key gagal. {$lastError} Periksa konfigurasi atau coba lagi nanti.",
            'mock'    => false,
        ];
    }

    private function mockResponse(string $prompt): array
    {
        return [
            'success' => true,
            'text' => "**[MOCK MODE — Gemini API key belum dikonfigurasi]**\n\n"
                . "Ini adalah output simulasi. Untuk menggunakan AI sungguhan, Superadmin perlu menambahkan Gemini API key di Pengaturan → Integrasi AI.\n\n"
                . "Prompt yang diterima:\n---\n" . mb_strimwidth($prompt, 0, 500, '…') . "\n---\n\n"
                . "Setelah API key dikonfigurasi, AI akan menghasilkan respons natural berdasarkan input ini.",
            'model' => 'mock',
            'tokens' => 0,
            'mock' => true,
        ];
    }
}
