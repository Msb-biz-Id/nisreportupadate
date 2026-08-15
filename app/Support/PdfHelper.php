<?php

namespace App\Support;

use ArPHP\I18N\Arabic;
use Illuminate\Support\Facades\Log;

class PdfHelper
{
    /** @var Arabic|null  Singleton instance of ArPHP for Arabic reshaping */
    private static ?Arabic $arabic = null;

    private static function arPhp(): Arabic
    {
        if (self::$arabic === null) {
            self::$arabic = new Arabic();
        }
        return self::$arabic;
    }

    private static function arabicToLatinDigits(string $string): string
    {
        $arabicIndic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $latin = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $string = str_replace($arabicIndic, $latin, $string);
        $string = str_replace($persian, $latin, $string);
        return $string;
    }

    /**
     * Normalize Quranic and extended Unicode Arabic characters (e.g. Alif Wasla ٱ U+0671,
     * Dagger Alef ٰ U+0670, Quranic Sukun ۡ U+06E1) into standard Arabic glyphs supported
     * by ArPHP and DomPDF ligature shaping engine.
     */
    private static function normalizeArabicForDompdf(string $string): string
    {
        $map = [
            "\u{0671}" => 'ا',        // Alif Wasla (ٱ) -> Standard Alif (ا)
            "\u{0670}" => '',         // Dagger Alef / Superscript Alef (ٰ) -> Strip for proper Allah ligature forming
            "\u{06E1}" => "\u{0652}", // Quranic Sukun (ۡ) -> Standard Sukun (ْ)
            "\u{06E5}" => '',         // Small Waw
            "\u{06E6}" => '',         // Small Ya
            "\u{06E7}" => '',         // Small High Yeh
            "\u{06E8}" => '',         // Small High Noon
        ];

        return strtr($string, $map);
    }

