<?php

namespace App\Support;

class PdfHelper
{
    /**
     * Formatting text for PDF output. Escapes HTML entities and wraps Japanese (CJK)
     * and Arabic characters in spans with custom styling classes.
     *
     * @param string|null $text
     * @return string
     */
    public static function formatText(?string $text): string
    {
        if (empty($text)) {
            return '';
        }

        // Escape original text first to prevent HTML/XSS injection
        $processed = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // Regex patterns for CJK (Japanese/Chinese/Korean) and Arabic characters
        $cjkPattern = '/[\x{3000}-\x{303F}\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{FF00}-\x{FFEF}\x{4E00}-\x{9FAF}\x{3400}-\x{4DBF}]+/u';
        $arabicPattern = '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]+/u';

        // Wrap Japanese text in cjk-font span
        $processed = preg_replace_callback($cjkPattern, function ($matches) {
            return '<span class="cjk-font">' . $matches[0] . '</span>';
        }, $processed);

        // Wrap Arabic text in arabic-font span (enforcing RTL direction)
        $processed = preg_replace_callback($arabicPattern, function ($matches) {
            return '<span class="arabic-font" dir="rtl">' . $matches[0] . '</span>';
        }, $processed);

        return $processed;
    }
}
