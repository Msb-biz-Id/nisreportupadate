<?php

namespace App\Mail;

use App\Models\Hcm\HcmEmployee;
use App\Models\Settings\SystemSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class HcmSlipEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public HcmEmployee $employee,
        public string $slipType,       // 'salary', 'meal_allowance', 'overtime'
        public string $slipTitle,      // e.g. 'Slip Gaji Bulanan', 'Slip Uang Makan', 'Slip Upah Lembur'
        public string $periodTitle,    // e.g. 'September 2026', 'Minggu ke-1 Oktober 2026'
        public float $netAmount,
        public string $pdfContent,
        public string $pdfFilename,
        public string $bankInfo = '',
        public ?string $notes = null
    ) {}

    public function build()
    {
        $companyName = SystemSetting::get('hcm_profile', 'company_name', config('app.name', 'NISGroup'));
        $divisionName = SystemSetting::get('hcm_profile', 'division_name', 'Divisi Human Capital Management');
        $signerName = SystemSetting::get('hcm_profile', 'signer_name', 'Head of HCM');

        $subject = "[{$companyName}] {$this->slipTitle} – {$this->periodTitle} – {$this->employee->name}";
        $formattedAmount = 'Rp ' . number_format($this->netAmount, 0, ',', '.');

        $badgeColor = match ($this->slipType) {
            'salary' => '#a8001c',
            'meal_allowance' => '#16a34a',
            'overtime' => '#ea580c',
            default => '#2563eb',
        };

        $htmlContent = "
        <!DOCTYPE html>
        <html lang='id'>
        <head>
            <meta charset='utf-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$subject}</title>
        </head>
        <body style='font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 24px; color: #1e293b;'>
            <div style='max-width: 640px; margin: 0 auto; background-color: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);'>
                
                <!-- HEADER RESMI -->
                <div style='background: linear-gradient(135deg, #a8001c 0%, #7e0015 100%); padding: 26px 30px; color: #ffffff;'>
                    <div style='font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; opacity: 0.85; font-weight: 700;'>
                        {$companyName} &bull; {$divisionName}
                    </div>
                    <h1 style='margin: 6px 0 0 0; font-size: 22px; font-weight: 800; letter-spacing: -0.02em;'>
                        {$this->slipTitle}
                    </h1>
                    <p style='margin: 4px 0 0 0; font-size: 13px; opacity: 0.9; font-weight: 500;'>
                        Periode: <strong>{$this->periodTitle}</strong>
                    </p>
                </div>

                <!-- BODY ISI SURAT -->
                <div style='padding: 28px 30px; font-size: 14px; line-height: 1.6; color: #334155;'>
                    <p style='margin-top: 0;'>
                        Halo, Sdr/i <strong>" . e($this->employee->name) . "</strong> (" . e($this->employee->employee_code) . "),
                    </p>
                    <p>
                        Bersama email ini kami sampaikan dokumen digital resmi <strong>{$this->slipTitle}</strong> Anda untuk periode <strong>{$this->periodTitle}</strong> yang telah melalui proses double sign-off pengesahan Divisi HCM dan otorisasi Divisi Keuangan.
                    </p>

                    <!-- KARTU RINGKASAN PENERIMAAN -->
                    <div style='background: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid {$badgeColor}; border-radius: 8px; padding: 18px 20px; margin: 20px 0;'>
                        <div style='font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;'>
                            Total Bersih Diterima
                        </div>
                        <div style='font-size: 24px; font-weight: 800; color: #0f172a; margin-top: 2px; font-family: monospace;'>
                            {$formattedAmount}
                        </div>
                        <div style='font-size: 12px; color: #475569; margin-top: 8px; border-top: 1px dashed #cbd5e1; padding-top: 8px;'>
                            <strong>Penyaluran Bank:</strong> " . e($this->bankInfo ?: ($this->employee->bank_name ?: 'Bank BRI') . ' - No Rek: ' . ($this->employee->bank_account_no ?: '-')) . "
                        </div>
                    </div>

                    <!-- PEMBERITAHUAN SETOR BANK BRI -->
                    <div style='background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 14px 16px; margin: 16px 0; font-size: 13px; color: #166534;'>
                        <strong>Pemberitahuan Rekening Bank BRI:</strong><br>
                        Otorisasi keuangan telah selesai dan tim keuangan telah menjadwalkan/melakukan penyetoran dana ke pihak Bank BRI. Mohon periksa saldo rekening Bank BRI Anda secara berkala sesuai dengan jadwal kliring perbankan.
                    </div>

                    <p style='font-size: 13px; color: #64748b;'>
                        Lampiran berkas PDF resmi ber-QR Code telah disertakan pada email ini untuk arsip pribadi Anda. Keabsahan slip dapat diverifikasi langsung melalui pemindaian QR code yang tertera pada dokumen PDF terlampir.
                    </p>
                </div>

                <!-- FOOTER -->
                <div style='background-color: #f8fafc; padding: 20px 30px; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0;'>
                    <p style='margin: 0; font-weight: 600; color: #334155;'>
                        Hormat kami,<br>
                        {$signerName}<br>
                        <span style='font-size: 11px; font-weight: normal; color: #64748b;'>{$divisionName} &bull; {$companyName}</span>
                    </p>
                    <p style='margin: 12px 0 0 0; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 8px;'>
                        Email ini dikirimkan secara otomatis oleh Sistem HRIS & Payroll {$companyName}. Mohon tidak membalas langsung ke alamat email sistem ini.
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";

        $mail = $this->subject($subject)->html($htmlContent);

        if (!empty($this->pdfContent)) {
            $mail->attachData($this->pdfContent, $this->pdfFilename, [
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}
