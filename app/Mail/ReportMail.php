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

        // Convert simple markdown formatting (*bold*, \n) into HTML for rich email rendering
        $htmlBody = nl2br(e($this->reportContent));
        // Format bold text (*text* or **text**)
        $htmlBody = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $htmlBody);
        $htmlBody = preg_replace('/\*([^\*\n]+)\*/s', '<strong>$1</strong>', $htmlBody);

        return $this->subject($subject)
                    ->html("
                        <div style=\"font-family: Arial, sans-serif; background-color: #f8fafc; padding: 24px; color: #1e293b;\">
                            <div style=\"max-width: 640px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);\">
                                <div style=\"background-color: #a8001c; padding: 18px 24px; color: #ffffff;\">
                                    <h2 style=\"margin: 0; font-size: 18px; font-weight: 600;\">" . e($subject) . "</h2>
                                    <p style=\"margin: 4px 0 0 0; font-size: 12px; opacity: 0.85;\">Laporan Otomatis System — " . ucfirst($this->periode) . "</p>
                                </div>
                                <div style=\"padding: 24px; font-size: 14px; line-height: 1.6; color: #334155;\">
                                    {$htmlBody}
                                </div>
                                <div style=\"background-color: #f1f5f9; padding: 12px 24px; font-size: 11px; color: #64748b; text-align: center; border-top: 1px solid #e2e8f0;\">
                                    Dikirim secara otomatis oleh Sistem Report & Tracking PO.
                                </div>
                            </div>
                        </div>
                    ");
    }
}
