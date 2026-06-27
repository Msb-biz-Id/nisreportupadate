<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Broadcasting\PrivateChannel;
use App\Channels\WhatsappChannel;
use App\Channels\TelegramChannel;

class SystemEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $eventKey,
        public array $payload,
        public array $settings
    ) {
        if (app()->environment('local') && config('queue.default') === 'sync') {
            $this->connection = 'sync';
        }
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $channels = [];

        if ($this->settings['in_app'] ?? true) {
            $channels[] = 'database';
            $channels[] = 'broadcast';
        }

        if ($this->settings['whatsapp'] ?? false) {
            $channels[] = WhatsappChannel::class;
        }

        if ($this->settings['telegram'] ?? false) {
            $channels[] = TelegramChannel::class;
        }

        return $channels;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'title' => $this->getTitle(),
            'body' => $this->getBody(),
            'no_po' => $this->payload['no_po'] ?? null,
            'action_url' => $this->payload['action_url'] ?? null,
            'sound' => $this->settings['sound'] ?? 'bell-chime',
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'title' => $this->getTitle(),
            'body' => $this->getBody(),
            'no_po' => $this->payload['no_po'] ?? null,
            'action_url' => $this->payload['action_url'] ?? null,
            'sound' => $this->settings['sound'] ?? 'bell-chime',
        ]);
    }



    /**
     * Get WhatsApp formatted message.
     */
    public function toWhatsapp($notifiable): ?string
    {
        $emoji = $this->getEmoji();
        $title = $this->getTitle();
        $body = $this->getBody();
        $actionUrl = $this->payload['action_url'] ?? null;

        $msg = "*[NISReport]* {$emoji} *{$title}*\n\n{$body}";
        if ($actionUrl) {
            $msg .= "\n\nDetail PO: " . url($actionUrl);
        }
        return $msg;
    }

    /**
     * Get Telegram formatted message.
     */
    public function toTelegram($notifiable): ?string
    {
        $emoji = $this->getEmoji();
        $title = $this->getTitle();
        $body = $this->getBody();
        $actionUrl = $this->payload['action_url'] ?? null;

        $msg = "*[NISReport]* {$emoji} *{$title}*\n\n{$body}";
        if ($actionUrl) {
            // Escapes or formats URL for MarkdownV2 if required, but standard Markdown is fine
            $msg .= "\n\nDetail PO: [Buka Halaman](" . url($actionUrl) . ")";
        }
        return $msg;
    }

    // Helper functions
    public function getTitle(): string
    {
        return match ($this->eventKey) {
            'order_published' => 'PO Baru Diterbitkan',
            'order_completed' => 'PO Selesai',
            'progress_updated' => 'Progress PO Diperbarui',
            'rijek_reported' => 'Laporan Rijek Baru',
            'refund_submitted' => 'Pengajuan Refund Dana',
            'refund_processed' => 'Pengajuan Refund Diproses',
            'payment_submitted' => 'Pembayaran PO Diajukan',
            'payment_verified' => 'Pembayaran PO Diverifikasi',
            default => 'Notifikasi Sistem',
        };
    }

    public function getBody(): string
    {
        $noPo = $this->payload['no_po'] ?? '-';
        $brandNama = $this->payload['brand_nama'] ?? 'Circle Sportwear';
        $nominal = $this->payload['nominal'] ?? 'Rp 0';
        $stage = $this->payload['stage'] ?? '-';
        $status = $this->payload['status'] ?? '-';

        return match ($this->eventKey) {
            'order_published' => "PO {$noPo} dari Brand {$brandNama} baru saja diterbitkan.",
            'order_completed' => "PO {$noPo} dari Brand {$brandNama} telah diselesaikan.",
            'progress_updated' => "Progress PO {$noPo} ({$brandNama}) pada tahapan {$stage} telah diperbarui.",
            'rijek_reported' => "Terdapat laporan rijek baru pada tahapan {$stage} untuk PO {$noPo} ({$brandNama}).",
            'refund_submitted' => "Pengajuan refund untuk PO {$noPo} ({$brandNama}) senilai {$nominal} telah dibuat.",
            'refund_processed' => "Refund untuk PO {$noPo} ({$brandNama}) telah diperbarui dengan status: {$status}.",
            'payment_submitted' => "Pembayaran untuk PO {$noPo} ({$brandNama}) senilai {$nominal} telah diajukan.",
            'payment_verified' => "Pembayaran untuk PO {$noPo} ({$brandNama}) senilai {$nominal} telah diverifikasi.",
            default => $this->payload['body'] ?? 'Aktivitas sistem baru.',
        };
    }

    public function getEmoji(): string
    {
        return match ($this->eventKey) {
            'order_published' => '📦',
            'order_completed' => '✅',
            'progress_updated' => '⚙️',
            'rijek_reported' => '⚠️',
            'refund_submitted' => '🪙',
            'refund_processed' => '💳',
            'payment_submitted' => '📥',
            'payment_verified' => '💸',
            default => '🔔',
        };
    }

    private function getNotifiableId(array $payload): ?string
    {
        return $payload['user_id'] ?? null;
    }
}