    /**
     * Clean and normalize text for PDF rendering.
     * Maps fancy fonts (mathematical alphanumeric symbols, circled chars) to standard ASCII
     * and strips/maps decorative symbols that render as boxes in standard PDF fonts.
     */
    private static function cleanPdfText(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        // 1. Compatibility decomposition to map fancy alphanumeric characters (e.g. 𝘼 -> A, ① -> 1)
        if (class_exists('Normalizer')) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_KC) ?: $text;
        }

        // 2. Map specific common symbols to standard equivalents
        $symbolMap = [
            '✅' => '[v]',
            '✔' => '[v]',
            '✓' => '[v]',
            '❤' => '<3',
            '♥' => '<3',
            '♡' => '<3',
        ];
        $text = strtr($text, $symbolMap);

        // 3. Remove other decorative Unicode symbols, shapes, emojis, dingbats that render as boxes in PDF
        $patterns = [
            '/[\x{2190}-\x{21FF}]/u', // Arrows
            '/[\x{2200}-\x{22FF}]/u', // Math Operators
            '/[\x{2300}-\x{23FF}]/u', // Misc Technical
            '/[\x{2400}-\x{243F}]/u', // Control Pictures
            '/[\x{25A0}-\x{25FF}]/u', // Geometric Shapes
            '/[\x{2600}-\x{26FF}]/u', // Misc Symbols (includes black/white stars, hearts)
            '/[\x{2700}-\x{27BF}]/u', // Dingbats (includes ✧ U+2727)
            '/[\x{27C0}-\x{2BFF}]/u', // Misc Math/Arrows
            '/[\x{1F000}-\x{1FFFF}]/u', // Emojis / Pictographs
        ];

        $cleaned = preg_replace($patterns, '', $text);

        // Normalize spaces: replace multiple spaces with a single space, and trim
        $cleaned = preg_replace('/\s+/u', ' ', $cleaned);
        return trim($cleaned);
    }

    /**
     * Format text for **PDF** output (DOMPDF).
     *
     * - Escapes HTML entities to prevent XSS.
     * - Wraps CJK (Japanese/Chinese/Korean) characters in <span class="cjk-font">.
     * - Reshapes Arabic text using ArPHP::utf8Glyphs() so connected glyphs are rendered
     *   correctly by DOMPDF (which lacks Unicode text-shaping support), then wraps it in
     *   <span class="arabic-font" dir="rtl">.
     *
     * For **web preview** use formatTextWeb() which skips reshaping and lets the
     * browser's own Unicode engine handle glyph joining.
     *
     * @param string|null $text
     * @return string HTML-safe string, may contain <span> wrappers
     */
    public static function formatText(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $text = self::cleanPdfText($text);

        // Escape HTML entities first (prevent XSS / broken HTML structure)
        $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // Regex patterns
        $cjkPattern      = '/[\x{3000}-\x{303F}\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{FF00}-\x{FFEF}\x{4E00}-\x{9FAF}\x{3400}-\x{4DBF}]+/u';
        $arabicPattern   = '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]+(?:\s+[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}0-9]+)*/u';
        $javanesePattern = '/[\x{A980}-\x{A9DF}]+/u';
        $thaiPattern     = '/[\x{0E00}-\x{0E7F}]+/u';

        // Wrap CJK (Hiragana / Katakana / Kanji / Han) — browser & DOMPDF both handle
        // CJK glyph rendering correctly once the proper @font-face is embedded.
        $processed = preg_replace_callback($cjkPattern, function ($matches) {
            return '<span class="cjk-font">' . $matches[0] . '</span>';
        }, $escaped);

        // Wrap Javanese (Aksara Jawa)
        $processed = preg_replace_callback($javanesePattern, function ($matches) {
            return '<span class="javanese-font">' . $matches[0] . '</span>';
        }, $processed);

        // Wrap Thai
        $processed = preg_replace_callback($thaiPattern, function ($matches) {
            return '<span class="thai-font">' . $matches[0] . '</span>';
        }, $processed);

        // Arabic: reshape & reverse for DOMPDF before wrapping.
        // DOMPDF renders Unicode characters in their *isolated* (disconnected) form
        // without BiDi reordering. ArPHP::utf8Glyphs() converts logical-order Arabic
        // into pre-shaped visual-order glyphs so DOMPDF displays them joined correctly.
        $processed = preg_replace_callback($arabicPattern, function ($matches) {
            try {
                $preProcessed = self::arabicToLatinDigits($matches[0]);
                $normalized   = self::normalizeArabicForDompdf($preProcessed);
                $reshaped     = self::arPhp()->utf8Glyphs($normalized);
                return '<span class="arabic-font">' . $reshaped . '</span>';
            } catch (\Throwable) {
                // Fallback: render un-shaped (still readable with Noto Sans Arabic font)
                return '<span class="arabic-font" dir="rtl">' . $matches[0] . '</span>';
            }
        }, $processed);

        return $processed;
    }

    /**
     * Format text for **web / browser** output.
     *
     * Identical to formatText() for CJK, but for Arabic it skips the ArPHP glyph
     * reshaping because browsers implement the Unicode BiDi algorithm natively and
     * reshape Arabic automatically. Applying utf8Glyphs() in a browser context would
     * produce double-shaped, broken output.
     *
     * @param string|null $text
     * @return string HTML-safe string, may contain <span> wrappers
     */
    public static function formatTextWeb(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        $cjkPattern      = '/[\x{3000}-\x{303F}\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{FF00}-\x{FFEF}\x{4E00}-\x{9FAF}\x{3400}-\x{4DBF}]+/u';
        $arabicPattern   = '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]+(?:\s+[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}0-9]+)*/u';
        $javanesePattern = '/[\x{A980}-\x{A9DF}]+/u';

        $processed = preg_replace_callback($cjkPattern, function ($matches) {
            return '<span class="cjk-font">' . $matches[0] . '</span>';
        }, $escaped);

        $processed = preg_replace_callback($javanesePattern, function ($matches) {
            return '<span class="javanese-font">' . $matches[0] . '</span>';
        }, $processed);

        // No reshaping — browser handles glyph joining + BiDi reordering natively
        $processed = preg_replace_callback($arabicPattern, function ($matches) {
            return '<span class="arabic-font" dir="rtl">' . $matches[0] . '</span>';
        }, $processed);

        return $processed;
    }

    /**
     * Resolve image path for DOMPDF.
     *
     * Returns physical local file path (`file:///...`) for JPEG/PNG to allow Dompdf
     * to read images directly from disk without Base64 memory overhead.
     * For legacy WebP images, converts to a persistent disk cache (`storage/app/pdf-cache/`)
     * once and returns the cached physical path.
     *
     * @param string|null $path Relative path in storage/app/public/
     * @return string Physical file URI or Base64 Data URI fallback, empty string if not found
     */
    public static function resolveImageForPdf(?string $path): string
    {
        if (empty($path)) {
            Log::debug("PdfHelper::resolveImageForPdf - Empty path provided");
            return '';
        }

        // Clean/normalize path
        $normalizedPath = $path;
        
        // If it's a URL, parse and get the path component
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            $parsed = parse_url($path);
            $normalizedPath = ltrim($parsed['path'] ?? '', '/');
        }
        
        // Strip leading slashes
        $normalizedPath = ltrim($normalizedPath, '/');
        
        // Strip common storage/public prefixes to obtain pure relative path
        $prefixesToStrip = ['storage/', 'public/storage/', 'app/public/', 'public/'];
        foreach ($prefixesToStrip as $prefix) {
            if (str_starts_with($normalizedPath, $prefix)) {
                $normalizedPath = substr($normalizedPath, strlen($prefix));
            }
        }

        // Try candidate paths in order of preference
        $candidates = [
            $path, // Direct input if already an absolute path
            storage_path('app/public/' . $normalizedPath),
            public_path('storage/' . $normalizedPath),
            public_path($normalizedPath),
            storage_path('app/' . $normalizedPath),
        ];

        $fullPath = null;
        foreach ($candidates as $candidate) {
            if (!empty($candidate) && file_exists($candidate) && !is_dir($candidate)) {
                $fullPath = $candidate;
                break;
            }
        }

        if (!$fullPath) {
            Log::warning("PdfHelper::resolveImageForPdf - File not found for input: {$path}", [
                'input_path' => $path,
                'normalized_path' => $normalizedPath,
                'searched_candidates' => $candidates
            ]);
            return '';
        }

        // Normalize slashes and resolve drive letters on Windows
        $realPath = realpath($fullPath) ?: $fullPath;

        try {
            $extension = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
            $mime = @mime_content_type($realPath) ?: '';

            // 1. JPEG or PNG: Return physical file URI directly (No Base64 memory overhead)
            if (in_array($extension, ['jpg', 'jpeg', 'png']) || str_contains($mime, 'jpeg') || str_contains($mime, 'png')) {
                return 'file:///' . str_replace('\\', '/', $realPath);
            }

            // 2. Legacy WebP: Convert once to persistent PDF disk cache & return physical path
            if ($extension === 'webp' || str_contains($mime, 'webp')) {
                $cachedPath = self::getOrCreateWebpPdfCache($realPath);
                if ($cachedPath && file_exists($cachedPath)) {
                    return 'file:///' . str_replace('\\', '/', $cachedPath);
                }
            }

            // 3. Fallback to in-memory Base64 for other formats (GIF, SVG) or failed conversions
            if (empty($mime)) {
                $mimeMap = [
                    'png' => 'image/png',
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                    'svg' => 'image/svg+xml',
                ];
                $mime = $mimeMap[$extension] ?? 'image/png';
            }
            
            $data = file_get_contents($realPath);
            if ($data !== false) {
                return 'data:' . $mime . ';base64,' . base64_encode($data);
            }
        } catch (\Throwable $e) {
            Log::error("PdfHelper::resolveImageForPdf - Exception during resolution: " . $e->getMessage(), [
                'exception' => $e
            ]);
        }

        return '';
    }

    /**
     * Konversi file WebP legacy ke file cache PDF (JPEG) di storage/app/pdf-cache/
     */
    private static function getOrCreateWebpPdfCache(string $realPath): ?string
    {
        try {
            $cacheDir = storage_path('app/pdf-cache');
            if (!file_exists($cacheDir)) {
                @mkdir($cacheDir, 0755, true);
            }

            $mtime = @filemtime($realPath) ?: 0;
            $hashKey = md5($realPath . '_' . $mtime);
            $targetCacheFile = $cacheDir . '/' . $hashKey . '.jpg';

            if (file_exists($targetCacheFile) && filesize($targetCacheFile) > 0) {
                return $targetCacheFile;
            }

            if (!function_exists('imagecreatefromwebp')) {
                Log::warning("PdfHelper: imagecreatefromwebp function missing in PHP GD");
                return null;
            }

            $im = @imagecreatefromwebp($realPath);
            if (!$im) {
                return null;
            }

            $tempFile = $cacheDir . '/' . $hashKey . '_' . uniqid() . '.tmp';
            
            $width = imagesx($im);
            $height = imagesy($im);
            $bg = imagecreatetruecolor($width, $height);
            $white = imagecolorallocate($bg, 255, 255, 255);
            imagefill($bg, 0, 0, $white);
            imagealphablending($bg, true);
            imagecopy($bg, $im, 0, 0, 0, 0, $width, $height);
            imagedestroy($im);

            $saved = @imagejpeg($bg, $tempFile, 98);
            imagedestroy($bg);

            if ($saved && file_exists($tempFile) && filesize($tempFile) > 0) {
                @rename($tempFile, $targetCacheFile);
                return $targetCacheFile;
            }
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
        } catch (\Throwable $e) {
            Log::error("PdfHelper::getOrCreateWebpPdfCache failed: " . $e->getMessage());
        }

        return null;
    }
}

