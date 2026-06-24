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
        $cjkPattern    = '/[\x{3000}-\x{303F}\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{FF00}-\x{FFEF}\x{4E00}-\x{9FAF}\x{3400}-\x{4DBF}]+/u';
        $arabicPattern = '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]+/u';

        // Wrap CJK (Hiragana / Katakana / Kanji / Han) — browser & DOMPDF both handle
        // CJK glyph rendering correctly once the proper @font-face is embedded.
        $processed = preg_replace_callback($cjkPattern, function ($matches) {
            return '<span class="cjk-font">' . $matches[0] . '</span>';
        }, $escaped);

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

        $cjkPattern    = '/[\x{3000}-\x{303F}\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{FF00}-\x{FFEF}\x{4E00}-\x{9FAF}\x{3400}-\x{4DBF}]+/u';
        $arabicPattern = '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]+/u';

        $processed = preg_replace_callback($cjkPattern, function ($matches) {
            return '<span class="cjk-font">' . $matches[0] . '</span>';
        }, $escaped);

        // No reshaping — browser handles glyph joining + BiDi reordering natively
        $processed = preg_replace_callback($arabicPattern, function ($matches) {
            return '<span class="arabic-font" dir="rtl">' . $matches[0] . '</span>';
        }, $processed);

        return $processed;
    }

    private static array $tempFiles = [];

    /**
     * Resolve image path for DOMPDF.
     *
     * If the image is a WebP format, it will be dynamically converted to a temporary PNG
     * file because DOMPDF has performance issues/bugs rendering WebP.
     * For other formats, it normalizes and returns the absolute local filepath.
     *
     * @param string|null $path Relative path in storage/app/public/
     * @return string Absolute file path or empty string if not found
     */
    public static function resolveImageForPdf(?string $path): string
    {
        if (empty($path)) {
            return '';
        }

        $fullPath = storage_path('app/public/' . $path);
        if (!file_exists($fullPath)) {
            return '';
        }

        // Normalize slashes and resolve drive letters on Windows
        $realPath = realpath($fullPath);
        if (!$realPath) {
            return $fullPath;
        }

        try {
            $extension = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));

            if ($extension === 'webp' || mime_content_type($realPath) === 'image/webp') {
                if (function_exists('imagecreatefromwebp')) {
                    $im = @imagecreatefromwebp($realPath);
                    if ($im) {
                        $tempDir = storage_path('app/public/temp');
                        if (!is_dir($tempDir)) {
                            @mkdir($tempDir, 0777, true);
                        }
                        $tempFile = tempnam($tempDir, 'fo_webp_conv_');
                        if ($tempFile) {
                            $pngPath = $tempFile . '.png';
                            ob_start();
                            imagepng($im);
                            $pngData = ob_get_clean();
                            file_put_contents($pngPath, $pngData);
                            imagedestroy($im);
                            unlink($tempFile);

                            self::registerTempFile($pngPath);

                            return $pngPath;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // fallback
        }

        return $realPath;
    }

    private static function registerTempFile(string $path): void
    {
        self::$tempFiles[] = $path;
        
        static $registered = false;
        if (!$registered) {
            register_shutdown_function(fn() => self::cleanupTempFiles());
            $registered = true;
        }
    }

    public static function cleanupTempFiles(): void
    {
        foreach (self::$tempFiles as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
        self::$tempFiles = [];
    }
}

