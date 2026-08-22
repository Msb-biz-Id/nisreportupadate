<?php

namespace App\Services\Ai;

use App\Models\Settings\SystemSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiClient
{
    public function __construct(
        private readonly float $temperature = 0.7,
        private readonly int $maxOutputTokens = 2048,
        private readonly array $providers = [],
    ) {}

    public static function fromSettings(): self
    {
        $temp = (float) SystemSetting::get('ai', 'temperature', 0.7);
        $maxTokens = (int) SystemSetting::get('ai', 'max_tokens', 2048);
        
        $providersJson = SystemSetting::get('ai', 'providers', '[]');
        $providers = json_decode($providersJson, true) ?: [];

        return new self(
            $temp,
            $maxTokens,
            $providers
        );
    }

    public function isConfigured(): bool
    {
        $activeProviders = array_filter($this->providers, function ($p) {
            return ($p['is_active'] ?? false) === true;
        });
        return count($activeProviders) > 0;
    }

    public function generate(string $prompt): array
    {
        if (! $this->isConfigured()) {
            return $this->mockResponse($prompt);
        }

        $activeProviders = array_filter($this->providers, function ($p) {
            return ($p['is_active'] ?? false) === true;
        });

        // Shuffle providers to load-balance across different AI platforms
        shuffle($activeProviders);

        $lastError = 'Semua provider AI gagal merespons.';

        foreach ($activeProviders as $provider) {
            $keys = $provider['api_keys'] ?? [];
            if (empty($keys)) {
                $keys = ['local-or-keyless'];
            } else {
                shuffle($keys);
            }

            $baseUrl = rtrim($provider['base_url'] ?? '', '/');
            $model = $provider['model'] ?? '';
            $url = $baseUrl . '/chat/completions';
            $providerName = $provider['name'] ?? 'Custom AI';

            foreach ($keys as $index => $key) {
                try {
                    $headers = ['Content-Type' => 'application/json'];
                    if ($key !== 'local-or-keyless') {
                        $headers['Authorization'] = "Bearer {$key}";
                    }

                    $response = Http::timeout(30)
                        ->withHeaders($headers)
                        ->post($url, [
                            'model' => $model,
                            'messages' => [
                                ['role' => 'user', 'content' => $prompt]
                            ],
                            'temperature' => $this->temperature,
                            'max_tokens' => $this->maxOutputTokens,
                        ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        $text = $data['choices'][0]['message']['content'] ?? '';

                        if (trim($text) === '') {
                            Log::warning("Provider {$providerName} key #{$index} merespons kosong.");
                            continue;
                        }

                        return [
                            'success' => true,
                            'text'    => trim($text),
                            'model'   => $model . ' (' . parse_url($baseUrl, PHP_URL_HOST) . ')',
                            'tokens'  => $data['usage']['total_tokens'] ?? null,
                            'mock'    => false,
                        ];
                    }

                    $status = $response->status();
                    if ($status === 429) {
                        Log::info("Provider {$providerName} key #{$index} rate-limited (429), mencoba key/provider berikutnya.");
                        $lastError = "Rate limit {$providerName} tercapai.";
                        continue;
                    }

                    $errMsg = $response->json('error.message') ?? $response->body();
                    Log::warning("Provider {$providerName} error {$status}", ['error' => $errMsg]);
                    $lastError = "{$providerName} HTTP {$status}: " . mb_strimwidth((string)$errMsg, 0, 200, '…');

                } catch (ConnectionException $e) {
                    Log::warning("Provider {$providerName} key #{$index} timeout", ['error' => $e->getMessage()]);
                    $lastError = "{$providerName} Connection timeout.";
                } catch (\Throwable $e) {
                    Log::warning("Provider {$providerName} key #{$index} exception", ['error' => $e->getMessage()]);
                    $lastError = "{$providerName}: " . $e->getMessage();
                }
            }
        }

        return [
            'success' => false,
            'text'    => '',
            'error'   => "Semua provider AI gagal: {$lastError}",
            'mock'    => false,
        ];
    }

    private function mockResponse(string $prompt): array
    {
        return [
            'success' => true,
            'text' => "**[MOCK MODE — Provider AI belum dikonfigurasi]**\n\n"
                . "Ini adalah output simulasi. Untuk menggunakan AI sungguhan, tambahkan API key Groq, DeepSeek, OpenRouter, atau OpenAI di Pengaturan → Integrasi AI.\n\n"
                . "Prompt yang diterima:\n---\n" . mb_strimwidth($prompt, 0, 500, '…') . "\n---\n\n"
                . "Setelah API key dikonfigurasi, AI akan menghasilkan respons natural.",
            'model' => 'mock',
            'tokens' => 0,
            'mock' => true,
        ];
    }
}
