<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use App\Services\Notifications\TelegramClient;

class TelegramChannel
{
    public function __construct(protected TelegramClient $telegram) {}

    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toTelegram')) {
            return;
        }

        $message = $notification->toTelegram($notifiable);
        if (empty($message)) {
            return;
        }

        $chatId = $notifiable->telegram_chat_id ?? null;
        if (empty($chatId)) {
            return;
        }

        $this->telegram->send($chatId, $message);
    }
}
