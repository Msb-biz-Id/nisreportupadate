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

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Force HTTPS jika APP_URL pakai https (production di belakang reverse proxy)
        if (str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Vite::prefetch(concurrency: 3);

        Gate::before(function ($user) {
            return $user->hasRole('superadmin') ? true : null;
        });

        Order::observe(OrderObserver::class);
        OrderPayment::observe(OrderPaymentObserver::class);
        Refund::observe(RefundObserver::class);
    }
}
