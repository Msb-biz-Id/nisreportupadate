<?php

namespace Tests\Feature\Notifications;

use App\Models\Brand;
use App\Models\Order\Order;
use App\Models\Order\POLockStatus;
use App\Models\Order\OrderPayment;
use App\Models\Order\Refund;
use App\Models\Master\Customer;
use App\Models\User;
use App\Services\Notifications\IdealNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationSynchronizationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Brand $brand;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->brand = $this->makeBrand();
        $this->user = $this->makeUser('owner', [$this->brand]);
        
        $this->customer = Customer::create([
            'brand_id' => $this->brand->id,
            'kode' => 'CST-01',
            'nama' => 'Test Pelanggan',
            'nomor_hp' => '08123456789',
            'is_active' => true
        ]);
    }

    public function test_mark_as_read_for_resource()
    {
        $id = \Illuminate\Support\Str::uuid()->toString();
        DB::table('notifications')->insert([
            'id' => $id,
            'type' => \App\Notifications\SystemEventNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $this->user->id,
            'data' => json_encode([
                'type' => 'unlock_requested',
                'event_key' => 'unlock_requested',
                'no_po' => 'PO-TEST-123',
                'title' => 'Unlock Request',
                'body' => 'Test Body',
                'action_url' => '/orders/1',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $id,
            'read_at' => null,
        ]);

        IdealNotificationService::markAsReadForResource('unlock_requested', 'PO-TEST-123');

        $this->assertDatabaseMissing('notifications', [
            'id' => $id,
            'read_at' => null,
        ]);
    }

    public function test_verify_route_valid_unlock_request()
    {
        $order = Order::create([
            'brand_id' => $this->brand->id,
            'no_po' => 'PO-TEST-123',
            'nama_po' => 'PO Test',
            'status_po' => 'published',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => $this->customer->id,
            'total_tagihan' => 1000000,
            'published_at' => now(),
            'created_by' => $this->user->id,
        ]);

        $order->lockStatus()->create([
            'is_locked' => true,
            'locked_at' => now(),
            'locked_by' => $this->user->id,
            'unlock_requested_by' => $this->user->id,
            'unlock_request_reason' => 'Test reason',
            'unlock_requested_at' => now(),
        ]);

        $id = \Illuminate\Support\Str::uuid()->toString();
        DB::table('notifications')->insert([
            'id' => $id,
            'type' => \App\Notifications\SystemEventNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $this->user->id,
            'data' => json_encode([
                'type' => 'unlock_requested',
                'event_key' => 'unlock_requested',
                'no_po' => 'PO-TEST-123',
                'title' => 'Unlock Request',
                'body' => 'Test Body',
                'action_url' => '/orders/' . $order->id,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.verify', $id));

        if ($response->status() !== 200) {
            dd($response->status(), $response->content());
        }

        $response->assertStatus(200)
            ->assertJson([
                'valid' => true
            ]);
    }

    public function test_verify_route_invalid_unlock_request()
    {
        $order = Order::create([
            'brand_id' => $this->brand->id,
            'no_po' => 'PO-TEST-123',
            'nama_po' => 'PO Test',
            'status_po' => 'published',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => $this->customer->id,
            'total_tagihan' => 1000000,
            'published_at' => now(),
            'created_by' => $this->user->id,
        ]);

        $order->lockStatus()->create([
            'is_locked' => true,
            'locked_at' => now(),
            'locked_by' => $this->user->id,
        ]);

        $id = \Illuminate\Support\Str::uuid()->toString();
        DB::table('notifications')->insert([
            'id' => $id,
            'type' => \App\Notifications\SystemEventNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $this->user->id,
            'data' => json_encode([
                'type' => 'unlock_requested',
                'event_key' => 'unlock_requested',
                'no_po' => 'PO-TEST-123',
                'title' => 'Unlock Request',
                'body' => 'Test Body',
                'action_url' => '/orders/' . $order->id,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.verify', $id));

        if ($response->status() !== 200) {
            dd($response->status(), $response->content());
        }

        $response->assertStatus(200)
            ->assertJson([
                'valid' => false,
                'message' => 'Permohonan unlock PO ini sudah diproses oleh user lain.'
            ]);

        $this->assertDatabaseMissing('notifications', [
            'id' => $id,
            'read_at' => null
        ]);
    }

    public function test_verify_route_valid_payment_submitted()
    {
        $order = Order::create([
            'brand_id' => $this->brand->id,
            'no_po' => 'PO-TEST-123',
            'nama_po' => 'PO Test',
            'status_po' => 'published',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => $this->customer->id,
            'total_tagihan' => 1000000,
            'published_at' => now(),
            'created_by' => $this->user->id,
        ]);

        $order->payments()->create([
            'payment_type' => 'dp',
            'amount' => 500000,
            'payment_date' => now()->toDateString(),
            'recorded_by' => $this->user->id,
            'verified_at' => null,
        ]);

        $id = \Illuminate\Support\Str::uuid()->toString();
        DB::table('notifications')->insert([
            'id' => $id,
            'type' => \App\Notifications\SystemEventNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $this->user->id,
            'data' => json_encode([
                'type' => 'payment_submitted',
                'event_key' => 'payment_submitted',
                'no_po' => 'PO-TEST-123',
                'title' => 'Payment DP',
                'body' => 'Test Body',
                'action_url' => '/orders/' . $order->id,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.verify', $id));

        if ($response->status() !== 200) {
            dd($response->status(), $response->content());
        }

        $response->assertStatus(200)
            ->assertJson([
                'valid' => true
            ]);
    }

    public function test_verify_route_invalid_payment_submitted()
    {
        $order = Order::create([
            'brand_id' => $this->brand->id,
            'no_po' => 'PO-TEST-123',
            'nama_po' => 'PO Test',
            'status_po' => 'published',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(7)->toDateString(),
            'pelanggan_id' => $this->customer->id,
            'total_tagihan' => 1000000,
            'published_at' => now(),
            'created_by' => $this->user->id,
        ]);

        // payment is already verified
        $order->payments()->create([
            'payment_type' => 'dp',
            'amount' => 500000,
            'payment_date' => now()->toDateString(),
            'recorded_by' => $this->user->id,
            'verified_at' => now(),
        ]);

        $id = \Illuminate\Support\Str::uuid()->toString();
        DB::table('notifications')->insert([
            'id' => $id,
            'type' => \App\Notifications\SystemEventNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $this->user->id,
            'data' => json_encode([
                'type' => 'payment_submitted',
                'event_key' => 'payment_submitted',
                'no_po' => 'PO-TEST-123',
                'title' => 'Payment DP',
                'body' => 'Test Body',
                'action_url' => '/orders/' . $order->id,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('notifications.verify', $id));

        if ($response->status() !== 200) {
            dd($response->status(), $response->content());
        }

        $response->assertStatus(200)
            ->assertJson([
                'valid' => false,
                'message' => 'Pembayaran PO ini sudah diproses atau tidak ditemukan.'
            ]);

        $this->assertDatabaseMissing('notifications', [
            'id' => $id,
            'read_at' => null
        ]);
    }
}
