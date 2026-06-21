<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use App\Services\Notifications\SidobeClient;

class WhatsappChannel
{
    public function __construct(protected SidobeClient $whatsapp) {}

    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWhatsapp')) {
            return;
        }

        $message = $notification->toWhatsapp($notifiable);
        if (empty($message)) {
            return;
        }

        $to = $notifiable->phone ?? $notifiable->nomor_hp ?? null;
        if (empty($to)) {
            return;
        }

        $this->whatsapp->send($to, $message);
    }
}
