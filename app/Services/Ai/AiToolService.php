<?php

namespace App\Services\Ai;

use App\Models\AiToolLog;
use App\Models\User;

class AiToolService
{
    public function __construct(private readonly GeminiClient $client) {}

    public static function tools(): array
    {
        return [
            'whatsapp-reply' => [
                'slug' => 'whatsapp-reply',
                'label' => 'WhatsApp Reply Generator',
                'icon' => 'MessageCircle',
                'description' => 'Bikin balasan WhatsApp profesional dari pesan customer.',
            ],
            'copywriter' => [
                'slug' => 'copywriter',
                'label' => 'Apparel Copywriter',
                'icon' => 'PenLine',
                'description' => 'Generate copywriting marketing untuk produk apparel/jersey.',
            ],
            'order-summarizer' => [
                'slug' => 'order-summarizer',
                'label' => 'Smart Order Summarizer',
                'icon' => 'ListChecks',
                'description' => 'Ringkas chat pesanan customer jadi summary terstruktur.',
            ],
            'order-formatter' => [
                'slug' => 'order-formatter',
                'label' => 'Order Formatter (SPK)',
                'icon' => 'FileText',
                'description' => 'Ekstrak detail pesanan jadi format SPK siap produksi.',
            ],
            'complaint-handler' => [
                'slug' => 'complaint-handler',
                'label' => 'Complaint Handler',
                'icon' => 'AlertCircle',
                'description' => 'Generate respons profesional untuk komplain customer.',
            ],
        ];
    }

    public function run(string $slug, array $input, ?User $user = null): array
    {
        $prompt = $this->buildPrompt($slug, $input);
        $result = $this->client->generate($prompt);

        AiToolLog::create([
            'user_id' => $user?->id,
            'tool_slug' => $slug,
            'input' => $input,
            'output' => $result['text'] ?? null,
            'tokens_used' => $result['tokens'] ?? null,
            'model' => $result['model'] ?? null,
            'status' => $result['success'] ? 'success' : 'failed',
            'error_message' => $result['error'] ?? null,
        ]);

        return $result;
    }

    private function buildPrompt(string $slug, array $input): string
    {
        return match ($slug) {
            'whatsapp-reply' => $this->whatsappReplyPrompt($input),
            'copywriter' => $this->copywriterPrompt($input),
            'order-summarizer' => $this->orderSummarizerPrompt($input),
            'order-formatter' => $this->orderFormatterPrompt($input),
            'complaint-handler' => $this->complaintHandlerPrompt($input),
            default => '',
        };
    }

    private function whatsappReplyPrompt(array $i): string
    {
        return <<<PROMPT
Kamu adalah customer service profesional sebuah bisnis apparel/jersey custom di Indonesia.
Tugas: balas pesan customer berikut dengan tone & gaya yang sesuai.

PESAN ASLI:
"{$this->v($i, 'pesan_asli')}"

KONTEKS BALASAN:
- Tujuan: {$this->v($i, 'tujuan')} (cs/sales/problem solving/follow-up/penolakan/personal)
- Tone & Gaya: {$this->v($i, 'tone')} (profesional/ramah/kasual/empatik/tegas)
- Target Audiens: {$this->v($i, 'audiens')} (klien VIP/calon pembeli/rekan kerja/dll)
- Panjang Pesan: {$this->v($i, 'panjang')} (sangat singkat/sedang/detail)
- Call to Action: {$this->v($i, 'cta')} (tidak ada/bertanya/link/konfirmasi/bantuan)
- Info Spesifik: {$this->v($i, 'info')}

ATURAN:
1. Bahasa Indonesia yang natural.
2. Jangan kaku, hindari template generik.
3. Sesuaikan dengan tone yang diminta.
4. Langsung berikan balasan saja, tanpa preamble seperti "Berikut balasannya:".
PROMPT;
    }

