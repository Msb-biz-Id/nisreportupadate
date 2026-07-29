<?php

namespace App\Http\Controllers;

use App\Models\Order\Invoice;
use App\Services\Notifications\SidobeClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * WebhookController — menerima callback dari Sidobe WhatsApp API.
 * Docs: https://docs.sidobe.com
 *
 * Signature: sha256(secretKey + "|" + webhookId)
 * Header   : X-Webhook-Signature
 */
class WebhookController extends Controller
{
    /**
     * POST /webhooks/sidobe
     * Event: SEND_MESSAGE_STATUS
     */
    public function sidobe(Request $request): \Illuminate\Http\JsonResponse
    {
        $payload = $request->json()->all();

        // 1. Verifikasi signature
        $signature = $request->header('X-Webhook-Signature', '');
        $webhookId = $payload['id'] ?? '';

        $sidobe = SidobeClient::fromSettings();
        if (! $sidobe->verifyWebhookSignature($signature, $webhookId)) {
            Log::warning('Sidobe webhook: signature invalid', [
                'signature' => $signature,
                'webhookId' => $webhookId,
            ]);
            // Tetap return 200 agar Sidobe tidak retry (log untuk investigasi)
            return response()->json(['ok' => false, 'reason' => 'invalid_signature'], 200);
        }

        // 2. Proses event
        $event = $payload['event'] ?? '';
        Log::info('Sidobe webhook received', ['event' => $event, 'id' => $webhookId]);

        match ($event) {
            'SEND_MESSAGE_STATUS' => $this->handleMessageStatus($payload),
            default               => null,
        };

        return response()->json(['ok' => true]);
    }

    /**
     * Update status invoice berdasar delivery status pesan WA.
     *
     * Status Sidobe: PENDING | SUCCESS | FAILED
     */
    private function handleMessageStatus(array $payload): void
    {
        $data   = $payload['data'] ?? [];
        $status = $data['status'] ?? '';

        Log::info('Sidobe SEND_MESSAGE_STATUS', [
            'message_id' => $data['whatsapp_message_id'] ?? null,
            'status'     => $status,
            'sent_at'    => $data['send_at'] ?? null,
        ]);

        // Tidak ada aksi tambahan untuk saat ini — status pengiriman dicatat di log.
        // Jika perlu, bisa update invoice.status berdasar message_id di sini.
    }

