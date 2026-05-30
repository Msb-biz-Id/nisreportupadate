<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Master\BahanKain;
use App\Models\Master\Customer;
use App\Models\Master\CustomerType;
use App\Models\Master\KategoriOrder;
use App\Models\Master\Logo;
use App\Models\Master\Product;
use App\Models\Master\Progress;
use App\Models\Master\Size;
use App\Models\Master\SumberOrder;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Order\OrderNameset;
use App\Models\Order\OrderPayment;
use App\Models\Order\OrderProgressDetail;
use App\Models\Order\POLockStatus;
use App\Models\User;
use App\Services\NumberGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCustomers();
        $this->seedOrders();
    }

    private function seedCustomers(): void
    {
        foreach (Brand::all() as $brand) {
            $type = CustomerType::where(function ($q) use ($brand) {
                $q->where('brand_id', $brand->id)->orWhereNull('brand_id');
            })->where('nama', 'Reguler')->first();

            $samples = [
                ['nama' => 'Klub Garuda Mei', 'nomor_hp' => '081234567001'],
                ['nama' => 'SMA Negeri 1', 'nomor_hp' => '081234567002'],
                ['nama' => 'Komunitas Jogging Pagi', 'nomor_hp' => '081234567003'],
                ['nama' => 'PT Maju Bersama', 'nomor_hp' => '081234567004'],
                ['nama' => 'Tim Futsal Kantor', 'nomor_hp' => '081234567005'],
            ];

            foreach ($samples as $idx => $s) {
                $kode = $brand->kode . '-CUST-' . str_pad((string) ($idx + 1), 3, '0', STR_PAD_LEFT);
                Customer::firstOrCreate(
                    ['brand_id' => $brand->id, 'kode' => $kode],
                    [
                        'nama' => $s['nama'],
                        'nomor_hp' => $s['nomor_hp'],
                        'email' => strtolower(str_replace(' ', '', $s['nama'])) . '@example.test',
                        'type_pelanggan_id' => $type?->id,
                        'provinsi_code' => '32',
                        'provinsi_nama' => 'JAWA BARAT',
                        'kabupaten_code' => '3273',
                        'kabupaten_nama' => 'KOTA BANDUNG',
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    private function seedOrders(): void
    {
        $numbers = app(NumberGenerator::class);
        $admin = User::where('email', 'admin.aleegiant@nisreport.local')->first();
        $superadmin = User::where('email', 'superadmin@nisreport.local')->first();

        foreach (Brand::all() as $brand) {
            $customers = Customer::where('brand_id', $brand->id)->get();
            if ($customers->isEmpty()) continue;

            $kategoris = KategoriOrder::where(function ($q) use ($brand) {
                $q->where('brand_id', $brand->id)->orWhereNull('brand_id');
            })->get();
            $sumbers = SumberOrder::where(function ($q) use ($brand) {
                $q->where('brand_id', $brand->id)->orWhereNull('brand_id');
            })->get();
            $products = Product::where(function ($q) use ($brand) {
                $q->where('brand_id', $brand->id)->orWhereNull('brand_id');
            })->get();
            $bahan = BahanKain::active()->first();
            $logo = Logo::active()->first();
            $sizes = Size::where('kategori_size', 'LAKI-LAKI')->orderBy('urutan')->limit(5)->get();
            $progresses = Progress::active()->ordered()->get();
            $creator = $admin && $admin->brands->contains('id', $brand->id) ? $admin : $superadmin;

            // 6 PO dengan status berbeda per brand
            $scenarios = [
                ['status' => 'draft', 'progress_offset' => -1, 'with_dp' => false],
                ['status' => 'draft', 'progress_offset' => -1, 'with_dp' => true],
                ['status' => 'published', 'progress_offset' => 0, 'with_dp' => true],
                ['status' => 'on_progress', 'progress_offset' => 3, 'with_dp' => true],
                ['status' => 'siap_dikirim', 'progress_offset' => 11, 'with_dp' => true],
                ['status' => 'sudah_dikirim', 'progress_offset' => 12, 'with_dp' => true],
            ];

            foreach ($scenarios as $idx => $sc) {
                $customer = $customers[$idx % $customers->count()];
                $tanggalMasuk = Carbon::now()->subDays(20 - $idx * 3);
                $deadline = $tanggalMasuk->copy()->addDays(rand(10, 21));

                $order = Order::create([
                    'brand_id' => $brand->id,
                    'no_po' => $numbers->generateOrderNumber($brand, "PO {$customer->nama} #" . ($idx + 1)),
                    'nama_po' => "PO {$customer->nama} #" . ($idx + 1),
                    'status_po' => 'draft',
                    'is_special_order' => $idx === 1,
                    'tanggal_masuk' => $tanggalMasuk->toDateString(),
                    'deadline_customer' => $deadline->toDateString(),
                    'kategori_order_id' => $kategoris->random()?->id,
                    'sumber_order_id' => $sumbers->random()?->id,
                    'pelanggan_id' => $customer->id,
                    'catatan' => $idx % 2 === 0 ? 'Order tim, mohon segera diproses.' : null,
                    'created_by' => $creator->id,
                ]);

                // Items (2 produk per PO)
                $totalTagihan = 0;
                foreach ($products->random(min(2, $products->count())) as $product) {
                    $qty = rand(15, 30);
                    $harga = (float) $product->harga;
                    $subtotal = $qty * $harga;
                    $totalTagihan += $subtotal;

                    $item = OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'nama_produk' => $product->nama,
                        'varian_label' => 'Pemain Utama',
                        'quantity' => $qty,
                        'harga_satuan' => $harga,
                        'subtotal' => $subtotal,
                        'bahan_kain_id' => $bahan?->id,
                        'jenis_setelan' => 'stell',
                        'logo_id' => $logo?->id,
                        'warna' => ['Merah', 'Biru', 'Hijau', 'Kuning'][rand(0, 3)],
                    ]);

                    // 3 nameset per item
                    foreach (range(1, 3) as $n) {
                        $size = $sizes->random();
                        OrderNameset::create([
                            'order_item_id' => $item->id,
                            'nama_punggung' => 'Player ' . $n,
                            'nomor_punggung' => (string) (rand(1, 99)),
                            'size_id' => $size->id,
                            'size_label' => $size->kategori_size . '-' . $size->ukuran,
                            'urutan' => $n,
                        ]);
                    }
                }
                $order->update(['total_tagihan' => $totalTagihan]);

                // DP
                if ($sc['with_dp']) {
                    OrderPayment::create([
                        'order_id' => $order->id,
                        'payment_type' => 'dp',
                        'amount' => $totalTagihan * 0.3,
                        'payment_date' => $tanggalMasuk->copy()->addDay()->toDateString(),
                        'recorded_by' => $creator->id,
                    ]);
                }

                // Publish + progress
                if ($sc['status'] !== 'draft') {
                    $order->update([
                        'status_po' => $sc['status'],
                        'published_at' => $tanggalMasuk->copy()->addDay(),
                        'published_by' => $creator->id,
                    ]);

                    foreach ($progresses as $i => $p) {
                        $status = 'pending';
                        if ($i < $sc['progress_offset']) $status = 'selesai';
                        elseif ($i == $sc['progress_offset']) $status = 'on_progress';

                        $detail = OrderProgressDetail::create([
                            'order_id' => $order->id,
                            'progress_id' => $p->id,
                            'status' => $status,
                            'catatan' => $status === 'selesai' ? 'Tahap ' . $p->nama_progress . ' selesai.' : null,
                            'started_at' => $status !== 'pending' ? now()->subDays(rand(1, 5)) : null,
                            'completed_at' => $status === 'selesai' ? now()->subDays(rand(0, 4)) : null,
                            'updated_by' => $creator->id,
                        ]);
                    }

                    // Lock saat masuk pengerjaan
                    if (in_array($sc['status'], ['on_progress', 'siap_dikirim', 'sudah_dikirim'], true)) {
                        POLockStatus::create([
                            'order_id' => $order->id,
                            'is_locked' => true,
                            'locked_at' => $tanggalMasuk->copy()->addDays(2),
                            'locked_by' => $creator->id,
                        ]);
                    }
                }
            }
        }
    }
}
