<?php

return [
    App\Providers\AppServiceProvider::class,
    ...(app()->environment('local') ? [
        Laravel\Telescope\TelescopeServiceProvider::class,
        App\Providers\TelescopeServiceProvider::class,
    ] : []),
];
