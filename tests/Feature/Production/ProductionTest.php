<?php

namespace Tests\Feature\Production;

use App\Models\Master\Customer;
use App\Models\Master\Progress;
use App\Models\Order\Order;
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

        app(POStatusManager::class)->publish($order, $user);
        return [$brand, $user, $order->fresh(['progressDetails.progress'])];
    }

    public function test_update_progress_to_on_progress_auto_locks_po(): void
    {
        [$brand, $user, $order] = $this->setupPublishedOrder();
        $produksi = $this->makeUser('admin_produksi', [$brand]);
        $detail = $order->progressDetails->first();

        $this->assertFalse($order->isLocked());

        $this->actingAsWithBrand($produksi, $brand)
            ->put(route('produksi.progress.update', ['order' => $order->id, 'detail' => $detail->id]), [
                'status' => 'on_progress',
                'catatan' => 'Mulai dikerjakan',
            ])
            ->assertRedirect();

        $order = $order->fresh(['lockStatus']);
        $this->assertTrue($order->isLocked(), 'PO should be auto-locked when first stage on_progress');
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
                'biaya_ganti' => 50000,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('rijeks', [
            'order_id' => $order->id, 'jumlah' => 3, 'jenis' => 'jahit',
        ]);
    }
}

