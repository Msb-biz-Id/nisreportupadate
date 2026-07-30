<?php

namespace App\Services\Reports;

class TelegramChartGenerator
{
    /**
     * Generate a beautiful bar chart image of brand performance and save to a temporary file.
     * Returns the absolute path of the generated PNG file.
     */
    public static function generateBarChart(string $title, array $data): string
    {
        $width = 600;
        $height = 400;

        // 1. Create canvas
        $img = imagecreatetruecolor($width, $height);

        // 2. Allocate colors
        $bg = imagecolorallocate($img, 30, 41, 59);        // Sleek Dark Slate #1e293b
        $white = imagecolorallocate($img, 255, 255, 255);  // #ffffff
        $gridColor = imagecolorallocate($img, 71, 85, 105); // #475569
        $barColor = imagecolorallocate($img, 14, 165, 233); // Bright Teal #0ea5e9
        $barColorAccent = imagecolorallocate($img, 249, 115, 22); // Orange Accent #f97316
        $labelColor = imagecolorallocate($img, 148, 163, 184); // #94a3b8

        // Fill background
        imagefill($img, 0, 0, $bg);

        // 3. Draw Title (built-in font size 5 is boldest)
        $titleLength = strlen($title) * 9; // Size 5 character is ~9px wide
        $titleX = (int) (($width - $titleLength) / 2);
        imagestring($img, 5, $titleX, 20, $title, $white);

        // 4. Calculate boundaries
        $paddingLeft = 80;
        $paddingRight = 40;
        $paddingTop = 70;
        $paddingBottom = 60;

        $chartWidth = $width - $paddingLeft - $paddingRight;
        $chartHeight = $height - $paddingTop - $paddingBottom;

        // Draw axis lines
        imageline($img, $paddingLeft, $paddingTop, $paddingLeft, $height - $paddingBottom, $white);
        imageline($img, $paddingLeft, $height - $paddingBottom, $width - $paddingRight, $height - $paddingBottom, $white);

        if (empty($data)) {
            // No data placeholder
            $noDataText = 'TIDAK ADA DATA UNTUK DITAMPILKAN';
            $textX = (int) (($width - (strlen($noDataText) * 9)) / 2);
            imagestring($img, 5, $textX, (int) ($height / 2), $noDataText, $labelColor);
            
            $tmpFile = tempnam(sys_get_temp_dir(), 'chart_') . '.png';
            imagepng($img, $tmpFile);
            imagedestroy($img);
            return $tmpFile;
        }

        // Get max value for scaling
        $maxValue = 0;
        foreach ($data as $item) {
            if ($item['value'] > $maxValue) {
                $maxValue = $item['value'];
            }
        }
        if ($maxValue === 0) {
            $maxValue = 100;
        }

        // Round max value to clean grid intervals
        $gridIntervals = 4;
        $maxValRounded = self::roundUpMax($maxValue);

        // Draw horizontal grid lines and Y-axis labels
        for ($i = 0; $i <= $gridIntervals; $i++) {
            $val = ($maxValRounded / $gridIntervals) * $i;
            $y = (int) ($height - $paddingBottom - (($val / $maxValRounded) * $chartHeight));
            
            // Grid line
            if ($i > 0) {
                imageline($img, $paddingLeft, $y, $width - $paddingRight, $y, $gridColor);
            }

            // Y-Label
            $yLabel = self::formatShort($val);
            $yLabelX = $paddingLeft - (strlen($yLabel) * 6) - 10;
            imagestring($img, 2, $yLabelX, $y - 6, $yLabel, $labelColor);
        }

        // 5. Draw Bars & X-axis labels
        $count = count($data);
        $barGap = 15;
        $totalGapsWidth = $barGap * ($count + 1);
        $barWidth = (int) (($chartWidth - $totalGapsWidth) / $count);

        foreach ($data as $index => $item) {
            $val = $item['value'];
            $label = $item['label'];

            // Calculate coordinates
            $x1 = $paddingLeft + $barGap + $index * ($barWidth + $barGap);
            $x2 = $x1 + $barWidth;
            
            $barHeight = (int) (($val / $maxValRounded) * $chartHeight);
            $y1 = $height - $paddingBottom - $barHeight;
            $y2 = $height - $paddingBottom - 1;

            // Pick color (alternate accent color for first bar or alternating)
            $colorToUse = ($index === 0) ? $barColorAccent : $barColor;

            // Draw filled bar
            if ($barHeight > 0) {
                imagefilledrectangle($img, $x1, $y1, $x2, $y2, $colorToUse);
            }

            // Draw X-Label
            $shortLabel = mb_strimwidth($label, 0, 8, '..');
            $labelX = (int) ($x1 + ($barWidth - (strlen($shortLabel) * 6)) / 2);
            imagestring($img, 2, $labelX, $height - $paddingBottom + 10, $shortLabel, $white);

            // Draw value on top of the bar
            $valLabel = self::formatShort($val);
            $valY = $y1 - 18;
            if ($valY < $paddingTop) {
                $valY = $y1 + 5;
            }
            $valLabelX = (int) ($x1 + ($barWidth - (strlen($valLabel) * 6)) / 2);
            imagestring($img, 2, $valLabelX, $valY, $valLabel, $white);
        }

        // Save image to temp file
        $tmpFile = tempnam(sys_get_temp_dir(), 'chart_') . '.png';
        imagepng($img, $tmpFile);
        imagedestroy($img);

        return $tmpFile;
    }

    private static function roundUpMax(float $max): float
    {
        if ($max <= 10) return 10;
        if ($max <= 100) return ceil($max / 10) * 10;
        if ($max <= 1000) return ceil($max / 100) * 100;
        
        $len = strlen((int)$max);
        $factor = pow(10, $len - 2);
        return ceil($max / $factor) * $factor;
    }

    private static function formatShort(float $val): string
    {
        if ($val >= 1000000000) {
            return round($val / 1000000000, 1) . 'B';
        }
        if ($val >= 1000000) {
            return round($val / 1000000, 1) . 'M';
        }
        if ($val >= 1000) {
            return round($val / 1000, 1) . 'K';
        }
        return (string) $val;
    }
}
