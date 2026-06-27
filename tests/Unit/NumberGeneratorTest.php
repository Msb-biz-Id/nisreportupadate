<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Brand;
use App\Models\User;
use App\Models\Master\Customer;
use App\Models\Master\SumberOrder;
use App\Models\Order\Order;
use App\Models\Order\Invoice;
use App\Models\Order\Refund;
use App\Models\Order\DesignDeposit;
use App\Services\NumberGenerator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NumberGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private NumberGenerator $generator;
    private Brand $brand;
    private User $user;
    private Customer $customer;
    private SumberOrder $sumber;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = app(NumberGenerator::class);
        
        $this->brand = Brand::create([
            'nama_brand' => 'Test Brand',
            'kode' => 'TST',
            'min_dp_percentage' => 30,
        ]);

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->customer = Customer::create([
            'brand_id' => $this->brand->id,
            'kode' => 'CUST-001',
            'nama' => 'Test Customer',
            'nomor_hp' => '08123456789',
        ]);

        $this->sumber = SumberOrder::create([
            'brand_id' => $this->brand->id,
            'nama' => 'Test Sumber',
            'is_active' => true,
        ]);

        // Seed roles required by the RefundObserver notification logic
        \Spatie\Permission\Models\Role::create(['name' => 'admin_keuangan', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::create(['name' => 'owner', 'guard_name' => 'web']);
    }

    public function test_order_number_sequence_resets_on_new_year(): void
    {
        // 1. Create an order in 2025
        $date2025 = Carbon::create(2025, 12, 31);
        $noPo2025 = $this->generator->generateOrderNumber($this->brand, 'Jersey', $date2025);
        $this->assertEquals('PO-TST-JERSEY-001', $noPo2025);

        // Store this order in DB with tanggal_masuk in 2025
        $order = new Order([
            'brand_id' => $this->brand->id,
            'no_po' => $noPo2025,
            'nama_po' => 'Jersey',
            'tanggal_masuk' => $date2025->toDateString(),
            'deadline_customer' => $date2025->copy()->addDays(10)->toDateString(),
            'pelanggan_id' => $this->customer->id,
            'sumber_order_id' => $this->sumber->id,
            'created_by' => $this->user->id,
        ]);
        $order->created_at = $date2025;
        $order->updated_at = $date2025;
        $order->save();

        // Second order in 2025 should increment
        $noPo2025Sec = $this->generator->generateOrderNumber($this->brand, 'Jersey', $date2025);
        $this->assertEquals('PO-TST-JERSEY-002', $noPo2025Sec);

        // 2. Try to generate for 2026 (new year) -> should reset to 001
        $date2026 = Carbon::create(2026, 1, 1);
        $noPo2026 = $this->generator->generateOrderNumber($this->brand, 'Jersey', $date2026);
        $this->assertEquals('PO-TST-JERSEY-001', $noPo2026);
    }

    public function test_invoice_number_sequence_resets_on_new_year(): void
    {
        // 1. Generate fallback invoice for 2025
        $date2025 = Carbon::create(2025, 12, 31);
        $inv2025 = $this->generator->generateInvoiceNumber($this->brand, null, $date2025);
        $this->assertEquals('INV-TST-ORDER-0001', $inv2025);

        $order = new Order([
            'brand_id' => $this->brand->id,
            'no_po' => 'PO-TST-JERSEY-001',
            'nama_po' => 'Jersey',
            'tanggal_masuk' => $date2025->toDateString(),
            'deadline_customer' => $date2025->copy()->addDays(10)->toDateString(),
            'pelanggan_id' => $this->customer->id,
            'sumber_order_id' => $this->sumber->id,
            'created_by' => $this->user->id,
        ]);
        $order->created_at = $date2025;
        $order->updated_at = $date2025;
        $order->save();

        $invoice = new Invoice([
            'brand_id' => $this->brand->id,
            'order_id' => $order->id,
            'invoice_number' => $inv2025,
            'tanggal_terbit' => $date2025->toDateString(),
            'jatuh_tempo' => $date2025->copy()->addDays(14)->toDateString(),
            'total_tagihan' => 0.0,
            'created_by' => $this->user->id,
        ]);
        $invoice->created_at = $date2025;
        $invoice->updated_at = $date2025;
        $invoice->save();

        // Second fallback invoice in 2025 should increment
        $inv2025Sec = $this->generator->generateInvoiceNumber($this->brand, null, $date2025);
        $this->assertEquals('INV-TST-ORDER-0002', $inv2025Sec);

        // 2. Generate for 2026 -> should reset to 0001
        $date2026 = Carbon::create(2026, 1, 1);
        $inv2026 = $this->generator->generateInvoiceNumber($this->brand, null, $date2026);
        $this->assertEquals('INV-TST-ORDER-0001', $inv2026);
    }

    public function test_refund_number_sequence_resets_daily(): void
    {
        $dateDay1 = Carbon::create(2026, 6, 25);
        $ref1 = $this->generator->generateRefundNumber($this->brand, $dateDay1);
        $this->assertEquals('REF-TST-20260625-001', $ref1);

        $order = new Order([
            'brand_id' => $this->brand->id,
            'no_po' => 'PO-TST-JERSEY-001',
            'nama_po' => 'Jersey',
            'tanggal_masuk' => $dateDay1->toDateString(),
            'deadline_customer' => $dateDay1->copy()->addDays(10)->toDateString(),
            'pelanggan_id' => $this->customer->id,
            'sumber_order_id' => $this->sumber->id,
            'created_by' => $this->user->id,
        ]);
        $order->created_at = $dateDay1;
        $order->updated_at = $dateDay1;
        $order->save();

        $refund = new Refund([
            'brand_id' => $this->brand->id,
            'refund_number' => $ref1,
            'order_id' => $order->id,
            'alasan' => 'Test Refund Reason',
            'jenis_masalah' => 'lainnya',
            'jumlah_item' => 1,
            'nominal_refund' => 100000.00,
            'catatan' => 'Test Notes',
            'created_by' => $this->user->id,
        ]);
        $refund->created_at = $dateDay1;
        $refund->updated_at = $dateDay1;
        $refund->save();

        $ref2 = $this->generator->generateRefundNumber($this->brand, $dateDay1);
        $this->assertEquals('REF-TST-20260625-002', $ref2);

        // Next day should reset
        $dateDay2 = Carbon::create(2026, 6, 26);
        $refNextDay = $this->generator->generateRefundNumber($this->brand, $dateDay2);
        $this->assertEquals('REF-TST-20260626-001', $refNextDay);
    }
}
