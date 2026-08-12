<?php

namespace App\Services\Ai;

use App\Models\Settings\SystemSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiClient
{
    private const GEMINI_BASE_URL = 'https://generativelanguage.googleapis.com/v1beta';

    public function __construct(
        private readonly array $geminiApiKeys = [],
        private readonly string $geminiModel = 'gemini-2.0-flash',
        private readonly float $temperature = 0.7,
        private readonly int $maxOutputTokens = 2048,
        private readonly string $provider = 'auto_failover',
        private readonly string $openAiBaseUrl = 'https://api.groq.com/openai/v1',
        private readonly array $openAiApiKeys = [],
        private readonly string $openAiModel = 'llama-3.3-70b-versatile',
    ) {}

    public static function fromSettings(): self
    {
        $rawGeminiKeys = SystemSetting::get('ai', 'gemini_api_keys') ?: env('GEMINI_API_KEYS', '');
        $geminiKeys = array_filter(array_map('trim', preg_split('/[\r\n,]+/', $rawGeminiKeys)));
        $geminiModel = SystemSetting::get('ai', 'model', 'gemini-2.0-flash');
        
        $temp = (float) SystemSetting::get('ai', 'temperature', 0.7);
        $maxTokens = (int) SystemSetting::get('ai', 'max_tokens', 2048);

        $provider = SystemSetting::get('ai', 'provider', 'auto_failover');
        $openAiBaseUrl = rtrim(SystemSetting::get('ai', 'openai_base_url', 'https://api.groq.com/openai/v1'), '/');
        
        $rawOpenAiKeys = SystemSetting::get('ai', 'openai_api_keys') ?: env('OPENAI_API_KEYS', '');
        $openAiKeys = array_filter(array_map('trim', preg_split('/[\r\n,]+/', $rawOpenAiKeys)));
        $openAiModel = SystemSetting::get('ai', 'openai_model', 'llama-3.3-70b-versatile');

        return new self(
            $geminiKeys,
            $geminiModel,
            $temp,
            $maxTokens,
            $provider,
            $openAiBaseUrl,
            $openAiKeys,
            $openAiModel
        );
    }

    public function isConfigured(): bool
    {
        return count($this->geminiApiKeys) > 0 || count($this->openAiApiKeys) > 0;
    }

    public function generate(string $prompt): array
    {
        if (! $this->isConfigured()) {
            return $this->mockResponse($prompt);
        }

        if ($this->provider === 'openai_compatible') {
            return $this->generateOpenAiCompatible($prompt);
        }

        if ($this->provider === 'gemini') {
            return $this->generateGemini($prompt);
        }

        // Auto Failover Chain (Never get rate-limited): Try OpenAI-compatible / Groq first if keys exist, fallback to Gemini
        if (count($this->openAiApiKeys) > 0) {
            $result = $this->generateOpenAiCompatible($prompt);
            if ($result['success']) {
                return $result;
            }
            Log::info("OpenAI/Groq provider rate-limited or failed. Automatically falling back to Gemini provider.");
        }

        if (count($this->geminiApiKeys) > 0) {
            return $this->generateGemini($prompt);
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

    /** Multi-key load-balanced & fallback driver for Google Gemini API */
    public function generateGemini(string $prompt): array
    {
        $keys = $this->geminiApiKeys;
        if (empty($keys)) {
            return [
                'success' => false,
                'text'    => '',
                'error'   => 'Tidak ada Gemini API key terkonfigurasi.',
                'mock'    => false,
            ];
        }
        shuffle($keys);

        $lastError = 'Semua Gemini API key gagal merespons.';
        $totalKeys = count($keys);

        foreach ($keys as $index => $key) {
            try {
                $response = Http::timeout(30)
                    ->withHeaders(['x-goog-api-key' => $key])
                    ->post(self::GEMINI_BASE_URL . "/models/{$this->geminiModel}:generateContent", [
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
                        $lastError = 'Respons kosong (kemungkinan diblokir safety filter).';
                        continue;
                    }

                    return [
                        'success' => true,
                        'text'    => trim($text),
                        'model'   => $this->geminiModel,
                        'tokens'  => $data['usageMetadata']['totalTokenCount'] ?? null,
                        'mock'    => false,
                    ];
                }

                $status = $response->status();
                if ($status === 429) {
                    Log::info("Gemini key #{$index} rate-limited (429), mencoba key berikutnya.");
                    $lastError = 'Rate limit Gemini tercapai.';
                    continue;
                }

                $errMsg = $response->json('error.message') ?? $response->body();
                Log::warning("Gemini key #{$index} error {$status}", ['error' => $errMsg]);
                $lastError = "HTTP {$status}: " . mb_strimwidth((string)$errMsg, 0, 200, '…');

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
            'error'   => "Semua {$totalKeys} Gemini API key gagal. {$lastError}",
            'mock'    => false,
        ];
    }

    private function mockResponse(string $prompt): array
    {
        return [
            'success' => true,
            'text' => "**[MOCK MODE — Provider AI belum dikonfigurasi]**\n\n"
                . "Ini adalah output simulasi. Untuk menggunakan AI sungguhan, tambahkan API key Groq, DeepSeek, OpenRouter, atau Gemini di Pengaturan → Integrasi AI.\n\n"
                . "Prompt yang diterima:\n---\n" . mb_strimwidth($prompt, 0, 500, '…') . "\n---\n\n"
                . "Setelah API key dikonfigurasi, AI akan menghasilkan respons natural.",
            'model' => 'mock',
            'tokens' => 0,
            'mock' => true,
        ];
    }
}

