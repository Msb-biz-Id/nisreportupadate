<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Turnstile Configuration
    |--------------------------------------------------------------------------
    | Set TURNSTILE_ENABLED=true in .env to activate.
    | Get keys from: https://dash.cloudflare.com/ → Turnstile
    |
    | For local testing use:
    |   Site Key  : 1x00000000000000000000AA  (always passes)
    |   Secret Key: 1x0000000000000000000000000000000AA  (always passes)
    */

    'enabled'    => env('TURNSTILE_ENABLED', false),
    'site_key'   => env('TURNSTILE_SITE_KEY', ''),
    'secret_key' => env('TURNSTILE_SECRET_KEY', ''),

];
