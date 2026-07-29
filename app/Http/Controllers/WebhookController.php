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
                $normalizedPhone = \App\Services\Notifications\SidobeClient::normalizePhone($phone);

                // Cari user berdasarkan nomor HP yang terdaftar
                $user = \App\Models\User::all()->first(function ($u) use ($normalizedPhone) {
                    if (empty($u->phone)) return false;
                    return \App\Services\Notifications\SidobeClient::normalizePhone($u->phone) === $normalizedPhone;
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

        // 2. Jika pesan teks biasa (misal '/start' atau apa saja)
        // Kita minta mereka membagikan kontak menggunakan reply keyboard
        $this->requestTelegramContact($botToken, $chatId);

        return response()->json(['ok' => true]);
    }

    private function sendTelegramMessage(string $botToken, string $chatId, string $text): void
    {
        \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode([
                'remove_keyboard' => true // Hapus keyboard setelah sukses
            ])
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
