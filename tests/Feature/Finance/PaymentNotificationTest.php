<?php

namespace Tests\Feature\Finance;

use App\Models\Master\Customer;
use App\Models\Order\Order;
use App\Models\Order\OrderPayment;
use App\Notifications\SystemEventNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PaymentNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_created_verified_dispatches_payment_verified_notification(): void
    {
        Notification::fake();

        $brand = $this->makeBrand();
        $admin = $this->makeUser('admin_brand', [$brand]);
        $finance = $this->makeUser('admin_keuangan', [$brand]);

        Customer::create(['brand_id' => $brand->id, 'kode' => 'C1', 'nama' => 'Test Customer', 'nomor_hp' => '0812345', 'is_active' => true]);

        $order = Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-NOTIF-001',
            'nama_po' => 'PO Test Notif',
            'status_po' => 'published',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => Customer::first()->id,
            'total_tagihan' => 1000000,
            'published_at' => now(),
            'created_by' => $admin->id,
        ]);

        // Create verified payment directly (e.g. Cashback created by finance/superadmin)
        OrderPayment::create([
            'order_id' => $order->id,
            'payment_type' => 'cashback',
            'amount' => 50000,
            'payment_date' => now()->toDateString(),
            'recorded_by' => $finance->id,
            'verified_at' => now(),
            'verified_by' => $finance->id,
        ]);

        Notification::assertSentTo(
            $admin,
            SystemEventNotification::class,
            function ($notification) {
                return $notification->eventKey === 'payment_verified' &&
                       str_contains($notification->getTitle(), 'Cashback PO Diverifikasi') &&
                       str_contains($notification->getBody(), 'Cashback untuk PO PO-NOTIF-001') &&
                       $notification->getEmoji() === '🎁';
            }
        );
    }

    public function test_payment_created_unverified_dispatches_payment_submitted_notification(): void
    {
        Notification::fake();

        $brand = $this->makeBrand();
        $admin = $this->makeUser('admin_brand', [$brand]);
        $finance = $this->makeUser('admin_keuangan', [$brand]);

        Customer::create(['brand_id' => $brand->id, 'kode' => 'C1', 'nama' => 'Test Customer', 'nomor_hp' => '0812345', 'is_active' => true]);

        $order = Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-NOTIF-002',
            'nama_po' => 'PO Test Notif 2',
            'status_po' => 'published',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => Customer::first()->id,
            'total_tagihan' => 1000000,
            'published_at' => now(),
            'created_by' => $admin->id,
        ]);

        // Create unverified payment (e.g. DP submitted by admin brand)
        OrderPayment::create([
            'order_id' => $order->id,
            'payment_type' => 'dp',
            'amount' => 300000,
            'payment_date' => now()->toDateString(),
            'recorded_by' => $admin->id,
            'verified_at' => null,
            'verified_by' => null,
        ]);

        Notification::assertSentTo(
            $finance,
            SystemEventNotification::class,
            function ($notification) {
                return $notification->eventKey === 'payment_submitted' &&
                       str_contains($notification->getTitle(), 'DP PO Diajukan') &&
                       str_contains($notification->getBody(), 'DP untuk PO PO-NOTIF-002');
            }
        );
    }

    public function test_payment_verification_dispatches_payment_verified_notification(): void
    {
        Notification::fake();

        $brand = $this->makeBrand();
        $admin = $this->makeUser('admin_brand', [$brand]);
        $finance = $this->makeUser('admin_keuangan', [$brand]);

        Customer::create(['brand_id' => $brand->id, 'kode' => 'C1', 'nama' => 'Test Customer', 'nomor_hp' => '0812345', 'is_active' => true]);

        $order = Order::create([
            'brand_id' => $brand->id,
            'no_po' => 'PO-NOTIF-003',
            'nama_po' => 'PO Test Notif 3',
            'status_po' => 'published',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => Customer::first()->id,
            'total_tagihan' => 1000000,
            'published_at' => now(),
            'created_by' => $admin->id,
        ]);

        // Create unverified payment first
        $payment = OrderPayment::create([
            'order_id' => $order->id,
            'payment_type' => 'return',
            'amount' => 150000,
            'payment_date' => now()->toDateString(),
            'recorded_by' => $admin->id,
            'verified_at' => null,
            'verified_by' => null,
        ]);

        // Now verify it
        $payment->update([
            'verified_at' => now(),
            'verified_by' => $finance->id,
        ]);

        Notification::assertSentTo(
            $admin,
            SystemEventNotification::class,
            function ($notification) {
                return $notification->eventKey === 'payment_verified' &&
                       str_contains($notification->getTitle(), 'Return PO Diverifikasi') &&
                       str_contains($notification->getBody(), 'Return/Refund untuk PO PO-NOTIF-003') &&
                       $notification->getEmoji() === '🔄';
            }
        );
    }
}
