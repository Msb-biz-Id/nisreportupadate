<?php

namespace App\Http\Controllers;

use App\Models\Settings\SystemSetting;
use App\Services\Ai\GeminiClient;
use App\Services\Notifications\SidobeClient;
use App\Services\Notifications\TelegramClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

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
                'api_url'          => SystemSetting::get('whatsapp', 'api_url', 'https://api.sidobe.com/wa/v1'),
                'api_key_masked'   => SystemSetting::maskedValue(SystemSetting::get('whatsapp', 'api_key')),
                'has_key'          => ! empty(SystemSetting::get('whatsapp', 'api_key')),
                'default_recipient' => SystemSetting::get('whatsapp', 'default_recipient'),
                'sender_phone'     => SystemSetting::get('whatsapp', 'sender_phone'),
                'webhook_url'      => url('/webhooks/sidobe'),
                'is_configured'    => SidobeClient::fromSettings()->isConfigured(),
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
                'customer_import_enabled' => (bool) SystemSetting::get('system', 'customer_import_enabled', false),
            ],
            'seo' => [
                'site_name' => SystemSetting::get('seo', 'site_name', 'Circle Sportwear - Tracking PO'),
                'site_description' => SystemSetting::get('seo', 'site_description', 'Sistem tracking PO dan invoice secara aman dan privat.'),
                'logo' => SystemSetting::get('seo', 'logo'),
                'logo_url' => SystemSetting::get('seo', 'logo') ? \Illuminate\Support\Facades\Storage::disk('public')->url(SystemSetting::get('seo', 'logo')) : null,
                'favicon' => SystemSetting::get('seo', 'favicon'),
                'favicon_url' => SystemSetting::get('seo', 'favicon') ? \Illuminate\Support\Facades\Storage::disk('public')->url(SystemSetting::get('seo', 'favicon')) : null,
            ],
            'reseller_branding' => [
                'nama_brand' => SystemSetting::get('reseller_branding', 'nama_brand', 'Circle Reseller'),
                'tagline' => SystemSetting::get('reseller_branding', 'tagline', 'Reseller Official Hub'),
                'email' => SystemSetting::get('reseller_branding', 'email', 'reseller@circlesportwear.com'),
                'no_hp' => SystemSetting::get('reseller_branding', 'no_hp', '08123456789'),
                'alamat' => SystemSetting::get('reseller_branding', 'alamat', ''),
                'instagram' => SystemSetting::get('reseller_branding', 'instagram', ''),
                'tiktok' => SystemSetting::get('reseller_branding', 'tiktok', ''),
                'facebook' => SystemSetting::get('reseller_branding', 'facebook', ''),
                'logo' => SystemSetting::get('reseller_branding', 'logo'),
                'logo_url' => SystemSetting::get('reseller_branding', 'logo') ? \Illuminate\Support\Facades\Storage::disk('public')->url(SystemSetting::get('reseller_branding', 'logo')) : null,
            ],
            'mail' => [
                'mail_host' => SystemSetting::get('mail', 'mail_host', 'smtp.mailtrap.io'),
                'mail_port' => SystemSetting::get('mail', 'mail_port', '2525'),
                'mail_username' => SystemSetting::get('mail', 'mail_username'),
                'mail_password_masked' => SystemSetting::maskedValue(SystemSetting::get('mail', 'mail_password')),
                'has_password' => ! empty(SystemSetting::get('mail', 'mail_password')),
                'mail_encryption' => SystemSetting::get('mail', 'mail_encryption', 'tls'),
                'mail_from_address' => SystemSetting::get('mail', 'mail_from_address', 'no-reply@circlesportwear.com'),
                'mail_from_name' => SystemSetting::get('mail', 'mail_from_name', 'Circle Sportwear'),
            ],
            'notification_matrix' => [],
            'available_roles' => Role::orderBy('name')->pluck('name')->toArray(),
            'reports' => [
                'enable_auto_report'    => (bool)  SystemSetting::get('reports', 'enable_auto_report', false),
                'daily_report_time'     => SystemSetting::get('reports', 'daily_report_time', '08:00'),
                'weekly_report_day'     => SystemSetting::get('reports', 'weekly_report_day', 'monday'),
                'monthly_report_date'   => (int)   SystemSetting::get('reports', 'monthly_report_date', 1),
                'report_types'          => SystemSetting::get('reports', 'report_types', 'brand,produksi'),
                'superadmin_recipients' => SystemSetting::get('reports', 'superadmin_recipients', ''),
                'produksi_recipients'   => SystemSetting::get('reports', 'produksi_recipients', ''),
                'brand_recipients'      => SystemSetting::get('reports', 'brand_recipients', ''),
                'owner_recipients'      => SystemSetting::get('reports', 'owner_recipients', ''),
                'keuangan_recipients'   => SystemSetting::get('reports', 'keuangan_recipients', ''),
            ],
        ]);
     }

     public function updateReports(Request $request)
     {
         Gate::authorize('settings.system');
         $data = $request->validate([
             'enable_auto_report'    => ['boolean'],
             'daily_report_time'     => ['string', 'regex:/^\d{2}:\d{2}$/'],
             'weekly_report_day'     => ['string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
             'monthly_report_date'   => ['integer', 'min:1', 'max:28'],
             'report_types'          => ['string'],
             'superadmin_recipients' => ['nullable', 'string'],
             'produksi_recipients'   => ['nullable', 'string'],
             'brand_recipients'      => ['nullable', 'string'],
             'owner_recipients'      => ['nullable', 'string'],
             'keuangan_recipients'   => ['nullable', 'string'],
         ]);

         foreach ($data as $key => $value) {
             SystemSetting::set('reports', $key, $value);
         }

         return back()->with('success', 'Pengaturan laporan otomatis berhasil disimpan.');
     }
 
     public function updateSeo(Request $request)
    {
        Gate::authorize('settings.system');

        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'site_description' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'favicon' => ['nullable', 'image', 'mimes:png,jpg,jpeg,ico,svg,webp', 'max:1028'],
        ]);

        SystemSetting::set('seo', 'site_name', $data['site_name']);
        SystemSetting::set('seo', 'site_description', $data['site_description'] ?? '');

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('system', 'public');
            SystemSetting::set('seo', 'logo', $logoPath);
        }

        if ($request->hasFile('favicon')) {
            $faviconPath = $request->file('favicon')->store('system', 'public');
            SystemSetting::set('seo', 'favicon', $faviconPath);
        }

        return back()->with('success', 'Pengaturan SEO & Branding berhasil disimpan.');
    }

    public function updateResellerBranding(Request $request)
    {
        Gate::authorize('settings.system');

        $data = $request->validate([
            'nama_brand' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'instagram' => ['nullable', 'string', 'max:255'],
            'tiktok' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
        ]);

        SystemSetting::set('reseller_branding', 'nama_brand', $data['nama_brand']);
        SystemSetting::set('reseller_branding', 'tagline', $data['tagline'] ?? '');
        SystemSetting::set('reseller_branding', 'email', $data['email'] ?? '');
        SystemSetting::set('reseller_branding', 'no_hp', $data['no_hp'] ?? '');
        SystemSetting::set('reseller_branding', 'alamat', $data['alamat'] ?? '');
        SystemSetting::set('reseller_branding', 'instagram', $data['instagram'] ?? '');
        SystemSetting::set('reseller_branding', 'tiktok', $data['tiktok'] ?? '');
        SystemSetting::set('reseller_branding', 'facebook', $data['facebook'] ?? '');

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('system', 'public');
            SystemSetting::set('reseller_branding', 'logo', $logoPath);
        }

        return back()->with('success', 'Pengaturan Branding Reseller berhasil disimpan.');
    }

    public function updateMail(Request $request)
    {
        Gate::authorize('settings.system');

        $data = $request->validate([
            'mail_host' => ['required', 'string', 'max:255'],
            'mail_port' => ['required', 'integer', 'between:1,65535'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:500'],
            'mail_encryption' => ['nullable', 'string', 'max:10'],
            'mail_from_address' => ['required', 'email', 'max:255'],
            'mail_from_name' => ['required', 'string', 'max:255'],
        ]);

        SystemSetting::set('mail', 'mail_host', $data['mail_host']);
        SystemSetting::set('mail', 'mail_port', (string) $data['mail_port']);
        SystemSetting::set('mail', 'mail_username', $data['mail_username'] ?? '');
        
        if ($request->filled('mail_password')) {
            SystemSetting::set('mail', 'mail_password', $data['mail_password'], encrypted: true);
        }
        
        SystemSetting::set('mail', 'mail_encryption', $data['mail_encryption'] ?? '');
        SystemSetting::set('mail', 'mail_from_address', $data['mail_from_address']);
        SystemSetting::set('mail', 'mail_from_name', $data['mail_from_name']);

        return back()->with('success', 'Pengaturan Mail Server berhasil disimpan.');
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
            'api_url'           => ['nullable', 'url', 'max:255'],
            'api_key'           => ['nullable', 'string', 'max:500'],
            'default_recipient' => ['nullable', 'string', 'max:100'],
            'sender_phone'      => ['nullable', 'string', 'max:50'],
        ]);

        SystemSetting::set('whatsapp', 'api_url', $data['api_url'] ?: 'https://api.sidobe.com/wa/v1');
        if (! empty($data['api_key'])) {
            SystemSetting::set('whatsapp', 'api_key', $data['api_key'], encrypted: true);
        }
        SystemSetting::set('whatsapp', 'default_recipient', $data['default_recipient']);
        SystemSetting::set('whatsapp', 'sender_phone', $data['sender_phone']);

        return back()->with('success', 'Pengaturan WhatsApp (Sidobe) tersimpan.');
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
            'customer_import_enabled' => ['boolean'],
        ]);

        SystemSetting::set('system', 'notification_channel', $data['notification_channel']);
        SystemSetting::set('system', 'whatsapp_enabled', $data['whatsapp_enabled'] ? '1' : '0');
        SystemSetting::set('system', 'telegram_enabled', $data['telegram_enabled'] ? '1' : '0');
        SystemSetting::set('system', 'customer_import_enabled', $data['customer_import_enabled'] ? '1' : '0');

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
