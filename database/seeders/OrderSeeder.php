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
        $backupPath = app()->environment('testing')
            ? database_path('backup_orders_test.json')
            : database_path('backup_orders.json');
        if (file_exists($backupPath)) {
            $this->command->info("Menemukan berkas backup orders. Melakukan pemulihan data...");
            $this->restoreFromBackup($backupPath);
            return;
        }

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

        $this->seedOrders();
    }

    private function restoreFromBackup(string $path): void
    {
        $data = json_decode(file_get_contents($path), true);
        if (!$data) {
            $this->command->error("Format berkas backup tidak valid.");
            return;
        }

        Schema::disableForeignKeyConstraints();

        $tables = [
            'kategori_pemasukan',
            'kategori_pengeluaran',
            'customers',
            'orders',
            'order_items',
            'order_namesets',
            'order_payments',
            'order_progress_details',
            'po_lock_statuses',
            'po_versions',
            'invoices',
            'invoice_items',
            'rijeks',
            'refunds',
            'pemasukan',
            'pengeluaran',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                \DB::table($table)->truncate();
                if (isset($data[$table]) && is_array($data[$table])) {
                    foreach ($data[$table] as $row) {
                        foreach ($row as $key => $val) {
                            if (is_array($val) || is_object($val)) {
                                $row[$key] = json_encode($val);
                            }
                        }
                        \DB::table($table)->insert($row);
                    }
                }
            }
        }

        Schema::enableForeignKeyConstraints();
        $this->command->info("Data berhasil dipulihkan dari backup!");
    }

    private function seedOrders(): void
    {
        $numbers = app(NumberGenerator::class);
        $superadmin = User::where('email', 'superadmin@nisreport.local')->first();

        foreach (Brand::all() as $brand) {
            $customers = Customer::where('brand_id', $brand->id)->get();
            if ($customers->isEmpty()) continue;

            // Fetch master data for this brand
            $kategoris = KategoriOrder::where(function ($q) use ($brand) {
                $q->where('brand_id', $brand->id)->orWhereNull('brand_id');
            })->get();
            $jenisOrders = JenisOrder::where(function ($q) use ($brand) {
                $q->where('brand_id', $brand->id)->orWhereNull('brand_id');
            })->get();
            $sumbers = SumberOrder::where(function ($q) use ($brand) {
                $q->where('brand_id', $brand->id)->orWhereNull('brand_id');
            })->get();
            $iklans = Iklan::where(function ($q) use ($brand) {
                $q->where('brand_id', $brand->id)->orWhereNull('brand_id');
            })->get();
            $products = Product::where(function ($q) use ($brand) {
                $q->where('brand_id', $brand->id)->orWhereNull('brand_id');
            })->get();
            $masterBrandId = \App\Support\BrandContext::masterDataId(request(), $brand->id);
            $banks = \App\Models\Master\BankAccount::where('brand_id', $masterBrandId)->get();
            $bahanAtasan = BahanKain::active()->get();
            $logos = Logo::active()->get();
            $printings = Printing::active()->get();
            $resletings = Resleting::active()->get();
            $polaLengan = PolaJahitan::where('jenis_pola', 'Lengan')->get();
            $polaKerah = PolaJahitan::where('jenis_pola', 'Kerah')->get();
            $polaBawah = PolaJahitan::where('jenis_pola', 'Bawah')->get();
            $sizesFlat = Size::orderBy('urutan')->limit(12)->get();
            $progresses = Progress::active()->ordered()->get();

            $adminUser = User::where('email', 'like', '%' . strtolower($brand->kode) . '%')
                ->whereHas('roles', fn($q) => $q->where('name', 'admin_brand'))
                ->first() ?? $superadmin;
            $creator = $adminUser ?? $superadmin;

            // ----------------------------------------------------------------
            // Generate 10 realistic POs per brand across 2025-2026 timeline
            // ----------------------------------------------------------------
            $scenarios = $this->buildScenarios();

            foreach ($scenarios as $idx => $sc) {
                $customer = $customers[$idx % $customers->count()];
                $tanggalMasuk = Carbon::parse($sc['tanggal']);
                $deadline = $tanggalMasuk->copy()->addDays($sc['deadline_days']);

                // Determine printing_ids (array of printing UUIDs)
                $printingIds = $printings->isNotEmpty()
                    ? $printings->random(min(rand(1, 2), $printings->count()))->pluck('id')->toArray()
                    : [];

                $order = Order::create([
                    'brand_id'          => $brand->id,
                    'no_po'             => $numbers->generateOrderNumber($brand, $sc['nama_po'], $tanggalMasuk),
                    'nama_po'           => $sc['nama_po'],
                    'status_po'         => 'draft',
                    'is_special_order'  => $sc['special'],
                    'tanggal_masuk'     => $tanggalMasuk->toDateString(),
                    'deadline_customer' => $deadline->toDateString(),
                    'kategori_order_id' => $kategoris->isNotEmpty() ? $kategoris->random()->id : null,
                    'jenis_order_id'    => $jenisOrders->isNotEmpty() ? $jenisOrders->random()->id : null,
                    'sumber_order_id'   => $sumbers->isNotEmpty() ? $sumbers->random()->id : null,
                    'pelanggan_id'      => $customer->id,
                    'printing_ids'      => $printingIds,
                    'iklan_id'          => ($idx % 2 === 0 && $iklans->isNotEmpty()) ? $iklans->random()->id : null,
                    'catatan'           => $sc['catatan'],
                    'created_by'        => $creator->id,
                ]);

                // ---- ITEMS (1-3 produk per PO, qty bervariasi) ----
                $totalTagihan = 0;
                $numItems = $sc['items'];
                $selectedProducts = $products->count() >= $numItems
                    ? $products->random($numItems) : $products;

                $allItems = [];
                foreach ($selectedProducts as $pIdx => $product) {
                    $qty = $sc['quantities'][$pIdx] ?? rand(10, 25);
                    $harga = (float) $product->harga;
                    $subtotal = $qty * $harga;
                    $totalTagihan += $subtotal;

                    $setelan = ['stell', 'non_stell', 'atasan_saja'][rand(0, 2)];
                    $bahanA = $bahanAtasan->isNotEmpty() ? $bahanAtasan->random() : null;
                    $bahanB = ($setelan === 'stell' && $bahanAtasan->count() > 1)
                        ? $bahanAtasan->where('id', '!=', $bahanA?->id)->random() : null;

                    // Multiple logo_ids
                    $logoIds = $logos->isNotEmpty()
                        ? $logos->random(min(rand(1, 3), $logos->count()))->pluck('id')->toArray()
                        : [];

                    $item = OrderItem::create([
                        'order_id'              => $order->id,
                        'product_id'            => $product->id,
                        'nama_produk'           => $product->nama,
                        'varian_label'          => $sc['varian_labels'][$pIdx] ?? 'Pemain Utama',
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
                        'jml_atasan'            => (string) $qty,
                        'jml_bawahan'           => $setelan === 'stell' ? (string) $qty : null,
                        'jenis_kerah'           => ['V-Neck', 'O-Neck', 'Polo', 'Henley'][rand(0, 3)],
                        'jenis_rib'             => ['Rib Standar', 'Rib Rajut', 'Tanpa Rib'][rand(0, 2)],
                        'catatan'               => $pIdx === 0 ? 'Mohon perhatikan detail warna sesuai mockup.' : null,
                    ]);
                    $allItems[] = $item;

                    // ---- NAMESETS (jumlah = quantity, data lengkap) ----
                    $sizes = $sizesFlat;
                    if ($sizes->isEmpty()) $sizes = Size::orderBy('urutan')->get();

                    $lang = $sc['lang'] ?? 'latin';
                    $customNames = [];
                    if ($lang === 'ja') {
                        $customNames = $this->japaneseNamesets();
                    } elseif ($lang === 'ar') {
                        $customNames = $this->arabicNamesets();
                    } elseif ($lang === 'mixed') {
                        $customNames = $this->mixedNamesets();
                    }

                    $namaPemain = [
                        'Andi',
                        'Budi',
                        'Citra',
                        'Dedi',
                        'Eko',
                        'Fani',
                        'Gita',
                        'Hadi',
                        'Irfan',
                        'Joko',
                        'Kiki',
                        'Lina',
                        'Maman',
                        'Nana',
                        'Oscar',
                        'Putra',
                        'Qori',
                        'Rina',
                        'Susi',
                        'Tono',
                        'Udin',
                        'Vina',
                        'Wawan',
                        'Xena',
                        'Yudi'
                    ];

                    for ($n = 1; $n <= $qty; $n++) {
                        $size = $sizes[($n - 1) % $sizes->count()];
                        $sizeCelana = $setelan === 'stell' ? $sizes[($n) % $sizes->count()] : null;

                        if (!empty($customNames)) {
                            $cData = $customNames[($n - 1) % count($customNames)];
                            $namaPunggung = $cData['nama_punggung'];
                            $namaDada = $cData['nama_dada'] ?? null;
                            $namaLengan = $cData['nama_lengan'] ?? null;
                        } else {
                            $nama = $namaPemain[($n - 1) % count($namaPemain)];
                            $namaPunggung = strtoupper($nama);
                            $namaDada = $n <= 5 ? strtoupper($nama) : null;
                            $namaLengan = null;
                        }

                        OrderNameset::create([
                            'order_item_id'   => $item->id,
                            'nama_punggung'   => $namaPunggung,
                            'nomor_punggung'  => (string) $n,
                            'nama_dada'       => $namaDada,
                            'nomor_dada'      => $n <= 5 ? (string) $n : null,
                            'nama_lengan'     => $namaLengan,
                            'size_id'         => $size->id,
                            'size_label'      => $size->ukuran,
                            'size_celana_id'  => $sizeCelana?->id,
                            'size_celana_label' => $sizeCelana ? $sizeCelana->ukuran : null,
                            'urutan'          => $n,
                        ]);
                    }
                }

                // ---- Set total_tagihan from actual item subtotals ----
                $order->update(['total_tagihan' => $totalTagihan]);

                // ---- PAYMENTS (verified with bank, dates logical) ----
                $bank = $banks->isNotEmpty() ? $banks->random() : null;
                if ($sc['with_dp'] && $totalTagihan > 0) {
                    $dpAmount = round($totalTagihan * $sc['dp_pct'], 0);
                    $dpDate = $tanggalMasuk->copy()->addDay();
                    OrderPayment::create([
                        'order_id'     => $order->id,
                        'payment_type' => 'dp',
                        'dp_sequence'  => 1,
                        'is_debit'     => true,
                        'amount'       => $dpAmount,
                        'payment_date' => $dpDate->toDateString(),
                        'bank_id'      => $bank?->id,
                        'notes'        => 'DP ' . round($sc['dp_pct'] * 100) . '%',
                        'recorded_by'  => $creator->id,
                        'verified_by'  => $superadmin->id,
                        'verified_at'  => $dpDate->copy()->addHours(2),
                    ]);

                    // Pelunasan for completed orders
                    if ($sc['with_pelunasan'] && $totalTagihan > $dpAmount) {
                        $sisaAmount = $totalTagihan - $dpAmount;
                        $pelunasanDate = $tanggalMasuk->copy()->addDays($sc['deadline_days'] - 2);
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

                // ---- PUBLISH + PRODUCTION TIMELINE ----
                if ($sc['status'] !== 'draft') {
                    $publishDate = $tanggalMasuk->copy()->addDay();
                    $startProd = $publishDate->copy()->addDay();
                    $endProd = $sc['progress_offset'] >= 11
                        ? $startProd->copy()->addDays($sc['progress_offset'])
                        : null;

                    $order->update([
                        'status_po'             => $sc['status'],
                        'published_at'          => $publishDate,
                        'published_by'          => $creator->id,
                        'start_production_date' => $startProd->toDateString(),
                        'end_production_date'   => $endProd?->toDateString(),
                        'nama_ekspedisi'        => $sc['status'] === 'sudah_dikirim' ? ['JNE', 'J&T', 'SiCepat', 'AnterAja'][rand(0, 3)] : null,
                        'no_resi'               => $sc['status'] === 'sudah_dikirim' ? 'REG' . rand(100000000, 999999999) : null,
                        'is_lunas'              => $sc['with_pelunasan'],
                        'lunas_at'              => $sc['with_pelunasan'] ? $tanggalMasuk->copy()->addDays($sc['deadline_days'] - 1) : null,
                        'lunas_by'              => $sc['with_pelunasan'] ? $superadmin->id : null,
                    ]);

                    // Progress details with realistic timeline
                    foreach ($progresses as $i => $p) {
                        $status = 'pending';
                        $startedAt = null;
                        $completedAt = null;

                        if ($i < $sc['progress_offset']) {
                            $status = 'selesai';
                            $startedAt = $startProd->copy()->addDays($i);
                            $completedAt = $startedAt->copy()->addHours(rand(4, 16));
                        } elseif ($i == $sc['progress_offset']) {
                            $status = 'on_progress';
                            $startedAt = $startProd->copy()->addDays($i);
                        }

                        OrderProgressDetail::create([
                            'order_id'     => $order->id,
                            'progress_id'  => $p->id,
                            'status'       => $status,
                            'catatan'      => $status === 'selesai' ? $p->nama_progress . ' selesai tepat waktu.' : null,
                            'started_at'   => $startedAt,
                            'completed_at' => $completedAt,
                            'updated_by'   => $creator->id,
                        ]);
                    }

                    // Lock PO for active production
                    if (in_array($sc['status'], ['on_progress', 'selesai_produksi', 'siap_dikirim', 'sudah_dikirim'])) {
                        POLockStatus::create([
                            'order_id'  => $order->id,
                            'is_locked' => true,
                            'locked_at' => $startProd,
                            'locked_by' => $creator->id,
                        ]);
                    }
                }

                // Create corresponding Invoice and InvoiceItems for tracking & finance
                $totalTagihan = (float) $order->totalTagihan();
                $totalPaid = (float) $order->totalPaid();
                $dpAmount = (float) $order->payments()->where('payment_type', 'dp')->whereNotNull('verified_at')->sum('amount');
                $newSisa = max(0, $totalTagihan - $totalPaid);
                $invStatus = $sc['status'] === 'draft' ? 'draft' : ($sc['with_pelunasan'] ? 'paid' : 'published');

                $invoice = Invoice::create([
                    'brand_id' => $order->brand_id,
                    'order_id' => $order->id,
                    'invoice_number' => $numbers->generateInvoiceNumber($brand, $order),
                    'tanggal_terbit' => $tanggalMasuk->toDateString(),
                    'jatuh_tempo' => $tanggalMasuk->copy()->addDays(14)->toDateString(),
                    'status' => $invStatus,
                    'total_tagihan' => $totalTagihan,
                    'total_bayar' => $totalPaid,
                    'dp_amount' => $dpAmount,
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
        }
    }

    /**
     * 10 diverse PO scenarios spanning 2025-2026 with realistic garment production data.
     * All quantities and prices are deterministic (no rand()) so totals are reproducible.
     */
    private function buildScenarios(): array
    {
        return [
            // ---- 2025 Orders (historical data for comparison reports) ----
            [
                'nama_po' => 'Jersey Tim Futsal Liga Kota 2025',
                'tanggal' => '2025-03-15',
                'deadline_days' => 14,
                'special' => false,
                'status' => 'sudah_dikirim',
                'progress_offset' => 12,
                'with_dp' => true,
                'dp_pct' => 0.5,
                'with_pelunasan' => true,
                'items' => 2,
                'quantities' => [20, 20],
                'varian_labels' => ['Pemain', 'Kiper'],
                'catatan' => 'Order jersey liga kota, butuh sebelum pertandingan.',
            ],
            [
                'nama_po' => 'Jaket Komunitas Hiking Bandung',
                'tanggal' => '2025-06-10',
                'deadline_days' => 21,
                'special' => false,
                'status' => 'sudah_dikirim',
                'progress_offset' => 12,
                'with_dp' => true,
                'dp_pct' => 0.3,
                'with_pelunasan' => true,
                'items' => 1,
                'quantities' => [15],
                'varian_labels' => ['Anggota'],
                'catatan' => 'Jaket waterproof untuk komunitas hiking.',
            ],
            [
                'nama_po' => 'Seragam Olahraga SMA 3',
                'tanggal' => '2025-09-01',
                'deadline_days' => 18,
                'special' => false,
                'status' => 'sudah_dikirim',
                'progress_offset' => 12,
                'with_dp' => true,
                'dp_pct' => 0.5,
                'with_pelunasan' => true,
                'items' => 2,
                'quantities' => [30, 30],
                'varian_labels' => ['Putra', 'Putri'],
                'catatan' => 'Seragam olahraga tahunan.',
            ],
            // ---- 2026 Orders (current year, various statuses) ----
            [
                'nama_po' => 'Jersey Klub Badminton Garuda',
                'tanggal' => '2026-01-20',
                'deadline_days' => 14,
                'special' => false,
                'status' => 'sudah_dikirim',
                'progress_offset' => 12,
                'with_dp' => true,
                'dp_pct' => 0.5,
                'with_pelunasan' => true,
                'items' => 2,
                'quantities' => [12, 12],
                'varian_labels' => ['Pemain', 'Cadangan'],
                'catatan' => null,
            ],
            [
                'nama_po' => 'Kaos Event Marathon Kota',
                'tanggal' => '2026-03-05',
                'deadline_days' => 10,
                'special' => true,
                'status' => 'siap_dikirim',
                'progress_offset' => 11,
                'with_dp' => true,
                'dp_pct' => 0.5,
                'with_pelunasan' => true,
                'items' => 1,
                'quantities' => [50],
                'varian_labels' => ['Peserta'],
                'catatan' => 'Event marathon kota, pengiriman cepat.',
            ],
            [
                'nama_po' => 'Hoodie Tim Esport Phoenix',
                'tanggal' => '2026-04-12',
                'deadline_days' => 14,
                'special' => false,
                'status' => 'on_progress',
                'progress_offset' => 6,
                'with_dp' => true,
                'dp_pct' => 0.3,
                'with_pelunasan' => false,
                'items' => 2,
                'quantities' => [10, 5],
                'varian_labels' => ['Player', 'Staff'],
                'catatan' => 'Hoodie custom dengan bordir logo esport.',
            ],
            [
                'nama_po' => 'Jersey Sepak Bola Kantor PT Sentosa',
                'tanggal' => '2026-05-01',
                'deadline_days' => 21,
                'special' => false,
                'status' => 'on_progress',
                'progress_offset' => 3,
                'with_dp' => true,
                'dp_pct' => 0.5,
                'with_pelunasan' => false,
                'items' => 3,
                'quantities' => [15, 15, 5],
                'varian_labels' => ['Pemain', 'Cadangan', 'Kiper'],
                'catatan' => 'Pertandingan antar perusahaan.',
            ],
            [
                'nama_po' => 'Polo Shirt Dinas Pariwisata',
                'tanggal' => '2026-05-15',
                'deadline_days' => 14,
                'special' => false,
                'status' => 'published',
                'progress_offset' => 0,
                'with_dp' => true,
                'dp_pct' => 0.3,
                'with_pelunasan' => false,
                'items' => 1,
                'quantities' => [25],
                'varian_labels' => ['Pegawai'],
                'catatan' => 'Polo shirt seragam dinas.',
            ],
            [
                'nama_po' => 'Jersey Basket Sekolah Al-Azhar',
                'tanggal' => '2026-05-25',
                'deadline_days' => 18,
                'special' => false,
                'status' => 'draft',
                'progress_offset' => -1,
                'with_dp' => true,
                'dp_pct' => 0.5,
                'with_pelunasan' => false,
                'items' => 2,
                'quantities' => [12, 12],
                'varian_labels' => ['Putra', 'Putri'],
                'catatan' => 'Draft, menunggu konfirmasi desain.',
            ],
            [
                'nama_po' => 'Celana Training Klub Voli',
                'tanggal' => '2026-06-01',
                'deadline_days' => 12,
                'special' => false,
                'status' => 'draft',
                'progress_offset' => -1,
                'with_dp' => false,
                'dp_pct' => 0,
                'with_pelunasan' => false,
                'items' => 1,
                'quantities' => [18],
                'varian_labels' => ['Anggota'],
                'catatan' => 'Baru masuk, belum ada pembayaran.',
            ],
            // ---- Varied Japanese & Arabic scenarios ----
            [
                'nama_po' => 'Jersey Tim Sakura Tokyo テスト',
                'tanggal' => '2026-06-05',
                'deadline_days' => 14,
                'special' => false,
                'status' => 'on_progress',
                'progress_offset' => 5,
                'with_dp' => true,
                'dp_pct' => 0.5,
                'with_pelunasan' => false,
                'items' => 1,
                'quantities' => [10],
                'varian_labels' => ['Anime Division'],
                'catatan' => 'Test order dengan karakter Jepang (Hiragana/Katakana/Kanji) untuk layout multi-font.',
                'lang' => 'ja',
            ],
            [
                'nama_po' => 'Jersey Klub Arab Al-Ittihad عربي',
                'tanggal' => '2026-06-10',
                'deadline_days' => 12,
                'special' => false,
                'status' => 'published',
                'progress_offset' => 2,
                'with_dp' => true,
                'dp_pct' => 0.5,
                'with_pelunasan' => false,
                'items' => 1,
                'quantities' => [10],
                'varian_labels' => ['Timur Tengah'],
                'catatan' => 'Test order dengan tulisan Arab/RTL.',
                'lang' => 'ar',
            ],
            [
                'nama_po' => 'Jersey Mixed Internasional 国際 دولい',
                'tanggal' => '2026-06-15',
                'deadline_days' => 15,
                'special' => true,
                'status' => 'siap_dikirim',
                'progress_offset' => 11,
                'with_dp' => true,
                'dp_pct' => 0.5,
                'with_pelunasan' => true,
                'items' => 1,
                'quantities' => [12],
                'varian_labels' => ['Global Esports'],
                'catatan' => 'Campuran karakter Latin, Jepang, dan Arab.',
                'lang' => 'mixed',
            ],
        ];
    }

    private function japaneseNamesets(): array
    {
        return [
            ['nama_punggung' => '田中 健一', 'nama_dada' => 'タナカ', 'nama_lengan' => 'たなか'],
            ['nama_punggung' => '鈴木 二郎', 'nama_dada' => 'スズキ', 'nama_lengan' => 'すずき'],
            ['nama_punggung' => '佐藤 三花', 'nama_dada' => 'サトウ', 'nama_lengan' => 'さとう'],
            ['nama_punggung' => '山本 四季', 'nama_dada' => 'ヤマモト', 'nama_lengan' => 'やまもと'],
            ['nama_punggung' => '松本 五郎', 'nama_dada' => 'マツモト', 'nama_lengan' => 'まつもと'],
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
            ['nama_punggung' => 'خالد بن عمر', 'nama_dada' => 'خالد', 'nama_lengan' => 'عمر'],
            ['nama_punggung' => 'ناصر الدوسري', 'nama_dada' => 'ناصر', 'nama_lengan' => 'دوسري'],
            ['nama_punggung' => 'راشد الغامدي', 'nama_dada' => 'راشد', 'nama_lengan' => 'غامدي'],
            ['nama_punggung' => 'عمر الشهري', 'nama_dada' => 'عمر', 'nama_lengan' => 'شهري'],
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
