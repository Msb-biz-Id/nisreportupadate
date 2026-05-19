<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;
use App\Models\AiToolLog;
use App\Services\Ai\AiToolService;
use App\Services\Ai\GeminiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class AiToolsController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('tools.ai');

        $client = GeminiClient::fromSettings();

        return Inertia::render('Tools/AiIndex', [
            'tools' => array_values(AiToolService::tools()),
            'isConfigured' => $client->isConfigured(),
            'recentLogs' => AiToolLog::where('user_id', $request->user()->id)
                ->latest()->limit(10)->get(['id', 'tool_slug', 'status', 'created_at', 'tokens_used']),
        ]);
    }

    public function show(Request $request, string $slug)
    {
        Gate::authorize('tools.ai');
        $tools = AiToolService::tools();
        abort_unless(isset($tools[$slug]), 404);

        $client = GeminiClient::fromSettings();

        return Inertia::render('Tools/AiToolPage', [
            'tool' => $tools[$slug],
            'isConfigured' => $client->isConfigured(),
            'fields' => $this->fieldsFor($slug),
        ]);
    }

    public function run(Request $request, string $slug, AiToolService $service)
    {
        Gate::authorize('tools.ai');
        $tools = AiToolService::tools();
        abort_unless(isset($tools[$slug]), 404);

        $input = $request->validate([
            'pesan_asli' => ['nullable', 'string', 'max:5000'],
            'tujuan' => ['nullable', 'string', 'max:100'],
            'tone' => ['nullable', 'string', 'max:100'],
            'audiens' => ['nullable', 'string', 'max:100'],
            'panjang' => ['nullable', 'string', 'max:100'],
            'cta' => ['nullable', 'string', 'max:100'],
            'info' => ['nullable', 'string', 'max:1000'],
            'platform' => ['nullable', 'string', 'max:100'],
            'kategori' => ['nullable', 'string', 'max:100'],
            'target' => ['nullable', 'string', 'max:100'],
            'framework' => ['nullable', 'string', 'max:100'],
            'keunggulan' => ['nullable', 'string', 'max:1000'],
            'promo' => ['nullable', 'string', 'max:500'],
            'pesan_mentah' => ['nullable', 'string', 'max:5000'],
            'kategori_harga' => ['nullable', 'string', 'max:100'],
            'ongkir' => ['nullable', 'string', 'max:100'],
            'pembayaran' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'data_mentah' => ['nullable', 'string', 'max:5000'],
            'kategori_setelan' => ['nullable', 'string', 'max:100'],
            'atribut_cetak' => ['nullable', 'array'],
            'atribut_cetak.*' => ['string', 'max:50'],
            'urgensi' => ['nullable', 'string', 'max:50'],
            'format_output' => ['nullable', 'string', 'max:100'],
            'keluhan' => ['nullable', 'string', 'max:5000'],
            'akar_masalah' => ['nullable', 'string', 'max:100'],
            'opsi_solusi' => ['nullable', 'string', 'max:100'],
            'syarat_bukti' => ['nullable', 'string', 'max:100'],
            'nada' => ['nullable', 'string', 'max:100'],
        ]);

        $result = $service->run($slug, $input, $request->user());

        return response()->json($result);
    }

    private function fieldsFor(string $slug): array
    {
        $tone = ['Profesional', 'Ramah', 'Kasual', 'Empatik', 'Tegas'];

        return match ($slug) {
            'whatsapp-reply' => [
                ['name' => 'pesan_asli', 'label' => 'Pesan Asli dari Customer', 'type' => 'textarea', 'required' => true, 'rows' => 4],
                ['name' => 'tujuan', 'label' => 'Tujuan Balasan', 'type' => 'select', 'options' => ['Customer Service', 'Sales', 'Problem Solving', 'Follow-up', 'Penolakan', 'Personal']],
                ['name' => 'tone', 'label' => 'Tone & Gaya', 'type' => 'select', 'options' => $tone],
                ['name' => 'audiens', 'label' => 'Target Audiens', 'type' => 'select', 'options' => ['Klien VIP', 'Calon Pembeli', 'Rekan Kerja', 'Mahasiswa', 'Teman']],
                ['name' => 'panjang', 'label' => 'Panjang Pesan', 'type' => 'select', 'options' => ['Sangat Singkat', 'Sedang', 'Detail']],
                ['name' => 'cta', 'label' => 'Call to Action', 'type' => 'select', 'options' => ['Tidak Ada', 'Bertanya', 'Link', 'Konfirmasi', 'Bantuan']],
                ['name' => 'info', 'label' => 'Info Spesifik (opsional)', 'type' => 'textarea', 'rows' => 2],
            ],
            'copywriter' => [
                ['name' => 'platform', 'label' => 'Platform', 'type' => 'select', 'required' => true, 'options' => ['Instagram', 'Website', 'Facebook Ads', 'WhatsApp']],
                ['name' => 'kategori', 'label' => 'Kategori Produk', 'type' => 'select', 'options' => ['Jersey', 'Kaos', 'Jaket', 'Seragam']],
                ['name' => 'target', 'label' => 'Target Pasar', 'type' => 'select', 'options' => ['Tim/Klub', 'Gen Z', 'Penggemar', 'Komunitas']],
                ['name' => 'tone', 'label' => 'Tone', 'type' => 'select', 'options' => ['Hype', 'Eksklusif', 'Kasual', 'Persuasif']],
                ['name' => 'framework', 'label' => 'Framework Copywriting', 'type' => 'select', 'options' => ['AIDA', 'PAS', 'FAB', 'Storytelling']],
                ['name' => 'keunggulan', 'label' => 'Keunggulan Produk (comma-separated)', 'type' => 'textarea', 'required' => true, 'rows' => 2],
                ['name' => 'promo', 'label' => 'Promo (opsional)', 'type' => 'text'],
                ['name' => 'cta', 'label' => 'Call to Action', 'type' => 'select', 'options' => ['Klik bio', 'DM', 'Website', 'Chat WA']],
            ],
            'order-summarizer' => [
                ['name' => 'pesan_mentah', 'label' => 'Chat Pesanan Mentah dari Customer', 'type' => 'textarea', 'required' => true, 'rows' => 6],
                ['name' => 'kategori_harga', 'label' => 'Kategori Harga', 'type' => 'select', 'options' => ['Normal', 'Diskon', 'Reseller']],
                ['name' => 'ongkir', 'label' => 'Status Ongkir', 'type' => 'select', 'options' => ['Belum Termasuk', 'Cek Nanti', 'Gratis']],
                ['name' => 'pembayaran', 'label' => 'Metode Pembayaran', 'type' => 'select', 'options' => ['Transfer', 'E-Wallet']],
                ['name' => 'cta', 'label' => 'Call to Action', 'type' => 'select', 'options' => ['Minta ACC', 'Lengkapi Alamat']],
                ['name' => 'keterangan', 'label' => 'Keterangan Tambahan', 'type' => 'textarea', 'rows' => 2],
            ],
            'order-formatter' => [
                ['name' => 'data_mentah', 'label' => 'Data Mentah Pesanan', 'type' => 'textarea', 'required' => true, 'rows' => 8],
                ['name' => 'kategori_setelan', 'label' => 'Kategori Setelan', 'type' => 'select', 'options' => ['Atasan', 'Setelan', 'Lengkap']],
                ['name' => 'atribut_cetak', 'label' => 'Atribut Cetak', 'type' => 'checkbox_group', 'options' => ['Nama', 'Nomor', 'Logo', 'Sponsor']],
                ['name' => 'urgensi', 'label' => 'Urgensi', 'type' => 'select', 'options' => ['Normal', 'Prioritas']],
                ['name' => 'format_output', 'label' => 'Format Output', 'type' => 'select', 'options' => ['Tabel Rekap', 'SPK Gudang']],
            ],
            'complaint-handler' => [
                ['name' => 'keluhan', 'label' => 'Keluhan Customer', 'type' => 'textarea', 'required' => true, 'rows' => 5],
                ['name' => 'akar_masalah', 'label' => 'Akar Masalah', 'type' => 'select', 'options' => ['Produksi', 'Ekspedisi', 'Klien']],
                ['name' => 'opsi_solusi', 'label' => 'Opsi Solusi', 'type' => 'select', 'options' => ['Retur', 'Diskon', 'Next Order', 'Tolak']],
                ['name' => 'syarat_bukti', 'label' => 'Syarat Bukti', 'type' => 'select', 'options' => ['Video', 'Foto']],
                ['name' => 'nada', 'label' => 'Nada Bicara', 'type' => 'select', 'options' => ['Empatik', 'Tegas']],
            ],
            default => [],
        };
    }
}
