<?php

namespace App\Services\Notifications;

use App\Models\User;
use App\Notifications\SystemEventNotification;
use App\Models\Settings\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class IdealNotificationService
{
    /**
     * Dispatch system event notification to target users with deduplication lock.
     */
    public static function dispatch(string $eventKey, array $payload): array
    {
        $noPo = $payload['no_po'] ?? '';
        $stage = $payload['stage'] ?? '';
        $status = $payload['status'] ?? '';
        $brandId = $payload['brand_id'] ?? null;

        // Automatically resolve reseller brand to enforce strict brand isolation for notifications
        if (!empty($noPo)) {
            if (str_starts_with($noPo, 'Tanda Jadi ')) {
                $depNo = str_replace('Tanda Jadi ', '', $noPo);
                $deposit = \App\Models\Order\DesignDeposit::where('deposit_number', $depNo)->with('customer.brand')->first();
                if ($deposit && $deposit->customer && $deposit->customer->brand && $deposit->customer->brand->isReseller()) {
                    $resellerBrand = $deposit->customer->brand;
                    $brandId = $resellerBrand->id;
                    $payload['brand_id'] = $resellerBrand->id;
                    $payload['brand_nama'] = $resellerBrand->nama_brand;
                }
            } else {
                $order = \App\Models\Order\Order::where('no_po', $noPo)->first();
                if ($order) {
                    $resellerBrand = $order->resolveResellerBrand();
                    if ($resellerBrand) {
                        $brandId = $resellerBrand->id;
                        $payload['brand_id'] = $resellerBrand->id;
                        $payload['brand_nama'] = $resellerBrand->nama_brand;
                    }
                }
            }
        }

        // 1. Deduplication Lock (Cache lock for 3 seconds to prevent double clicks but allow quick sequential actions)
        $fingerprint = md5($eventKey . '_' . $noPo . '_' . $stage . '_' . $status . '_' . $brandId . '_' . serialize($payload));
        $lockKey = 'notif_lock_' . $fingerprint;

        if (Cache::has($lockKey)) {
            \Illuminate\Support\Facades\Log::info("Notification deduplicated. Key: {$eventKey}, PO: {$noPo}");
            return []; // Ignore duplicate request
        }
        Cache::put($lockKey, true, 3);

        \Illuminate\Support\Facades\Log::info("Dispatching Notification. Key: {$eventKey}, PO: {$noPo}, Fingerprint: {$fingerprint}");

        // 2. Fetch Notification Matrix Configuration
        $settingsJson = SystemSetting::get('notification_matrix', $eventKey);
        $settings = $settingsJson ? json_decode($settingsJson, true) : null;

        if (empty($settings)) {
            $defaults = [
                'order_published' => [
                    'in_app' => true,
                    'whatsapp' => false,
                    'telegram' => false,
                    'os_desktop' => true,
                    'roles' => ['admin_produksi', 'owner'],
                    'sound' => 'success-tada'
                ],
                'special_order_created' => [
                    'in_app' => true,
                    'whatsapp' => false,
                    'telegram' => false,
                    'os_desktop' => true,
                    'roles' => ['admin_keuangan', 'owner'],
                    'sound' => 'warning-alert'
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
                'unlock_requested' => [
                    'in_app' => true,
                    'whatsapp' => false,
                    'telegram' => false,
                    'os_desktop' => true,
                    'roles' => ['superadmin', 'owner', 'supervisor'],
                    'sound' => 'warning-alert'
                ],
                'order_unlocked' => [
                    'in_app' => true,
                    'whatsapp' => false,
                    'telegram' => false,
                    'os_desktop' => true,
                    'roles' => ['admin_brand', 'admin_reseller', 'owner'],
                    'sound' => 'success-tada'
                ],
                'relock_requested' => [
                    'in_app' => true,
                    'whatsapp' => false,
                    'telegram' => false,
                    'os_desktop' => true,
                    'roles' => ['superadmin', 'owner', 'supervisor'],
                    'sound' => 'warning-alert'
                ],
                'order_locked' => [
                    'in_app' => true,
                    'whatsapp' => false,
                    'telegram' => false,
                    'os_desktop' => true,
                    'roles' => ['admin_brand', 'admin_reseller', 'owner'],
                    'sound' => 'success-tada'
                ],
            ];
            $settings = $defaults[$eventKey] ?? [
                'in_app' => true,
                'whatsapp' => false,
                'telegram' => false,
                'os_desktop' => true,
                'roles' => ['owner'],
                'sound' => 'bell-chime'
            ];
        }

        // 3. Resolve Target Users by RBAC Roles
        $roles = $settings['roles'] ?? [];
        if (empty($roles)) {
            return [];
        }

        $rolesToQuery = $roles;
        if (!in_array('superadmin', $rolesToQuery, true)) {
            $rolesToQuery[] = 'superadmin';
        }

        $usersQuery = User::role($rolesToQuery);

        // 4. Filter by Brand Access (unless superadmin, owner, keuangan, or produksi)
        if ($brandId) {
            $users = $usersQuery->get()->filter(function ($u) use ($brandId) {
                return $u->isSuperadmin() || 
                       $u->hasRole(['owner', 'admin_keuangan', 'admin_produksi']) || 
                       $u->hasAccessToBrand($brandId);
            });
        } else {
            $users = $usersQuery->get();
        }

        // 5. Send Notification
        if ($users->isNotEmpty()) {
            Notification::send($users, new SystemEventNotification($eventKey, $payload, $settings));
        }

        return $users->pluck('id')->toArray();
    }
}
