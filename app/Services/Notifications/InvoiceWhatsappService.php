<?php

namespace App\Services\Notifications;

use App\Models\Order\Invoice;

/**
 * InvoiceWhatsappService — membangun teks pesan WA invoice per kondisi (BRD 13.5.2)
 * dan mengirim via SidobeClient.
 */
class InvoiceWhatsappService
{
    public function __construct(private SidobeClient $sidobe) {}

    /**
     * Kirim WA invoice ke nomor HP pelanggan.
     *
     * @param  Invoice  $invoice  (harus sudah load: order.pelanggan, brand)
     * @param  string   $condition  'new_invoice' | 'reminder' | 'overdue'
     * @return array    ['success' => bool, 'mock' => bool, 'error' => string|null]
     */
    public function send(Invoice $invoice, string $condition = 'new_invoice'): array
    {
        if ($invoice->order) {
            $resellerBrand = $invoice->order->resolveResellerBrand();
            if ($resellerBrand) {
                $resellerBrand->load('parentBrand');
                $invoice->setRelation('brand', $resellerBrand);
            }
        }

        $phone = $this->phoneFromInvoice($invoice);

        if ($phone === '') {
            return ['success' => false, 'mock' => false, 'error' => 'Nomor HP pelanggan tidak tersedia atau tidak valid.'];
        }

        $message = $this->buildMessage($invoice, $condition);
        return $this->sidobe->send($phone, $message);
    }

    /**
     * Bangun teks pesan sesuai kondisi.
     */
    public function buildMessage(Invoice $invoice, string $condition): string
    {
        if ($invoice->order) {
            $resellerBrand = $invoice->order->resolveResellerBrand();
            if ($resellerBrand) {
                $resellerBrand->load('parentBrand');
                $invoice->setRelation('brand', $resellerBrand);
            }
        }

        $pelanggan  = $invoice->order?->pelanggan?->nama ?? 'Pelanggan';
        $brand      = $invoice->brand?->nama_brand ?? '';
        $invNumber  = $invoice->invoice_number;
        $total      = 'Rp ' . number_format((float) $invoice->total_tagihan, 0, ',', '.');
        $sisa       = 'Rp ' . number_format((float) $invoice->sisa_pembayaran, 0, ',', '.');
        $jatuhTempo = $invoice->jatuh_tempo?->translatedFormat('d M Y') ?? '-';
        $invoiceUrl = url(route('invoice.public', $invNumber, false));
        $trackUrl   = url('/track/' . ($invoice->order?->no_po ?? ''));

        return match ($condition) {
            'reminder'   => $this->templateReminder($pelanggan, $brand, $invNumber, $sisa, $jatuhTempo, $invoiceUrl, $trackUrl, $invoice),
            'overdue'    => $this->templateOverdue($pelanggan, $brand, $invNumber, $sisa, $jatuhTempo, $invoiceUrl, $trackUrl, $invoice),
            default      => $this->templateNewInvoice($pelanggan, $brand, $invNumber, $total, $sisa, $jatuhTempo, $invoiceUrl, $trackUrl),
        };
    }

    // ── Template A: Invoice baru dipublish ──────────────────────────────────
    private function templateNewInvoice(
        string $pelanggan, string $brand, string $invNumber,
        string $total, string $sisa, string $jatuhTempo,
        string $invoiceUrl, string $trackUrl,
    ): string {
        return "Halo *{$pelanggan}*! 👋\n\n"
            . "Terima kasih telah memesan di *{$brand}*. "
            . "Invoice pesanan Anda telah diterbitkan.\n\n"
            . "📄 *Detail Invoice:*\n"
            . "• Nomor Invoice : {$invNumber}\n"
            . "• Total Tagihan : {$total}\n"
            . "• Sisa Pembayaran : {$sisa}\n"
            . "• Jatuh Tempo    : {$jatuhTempo}\n\n"
            . "Lihat detail lengkap & lacak pesanan Anda di:\n"
            . "🔗 {$invoiceUrl}\n\n"
            . "📦 Tracking pesanan:\n"
            . "🔗 {$trackUrl}\n\n"
            . "Terima kasih atas kepercayaan Anda! 🙏\n"
            . "_{$brand}_";
    }

    // ── Template B: Reminder mendekati jatuh tempo ──────────────────────────
    private function templateReminder(
        string $pelanggan, string $brand, string $invNumber,
        string $sisa, string $jatuhTempo,
        string $invoiceUrl, string $trackUrl,
        Invoice $invoice,
    ): string {
        $hariLagi = now()->startOfDay()->diffInDays($invoice->jatuh_tempo, false);
        $hariStr  = $hariLagi <= 0 ? 'hari ini' : "dalam *{$hariLagi} hari* ({$jatuhTempo})";

        return "⏰ *Pengingat Pembayaran*\n\n"
            . "Halo *{$pelanggan}*,\n\n"
            . "Invoice *{$invNumber}* dari *{$brand}* akan jatuh tempo {$hariStr}.\n\n"
            . "💰 Sisa pembayaran: *{$sisa}*\n\n"
            . "Lihat invoice & lakukan pembayaran di:\n"
            . "🔗 {$invoiceUrl}\n\n"
            . "📦 Status pesanan:\n"
            . "🔗 {$trackUrl}\n\n"
            . "_Mohon segera melakukan pelunasan agar pesanan dapat diproses. Terima kasih!_\n"
            . "_{$brand}_";
    }

    // ── Template C: Sudah melewati jatuh tempo ──────────────────────────────
    private function templateOverdue(
        string $pelanggan, string $brand, string $invNumber,
        string $sisa, string $jatuhTempo,
        string $invoiceUrl, string $trackUrl,
        Invoice $invoice,
    ): string {
        $hariLewat = abs((int) now()->startOfDay()->diffInDays($invoice->jatuh_tempo, false));

        return "⚠️ *Invoice Jatuh Tempo*\n\n"
            . "Halo *{$pelanggan}*,\n\n"
            . "Invoice *{$invNumber}* dari *{$brand}* telah melewati jatuh tempo "
            . "({$jatuhTempo}) sejak *{$hariLewat} hari* yang lalu.\n\n"
            . "💰 Sisa pembayaran: *{$sisa}*\n\n"
            . "Segera selesaikan pembayaran melalui:\n"
            . "🔗 {$invoiceUrl}\n\n"
            . "📦 Status pesanan Anda:\n"
            . "🔗 {$trackUrl}\n\n"
            . "_Harap segera menghubungi kami jika ada pertanyaan. Terima kasih._\n"
            . "_{$brand}_";
    }

    /**
     * Normalisasi nomor HP ke format internasional (628xxx).
     * Mengembalikan '' jika input kosong / tidak valid.
     */
    public function normalizePhone(string $phone): string
    {
        $phone = trim(preg_replace('/[^0-9+]/', '', $phone));

        // Guard: kosong setelah strip karakter non-numerik
        if ($phone === '' || $phone === '+') {
            return '';
        }

        if (str_starts_with($phone, '0')) {
            return '62' . substr($phone, 1);
        }

        if (str_starts_with($phone, '+62')) {
            return substr($phone, 1); // hapus tanda +
        }

        if (! str_starts_with($phone, '62')) {
            return '62' . $phone;
        }

        return $phone;
    }

    /**
     * Ambil dan normalisasi nomor HP dari invoice.
     * Mengembalikan '' jika tidak ada nomor valid.
     */
    public function phoneFromInvoice(Invoice $invoice): string
    {
        return $this->normalizePhone(
            $invoice->order?->pelanggan?->nomor_hp ?? ''
        );
    }
}
