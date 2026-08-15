<?php
header('Content-Type: text/plain');

// Disable cache for this script output
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

echo "=== ProTrack Cache Reset Tool ===\n\n";

if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "✔ OPcache reset successfully!\n";
    } else {
        echo "❌ Failed to reset OPcache.\n";
    }
} else {
    echo "ℹ OPcache extension is not enabled or opcache_reset() is disabled.\n";
}

if (function_exists('apc_clear_cache')) {
    apc_clear_cache();
    echo "✔ APC user cache cleared!\n";
}

echo "\nDone. Please refresh your PDF page now.\n";
