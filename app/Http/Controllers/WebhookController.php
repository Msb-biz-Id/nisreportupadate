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
}
