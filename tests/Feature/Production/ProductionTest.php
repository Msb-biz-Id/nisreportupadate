<?php
 
namespace Tests\Feature\Production;
 
use App\Models\Master\Customer;
use App\Models\Master\Progress;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Order\OrderPayment;
use App\Models\Order\OrderProgressDetail;
use App\Services\NumberGenerator;
use App\Services\POStatusManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
 
class ProductionTest extends TestCase
{
    use RefreshDatabase;

    private function setupPublishedOrder()
    {
        $brand = $this->makeBrand();
        $user = $this->makeUser('owner', [$brand]);
        Customer::create([
            'brand_id' => $brand->id, 'kode' => 'C1', 'nama' => 'Test', 'nomor_hp' => '081', 'is_active' => true,
        ]);
        foreach ([
            ['Setting', 1], ['Jahit', 2], ['Packing', 3], ['Sending', 4],
        ] as [$nama, $urut]) {
            Progress::create([
                'nama_progress' => $nama, 'urutan' => $urut, 'is_active' => true,
                'warna' => '#3B82F6', 'is_skippable' => false,
            ]);
        }

        $order = Order::create([
            'brand_id' => $brand->id,
            'no_po' => app(NumberGenerator::class)->generateOrderNumber($brand),
            'nama_po' => 'Test', 'status_po' => 'draft',
            'tanggal_masuk' => now()->toDateString(),
            'deadline_customer' => now()->addDays(14)->toDateString(),
            'pelanggan_id' => Customer::first()->id,
            'total_tagihan' => 100000,
            'created_by' => $user->id,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'nama_produk' => 'Jersey Test',
            'quantity' => 1,
            'harga_satuan' => 100000,
            'subtotal' => 100000,
        ]);

        app(POStatusManager::class)->publish($order, $user);
        return [$brand, $user, $order->fresh(['progressDetails.progress'])];
    }

    public function test_update_progress_to_on_progress_auto_locks_po(): void
    {
        [$brand, $user, $order] = $this->setupPublishedOrder();
        $produksi = $this->makeUser('admin_produksi', [$brand]);
        $detail = $order->progressDetails->first();

        $this->assertTrue($order->isLocked());

        $this->actingAsWithBrand($produksi, $brand)
            ->put(route('produksi.progress.update', ['order' => $order->id, 'detail' => $detail->id]), [
                'status' => 'on_progress',
                'catatan' => 'Mulai dikerjakan',
            ])
            ->assertRedirect();

        $order = $order->fresh(['lockStatus']);
        $this->assertTrue($order->isLocked(), 'PO should be locked');
        $this->assertEquals('on_progress', $order->status_po);
    }

    public function test_skipping_progress_requires_reason(): void
    {
        [$brand, $user, $order] = $this->setupPublishedOrder();
        $produksi = $this->makeUser('admin_produksi', [$brand]);
        $detail = $order->progressDetails->first();

        $this->actingAsWithBrand($produksi, $brand)
            ->put(route('produksi.progress.update', ['order' => $order->id, 'detail' => $detail->id]), [
                'status' => 'skipped',
                'catatan' => 'skip',
            ])
            ->assertSessionHasErrors('skipped_reason');
    }

    public function test_packing_complete_transitions_status_to_siap_dikirim(): void
    {
        [$brand, $user, $order] = $this->setupPublishedOrder();
        $produksi = $this->makeUser('admin_produksi', [$brand]);

        // Selesaikan semua tahap kecuali Sending
        foreach ($order->progressDetails as $d) {
            if (str_contains($d->progress->nama_progress, 'Sending')) continue;
            $this->actingAsWithBrand($produksi, $brand)
                ->put(route('produksi.progress.update', ['order' => $order->id, 'detail' => $d->id]), [
                    'status' => 'selesai',
                    'catatan' => 'OK',
                ]);
        }

        $this->assertEquals('siap_dikirim', $order->fresh()->status_po);
    }

