<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Master\BahanKain;
use App\Models\Master\Customer;
use App\Models\Master\CustomerType;
use App\Models\Master\Iklan;
use App\Models\Master\JenisOrder;
use App\Models\Master\KategoriOrder;
use App\Models\Master\Logo;
use App\Models\Master\PolaJahitan;
use App\Models\Master\Printing;
use App\Models\Master\Product;
use App\Models\Master\Progress;
use App\Models\Master\Resleting;
use App\Models\Master\Size;
use App\Models\Master\SumberOrder;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Order\OrderNameset;
use App\Models\Order\OrderPayment;
use App\Models\Order\OrderProgressDetail;
use App\Models\Order\POLockStatus;
use App\Models\Order\Invoice;
use App\Models\Order\InvoiceItem;
use App\Models\User;
use App\Services\NumberGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        InvoiceItem::truncate();
        Invoice::truncate();
        POLockStatus::truncate();
        OrderProgressDetail::truncate();
        OrderPayment::truncate();
        OrderNameset::truncate();
        OrderItem::truncate();
        Order::truncate();
        Schema::enableForeignKeyConstraints();

        $this->seed100MixedOrders();
    }

    private function seed100MixedOrders(): void
    {
        $numbers = app(NumberGenerator::class);
        $superadmin = User::where('email', 'superadmin@nisreport.local')->first();
        if (!$superadmin) {
            $superadmin = User::first();
        }

        $brands = Brand::all();
        if ($brands->isEmpty()) {
            $this->command->error("Tidak ada brand yang ditemukan. Silakan jalankan BrandSeeder terlebih dahulu.");
            return;
        }

        // Fetch master data globally
        $kategoris = KategoriOrder::all();
        $jenisOrders = JenisOrder::all();
        $sumbers = SumberOrder::all();
        $iklans = Iklan::all();
        $products = Product::all();
        $bahanAtasan = BahanKain::active()->get();
        $logos = Logo::active()->get();
        $printings = Printing::active()->get();
        $resletings = Resleting::active()->get();
        $polaLengan = PolaJahitan::where('jenis_pola', 'Lengan')->get();
        $polaKerah = PolaJahitan::where('jenis_pola', 'Kerah')->get();
        $polaBawah = PolaJahitan::where('jenis_pola', 'Bawah')->get();
        $sizesFlat = Size::orderBy('urutan')->limit(12)->get();
        $progresses = Progress::active()->ordered()->get();

        $prefixes = [
            'Jersey Tim Futsal', 'Jersey Sepakbola', 'Jaket Angkatan', 'Hoodie Komunitas', 'Polo Kantor',
            'Jersey Badminton', 'Celana Training', 'Jersey Voli', 'Kaos Event', 'Jersey Gowes',
            'Seragam Olahraga', 'Jersey Esport', 'Rompi Latihan', 'Kaos Polo Panitia', 'Jaket Bomber'
        ];
        $suffixes = [
            'Garuda FC', 'Maju Jaya', 'Lamongan Sport', 'Kencana', 'Bhayangkara', 'Sinar Terang',
            'Bintang Muda', 'Dinas Pendidikan', 'SMEA 1', 'Arhanud', 'Hore-Hore', 'Suka-Suka',
            'Rider Lamongan', 'Klub Smash', 'Al-Fatih', 'Wijaya Kusuma', 'Jaya Abadi', 'Semeru',
            'Merapi', 'Tunggal Ika', 'Satria', 'Elang Laut', 'Banteng Merah', 'Kancil Mas'
        ];

        $deadlineOffsets = [-2, -1, 0, 1, 2, 3, 5, 7];

        $this->command->info("Mulai melakukan seeding 100 data order campuran...");

        for ($i = 1; $i <= 100; $i++) {
            $brand = $brands[($i - 1) % $brands->count()];

            // 1. Resolve customer (up to 3 per brand)
            $customerIndex = (($i - 1) % 3) + 1;
            $customerCode = $brand->kode . '-CUST-' . str_pad((string)$customerIndex, 3, '0', STR_PAD_LEFT);
            $customer = Customer::where('brand_id', $brand->id)->where('kode', $customerCode)->first();
            if (!$customer) {
                $type = CustomerType::where(function ($q) use ($brand) {
                    $q->where('brand_id', $brand->id)->orWhereNull('brand_id');
                })->where('nama', 'Reguler')->first();

                $customerNames = [
                    1 => 'Klub ' . $brand->nama_brand . ' A',
                    2 => 'Klub ' . $brand->nama_brand . ' B',
                    3 => 'Instansi ' . $brand->nama_brand
                ];

                $customer = Customer::create([
                    'brand_id' => $brand->id,
                    'kode' => $customerCode,
                    'nama' => $customerNames[$customerIndex] ?? 'Pelanggan ' . $brand->nama_brand,
                    'nomor_hp' => '08123456' . str_pad((string)($i * 7), 4, '0', STR_PAD_LEFT),
                    'email' => strtolower($brand->kode) . '.cust' . $customerIndex . '@example.test',
                    'type_pelanggan_id' => $type?->id,
                    'provinsi_code' => '32',
                    'provinsi_nama' => 'JAWA BARAT',
                    'kabupaten_code' => '3273',
                    'kabupaten_nama' => 'KOTA BANDUNG',
                    'is_active' => true,
                ]);
            }

            // 2. Resolve creator user
            $adminUser = User::where('email', 'like', '%' . strtolower($brand->kode) . '%')
                ->whereHas('roles', fn($q) => $q->where('name', 'admin_brand'))
                ->first() ?? $superadmin;
            $creator = $adminUser ?? $superadmin;

            // 3. Determine status, deadline, and tanggal_masuk
            if ($i <= 10) {
                $status = 'draft';
                $deadline = Carbon::now()->addDays(rand(15, 30));
                $tanggalMasuk = $deadline->copy()->subDays(rand(7, 14));
            } elseif ($i <= 25) {
                $status = 'sudah_dikirim';
                $deadline = Carbon::now()->subDays(rand(10, 30));
                $tanggalMasuk = $deadline->copy()->subDays(rand(10, 20));
            } elseif ($i <= 30) {
                $status = 'selesai';
                $deadline = Carbon::now()->subDays(rand(10, 30));
                $tanggalMasuk = $deadline->copy()->subDays(rand(10, 20));
            } elseif ($i <= 60) {
                $status = 'on_progress';
                $deadline = Carbon::now()->addDays($deadlineOffsets[($i - 31) % count($deadlineOffsets)]);
                $tanggalMasuk = $deadline->copy()->subDays(rand(10, 15));
            } elseif ($i <= 90) {
                $status = 'published';
                $deadline = Carbon::now()->addDays($deadlineOffsets[($i - 61) % count($deadlineOffsets)]);
                $tanggalMasuk = $deadline->copy()->subDays(rand(10, 15));
            } else {
                $status = 'siap_dikirim';
                $deadline = Carbon::now()->addDays($deadlineOffsets[($i - 91) % count($deadlineOffsets)]);
                $tanggalMasuk = $deadline->copy()->subDays(rand(10, 15));
            }

            $namaPo = $prefixes[($i - 1) % count($prefixes)] . ' ' . $suffixes[($i * 3) % count($suffixes)] . ' #' . $i;
            $isSpecial = ($i % 7 === 0);
            $catatan = ($i % 5 === 0) ? 'Catatan khusus untuk order #' . $i : null;

            $printingIds = $printings->isNotEmpty()
                ? $printings->random(min(rand(1, 2), $printings->count()))->pluck('id')->toArray()
                : [];

            // 4. Create Order
            $order = Order::create([
                'brand_id'          => $brand->id,
                'no_po'             => $numbers->generateOrderNumber($brand, $namaPo, $tanggalMasuk),
                'nama_po'           => $namaPo,
                'status_po'         => 'draft', // updated later depending on status
                'is_special_order'  => $isSpecial,
                'tanggal_masuk'     => $tanggalMasuk->toDateString(),
                'deadline_customer' => $deadline->toDateString(),
                'kategori_order_id' => $kategoris->isNotEmpty() ? $kategoris->random()->id : null,
                'jenis_order_id'    => $jenisOrders->isNotEmpty() ? $jenisOrders->random()->id : null,
                'sumber_order_id'   => $sumbers->isNotEmpty() ? $sumbers->random()->id : null,
                'pelanggan_id'      => $customer->id,
                'printing_ids'      => $printingIds,
                'iklan_id'          => ($i % 4 === 0 && $iklans->isNotEmpty()) ? $iklans->random()->id : null,
                'catatan'           => $catatan,
                'created_by'        => $creator->id,
            ]);

            // 5. Create Items & Namesets
            $brandProducts = $products->where('brand_id', $brand->id);
            if ($brandProducts->isEmpty()) {
                $brandProducts = $products;
            }

            $numItems = (($i - 1) % 2) + 1; // 1 or 2 items
            $totalTagihan = 0;
            $allItems = [];

            for ($pIdx = 0; $pIdx < $numItems; $pIdx++) {
                $product = $brandProducts->isNotEmpty() ? $brandProducts->random() : null;
                if (!$product) continue;

                $qty = rand(4, 12);
                $harga = (float)$product->harga;
                $subtotal = $qty * $harga;
                $totalTagihan += $subtotal;

                $setelan = ['stell', 'non_stell', 'atasan_saja'][rand(0, 2)];
                $bahanA = $bahanAtasan->isNotEmpty() ? $bahanAtasan->random() : null;
                $bahanB = ($setelan === 'stell' && $bahanAtasan->count() > 1) ? $bahanAtasan->where('id', '!=', $bahanA?->id)->random() : null;

                $logoIds = $logos->isNotEmpty() ? $logos->random(min(rand(1, 3), $logos->count()))->pluck('id')->toArray() : [];

                $item = OrderItem::create([
                    'order_id'              => $order->id,
                    'product_id'            => $product->id,
                    'nama_produk'           => $product->nama,
                    'varian_label'          => 'Varian ' . ($pIdx + 1),
                    'quantity'              => $qty,
                    'harga_satuan'          => $harga,
                    'subtotal'              => $subtotal,
                    'bahan_kain_id'         => $bahanA?->id,
                    'bahan_kain_bawahan_id' => $bahanB?->id,
                    'jenis_setelan'         => $setelan,
                    'pola'                  => ['Reguler', 'Slim Fit', 'Oversize'][rand(0, 2)],
                    'logo_id'               => $logos->isNotEmpty() ? $logos->first()->id : null,
                    'logo_ids'              => $logoIds,
                    'printing_id'           => $printings->isNotEmpty() ? $printings->random()->id : null,
                    'resleting_id'          => $resletings->isNotEmpty() ? $resletings->random()->id : null,
                    'pola_jahitan_lengan_id' => $polaLengan->isNotEmpty() ? $polaLengan->random()->id : null,
                    'pola_jahitan_kerah_id'  => $polaKerah->isNotEmpty() ? $polaKerah->random()->id : null,
                    'pola_jahitan_bawah_id'  => $polaBawah->isNotEmpty() ? $polaBawah->random()->id : null,
                    'warna'                 => ['Merah-Hitam', 'Biru-Putih', 'Hijau-Kuning', 'Full Hitam', 'Navy-Gold'][rand(0, 4)],
                    'jml_atasan'            => (string)$qty,
                    'jml_bawahan'           => $setelan === 'stell' ? (string)$qty : null,
                    'jenis_kerah'           => ['V-Neck', 'O-Neck', 'Polo', 'Henley'][rand(0, 3)],
                    'jenis_rib'             => ['Rib Standar', 'Rib Rajut', 'Tanpa Rib'][rand(0, 2)],
                    'catatan'               => null,
                ]);
                $allItems[] = $item;

                // Select Language/Font for Nameset to verify Multi-font support
                $lang = 'latin';
                if ($i >= 40 && $i <= 43) {
                    $lang = 'ja';
                } elseif ($i >= 44 && $i <= 47) {
                    $lang = 'ar';
                } elseif ($i >= 48 && $i <= 50) {
                    $lang = 'mixed';
                }

                $customNames = [];
                if ($lang === 'ja') {
                    $customNames = $this->japaneseNamesets();
                } elseif ($lang === 'ar') {
                    $customNames = $this->arabicNamesets();
                } elseif ($lang === 'mixed') {
                    $customNames = $this->mixedNamesets();
                }

                $namaPemain = [
                    'Ahmad', 'Budi', 'Chandra', 'Dedy', 'Eka', 'Fadlan', 'Guntur', 'Hendra', 'Iwan', 'Joko',
                    'Kurniawan', 'Lutfi', 'Mulyono', 'Novi', 'Oki', 'Prabowo', 'Rian', 'Suryo', 'Taufik', 'Utomo'
                ];

                for ($n = 1; $n <= $qty; $n++) {
                    $size = $sizesFlat[($n - 1) % $sizesFlat->count()];
                    $sizeCelana = $setelan === 'stell' ? $sizesFlat[($n) % $sizesFlat->count()] : null;

                    if (!empty($customNames)) {
                        $cData = $customNames[($n - 1) % count($customNames)];
                        $namaPunggung = $cData['nama_punggung'];
                        $namaDada = $cData['nama_dada'] ?? null;
                        $namaLengan = $cData['nama_lengan'] ?? null;
                    } else {
                        $nama = $namaPemain[($n - 1) % count($namaPemain)];
                        $namaPunggung = strtoupper($nama);
                        $namaDada = $n <= 3 ? strtoupper($nama) : null;
                        $namaLengan = null;
                    }

                    OrderNameset::create([
                        'order_item_id'     => $item->id,
                        'nama_punggung'     => $namaPunggung,
                        'nomor_punggung'    => (string)$n,
                        'nama_dada'         => $namaDada,
                        'nomor_dada'        => $n <= 3 ? (string)$n : null,
                        'nama_lengan'       => $namaLengan,
                        'size_id'           => $size->id,
                        'size_label'        => $size->ukuran,
                        'size_celana_id'    => $sizeCelana?->id,
                        'size_celana_label' => $sizeCelana ? $sizeCelana->ukuran : null,
                        'urutan'            => $n,
                    ]);
                }
            }

            $order->update(['total_tagihan' => $totalTagihan]);

            // 6. Seed Payments
            $brandBanks = \App\Models\Master\BankAccount::where('brand_id', $brand->id)->get();
            $bank = $brandBanks->isNotEmpty() ? $brandBanks->random() : null;

            $withDp = ($status !== 'draft');
            $withPelunasan = ($status === 'sudah_dikirim' || $status === 'selesai');
            $dpPct = 0.5;

            if ($withDp && $totalTagihan > 0) {
                $dpAmount = round($totalTagihan * $dpPct, 0);
                $dpDate = $tanggalMasuk->copy()->addDay();
                OrderPayment::create([
                    'order_id'     => $order->id,
                    'payment_type' => 'dp',
                    'dp_sequence'  => 1,
                    'is_debit'     => true,
                    'amount'       => $dpAmount,
                    'payment_date' => $dpDate->toDateString(),
                    'bank_id'      => $bank?->id,
                    'notes'        => 'DP 50%',
                    'recorded_by'  => $creator->id,
                    'verified_by'  => $superadmin->id,
                    'verified_at'  => $dpDate->copy()->addHours(2),
                ]);

                if ($withPelunasan && $totalTagihan > $dpAmount) {
                    $sisaAmount = $totalTagihan - $dpAmount;
                    $pelunasanDate = $tanggalMasuk->copy()->addDays(5);
                    OrderPayment::create([
                        'order_id'     => $order->id,
                        'payment_type' => 'pelunasan',
                        'is_debit'     => true,
                        'amount'       => $sisaAmount,
                        'payment_date' => $pelunasanDate->toDateString(),
                        'bank_id'      => $bank?->id,
                        'notes'        => 'Pelunasan sisa tagihan',
                        'recorded_by'  => $creator->id,
                        'verified_by'  => $superadmin->id,
                        'verified_at'  => $pelunasanDate->copy()->addHours(3),
                    ]);
                }
            }

            // 7. Update Status PO & Production Progress
            if ($status !== 'draft') {
                $publishDate = $tanggalMasuk->copy()->addDay();
                $startProd = $publishDate->copy()->addDay();

                $progressOffset = 0;
                if ($status === 'on_progress') $progressOffset = 5;
                elseif ($status === 'siap_dikirim') $progressOffset = 11;
                elseif ($status === 'sudah_dikirim' || $status === 'selesai') $progressOffset = 12;

                $endProd = $progressOffset >= 11 ? $startProd->copy()->addDays($progressOffset) : null;

                $order->update([
                    'status_po'             => $status,
                    'published_at'          => $publishDate,
                    'published_by'          => $creator->id,
                    'start_production_date' => $startProd->toDateString(),
                    'end_production_date'   => $endProd?->toDateString(),
                    'nama_ekspedisi'        => $status === 'sudah_dikirim' ? ['JNE', 'J&T', 'SiCepat', 'AnterAja'][rand(0, 3)] : null,
                    'no_resi'               => $status === 'sudah_dikirim' ? 'REG' . rand(100000000, 999999999) : null,
                    'is_lunas'              => $withPelunasan,
                    'lunas_at'              => $withPelunasan ? $tanggalMasuk->copy()->addDays(6) : null,
                    'lunas_by'              => $withPelunasan ? $superadmin->id : null,
                ]);

                // Progress Details
                foreach ($progresses as $prIdx => $p) {
                    $prStatus = 'pending';
                    $startedAt = null;
                    $completedAt = null;

                    if ($prIdx < $progressOffset) {
                        $prStatus = 'selesai';
                        $startedAt = $startProd->copy()->addDays($prIdx);
                        $completedAt = $startedAt->copy()->addHours(rand(4, 16));
                    } elseif ($prIdx == $progressOffset) {
                        $prStatus = 'on_progress';
                        $startedAt = $startProd->copy()->addDays($prIdx);
                    }

                    OrderProgressDetail::create([
                        'order_id'     => $order->id,
                        'progress_id'  => $p->id,
                        'status'       => $prStatus,
                        'catatan'      => $prStatus === 'selesai' ? $p->nama_progress . ' selesai.' : null,
                        'started_at'   => $startedAt,
                        'completed_at' => $completedAt,
                        'updated_by'   => $creator->id,
                    ]);
                }

                // Lock PO
                if (in_array($status, ['on_progress', 'selesai_produksi', 'siap_dikirim', 'sudah_dikirim', 'selesai'])) {
                    POLockStatus::create([
                        'order_id'  => $order->id,
                        'is_locked' => true,
                        'locked_at' => $startProd,
                        'locked_by' => $creator->id,
                    ]);
                }
            }

            // 8. Create Invoices & Invoice Items
            $totalPaid = $withPelunasan ? $totalTagihan : ($withDp ? $dpAmount : 0);
            $newSisa = max(0, $totalTagihan - $totalPaid);
            $invStatus = $status === 'draft' ? 'draft' : ($withPelunasan ? 'paid' : 'published');

            $invoice = Invoice::create([
                'brand_id' => $order->brand_id,
                'order_id' => $order->id,
                'invoice_number' => $numbers->generateInvoiceNumber($brand, $order),
                'tanggal_terbit' => $tanggalMasuk->toDateString(),
                'jatuh_tempo' => $tanggalMasuk->copy()->addDays(14)->toDateString(),
                'status' => $invStatus,
                'total_tagihan' => $totalTagihan,
                'total_bayar' => $totalPaid,
                'dp_amount' => $withDp ? $dpAmount : 0,
                'sisa_pembayaran' => $newSisa,
                'created_by' => $creator->id,
            ]);

            foreach ($allItems as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'produk' => $item->nama_produk . ($item->varian_label ? " ({$item->varian_label})" : ''),
                    'jumlah' => $item->quantity,
                    'harga_satuan' => $item->harga_satuan,
                    'subtotal' => $item->subtotal,
                ]);
            }
        }

        $this->command->info("Berhasil melakukan seeding 100 data order campuran!");
    }

    private function japaneseNamesets(): array
    {
        return [
            ['nama_punggung' => '田中 健一', 'nama_dada' => 'タナカ', 'nama_lengan' => 'たなか'],
            ['nama_punggung' => '鈴木 二郎', 'nama_dada' => 'スズキ', 'nama_lengan' => 'すずき'],
            ['nama_punggung' => '佐藤 三花', 'nama_dada' => 'サトウ', 'nama_lengan' => 'さとう'],
            ['nama_punggung' => '山本 四季', 'nama_dada' => 'ヤマモト', 'nama_lengan' => 'やまもと'],
            ['nama_punggung' => '松本 五郎', 'nama_dada' => 'マツモト', 'nama_lengan' => 'まつomと'],
            ['nama_punggung' => 'けんじ', 'nama_dada' => 'ケンジ', 'nama_lengan' => 'けんじ'],
            ['nama_punggung' => 'サクラ', 'nama_dada' => 'サクラ', 'nama_lengan' => 'さくら'],
            ['nama_punggung' => '龍 神風', 'nama_dada' => 'リュウ', 'nama_lengan' => 'りゅう'],
            ['nama_punggung' => 'アカギ リョウ', 'nama_dada' => 'アカギ', 'nama_lengan' => 'あかぎ'],
            ['nama_punggung' => '光 速 丸', 'nama_dada' => 'コウソク', 'nama_lengan' => 'こうそく'],
        ];
    }

    private function arabicNamesets(): array
    {
        return [
            ['nama_punggung' => 'محمد علي', 'nama_dada' => 'محمد', 'nama_lengan' => 'علي'],
            ['nama_punggung' => 'أحمد الكريم', 'nama_dada' => 'أحمد', 'nama_lengan' => 'كريم'],
            ['nama_punggung' => 'عبد الرحمن', 'nama_dada' => 'عبدالرحمن', 'nama_lengan' => 'رحمن'],
            ['nama_punggung' => 'فيصل الأمين', 'nama_dada' => 'فيصل', 'nama_lengan' => 'أمين'],
            ['nama_punggung' => 'سعد المطيري', 'nama_dada' => 'سعد', 'nama_lengan' => 'مطيري'],
            ['nama_punggung' => 'خالد بن عمر', 'nama_dada' => 'خالد', 'nama_lengan' => 'عmer'],
            ['nama_punggung' => 'ناصر الدوسري', 'nama_dada' => 'ناصر', 'nama_lengan' => 'دوسري'],
            ['nama_punggung' => 'راشد الغامدي', 'nama_dada' => 'راشد', 'nama_lengan' => 'غامدي'],
            ['nama_punggung' => 'عمر الشهri', 'nama_dada' => 'عمر', 'nama_lengan' => 'شهري'],
            ['nama_punggung' => 'يوسف النجار', 'nama_dada' => 'يوسف', 'nama_lengan' => 'نجار'],
        ];
    }

    private function mixedNamesets(): array
    {
        return [
            ['nama_punggung' => 'SUPARMAN', 'nama_dada' => 'SUPARMAN', 'nama_lengan' => null],
            ['nama_punggung' => 'BUDI SANTOSO', 'nama_dada' => 'BUDI', 'nama_lengan' => null],
            ['nama_punggung' => '田中 健二', 'nama_dada' => 'タナカ', 'nama_lengan' => 'たなか'],
            ['nama_punggung' => 'アオイ', 'nama_dada' => 'アオイ', 'nama_lengan' => null],
            ['nama_punggung' => 'محمد علي', 'nama_dada' => 'محمد', 'nama_lengan' => 'علي'],
            ['nama_punggung' => 'أحمد سعيد', 'nama_dada' => 'أحمد', 'nama_lengan' => null],
            ['nama_punggung' => 'WAHYU P.', 'nama_dada' => 'WAHYU', 'nama_lengan' => null],
            ['nama_punggung' => '龍 神', 'nama_dada' => '龍神', 'nama_lengan' => null],
            ['nama_punggung' => 'عبد الله', 'nama_dada' => 'عبدالله', 'nama_lengan' => null],
            ['nama_punggung' => 'REZA F.', 'nama_dada' => 'REZA', 'nama_lengan' => null],
            ['nama_punggung' => 'HIRO 浩', 'nama_dada' => 'HIRO', 'nama_lengan' => '浩'],
            ['nama_punggung' => 'ALI عَلِي', 'nama_dada' => 'ALI', 'nama_lengan' => 'عَلِي'],
        ];
    }
}