    /**
     * POST /webhooks/telegram
     * Menerima updates dari Telegram Bot API.
     */
    public function telegram(Request $request): \Illuminate\Http\JsonResponse
    {
        $payload = $request->all();
        Log::info('Telegram webhook received', ['payload' => $payload]);

        $botToken = \App\Models\Settings\SystemSetting::get('telegram', 'bot_token');
        if (empty($botToken)) {
            return response()->json(['ok' => false, 'reason' => 'bot_token_not_configured']);
        }

        $message = $payload['message'] ?? null;
        if (! $message) {
            return response()->json(['ok' => true]);
        }

        $chatId = $message['chat']['id'] ?? null;
        if (! $chatId) {
            return response()->json(['ok' => true]);
        }

        // 1. Jika ada data Contact (User menekan tombol "Hubungkan Kontak")
        if (isset($message['contact'])) {
            $phone = $message['contact']['phone_number'] ?? '';
            if ($phone) {
                // Normalisasi nomor telepon dari Telegram (bisa berawalan '+' atau '0')
                $normalizedPhone = SidobeClient::normalizePhone($phone);

                // Cari user berdasarkan nomor HP yang terdaftar
                $user = \App\Models\User::all()->first(function ($u) use ($normalizedPhone) {
                    if (empty($u->phone)) return false;
                    return SidobeClient::normalizePhone($u->phone) === $normalizedPhone;
                });

                if ($user) {
                    $user->telegram_chat_id = (string) $chatId;
                    $user->save();

                    // Kirim konfirmasi berhasil
                    $this->sendTelegramMessage($botToken, $chatId, "✅ *Sukses!* Akun Telegram Anda telah terhubung dengan user *{$user->name}* ({$user->email}).\n\nAnda sekarang akan menerima laporan berkala dan notifikasi transaksi pribadi di sini secara otomatis.");
                } else {
                    // Kirim pesan gagal karena nomor HP tidak terdaftar
                    $this->sendTelegramMessage($botToken, $chatId, "⚠️ Nomor HP *{$phone}* tidak ditemukan di sistem database kami.\n\nSilakan pastikan nomor HP Anda di profil sistem sudah terdaftar dengan benar.");
                }
            }
            return response()->json(['ok' => true]);
        }

        // 2. Cek apakah user sudah terhubung sebelumnya
        $user = \App\Models\User::where('telegram_chat_id', (string) $chatId)->first();
        if (! $user) {
            // Jika belum terhubung, minta bagikan kontak
            $this->requestTelegramContact($botToken, $chatId);
            return response()->json(['ok' => true]);
        }

        // 3. Jika sudah terhubung, jalankan AI Chatbot!
        $text = trim($message['text'] ?? '');
        if (strtolower($text) === '/start') {
            $this->sendTelegramMessage($botToken, $chatId, "👋 Halo *{$user->name}*!\n\nAkun Anda telah terhubung. Tanyakan apa saja kepada saya tentang data order, invoice, atau laporan ringkas sesuai hak akses Anda.");
        } else {
            $this->handleAiChatbot($user, $text, $chatId, $botToken);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Proses pertanyaan Telegram via Gemini AI dengan konteks database & hak akses
     */
    private function handleAiChatbot(\App\Models\User $user, string $text, string $chatId, string $botToken): void
    {
        $gemini = \App\Services\Ai\GeminiClient::fromSettings();
        if (! $gemini->isConfigured()) {
            $this->sendTelegramMessage($botToken, $chatId, "⚠️ Layanan AI (Gemini) belum dikonfigurasi oleh Administrator.");
            return;
        }

        // Tentukan brand yang diizinkan sesuai hak akses user
        $brandIds = $user->isSuperadmin() 
            ? \App\Models\Brand::pluck('id')->all()
            : $user->brands()->pluck('brands.id')->all();

        $textLower = strtolower($text);

        // --- CONTEXT 1: STATISTIK KEUANGAN & OMSET (REAL-TIME) ---
        $financialSummary = [];
        if (preg_match('/(omset|omzet|uang|pendapatan|keuangan|penjualan|tagihan|dp|lunas|bayar|harga)/i', $textLower)) {
            $financialSummary = [
                'total_tagihan_po_aktif' => 'Rp' . number_format(\App\Models\Order\Order::whereIn('brand_id', $brandIds)->whereIn('status_po', ['published', 'on_progress', 'selesai_produksi'])->sum('total_tagihan'), 0, ',', '.'),
                'total_lunas_po' => \App\Models\Order\Order::whereIn('brand_id', $brandIds)->where('is_lunas', true)->count(),
                'total_belum_lunas_po' => \App\Models\Order\Order::whereIn('brand_id', $brandIds)->where('is_lunas', false)->count(),
                'total_pembayaran_diterima' => 'Rp' . number_format(\App\Models\Order\OrderPayment::whereHas('order', fn($q) => $q->whereIn('brand_id', $brandIds))->where('status', 'verified')->sum('jumlah_bayar'), 0, ',', '.'),
                'omset_hari_ini' => 'Rp' . number_format(\App\Models\Order\Order::whereIn('brand_id', $brandIds)->whereDate('created_at', today())->sum('total_tagihan'), 0, ',', '.'),
                'pembayaran_hari_ini' => 'Rp' . number_format(\App\Models\Order\OrderPayment::whereHas('order', fn($q) => $q->whereIn('brand_id', $brandIds))->where('status', 'verified')->whereDate('verified_at', today())->sum('jumlah_bayar'), 0, ',', '.'),
            ];
        }

        // --- CONTEXT 2: STATUS PRODUKSI & ANTREAN PO (REAL-TIME) ---
        $productionSummary = [];
        if (preg_match('/(produksi|po|status|kerja|proses|antrean|antri|selesai|kirim|deadline|tanggal|lambat|delay)/i', $textLower)) {
            $statuses = ['draft', 'published', 'on_progress', 'selesai_produksi', 'siap_dikirim', 'sudah_dikirim', 'delay', 'hold', 'selesai'];
            foreach ($statuses as $st) {
                $productionSummary[$st] = \App\Models\Order\Order::whereIn('brand_id', $brandIds)->where('status_po', $st)->count();
            }
            // Tambahkan PO deadline terdekat
            $productionSummary['po_deadline_terdekat'] = \App\Models\Order\Order::whereIn('brand_id', $brandIds)
                ->whereIn('status_po', ['published', 'on_progress'])
                ->whereNotNull('deadline_customer')
                ->orderBy('deadline_customer')
                ->limit(5)
                ->get()
                ->map(fn($o) => [
                    'no_po' => $o->no_po,
                    'nama_po' => $o->nama_po,
                    'status' => $o->status_po,
                    'deadline' => $o->deadline_customer,
                ])->toArray();
        }

        // --- CONTEXT 2.5: PO YANG TERLAMBAT (OVERDUE PO) ---
        $overdueSummary = [];
        if (preg_match('/(lambat|telat|terlambat|delay|overdue|lewat|deadline)/i', $textLower)) {
            $overdueOrders = \App\Models\Order\Order::whereIn('brand_id', $brandIds)
                ->where(function ($q) {
                    $q->where(function ($sub) {
                        $sub->whereNotNull('deadline_customer')
                            ->where('deadline_customer', '<', today()->format('Y-m-d'))
                            ->whereNotIn('status_po', ['sudah_dikirim', 'selesai', 'draft']);
                    })
                    ->orWhere('status_po', 'delay');
                })
                ->with(['brand', 'customer'])
                ->orderBy('deadline_customer')
                ->get();

            $overdueSummary['total_terlambat'] = $overdueOrders->count();
            $overdueSummary['detail_po_terlambat'] = $overdueOrders->map(function ($o) {
                $daysLate = $o->deadline_customer ? today()->diffInDays(\Carbon\Carbon::parse($o->deadline_customer), false) : null;
                // Selisih negatif berarti terlambat
                $lateText = $daysLate !== null && $daysLate < 0 ? abs($daysLate) . ' hari terlambat' : 'Terlambat';

                return [
                    'no_po' => $o->no_po,
                    'kode_order' => $o->kode_order,
                    'nama_po' => $o->nama_po,
                    'brand' => $o->brand->nama_brand ?? '',
                    'customer' => $o->customer->nama ?? '',
                    'status_po' => $o->status_po,
                    'deadline' => $o->deadline_customer,
                    'keterangan_telat' => $lateText,
                ];
            })->toArray();
        }

        // --- CONTEXT 3: PENCARIAN PO SPESIFIK & CUSTOMER ---
        $matchedOrders = [];
        $words = array_filter(array_map('trim', explode(' ', preg_replace('/[^A-Za-z0-9-]/', ' ', $text))));
        foreach ($words as $word) {
            if (strlen($word) >= 3) {
                // Cari PO berdasarkan No PO, Nama PO, Kode Order, atau Nama Pelanggan
                $found = \App\Models\Order\Order::whereIn('brand_id', $brandIds)
                    ->where(function ($q) use ($word) {
                        $q->where('no_po', 'like', "%{$word}%")
                          ->orWhere('nama_po', 'like', "%{$word}%")
                          ->orWhere('kode_order', 'like', "%{$word}%")
                          ->orWhereHas('customer', fn($c) => $c->where('nama', 'like', "%{$word}%"));
                    })
                    ->with(['brand', 'customer', 'items', 'payments'])
                    ->limit(5)
                    ->get();
                
                foreach ($found as $f) {
                    $verifiedPayments = $f->payments->where('status', 'verified')->sum('jumlah_bayar');
                    $sisaTagihan = max(0, $f->total_tagihan - $verifiedPayments);

                    $matchedOrders[$f->no_po] = [
                        'no_po' => $f->no_po,
                        'kode_order' => $f->kode_order,
                        'nama_po' => $f->nama_po,
                        'brand' => $f->brand->nama_brand ?? '',
                        'customer' => $f->customer->nama ?? '',
                        'status_po' => $f->status_po,
                        'total_tagihan' => 'Rp' . number_format($f->total_tagihan, 0, ',', '.'),
                        'sisa_tagihan' => 'Rp' . number_format($sisaTagihan, 0, ',', '.'),
                        'items' => $f->items->map(fn($it) => $it->nama_produk . ' (qty: ' . $it->qty . ')')->toArray(),
                        'payments' => $f->payments->map(fn($p) => 'Rp' . number_format($p->jumlah_bayar, 0, ',', '.') . ' (' . $p->status . ')')->toArray(),
                        'tanggal_masuk' => $f->tanggal_masuk,
                        'deadline' => $f->deadline_customer,
                    ];
                }
            }
        }

        // --- CONTEXT 4: STATISTIK GLOBAL BRAND ---
        $brandStats = [];
        $brands = \App\Models\Brand::whereIn('id', $brandIds)->get();
        foreach ($brands as $b) {
            $brandStats[] = [
                'nama' => $b->nama_brand,
                'kode' => $b->kode,
                'total_po' => \App\Models\Order\Order::where('brand_id', $b->id)->count(),
                'total_omset' => 'Rp' . number_format(\App\Models\Order\Order::where('brand_id', $b->id)->sum('total_tagihan'), 0, ',', '.'),
            ];
        }

        $context = [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->pluck('name')->implode(','),
            ],
            'accessible_brands' => $brands->pluck('nama_brand')->toArray(),
            'brand_statistics' => $brandStats,
            'realtime_financials' => $financialSummary,
            'realtime_production' => $productionSummary,
            'overdue_summary' => $overdueSummary,
            'matched_specific_orders' => array_values($matchedOrders),
        ];

        $prompt = <<<PROMPT
Kamu adalah AI Chatbot Asisten ProTrack (Sistem Tracking PO & Invoice Apparel).
Tugas kamu adalah menjawab pertanyaan user melalui Telegram berdasarkan data database dan hak akses yang diberikan secara akurat dan realtime.

DATA USER & HAK AKSES:
- Nama: {$user->name}
- Role: {$user->roles->pluck('name')->implode(', ')}
- Brand yang Boleh Diakses: {json_encode($context['accessible_brands'])}

RINGKASAN DATA DATABASE REAL-TIME (Hanya data ini yang sah dan boleh kamu gunakan):
Statistik Brand:
{json_encode($context['brand_statistics'])}

Informasi Keuangan Realtime:
{json_encode($context['realtime_financials'])}

Informasi Status Produksi & Antrean PO:
{json_encode($context['realtime_production'])}

Informasi PO Terlambat (Overdue):
{json_encode($context['overdue_summary'])}

Detail Order Terkait Pencarian Kata Kunci/Kode:
{json_encode($context['matched_specific_orders'])}

PERTANYAAN USER:
"{$text}"

ATURAN JAWABAN KETAT:
1. Jawablah menggunakan Bahasa Indonesia yang ramah, profesional, ringkas, dan jelas.
2. Gunakan format Markdown Telegram (seperti *tebal*, _miring_, `code`) agar mudah dibaca.
3. HAK AKSES KETAT: Jika user menanyakan data/brand yang tidak tertera pada daftar "Brand yang Boleh Diakses", kamu WAJIB menolak dengan sopan dan menyatakan bahwa Anda tidak memiliki hak akses untuk brand tersebut.
4. JANGAN mengarang data atau memunculkan data imajiner jika tidak ada di dalam ringkasan database di atas. Jika data tidak ada, katakan data tidak ditemukan.
PROMPT;

        $response = $gemini->generate($prompt);
        $answer = $response['text'] ?? 'Maaf, saya tidak dapat memproses jawaban saat ini.';

        $this->sendTelegramMessage($botToken, $chatId, $answer);
    }

    private function sendTelegramMessage(string $botToken, string $chatId, string $text): void
    {
        \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ]);
    }

    private function requestTelegramContact(string $botToken, string $chatId): void
    {
        \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => "Halo! Silakan klik tombol di bawah ini untuk menghubungkan nomor WhatsApp/HP Anda dengan Telegram agar dapat menerima notifikasi laporan pribadi otomatis.",
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode([
                'keyboard' => [
                    [
                        ['text' => 'Hubungkan Kontak / Nomor HP 📱', 'request_contact' => true]
                    ]
                ],
                'one_time_keyboard' => true,
                'resize_keyboard' => true
            ])
        ]);
    }
}
