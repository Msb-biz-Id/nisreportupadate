<?php

namespace App\Support;

use ArPHP\I18N\Arabic;

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
        if (empty($text)) {
            return '';
        }

        // Escape HTML entities first (prevent XSS / broken HTML structure)
        $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // Regex patterns
        $cjkPattern      = '/[\x{3000}-\x{303F}\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{FF00}-\x{FFEF}\x{4E00}-\x{9FAF}\x{3400}-\x{4DBF}]+/u';
        $arabicPattern   = '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]+/u';
        $javanesePattern = '/[\x{A980}-\x{A9DF}]+/u';

        // Wrap CJK (Hiragana / Katakana / Kanji / Han) — browser & DOMPDF both handle
        // CJK glyph rendering correctly once the proper @font-face is embedded.
        $processed = preg_replace_callback($cjkPattern, function ($matches) {
            return '<span class="cjk-font">' . $matches[0] . '</span>';
        }, $escaped);

        // Wrap Javanese (Aksara Jawa)
        $processed = preg_replace_callback($javanesePattern, function ($matches) {
            return '<span class="javanese-font">' . $matches[0] . '</span>';
        }, $processed);

        // Arabic: reshape & reverse for DOMPDF before wrapping.
        // DOMPDF renders Unicode characters in their *isolated* (disconnected) form
        // without BiDi reordering. ArPHP::utf8Glyphs() converts logical-order Arabic
        // into pre-shaped visual-order glyphs so DOMPDF displays them joined correctly.
        $processed = preg_replace_callback($arabicPattern, function ($matches) {
            try {
                $reshaped = self::arPhp()->utf8Glyphs($matches[0]);
            } catch (\Throwable) {
                // Fallback: render un-shaped (still readable with Noto Sans Arabic font)
                $reshaped = $matches[0];
            }
            return '<span class="arabic-font" dir="rtl">' . $reshaped . '</span>';
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
        if (empty($text)) {
            return '';
        }

        $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        $cjkPattern      = '/[\x{3000}-\x{303F}\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{FF00}-\x{FFEF}\x{4E00}-\x{9FAF}\x{3400}-\x{4DBF}]+/u';
        $arabicPattern   = '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]+/u';
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
     * Converts the image into a base64 Data URI in-memory, completely bypassing
     * filesystem path limitations and ensuring consistent PDF rendering.
     * WebP images are converted to PNG in-memory using the GD library,
     * while other formats are encoded directly.
     *
     * @param string|null $path Relative path in storage/app/public/
     * @return string base64 Data URI or empty string if not found
     */
    public static function resolveImageForPdf(?string $path): string
    {
        if (empty($path)) {
            \Log::debug("PdfHelper::resolveImageForPdf - Empty path provided");
            return '';
        }

        // Clean/normalize path
        $normalizedPath = $path;
        
        // If it's a URL, parse and get the path component
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            $parsed = parse_url($path);
            $normalizedPath = ltrim($parsed['path'] ?? '', '/');
        }
        
        // Strip leading slashes and storage prefix
        $normalizedPath = ltrim($normalizedPath, '/');
        if (str_starts_with($normalizedPath, 'storage/')) {
            $normalizedPath = substr($normalizedPath, 8);
        }

        // Try candidate paths in order of preference
        $candidates = [
            storage_path('app/public/' . $normalizedPath),
            public_path('storage/' . $normalizedPath),
            public_path($normalizedPath),
            $path, // fallback to original input
        ];

        $fullPath = null;
        foreach ($candidates as $candidate) {
            if (!empty($candidate) && file_exists($candidate) && !is_dir($candidate)) {
                $fullPath = $candidate;
                break;
            }
        }

        if (!$fullPath) {
            \Log::warning("PdfHelper::resolveImageForPdf - File not found for input: {$path}", [
                'input_path' => $path,
                'normalized_path' => $normalizedPath,
                'searched_candidates' => $candidates
            ]);
            return '';
        }

        // Normalize slashes and resolve drive letters on Windows
        $realPath = realpath($fullPath);
        if (!$realPath) {
            \Log::warning("PdfHelper::resolveImageForPdf - Realpath failed for: {$fullPath}");
            $realPath = $fullPath;
        }

        try {
            $extension = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
            $mime = @mime_content_type($realPath) ?: '';

            // Handle WebP in-memory conversion to PNG
            if ($extension === 'webp' || $mime === 'image/webp') {
                \Log::debug("PdfHelper::resolveImageForPdf - Attempting WebP conversion for: {$realPath}");
                if (function_exists('imagecreatefromwebp')) {
                    $im = @imagecreatefromwebp($realPath);
                    if ($im) {
                        ob_start();
                        imagepng($im);
                        $pngData = ob_get_clean();
                        imagedestroy($im);
                        
                        \Log::info("PdfHelper::resolveImageForPdf - WebP converted successfully to PNG base64 in-memory: {$realPath}");
                        return 'data:image/png;base64,' . base64_encode($pngData);
                    } else {
                        \Log::error("PdfHelper::resolveImageForPdf - imagecreatefromwebp failed to read image at: {$realPath}");
                    }
                } else {
                    \Log::warning("PdfHelper::resolveImageForPdf - function imagecreatefromwebp does not exist, GD library might be missing WebP support.");
                }
            }

            // Fallback / default case for non-webp images or failed webp conversion
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
            \Log::error("PdfHelper::resolveImageForPdf - Exception during conversion: " . $e->getMessage(), [
                'exception' => $e
            ]);
        }

        return '';
    }
}