    public function test_admin_produksi_can_input_rijek(): void
    {
        [$brand, $user, $order] = $this->setupPublishedOrder();
        $produksi = $this->makeUser('admin_produksi', [$brand]);
        $progress = Progress::first();

        $this->actingAsWithBrand($produksi, $brand)
            ->post(route('produksi.rijek.store', $order->id), [
                'progress_id' => $progress->id,
                'jumlah' => 3,
                'jenis' => 'jahit',
                'tingkat' => 'ringan',
                'kendala' => 'Jahitan miring',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('rijeks', [
            'order_id' => $order->id, 'jumlah' => 3, 'jenis' => 'jahit',
        ]);
    }

    public function test_admin_produksi_can_update_rijek(): void
    {
        [$brand, $user, $order] = $this->setupPublishedOrder();
        $produksi = $this->makeUser('admin_produksi', [$brand]);
        $progress = Progress::first();

        $this->actingAsWithBrand($produksi, $brand)
            ->post(route('produksi.rijek.store', $order->id), [
                'progress_id' => $progress->id,
                'jumlah' => 3,
                'jenis' => 'jahit',
                'tingkat' => 'ringan',
                'kendala' => 'Jahitan miring',
            ]);

        $rijek = \App\Models\Order\Rijek::first();

        $this->actingAsWithBrand($produksi, $brand)
            ->put(route('produksi.rijek.update', ['order' => $order->id, 'rijek' => $rijek->id]), [
                'progress_id' => $progress->id,
                'jumlah' => 5,
                'jenis' => 'sablon',
                'tingkat' => 'sedang',
                'kendala' => 'Sablon pecah',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('rijeks', [
            'id' => $rijek->id, 'jumlah' => 5, 'jenis' => 'sablon',
        ]);
    }

    public function test_admin_produksi_can_delete_rijek(): void
    {
        [$brand, $user, $order] = $this->setupPublishedOrder();
        $produksi = $this->makeUser('admin_produksi', [$brand]);
        $progress = Progress::first();

        $this->actingAsWithBrand($produksi, $brand)
            ->post(route('produksi.rijek.store', $order->id), [
                'progress_id' => $progress->id,
                'jumlah' => 3,
                'jenis' => 'jahit',
                'tingkat' => 'ringan',
                'kendala' => 'Jahitan miring',
            ]);

        $rijek = \App\Models\Order\Rijek::first();

        $this->actingAsWithBrand($produksi, $brand)
            ->delete(route('produksi.rijek.destroy', ['order' => $order->id, 'rijek' => $rijek->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('rijeks', [
            'id' => $rijek->id,
        ]);
    }

    public function test_sending_stage_is_locked_when_unpaid_and_unlocked_when_paid(): void
    {
        [$brand, $user, $order] = $this->setupPublishedOrder();
        $produksi = $this->makeUser('admin_produksi', [$brand]);
        
        // Cari detail progres 'Sending'
        $sendingDetail = $order->progressDetails->first(fn($d) => strtoupper($d->progress->nama_progress) === 'SENDING');
        $this->assertNotNull($sendingDetail, 'Sending stage must exist');

        // Awalnya order total_tagihan = 100.000 dan totalPaid = 0, sehingga is_lunas = false.
        $this->assertFalse($order->is_lunas);

        // Mencoba memperbarui tahap Sending harus gagal dengan pesan error lunas
        $this->actingAsWithBrand($produksi, $brand)
            ->put(route('produksi.progress.update', ['order' => $order->id, 'detail' => $sendingDetail->id]), [
                'status' => 'selesai',
                'catatan' => 'Kirim',
            ])
            ->assertSessionHas('error', 'Tahap Sending belum bisa diupdate. Konfirmasi LUNAS dari Keuangan diperlukan terlebih dahulu.');

        // Buat jenis pembayaran Pelunasan agar pemasukan mengurangi sisa tagihan
        $paymentType = \App\Models\Finance\MasterJenisPembayaran::firstOrCreate(
            ['brand_id' => $brand->id, 'nama' => 'Pelunasan'],
            ['tipe_keuangan' => 'pemasukan', 'efek_tagihan' => 'pengurangan', 'is_active' => true]
        );

        // Tambah pembayaran lunas yang terverifikasi (nominal = 100.000)
        $payment = OrderPayment::create([
            'order_id' => $order->id,
            'master_jenis_pembayaran_id' => $paymentType->id,
            'amount' => 100000,
            'payment_date' => now()->toDateString(),
            'recorded_by' => $user->id,
            'verified_by' => $user->id,
            'verified_at' => now(),
        ]);

        // Setelah pembayaran diverifikasi, order harus otomatis diset is_lunas = true oleh observer
        $order = $order->fresh();
        $this->assertTrue($order->is_lunas, 'Order should be automatically marked as lunas when remaining balance is 0');

        // Memperbarui tahap Sending sekarang harus berhasil
        $this->actingAsWithBrand($produksi, $brand)
            ->put(route('produksi.progress.update', ['order' => $order->id, 'detail' => $sendingDetail->id]), [
                'status' => 'selesai',
                'catatan' => 'Kirim',
                'nama_ekspedisi' => 'JNE',
                'no_resi' => '1234567890',
            ])
            ->assertRedirect();
            
        $this->assertEquals('selesai', $sendingDetail->fresh()->status);
    }

    public function test_sending_stage_unlocked_by_manual_override_even_if_unpaid(): void
    {
        [$brand, $user, $order] = $this->setupPublishedOrder();
        $produksi = $this->makeUser('admin_produksi', [$brand]);
        $finance = $this->makeUser('admin_keuangan', [$brand]);
        
        // Cari detail progres 'Sending'
        $sendingDetail = $order->progressDetails->first(fn($d) => strtoupper($d->progress->nama_progress) === 'SENDING');
        $this->assertNotNull($sendingDetail, 'Sending stage must exist');

        // Awalnya belum lunas
        $this->assertFalse($order->is_lunas);

        // Tandai lunas secara manual oleh admin keuangan meskipun sisa tagihan masih 100.000
        $this->actingAsWithBrand($finance, $brand)
            ->post(route('orders.mark-lunas', $order->id))
            ->assertRedirect();

        $order = $order->fresh();
        $this->assertTrue($order->is_lunas, 'Order should be marked as lunas manually');
        $this->assertEquals(100000, (float) $order->sisaTagihan(), 'Remaining balance should still be 100,000');

        // Pemicu update order (misal perbarui total tagihan) - status lunas tidak boleh tertimpa/hilang
        $order->update(['total_tagihan' => $order->totalTagihan()]);
        
        $order = $order->fresh();
        $this->assertTrue($order->is_lunas, 'Order should remain lunas to preserve manual override');

        // Memperbarui tahap Sending harus berhasil karena toleransi manual override
        $this->actingAsWithBrand($produksi, $brand)
            ->put(route('produksi.progress.update', ['order' => $order->id, 'detail' => $sendingDetail->id]), [
                'status' => 'selesai',
                'catatan' => 'Kirim',
                'nama_ekspedisi' => 'JNE',
                'no_resi' => '1234567890',
            ])
            ->assertRedirect();
            
        $this->assertEquals('selesai', $sendingDetail->fresh()->status);
    }
}

