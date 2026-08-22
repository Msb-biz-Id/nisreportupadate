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
        private readonly string $openAiBaseUrl = 'https://api.groq.com/openai/v1',
        private readonly array $openAiApiKeys = [],
        private readonly string $openAiModel = 'llama-3.3-70b-versatile',
    ) {}

    public static function fromSettings(): self
    {
        $temp = (float) SystemSetting::get('ai', 'temperature', 0.7);
        $maxTokens = (int) SystemSetting::get('ai', 'max_tokens', 2048);
        $openAiBaseUrl = rtrim(SystemSetting::get('ai', 'openai_base_url', 'https://api.groq.com/openai/v1'), '/');
        
        $rawOpenAiKeys = SystemSetting::get('ai', 'openai_api_keys') ?: env('OPENAI_API_KEYS', '');
        $openAiKeys = array_filter(array_map('trim', preg_split('/[\r\n,]+/', $rawOpenAiKeys)));
        $openAiModel = SystemSetting::get('ai', 'openai_model', 'llama-3.3-70b-versatile');

        return new self(
            $temp,
            $maxTokens,
            $openAiBaseUrl,
            $openAiKeys,
            $openAiModel
        );
    }

    public function isConfigured(): bool
    {
        return count($this->openAiApiKeys) > 0;
    }

    public function generate(string $prompt): array
    {
        if (! $this->isConfigured()) {
            return $this->mockResponse($prompt);
        }

        return $this->generateOpenAiCompatible($prompt);
    }

    /** Multi-key load-balanced & fallback driver for OpenAI-compatible APIs (Groq, DeepSeek, OpenRouter, Together AI, Ollama, OpenAI) */
    public function generateOpenAiCompatible(string $prompt): array
    {
        $keys = $this->openAiApiKeys;
        if (empty($keys)) {
            // Ollama / local LLM might not require API keys
            $keys = ['local-or-keyless'];
        } else {
            shuffle($keys);
        }

        $lastError = 'Semua OpenAI/Groq API key gagal merespons.';
        $url = $this->openAiBaseUrl . '/chat/completions';

        foreach ($keys as $index => $key) {
            try {
                $headers = ['Content-Type' => 'application/json'];
                if ($key !== 'local-or-keyless') {
                    $headers['Authorization'] = "Bearer {$key}";
                }

                $response = Http::timeout(30)
                    ->withHeaders($headers)
                    ->post($url, [
                        'model' => $this->openAiModel,
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
                        $lastError = 'Respons kosong dari provider OpenAI/Groq.';
                        continue;
                    }

                    return [
                        'success' => true,
                        'text'    => trim($text),
                        'model'   => $this->openAiModel . ' (' . parse_url($this->openAiBaseUrl, PHP_URL_HOST) . ')',
                        'tokens'  => $data['usage']['total_tokens'] ?? null,
                        'mock'    => false,
                    ];
                }

                $status = $response->status();
                if ($status === 429) {
                    Log::info("OpenAI/Groq key #{$index} rate-limited (429), mencoba key/provider berikutnya.");
                    $lastError = 'Rate limit OpenAI/Groq tercapai.';
                    continue;
                }

                $errMsg = $response->json('error.message') ?? $response->body();
                Log::warning("OpenAI/Groq provider error {$status}", ['error' => $errMsg]);
                $lastError = "HTTP {$status}: " . mb_strimwidth((string)$errMsg, 0, 200, '…');

            } catch (ConnectionException $e) {
                Log::warning("OpenAI/Groq key #{$index} timeout", ['error' => $e->getMessage()]);
                $lastError = 'Connection timeout.';
            } catch (\Throwable $e) {
                Log::warning("OpenAI/Groq key #{$index} exception", ['error' => $e->getMessage()]);
                $lastError = $e->getMessage();
            }
        }

        return [
            'success' => false,
            'text'    => '',
            'error'   => "Provider OpenAI/Groq gagal: {$lastError}",
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
