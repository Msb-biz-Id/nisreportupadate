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

        /** @var \Illuminate\Filesystem\FilesystemAdapter $publicDisk */
        $publicDisk = \Illuminate\Support\Facades\Storage::disk('public');

        $providersJson = SystemSetting::get('ai', 'providers', '[]');
        $providers = json_decode($providersJson, true) ?: [];

        $maskedProviders = array_map(function($p) {
            $keys = $p['api_keys'] ?? [];
            $p['api_keys_masked'] = array_map(function($k) {
                if ($k === 'local-or-keyless') return $k;
                return SystemSetting::maskedValue($k);
            }, $keys);
            $p['has_keys'] = count($keys) > 0;
            $p['api_keys'] = ''; // Kept empty to prevent original keys exposure to frontend
            return $p;
        }, $providers);

        return Inertia::render('Settings/Integrations', [
            'ai' => [
                'providers' => $maskedProviders,
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
                'email_enabled' => (bool) SystemSetting::get('system', 'email_enabled', true),
                'customer_import_enabled' => (bool) SystemSetting::get('system', 'customer_import_enabled', false),
                'theme_color' => SystemSetting::get('system', 'theme_color', '#a8001c'),
                'target_view' => SystemSetting::get('system', 'target_view', 'both'),
            ],
            'seo' => [
                'site_name' => SystemSetting::get('seo', 'site_name', 'Circle Sportwear - Tracking PO'),
                'site_description' => SystemSetting::get('seo', 'site_description', 'Sistem tracking PO dan invoice secara aman dan privat.'),
                'logo' => SystemSetting::get('seo', 'logo'),
                'logo_url' => SystemSetting::get('seo', 'logo') ? \App\Support\UrlHelper::clean($publicDisk->url(SystemSetting::get('seo', 'logo')), $request) : null,
                'favicon' => SystemSetting::get('seo', 'favicon'),
                'favicon_url' => SystemSetting::get('seo', 'favicon') ? \App\Support\UrlHelper::clean($publicDisk->url(SystemSetting::get('seo', 'favicon')), $request) : null,
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
                'logo_url' => SystemSetting::get('reseller_branding', 'logo') ? \App\Support\UrlHelper::clean($publicDisk->url(SystemSetting::get('reseller_branding', 'logo')), $request) : null,
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
            'available_roles' => Role::all()->sortBy($nameField = 'name')->pluck($nameField)->toArray(),
            'reports' => [
                'enable_auto_report'    => (bool)  SystemSetting::get('reports', 'enable_auto_report', false),
                'daily_report_time'     => SystemSetting::get('reports', 'daily_report_time', '08:00'),
                'weekly_report_day'     => SystemSetting::get('reports', 'weekly_report_day', 'monday'),
                'monthly_report_date'   => SystemSetting::get('reports', 'monthly_report_date', 'last_day'),
                'report_types'          => SystemSetting::get('reports', 'report_types', 'superadmin,brand,produksi,owner,keuangan'),
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
             'monthly_report_date'   => ['required', 'string', 'max:10'],
             'report_types'          => ['nullable', 'string'],
             'superadmin_recipients' => ['nullable', 'string'],
             'produksi_recipients'   => ['nullable', 'string'],
             'brand_recipients'      => ['nullable', 'string'],
             'owner_recipients'      => ['nullable', 'string'],
             'keuangan_recipients'   => ['nullable', 'string'],
         ]);

         foreach ($data as $key => $value) {
             SystemSetting::set('reports', $key, $value ?? '');
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
            'providers' => ['nullable', 'array'],
            'providers.*.id' => ['required', 'string'],
            'providers.*.name' => ['required', 'string', 'max:100'],
            'providers.*.base_url' => ['required', 'string', 'max:500'],
            'providers.*.model' => ['required', 'string', 'max:500'],
            'providers.*.api_keys' => ['nullable', 'string', 'max:5000'],
            'providers.*.is_active' => ['required', 'boolean'],
        ]);

        $existingProviders = json_decode(SystemSetting::get('ai', 'providers', '[]'), true) ?: [];
        $existingMap = collect($existingProviders)->keyBy('id')->toArray();

        $savedProviders = [];
        $submittedProviders = $data['providers'] ?? [];

        foreach ($submittedProviders as $p) {
            $id = $p['id'];
            $name = $p['name'];
            $baseUrl = rtrim($p['base_url'], '/');
            $model = $p['model'];
            $isActive = (bool) $p['is_active'];

            // Parse API keys
            $rawKeys = $p['api_keys'] ?? '';
            $keys = array_filter(array_map('trim', preg_split('/[\r\n,]+/', $rawKeys)));

            // If keys input is empty/masked, try to reuse existing keys
            if (empty($keys) && isset($existingMap[$id])) {
                $keys = $existingMap[$id]['api_keys'] ?? [];
            }

            $savedProviders[] = [
                'id' => $id,
                'name' => $name,
                'base_url' => $baseUrl,
                'model' => $model,
                'api_keys' => $keys,
                'is_active' => $isActive,
            ];
        }

        SystemSetting::set('ai', 'provider', 'openai_compatible');
        SystemSetting::set('ai', 'providers', json_encode($savedProviders), encrypted: true);

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
            'notification_channel' => ['required', 'in:whatsapp,telegram,email,both,all'],
            'whatsapp_enabled' => ['nullable', 'boolean'],
            'telegram_enabled' => ['nullable', 'boolean'],
            'email_enabled' => ['nullable', 'boolean'],
            'customer_import_enabled' => ['nullable', 'boolean'],
            'theme_color' => ['required', 'string', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'target_view' => ['nullable', 'in:both,revenue,pcs'],
        ]);

        SystemSetting::set('system', 'notification_channel', $data['notification_channel']);
        SystemSetting::set('system', 'whatsapp_enabled', $request->boolean('whatsapp_enabled') ? '1' : '0');
        SystemSetting::set('system', 'telegram_enabled', $request->boolean('telegram_enabled') ? '1' : '0');
        SystemSetting::set('system', 'email_enabled', $request->boolean('email_enabled') ? '1' : '0');
        SystemSetting::set('system', 'customer_import_enabled', $request->boolean('customer_import_enabled') ? '1' : '0');
        SystemSetting::set('system', 'theme_color', $data['theme_color']);
        SystemSetting::set('system', 'target_view', $data['target_view'] ?? 'pcs');

        \App\Services\ActivityLogger::log('update', 'settings', null, 'Perbarui pengaturan sistem & notifikasi');

        return back()->with('success', 'Pengaturan sistem tersimpan.');
    }

    public function testAi(Request $request)
    {
        Gate::authorize('settings.ai');
        $client = GeminiClient::fromSettings();
        $result = $client->generate('Halo, jawab dengan satu kalimat singkat untuk konfirmasi koneksi berhasil dalam Bahasa Indonesia.');

        return back()->with($result['success'] ? 'success' : 'error',
            $result['success']
                ? ($result['mock'] ? 'Mock mode aktif (API key belum dikonfigurasi).' : 'Koneksi AI OK [' . ($result['model'] ?? 'AI') . ']: ' . mb_strimwidth($result['text'], 0, 150, '…'))
                : 'Gagal: ' . ($result['error'] ?? 'unknown')
        );
    }

    public function testWhatsapp(Request $request)
    {
        Gate::authorize('settings.system');
        $client = SidobeClient::fromSettings();
        $to = $request->string('to')->toString() ?: SystemSetting::get('whatsapp', 'default_recipient', '6281234567890');
        $appName = SystemSetting::get('seo', 'site_name', config('app.name', 'ProTrack'));
        $result = $client->send($to, "Test pesan dari {$appName}. Jika kamu menerima ini, integrasi WhatsApp berhasil.");

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
        $appName = SystemSetting::get('seo', 'site_name', config('app.name', 'ProTrack'));
        $result = $client->send($chatId, "*Test* dari {$appName}.");

        return back()->with($result['success'] ? 'success' : 'error',
            $result['success']
                ? ($result['mock'] ? 'Mock mode (bot token belum dikonfigurasi).' : 'Telegram terkirim.')
                : 'Gagal: ' . ($result['error'] ?? 'unknown')
        );
    }

    public function testMail(Request $request)
    {
        Gate::authorize('settings.system');

        $to = $request->string('to')->toString() ?: SystemSetting::get('mail', 'mail_from_address', config('mail.from.address'));
        if (empty($to)) {
            $to = $request->user()?->email;
        }

        if (empty($to)) {
            return back()->with('error', 'Alamat email tujuan belum diisi.');
        }

        // Configure mail server dynamically from SystemSetting
        $host = SystemSetting::get('mail', 'mail_host', config('mail.mailers.smtp.host'));
        $port = SystemSetting::get('mail', 'mail_port', config('mail.mailers.smtp.port'));
        $username = SystemSetting::get('mail', 'mail_username', config('mail.mailers.smtp.username'));
        $password = SystemSetting::get('mail', 'mail_password', config('mail.mailers.smtp.password'));
        $encryption = SystemSetting::get('mail', 'mail_encryption', config('mail.mailers.smtp.encryption'));
        $fromAddress = SystemSetting::get('mail', 'mail_from_address', config('mail.from.address'));
        $fromName = SystemSetting::get('mail', 'mail_from_name', config('mail.from.name'));

        if ($host) {
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => $host,
            ]);
        }
        if ($port) config(['mail.mailers.smtp.port' => (int) $port]);
        if ($username) config(['mail.mailers.smtp.username' => $username]);
        if ($password) config(['mail.mailers.smtp.password' => $password]);
        if ($encryption) config(['mail.mailers.smtp.encryption' => $encryption]);
        if ($fromAddress) config(['mail.from.address' => $fromAddress]);
        if ($fromName) config(['mail.from.name' => $fromName]);

        try {
            $appName = SystemSetting::get('seo', 'site_name', config('app.name', 'ProTrack'));
            \Illuminate\Support\Facades\Mail::raw("Halo,\n\nIni adalah email uji coba dari {$appName}.\nJika Anda menerima email ini, integrasi Mail Server (SMTP) telah berhasil tersambung.", function ($message) use ($to, $appName) {
                $message->to($to)->subject("[{$appName}] Uji Coba Mail Server SMTP");
            });

            return back()->with('success', "Email uji coba berhasil dikirim ke {$to}.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mengirim email: ' . $e->getMessage());
        }
    }

    public function testReports(Request $request)
    {
        Gate::authorize('settings.system');

        $periode = $request->input('periode', 'harian');
        if (! in_array($periode, ['harian', 'mingguan', 'bulanan'])) {
            $periode = 'harian';
        }

        try {
            \Illuminate\Support\Facades\Artisan::call('reports:send', [
                'periode' => $periode,
                '--force' => true,
            ]);

            $output = \Illuminate\Support\Facades\Artisan::output();

            return back()->with('success', "Pengujian pengiriman laporan {$periode} selesai diproses:\n\n" . trim($output));
        } catch (\Throwable $e) {
            return back()->with('error', "Gagal menguji laporan: " . $e->getMessage());
        }
    }

    public function notifications(Request $request)
    {
        Gate::authorize('settings.system');

        $matrix = SystemSetting::getGroup('notification_matrix');
        if (empty($matrix)) {
            $defaults = [
                'order_published' => [
                    'in_app' => true,
                    'whatsapp' => false,
                    'telegram' => false,
                    'os_desktop' => true,
                    'roles' => ['admin_produksi', 'owner'],
                    'sound' => 'success-tada'
                ],
                'special_order_created' => [
                    'in_app' => true,
                    'whatsapp' => false,
                    'telegram' => false,
                    'os_desktop' => true,
                    'roles' => ['admin_keuangan', 'owner'],
                    'sound' => 'warning-alert'
                ],
                'progress_updated' => [
                    'in_app' => true,
                    'whatsapp' => false,
                    'telegram' => false,
                    'os_desktop' => true,
                    'roles' => ['admin_brand', 'admin_reseller', 'owner'],
                    'sound' => 'bell-chime'
                ],
                'rijek_reported' => [
                    'in_app' => true,
                    'whatsapp' => false,
                    'telegram' => false,
                    'os_desktop' => true,
                    'roles' => ['admin_brand', 'owner'],
                    'sound' => 'warning-alert'
                ],
                'refund_submitted' => [
                    'in_app' => true,
                    'whatsapp' => false,
                    'telegram' => false,
                    'os_desktop' => true,
                    'roles' => ['admin_keuangan', 'owner'],
                    'sound' => 'cash-register'
                ],
                'refund_processed' => [
                    'in_app' => true,
                    'whatsapp' => false,
                    'telegram' => false,
                    'os_desktop' => true,
                    'roles' => ['admin_brand', 'admin_reseller', 'owner'],
                    'sound' => 'bell-chime'
                ],
                'payment_submitted' => [
                    'in_app' => true,
                    'whatsapp' => false,
                    'telegram' => false,
                    'os_desktop' => true,
                    'roles' => ['admin_keuangan', 'owner'],
                    'sound' => 'cash-register'
                ],
                'payment_verified' => [
                    'in_app' => true,
                    'whatsapp' => false,
                    'telegram' => false,
                    'os_desktop' => true,
                    'roles' => ['admin_brand', 'owner'],
                    'sound' => 'success-tada'
                ],
                'unlock_requested' => [
                    'in_app' => true,
                    'whatsapp' => false,
                    'telegram' => false,
                    'os_desktop' => true,
                    'roles' => ['superadmin', 'owner', 'supervisor'],
                    'sound' => 'warning-alert'
                ],
                'order_unlocked' => [
                    'in_app' => true,
                    'whatsapp' => false,
                    'telegram' => false,
                    'os_desktop' => true,
                    'roles' => ['admin_brand', 'admin_reseller', 'owner'],
                    'sound' => 'success-tada'
                ],
                'relock_requested' => [
                    'in_app' => true,
                    'whatsapp' => false,
                    'telegram' => false,
                    'os_desktop' => true,
                    'roles' => ['superadmin', 'owner', 'supervisor'],
                    'sound' => 'warning-alert'
                ],
                'order_locked' => [
                    'in_app' => true,
                    'whatsapp' => false,
                    'telegram' => false,
                    'os_desktop' => true,
                    'roles' => ['admin_brand', 'admin_reseller', 'owner'],
                    'sound' => 'success-tada'
                ],
            ];
            foreach ($defaults as $key => $val) {
                SystemSetting::set('notification_matrix', $key, json_encode($val));
            }
            $matrix = SystemSetting::getGroup('notification_matrix');
        }

        $decodedMatrix = [];
        foreach ($matrix as $key => $val) {
            $decodedMatrix[$key] = is_string($val) ? json_decode($val, true) : $val;
        }

        $soundsPath = public_path('sounds');
        if (!is_dir($soundsPath)) {
            @mkdir($soundsPath, 0755, true);
        }

        $defaultSounds = [
            ['value' => 'bell-chime', 'label' => 'Pleasant Bell 🔔'],
            ['value' => 'success-tada', 'label' => 'Success Tada 🎉'],
            ['value' => 'warning-alert', 'label' => 'Sweep Alert ⚠️'],
            ['value' => 'cash-register', 'label' => 'Coins Register 🪙'],
        ];

        $mp3Files = glob($soundsPath . '/*.mp3');
        $customSounds = [];
        if ($mp3Files) {
            foreach ($mp3Files as $file) {
                $filename = basename($file, '.mp3');
                $isDefault = in_array($filename, ['bell-chime', 'success-tada', 'warning-alert', 'cash-register']);
                if (!$isDefault) {
                    $labelName = ucwords(str_replace(['-', '_'], ' ', $filename));
                    $customSounds[] = [
                        'value' => $filename,
                        'label' => $labelName . ' 🎵'
                    ];
                }
            }
        }

        $availableSounds = array_merge($defaultSounds, $customSounds);

        return Inertia::render('Settings/Notifications', [
            'notification_matrix' => $decodedMatrix,
            'available_roles' => Role::all()->sortBy($nameField = 'name')->pluck($nameField)->toArray(),
            'available_sounds' => $availableSounds
        ]);
    }

    public function updateMatrix(Request $request)
    {
        Gate::authorize('settings.system');

        $data = $request->validate([
            'matrix' => ['required', 'array'],
            'matrix.*.in_app' => ['required', 'boolean'],
            'matrix.*.whatsapp' => ['required', 'boolean'],
            'matrix.*.telegram' => ['required', 'boolean'],
            'matrix.*.email' => ['nullable', 'boolean'],
            'matrix.*.os_desktop' => ['required', 'boolean'],
            'matrix.*.roles' => ['nullable', 'array'],
            'matrix.*.roles.*' => ['string', \Illuminate\Validation\Rule::in(Role::pluck('name')->toArray())],
            'matrix.*.sound' => ['required', 'string', 'max:50'],
        ]);

        foreach ($data['matrix'] as $key => $val) {
            SystemSetting::set('notification_matrix', $key, json_encode($val));
        }

        return back()->with('success', 'Pengaturan matriks notifikasi dinamis berhasil disimpan.');
    }

    private function maskCsv(?string $csv): array
    {
        if (! $csv) return [];
        return collect(explode(',', $csv))->map(fn ($k) => SystemSetting::maskedValue(trim($k)))->all();
    }
}
