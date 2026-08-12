<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectTitle,
        public string $reportContent,
        public string $periode = 'harian',
        public ?string $brandNama = null
    ) {}

    public function build()
    {
        $subject = $this->subjectTitle;
        if ($this->brandNama) {
            $subject .= " — {$this->brandNama}";
        }

        $formattedHtml = $this->parseReportToHtml($this->reportContent);

        return $this->subject($subject)
                    ->html("
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <meta charset=\"utf-8\">
                            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
                        </head>
                        <body style=\"font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 24px; color: #1e293b;\">
                            <div style=\"max-width: 680px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);\">
                                <div style=\"background: linear-gradient(135deg, #a8001c 0%, #7e0015 100%); padding: 24px 28px; color: #ffffff;\">
                                    <h2 style=\"margin: 0; font-size: 20px; font-weight: 700; letter-spacing: -0.01em;\">" . e($subject) . "</h2>
                                    <p style=\"margin: 6px 0 0 0; font-size: 13px; opacity: 0.9; font-weight: 500;\">Laporan Eksekutif Otomatis Sistem — " . ucfirst($this->periode) . "</p>
                                </div>
                                <div style=\"padding: 28px; font-size: 14px; line-height: 1.6; color: #334155;\">
                                    {$formattedHtml}
                                </div>
                                <div style=\"background-color: #f8fafc; padding: 16px 28px; font-size: 12px; color: #64748b; text-align: center; border-top: 1px solid #e2e8f0; font-weight: 500;\">
                                    Pesan ini dikirim otomatis oleh <strong>Sistem Report & Tracking PO</strong>.<br>
                                    <span style=\"font-size: 11px; color: #94a3b8;\">Mendukung WhatsApp, Telegram, dan Email Notification Engine.</span>
                                </div>
                            </div>
                        </body>
                        </html>
                    ");
    }

    private function parseReportToHtml(string $text): string
    {
        $lines = explode("\n", $text);
        $outputHtml = '';
        $inTable = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (empty($trimmed)) {
                if ($inTable) {
                    $outputHtml .= "</table></div>";
                    $inTable = false;
                }
                $outputHtml .= "<div style=\"height: 12px;\"></div>";
                continue;
            }

            // AI Insight Section
            if (str_contains($trimmed, 'ANALISIS & REKOMENDASI AI') || str_contains($trimmed, 'ACTION ITEMS')) {
                if ($inTable) {
                    $outputHtml .= "</table></div>";
                    $inTable = false;
                }
                $cleanTitle = str_replace(['*', '🤖', '📌'], '', $trimmed);
                $outputHtml .= "<div style=\"background-color: #f0f9ff; border-left: 4px solid #0284c7; padding: 14px 16px; border-radius: 8px; margin: 16px 0;\">";
                $outputHtml .= "<strong style=\"color: #0369a1; font-size: 14px;\">🤖 {$cleanTitle}</strong>";
                $outputHtml .= "</div>";
                continue;
            }

            // Section Header (e.g. 📊 *LAPORAN HARIAN SUPERADMIN*)
            if (str_starts_with($trimmed, '📊') || str_starts_with($trimmed, '🏭') || str_starts_with($trimmed, '💰') || str_starts_with($trimmed, '📌')) {
                if ($inTable) {
                    $outputHtml .= "</table></div>";
                    $inTable = false;
                }
                $cleanHeader = preg_replace('/\*(.*?)\*/', '<strong>$1</strong>', e($trimmed));
                $outputHtml .= "<h3 style=\"color: #0f172a; font-size: 16px; font-weight: 700; margin: 20px 0 10px 0; padding-bottom: 6px; border-bottom: 2px solid #e2e8f0;\">{$cleanHeader}</h3>";
                continue;
            }

            // Bullet Point / Key-Value Pair (e.g. • Total PO: 12)
            if (str_starts_with($trimmed, '•') || str_starts_with($trimmed, '-')) {
                $cleanLine = ltrim($trimmed, '•- ');
                $parts = explode(':', $cleanLine, 2);

                if (count($parts) === 2) {
                    if (!$inTable) {
                        $outputHtml .= "<div style=\"background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px 16px; margin-bottom: 12px;\"><table style=\"width: 100%; border-collapse: collapse;\">";
                        $inTable = true;
                    }
                    $key = preg_replace('/\*(.*?)\*/', '<strong>$1</strong>', e(trim($parts[0])));
                    $val = preg_replace('/\*(.*?)\*/', '<strong>$1</strong>', e(trim($parts[1])));

                    // Format progress bars if any
                    $val = preg_replace('/`\[([█░]+)\]\s*([\d\.]+%?)`/', '<div style="display:inline-block; vertical-align:middle; background:#e2e8f0; border-radius:4px; width:80px; height:8px; overflow:hidden; margin-right:6px;"><div style="background:#a8001c; height:100%; width:$2;"></div></div><strong>$2</strong>', $val);

                    $outputHtml .= "<tr style=\"border-bottom: 1px solid #f1f5f9;\"><td style=\"padding: 8px 0; color: #475569; font-size: 13px;\">{$key}</td><td style=\"padding: 8px 0; text-align: right; color: #0f172a; font-size: 13px;\">{$val}</td></tr>";
                    continue;
                }
            }

            // Regular line
            if ($inTable) {
                $outputHtml .= "</table></div>";
                $inTable = false;
            }
            $formattedLine = preg_replace('/\*(.*?)\*/', '<strong>$1</strong>', e($trimmed));
            $outputHtml .= "<p style=\"margin: 4px 0; color: #334155; font-size: 13px;\">{$formattedLine}</p>";
        }

        if ($inTable) {
            $outputHtml .= "</table></div>";
        }

        return $outputHtml;
    }
}
