<?php

namespace App\Http\Controllers;

use App\Models\Settings\SystemSetting;
use App\Services\Ai\GeminiClient;
use App\Services\Notifications\SidobeClient;
use App\Services\Notifications\TelegramClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('settings.system');

        return Inertia::render('Settings/Integrations', [
            'ai' => [
                'gemini_api_keys_masked' => $this->maskCsv(SystemSetting::get('ai', 'gemini_api_keys')),
                'has_keys' => ! empty(SystemSetting::get('ai', 'gemini_api_keys')),
                'model' => SystemSetting::get('ai', 'model', 'gemini-1.5-flash'),
                'temperature' => (float) SystemSetting::get('ai', 'temperature', 0.7),
                'max_tokens' => (int) SystemSetting::get('ai', 'max_tokens', 2048),
                'is_configured' => GeminiClient::fromSettings()->isConfigured(),
            ],
            'whatsapp' => [
                'api_url' => SystemSetting::get('whatsapp', 'api_url'),
                'api_key_masked' => SystemSetting::maskedValue(SystemSetting::get('whatsapp', 'api_key')),
                'has_key' => ! empty(SystemSetting::get('whatsapp', 'api_key')),
                'default_recipient' => SystemSetting::get('whatsapp', 'default_recipient'),
                'is_configured' => SidobeClient::fromSettings()->isConfigured(),
            ],
            'telegram' => [
                'bot_token_masked' => SystemSetting::maskedValue(SystemSetting::get('telegram', 'bot_token')),
                'has_key' => ! empty(SystemSetting::get('telegram', 'bot_token')),
                'default_chat_id' => SystemSetting::get('telegram', 'default_chat_id'),
                'is_configured' => TelegramClient::fromSettings()->isConfigured(),
            ],
            'system' => [
                'notification_channel' => SystemSetting::get('system', 'notification_channel', 'whatsapp'),
                'whatsapp_enabled' => (bool) SystemSetting::get('system', 'whatsapp_enabled', true),
                'telegram_enabled' => (bool) SystemSetting::get('system', 'telegram_enabled', false),
            ],
        ]);
    }

    public function updateAi(Request $request)
    {
        Gate::authorize('settings.ai');

        $data = $request->validate([
            'gemini_api_keys' => ['nullable', 'string', 'max:5000'],
            'model' => ['required', 'string', 'max:50'],
            'temperature' => ['required', 'numeric', 'between:0,2'],
            'max_tokens' => ['required', 'integer', 'between:128,8192'],
        ]);

        if (! empty($data['gemini_api_keys'])) {
            SystemSetting::set('ai', 'gemini_api_keys', $data['gemini_api_keys'], encrypted: true);
        }
        SystemSetting::set('ai', 'model', $data['model']);
        SystemSetting::set('ai', 'temperature', (string) $data['temperature']);
        SystemSetting::set('ai', 'max_tokens', (string) $data['max_tokens']);

        return back()->with('success', 'Pengaturan AI tersimpan.');
    }

    public function updateWhatsapp(Request $request)
    {
        Gate::authorize('settings.system');

        $data = $request->validate([
            'api_url' => ['nullable', 'url', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:500'],
            'default_recipient' => ['nullable', 'string', 'max:100'],
        ]);

        SystemSetting::set('whatsapp', 'api_url', $data['api_url']);
        if (! empty($data['api_key'])) {
            SystemSetting::set('whatsapp', 'api_key', $data['api_key'], encrypted: true);
        }
        SystemSetting::set('whatsapp', 'default_recipient', $data['default_recipient']);

        return back()->with('success', 'Pengaturan WhatsApp tersimpan.');
    }

    public function updateTelegram(Request $request)
    {
        Gate::authorize('settings.system');

        $data = $request->validate([
            'bot_token' => ['nullable', 'string', 'max:500'],
            'default_chat_id' => ['nullable', 'string', 'max:100'],
        ]);

        if (! empty($data['bot_token'])) {
            SystemSetting::set('telegram', 'bot_token', $data['bot_token'], encrypted: true);
        }
        SystemSetting::set('telegram', 'default_chat_id', $data['default_chat_id']);

        return back()->with('success', 'Pengaturan Telegram tersimpan.');
    }

    public function updateSystem(Request $request)
    {
        Gate::authorize('settings.system');

        $data = $request->validate([
            'notification_channel' => ['required', 'in:whatsapp,telegram,both'],
            'whatsapp_enabled' => ['boolean'],
            'telegram_enabled' => ['boolean'],
        ]);

        SystemSetting::set('system', 'notification_channel', $data['notification_channel']);
        SystemSetting::set('system', 'whatsapp_enabled', $data['whatsapp_enabled'] ? '1' : '0');
        SystemSetting::set('system', 'telegram_enabled', $data['telegram_enabled'] ? '1' : '0');

        return back()->with('success', 'Pengaturan sistem tersimpan.');
    }

    public function testAi(Request $request)
    {
        Gate::authorize('settings.ai');
        $client = GeminiClient::fromSettings();
        $result = $client->generate('Halo, jawab dengan satu kalimat singkat untuk konfirmasi koneksi berhasil dalam Bahasa Indonesia.');

        return back()->with($result['success'] ? 'success' : 'error',
            $result['success']
                ? ($result['mock'] ? 'Mock mode aktif (API key belum dikonfigurasi).' : 'Koneksi Gemini OK: ' . mb_strimwidth($result['text'], 0, 150, '…'))
                : 'Gagal: ' . ($result['error'] ?? 'unknown')
        );
    }

    public function testWhatsapp(Request $request)
    {
        Gate::authorize('settings.system');
        $client = SidobeClient::fromSettings();
        $to = $request->string('to')->toString() ?: SystemSetting::get('whatsapp', 'default_recipient', '6281234567890');
        $result = $client->send($to, 'Test pesan dari NISReport. Jika kamu menerima ini, integrasi WhatsApp berhasil.');

        return back()->with($result['success'] ? 'success' : 'error',
            $result['success']
                ? ($result['mock'] ? 'Mock mode (API key belum dikonfigurasi).' : 'WhatsApp terkirim ke ' . $to)
                : 'Gagal: ' . ($result['error'] ?? 'unknown')
        );
    }

    public function testTelegram(Request $request)
    {
        Gate::authorize('settings.system');
        $client = TelegramClient::fromSettings();
        $chatId = $request->string('chat_id')->toString() ?: SystemSetting::get('telegram', 'default_chat_id', '');
        if (! $chatId) return back()->with('error', 'Default chat ID belum diatur.');
        $result = $client->send($chatId, '*Test* dari NISReport.');

        return back()->with($result['success'] ? 'success' : 'error',
            $result['success']
                ? ($result['mock'] ? 'Mock mode (bot token belum dikonfigurasi).' : 'Telegram terkirim.')
                : 'Gagal: ' . ($result['error'] ?? 'unknown')
        );
    }

    private function maskCsv(?string $csv): array
    {
        if (! $csv) return [];
        return collect(explode(',', $csv))->map(fn ($k) => SystemSetting::maskedValue(trim($k)))->all();
    }
}
