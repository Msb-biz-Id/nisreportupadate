<?php

namespace App\Services\Notifications;

use App\Models\User;
use App\Models\Notification;
use App\Models\Settings\SystemSetting;
use Illuminate\Support\Facades\Log;
use App\Events\NotificationSent;

class DynamicNotificationService
{
    /**
     * Dispatch a dynamic notification based on an event type and matrix configuration.
     *
     * @param string $eventType e.g. 'order_published', 'progress_updated', 'rijek_reported', 'refund_submitted', 'refund_processed'
     * @param array $payload Key-value options like 'no_po', 'brand_id', 'brand_nama', 'stage', 'status', 'action_url', 'custom_body'
     */
    public static function dispatch(string $eventType, array $payload = []): array
    {
        Log::info("Dispatching notification event: {$eventType}", $payload);

        // 1. Fetch Event Configuration from SystemSetting (Group: notification_matrix)
        $config = SystemSetting::get('notification_matrix', $eventType);
        if ($config) {
            $config = is_string($config) ? json_decode($config, true) : $config;
        }

        // Fallback Defaults if not seeded or configured
        if (empty($config)) {
            $defaults = [
                'order_published' => [
                    'in_app' => true,
                    'whatsapp' => false,
                    'telegram' => false,
                    'os_desktop' => true,
                    'roles' => ['admin_produksi', 'owner'],
                    'sound' => 'success-tada'
                ],
                'progress_updated' => [
                    'in_app' => true,
                    'whatsapp' => false,
                    'telegram' => false,
                    'os_desktop' => true,
                    'roles' => ['admin_brand', 'admin_reseller', 'owner'],
                    'sound' => 'bell-chime'
                ],
                'rijek_reported' => [
                    'in_app' => true,
                    'whatsapp' => false,
                    'telegram' => false,
                    'os_desktop' => true,
                    'roles' => ['admin_brand', 'owner'],
                    'sound' => 'warning-alert'
                ],
                'refund_submitted' => [
                    'in_app' => true,
                    'whatsapp' => false,
                    'telegram' => false,
                    'os_desktop' => true,
                    'roles' => ['admin_keuangan', 'owner'],
                    'sound' => 'cash-register'
                ],
                'refund_processed' => [
                    'in_app' => true,
                    'whatsapp' => false,
                    'telegram' => false,
                    'os_desktop' => true,
                    'roles' => ['admin_brand', 'admin_reseller', 'owner'],
                    'sound' => 'bell-chime'
                ],
                'payment_submitted' => [
                    'in_app' => true,
                    'whatsapp' => false,
                    'telegram' => false,
                    'os_desktop' => true,
                    'roles' => ['admin_keuangan', 'owner'],
                    'sound' => 'cash-register'
                ],
                'payment_verified' => [
                    'in_app' => true,
                    'whatsapp' => false,
                    'telegram' => false,
                    'os_desktop' => true,
                    'roles' => ['admin_brand', 'owner'],
                    'sound' => 'success-tada'
                ],
                'order_completed' => [
                    'in_app' => true,
                    'whatsapp' => false,
                    'telegram' => false,
                    'os_desktop' => true,
                    'roles' => ['admin_brand', 'owner'],
                    'sound' => 'success-tada'
                ]
            ];
            $config = $defaults[$eventType] ?? [
                'in_app' => true,
                'whatsapp' => false,
                'telegram' => false,
                'os_desktop' => true,
                'roles' => ['superadmin', 'owner'],
                'sound' => 'bell-chime'
            ];
        }

        $roles = $config['roles'] ?? [];
        if (empty($roles)) {
            Log::info("No roles targeted for notification event: {$eventType}");
            return [];
        }

        // 2. Define standard templates
        $templates = [
            'order_published' => [
                'title' => 'PO Baru Diterbitkan',
                'body' => 'PO {no_po} telah diterbitkan oleh Admin Brand untuk brand {brand_nama}. Siap dikerjakan.',
            ],
            'progress_updated' => [
                'title' => 'Progress PO Diperbarui',
                'body' => 'Progress PO {no_po} untuk brand {brand_nama} diperbarui ke tahap {stage}.',
            ],
            'rijek_reported' => [
                'title' => 'Laporan Rijek Baru',
                'body' => 'Rijek dilaporkan pada PO {no_po} (brand {brand_nama}) pada tahap {stage}.',
            ],
            'refund_submitted' => [
                'title' => 'Pengajuan Refund Dana',
                'body' => 'Refund diajukan oleh Admin Brand untuk PO {no_po} (brand {brand_nama}). Menunggu persetujuan Keuangan.',
            ],
            'refund_processed' => [
                'title' => 'Pengajuan Refund Diproses',
                'body' => 'Refund dana untuk PO {no_po} (brand {brand_nama}) telah status: {status} oleh Keuangan.',
            ],
            'payment_submitted' => [
                'title' => 'Pembayaran Baru Ditambahkan',
                'body' => 'Pembayaran baru sebesar {nominal} untuk PO {no_po} telah ditambahkan oleh Admin Brand. Menunggu validasi Keuangan.',
            ],
            'payment_verified' => [
                'title' => 'Pembayaran PO Divalidasi',
                'body' => 'Pembayaran sebesar {nominal} untuk PO {no_po} telah disetujui dan divalidasi oleh Admin Keuangan.',
            ],
            'order_completed' => [
                'title' => 'PO Selesai',
                'body' => 'PO {no_po} (brand {brand_nama}) telah selesai dan resmi ditutup.',
            ]
        ];

        $template = $templates[$eventType] ?? [
            'title' => 'Notifikasi Sistem',
            'body' => 'Ada aktivitas baru pada sistem.'
        ];

        // Process custom body or title
        $title = $payload['custom_title'] ?? $template['title'];
        $body = $payload['custom_body'] ?? $template['body'];

        // Replace placeholders
        $noPo = $payload['no_po'] ?? '-';
        $brandNama = $payload['brand_nama'] ?? '-';
        $stage = $payload['stage'] ?? '-';
        $status = $payload['status'] ?? '-';
        $nominal = $payload['nominal'] ?? '-';

        $body = str_replace(
            ['{no_po}', '{brand_nama}', '{stage}', '{status}', '{nominal}'],
            [$noPo, $brandNama, $stage, $status, $nominal],
            $body
        );

        $actionUrl = $payload['action_url'] ?? null;
        $sound = $config['sound'] ?? 'bell-chime';

        // 3. Query Target Users
        $users = User::role($roles)->where('is_active', true)->get()->unique('id');

        // 4. Filter by brand access if brand_id is specified
        if (!empty($payload['brand_id'])) {
            $brandId = $payload['brand_id'];
            $users = $users->filter(function ($user) use ($brandId) {
                // Superadmins & owners always pass brand filters
                if ($user->hasRole('superadmin') || $user->hasRole('owner')) {
                    return true;
                }
                return $user->hasAccessToBrand($brandId);
            });
        }

        $results = [];

        // 5. Deliver to each targeted User
        foreach ($users as $user) {
            $userResult = ['user_id' => $user->id, 'channels' => []];

            // A. In-App Notification (Bell / Dashboard)
            if ($config['in_app'] ?? true) {
                $notification = Notification::create([
                    'user_id' => $user->id,
                    'title' => $title,
                    'body' => $body,
                    'no_po' => $noPo,
                    'action_url' => $actionUrl,
                    'sound' => $sound
                ]);

                // Dispatch real-time Broadcast Event (Laravel Reverb / Pusher)
                try {
                    event(new NotificationSent($notification));
                } catch (\Throwable $e) {
                    // Suppress broadcast driver errors, fallback gracefully
                }

                $userResult['channels']['in_app'] = true;
            }

            // B. WhatsApp Delivery
            $waGlobal = (bool) SystemSetting::get('system', 'whatsapp_enabled', true);
            if (($config['whatsapp'] ?? false) && $waGlobal) {
                $waClient = SidobeClient::fromSettings();
                $toPhone = $user->phone ?: SystemSetting::get('whatsapp', 'default_recipient');
                if (!empty($toPhone)) {
                    $waMsg = "*{$title}*\n\n{$body}";
                    if ($actionUrl) {
                        $waMsg .= "\n\nLihat detail: " . url($actionUrl);
                    }
                    $res = $waClient->send($toPhone, $waMsg);
                    $userResult['channels']['whatsapp'] = $res;
                }
            }

            // C. Telegram Delivery
            $tgGlobal = (bool) SystemSetting::get('system', 'telegram_enabled', false);
            if (($config['telegram'] ?? false) && $tgGlobal) {
                $tgClient = TelegramClient::fromSettings();
                $chatId = $user->telegram_chat_id ?: SystemSetting::get('telegram', 'default_chat_id');
                if (!empty($chatId)) {
                    $tgMsg = "*{$title}*\n\n" . esc_telegram_markdown($body);
                    if ($actionUrl) {
                        $tgMsg .= "\n\n[Lihat detail](" . url($actionUrl) . ")";
                    }
                    $res = $tgClient->send($chatId, $tgMsg);
                    $userResult['channels']['telegram'] = $res;
                }
            }

            $results[] = $userResult;
        }

        return $results;
    }
}

/**
 * Helper function to escape Telegram markdown special characters.
 */
if (!function_exists('esc_telegram_markdown')) {
    function esc_telegram_markdown(string $text): string
    {
        return str_replace(
            ['_', '*', '[', ']', '(', ')', '~', '`', '#', '+', '-', '=', '|', '{', '}', '.', '!'],
            ['\\_', '\\*', '\\[', '\\]', '\\(', '\\)', '\\~', '\\`', '\\#', '\\+', '\\-', '\\=', '\\|', '\\{', '\\}', '\\.', '\\!'],
            $text
        );
    }
}