    private function copywriterPrompt(array $i): string
    {
        return <<<PROMPT
Kamu adalah copywriter profesional untuk brand apparel/jersey custom di Indonesia.
Tugas: buat copywriting marketing yang menarik untuk platform yang ditentukan.

DETAIL PRODUK:
- Platform: {$this->v($i, 'platform')} (Instagram/Website/Facebook Ads/WhatsApp)
- Kategori Produk: {$this->v($i, 'kategori')} (Jersey/Kaos/Jaket/Seragam)
- Target Pasar: {$this->v($i, 'target')} (Tim/Klub/Gen Z/Penggemar/Komunitas)
- Tone: {$this->v($i, 'tone')} (Hype/Eksklusif/Kasual/Persuasif)
- Framework: {$this->v($i, 'framework')} (AIDA/PAS/FAB/Storytelling)
- Keunggulan: {$this->v($i, 'keunggulan')}
- Promo: {$this->v($i, 'promo')}
- Call to Action: {$this->v($i, 'cta')} (Klik bio/DM/Website/Chat WA)

ATURAN:
1. Bahasa Indonesia, sesuaikan dengan target pasar.
2. Pakai emoji secukupnya untuk platform sosmed.
3. Pesan jelas, hindari clichés.
4. Sertakan hashtag relevan di akhir kalau platform Instagram/Facebook.
PROMPT;
    }

    private function orderSummarizerPrompt(array $i): string
    {
        return <<<PROMPT
Kamu adalah admin penjualan apparel/jersey custom. Tugas: rapikan chat pesanan customer berikut menjadi ringkasan terstruktur.

PESAN MENTAH:
"{$this->v($i, 'pesan_mentah')}"

KONTEKS:
- Kategori Harga: {$this->v($i, 'kategori_harga')} (Normal/Diskon/Reseller)
- Status Ongkir: {$this->v($i, 'ongkir')} (Belum termasuk/Cek nanti/Gratis)
- Metode Pembayaran: {$this->v($i, 'pembayaran')} (Transfer/E-Wallet)
- Call to Action: {$this->v($i, 'cta')} (Minta ACC/Lengkapi alamat)
- Keterangan: {$this->v($i, 'keterangan')}

FORMAT OUTPUT (gunakan struktur markdown):
**Ringkasan Pesanan**
- **Nama:** ...
- **Kontak:** ...
- **Produk & Qty:** (list)
- **Spesifikasi:** ...
- **Alamat:** ...
- **Total Estimasi:** ...
- **Catatan:** ...

**Pesan Konfirmasi:**
(satu paragraf untuk dikirim ke customer)
PROMPT;
    }

    private function orderFormatterPrompt(array $i): string
    {
        $atributCetak = is_array($i['atribut_cetak'] ?? null) ? implode(', ', $i['atribut_cetak']) : ($i['atribut_cetak'] ?? '-');
        return <<<PROMPT
Kamu adalah admin produksi apparel. Tugas: ekstrak detail pesanan customer menjadi format SPK siap-produksi.

DATA MENTAH:
"{$this->v($i, 'data_mentah')}"

KONTEKS:
- Kategori Setelan: {$this->v($i, 'kategori_setelan')} (Atasan/Setelan/Lengkap)
- Atribut Cetak: $atributCetak
- Urgensi: {$this->v($i, 'urgensi')} (Normal/Prioritas)
- Format Output: {$this->v($i, 'format_output')} (Tabel Rekap/SPK Gudang)

ATURAN:
1. Output dalam tabel markdown.
2. Kolom: No, Nama Punggung, No. Punggung, Size, Keterangan.
3. Sertakan rekap size di bawah tabel.
4. Tandai item urgent dengan emoji ⚡ jika urgensi = Prioritas.
PROMPT;
    }

    private function complaintHandlerPrompt(array $i): string
    {
        return <<<PROMPT
Kamu adalah customer service profesional yang menangani komplain. Tugas: buat respons yang menenangkan customer dan jelas tentang langkah selanjutnya.

KELUHAN:
"{$this->v($i, 'keluhan')}"

DIAGNOSIS:
- Akar Masalah: {$this->v($i, 'akar_masalah')} (Produksi/Ekspedisi/Klien)
- Opsi Solusi: {$this->v($i, 'opsi_solusi')} (Retur/Diskon/Next Order/Tolak)
- Syarat Bukti: {$this->v($i, 'syarat_bukti')} (Video/Foto)
- Nada Bicara: {$this->v($i, 'nada')} (Empatik/Tegas)

ATURAN:
1. Mulai dengan empati & permohonan maaf yang tulus (jika sesuai).
2. Jelaskan akar masalah dengan singkat.
3. Tawarkan solusi yang ditentukan + syarat bukti.
4. Tutup dengan langkah selanjutnya yang jelas.
5. Bahasa Indonesia, tone sesuai yang diminta.
PROMPT;
    }

    private function v(array $arr, string $key): string
    {
        return trim((string) ($arr[$key] ?? '-'));
    }
}
