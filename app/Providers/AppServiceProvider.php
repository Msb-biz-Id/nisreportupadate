<?php

namespace App\Providers;

use App\Models\Order\Order;
use App\Models\Order\OrderPayment;
use App\Models\Order\Refund;
use App\Observers\OrderObserver;
use App\Observers\OrderPaymentObserver;
use App\Observers\RefundObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\Notifications\SidobeClient::class, function () {
            return \App\Services\Notifications\SidobeClient::fromSettings();
        });
        $this->app->singleton(\App\Services\Notifications\TelegramClient::class, function () {
            return \App\Services\Notifications\TelegramClient::fromSettings();
        });
        $this->app->singleton(\App\Services\Ai\GeminiClient::class, function () {
            return \App\Services\Ai\GeminiClient::fromSettings();
        });
    }

    public function boot(): void
    {
        // Force HTTPS jika APP_URL pakai https (production di belakang reverse proxy)
        if (str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Load Dynamic System Settings for Mail and SEO
        try {
            if (Schema::hasTable('system_settings')) {
                // Dynamic Mail config
                $mailHost = \App\Models\Settings\SystemSetting::get('mail', 'mail_host');
                if ($mailHost) {
                    config([
                        'mail.mailers.smtp.host' => $mailHost,
                        'mail.mailers.smtp.port' => (int) \App\Models\Settings\SystemSetting::get('mail', 'mail_port', 2525),
                        'mail.mailers.smtp.username' => \App\Models\Settings\SystemSetting::get('mail', 'mail_username'),
                        'mail.mailers.smtp.password' => \App\Models\Settings\SystemSetting::get('mail', 'mail_password'),
                        'mail.mailers.smtp.encryption' => \App\Models\Settings\SystemSetting::get('mail', 'mail_encryption', 'tls'),
                        'mail.from.address' => \App\Models\Settings\SystemSetting::get('mail', 'mail_from_address', 'no-reply@circlesportwear.com'),
                        'mail.from.name' => \App\Models\Settings\SystemSetting::get('mail', 'mail_from_name', 'Circle Sportwear'),
                    ]);
                }

                // Dynamic APP/SEO config
                $siteName = \App\Models\Settings\SystemSetting::get('seo', 'site_name');
                if ($siteName) {
                    config(['app.name' => $siteName]);
                }
            }
        } catch (\Exception $e) {
            // Prevent boot failures during migration or setup
        }

        Vite::prefetch(concurrency: 3);

        // Register Cache::forgetPattern macro to prevent BadMethodCallException
        \Illuminate\Support\Facades\Cache::macro('forgetPattern', function (string $pattern) {
            $driver = config('cache.default');
            if ($driver === 'redis') {
                try {
                    $redis = \Illuminate\Support\Facades\Redis::connection();
                    $prefix = config('cache.prefix');
                    $keys = $redis->keys($prefix . $pattern);
                    foreach ($keys as $key) {
                        $cleanKey = str_replace($prefix, '', $key);
                        \Illuminate\Support\Facades\Cache::forget($cleanKey);
                    }
                } catch (\Exception $e) {
                    // Fail silently
                }
                return;
            }
        });

        Gate::before(function ($user) {
            return $user->hasRole('superadmin') ? true : null;
        });

        Order::observe(OrderObserver::class);
        OrderPayment::observe(OrderPaymentObserver::class);
        Refund::observe(RefundObserver::class);
    }
}
