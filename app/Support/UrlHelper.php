<?php

namespace App\Support;

use Illuminate\Support\Str;

class UrlHelper
{
    /**
     * Clean absolute internal/local URLs to be relative paths,
     * and upgrade external HTTP URLs to HTTPS if request is secure.
     *
     * @param string|null $url
     * @param \Illuminate\Http\Request|null $request
     * @return string|null
     */
    public static function clean(?string $url, $request = null): ?string
    {
        if (empty($url)) {
            return $url;
        }

        if (!$request) {
            $request = request();
        }

        if (Str::startsWith($url, 'http://') || Str::startsWith($url, 'https://')) {
            $parsed = parse_url($url);
            $path = $parsed['path'] ?? '';

            // If the path points to internal storage/ folder, convert to relative root path directly
            if (Str::startsWith(ltrim($path, '/'), 'storage/')) {
                $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
                return '/' . ltrim($path, '/') . $query;
            }

            $appUrlHost = parse_url(config('app.url'), PHP_URL_HOST);
            $urlHost = $parsed['host'] ?? '';
            $requestHost = $request ? $request->getHost() : null;

            // If URL host is local or matches the APP_URL host or the request host, convert to root-relative path
            if (
                $urlHost === '127.0.0.1' ||
                $urlHost === 'localhost' ||
                ($appUrlHost && $urlHost === $appUrlHost) ||
                ($requestHost && $urlHost === $requestHost)
            ) {
                $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
                return '/' . ltrim($path, '/') . $query;
            }

            // Upgrade external HTTP links if request is HTTPS
            if ($request && $request->isSecure() && Str::startsWith($url, 'http://')) {
                return 'https://' . Str::after($url, 'http://');
            }
        }

        return $url;
    }
}
