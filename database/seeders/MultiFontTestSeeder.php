<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Master\BahanKain;
use App\Models\Master\Customer;
use App\Models\Master\Logo;
use App\Models\Master\Printing;
use App\Models\Master\Product;
use App\Models\Master\Resleting;
use App\Models\Master\Size;
use App\Models\Order\Invoice;
use App\Models\Order\InvoiceItem;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Order\OrderNameset;
use App\Models\User;
use App\Services\NumberGenerator;
use Illuminate\Database\Seeder;

/**
 * MultiFontTestSeeder
 *
 * Membuat order dengan nameset berisi karakter multi-bahasa untuk menguji fitur
 * multi-font di PDF (fo.blade.php, fo_draft.blade.php, invoice.blade.php)
 * melalui PdfHelper::formatText():
 *   - Karakter CJK (Jepang/Kanji/Hiragana/Katakana) → <span class="cjk-font">
 *   - Karakter Arab                                  → <span class="arabic-font" dir="rtl">
 *
 * Jalankan: php artisan db:seed --class=MultiFontTestSeeder
 */
class MultiFontTestSeeder extends Seeder
{
    public function run(): void
    {
        $brand = Brand::where('kode', 'ALG')->first();
        if (! $brand) {
            $this->command->warn('Brand ALG tidak ditemukan. Jalankan BrandSeeder dulu.');
            return;
        }

        $superadmin = User::where('email', 'superadmin@nisreport.local')->first();
        $creator    = $superadmin;

        // Ambil customer pertama milik brand ALG, atau buat satu jika belum ada
        $customer = Customer::where('brand_id', $brand->id)->first();
        if (! $customer) {
            $customer = Customer::create([
                'brand_id'  => $brand->id,
                'nama'      => 'Test Multi-Font Customer',
                'kode'      => 'MFT-001',
                'nomor_hp'  => '081234567890',
                'is_active' => true,
            ]);
        }

        $numbers  = app(NumberGenerator::class);
        $sizes    = Size::orderBy('urutan')->get();
        $products = Product::where(function ($q) use ($brand) {
            $q->where('brand_id', $brand->id)->orWhereNull('brand_id');
        })->first();

        $masterBrandId = \App\Support\BrandContext::masterDataId(request(), $brand->id);
        $banks = \App\Models\Master\BankAccount::where('brand_id', $masterBrandId)->get();
        $bank  = $banks->first();

        if (! $products) {
            $this->command->warn('Produk tidak ditemukan. Jalankan MasterDataSeeder dulu.');
            return;
        }

        $bahan = BahanKain::active()->first();
        $logo  = Logo::active()->first();
        $print = Printing::active()->first();
        $reslt = Resleting::active()->first();

        // ----------------------------------------------------------------
        // Order 1 — Nameset berisi karakter JEPANG (tim anime/gaming)
        // ----------------------------------------------------------------
        $order1 = Order::create([
            'brand_id'          => $brand->id,
            'no_po'             => $numbers->generateOrderNumber($brand, 'Jersey Tim Jepang テスト'),
            'nama_po'           => 'Jersey Tim Jepang テスト',
            'status_po'         => 'published',
            'is_special_order'  => false,
            'tanggal_masuk'     => now()->subDays(5)->toDateString(),
            'deadline_customer' => now()->addDays(10)->toDateString(),
            'pelanggan_id'      => $customer->id,
            'catatan'           => 'Test multi-font Jepang — nama punggung berisi karakter Hiragana/Katakana/Kanji.',
            'created_by'        => $creator->id,
            'published_at'      => now()->subDays(4),
            'published_by'      => $creator->id,
        ]);

        $item1 = OrderItem::create([
            'order_id'     => $order1->id,
            'product_id'   => $products->id,
            'nama_produk'  => $products->nama,
            'varian_label' => 'Tim Anime',
            'quantity'     => count($this->japaneseNamesets()),
            'harga_satuan' => $products->harga,
            'subtotal'     => count($this->japaneseNamesets()) * $products->harga,
            'bahan_kain_id'=> $bahan?->id,
            'jenis_setelan'=> 'atasan_saja',
            'pola'         => 'Reguler',
            'logo_id'      => $logo?->id,
            'printing_id'  => $print?->id,
            'resleting_id' => $reslt?->id,
            'warna'        => 'Hitam-Merah',
            'jenis_kerah'  => 'V-Neck',
            'jml_atasan'   => (string) count($this->japaneseNamesets()),
        ]);

        $order1->update(['total_tagihan' => $item1->subtotal]);

        foreach ($this->japaneseNamesets() as $idx => $ns) {
            $size = $sizes->get($idx % $sizes->count());
            OrderNameset::create([
                'order_item_id'  => $item1->id,
                'nama_punggung'  => $ns['nama_punggung'],
                'nomor_punggung' => $ns['nomor_punggung'],
                'nama_dada'      => $ns['nama_dada'] ?? null,
                'nomor_dada'     => $ns['nomor_dada'] ?? null,
                'nama_lengan'    => $ns['nama_lengan'] ?? null,
                'size_id'        => $size?->id,
                'size_label'     => $size?->ukuran,
                'urutan'         => $idx + 1,
                'keterangan'     => $ns['keterangan'] ?? null,
            ]);
        }

        // Invoice untuk order 1
        $inv1 = Invoice::create([
            'brand_id'        => $brand->id,
            'order_id'        => $order1->id,
            'invoice_number'  => $numbers->generateInvoiceNumber($brand, $order1),
            'tanggal_terbit'  => now()->subDays(5)->toDateString(),
            'jatuh_tempo'     => now()->addDays(9)->toDateString(),
            'status'          => 'published',
            'total_tagihan'   => $item1->subtotal,
            'total_bayar'     => 0,
            'dp_amount'       => 0,
            'sisa_pembayaran' => $item1->subtotal,
            'created_by'      => $creator->id,
        ]);

        InvoiceItem::create([
            'invoice_id'   => $inv1->id,
            'produk'       => $item1->nama_produk . ' (Tim Anime)',
            'jumlah'       => $item1->quantity,
            'harga_satuan' => $item1->harga_satuan,
            'subtotal'     => $item1->subtotal,
        ]);

        // ----------------------------------------------------------------
        // Order 2 — Nameset berisi karakter ARAB (tim Timur Tengah)
        // ----------------------------------------------------------------
        $order2 = Order::create([
            'brand_id'          => $brand->id,
            'no_po'             => $numbers->generateOrderNumber($brand, 'Jersey Tim Arab عربي'),
            'nama_po'           => 'Jersey Tim Arab عربي',
            'status_po'         => 'published',
            'is_special_order'  => false,
            'tanggal_masuk'     => now()->subDays(3)->toDateString(),
            'deadline_customer' => now()->addDays(12)->toDateString(),
            'pelanggan_id'      => $customer->id,
            'catatan'           => 'Test multi-font Arab — nama punggung berisi karakter Arabic Unicode.',
            'created_by'        => $creator->id,
            'published_at'      => now()->subDays(2),
            'published_by'      => $creator->id,
        ]);

        $item2 = OrderItem::create([
            'order_id'     => $order2->id,
            'product_id'   => $products->id,
            'nama_produk'  => $products->nama,
            'varian_label' => 'Tim Arab',
            'quantity'     => count($this->arabicNamesets()),
            'harga_satuan' => $products->harga,
            'subtotal'     => count($this->arabicNamesets()) * $products->harga,
            'bahan_kain_id'=> $bahan?->id,
            'jenis_setelan'=> 'atasan_saja',
            'pola'         => 'Reguler',
            'logo_id'      => $logo?->id,
            'printing_id'  => $print?->id,
            'resleting_id' => $reslt?->id,
            'warna'        => 'Putih-Hijau',
            'jenis_kerah'  => 'O-Neck',
            'jml_atasan'   => (string) count($this->arabicNamesets()),
        ]);

        $order2->update(['total_tagihan' => $item2->subtotal]);

        foreach ($this->arabicNamesets() as $idx => $ns) {
            $size = $sizes->get($idx % $sizes->count());
            OrderNameset::create([
                'order_item_id'  => $item2->id,
                'nama_punggung'  => $ns['nama_punggung'],
                'nomor_punggung' => $ns['nomor_punggung'],
                'nama_dada'      => $ns['nama_dada'] ?? null,
                'nomor_dada'     => $ns['nomor_dada'] ?? null,
                'nama_lengan'    => $ns['nama_lengan'] ?? null,
                'size_id'        => $size?->id,
                'size_label'     => $size?->ukuran,
                'urutan'         => $idx + 1,
                'keterangan'     => $ns['keterangan'] ?? null,
            ]);
        }

        // Invoice untuk order 2
        $inv2 = Invoice::create([
            'brand_id'        => $brand->id,
            'order_id'        => $order2->id,
            'invoice_number'  => $numbers->generateInvoiceNumber($brand, $order2),
            'tanggal_terbit'  => now()->subDays(3)->toDateString(),
            'jatuh_tempo'     => now()->addDays(11)->toDateString(),
            'status'          => 'published',
            'total_tagihan'   => $item2->subtotal,
            'total_bayar'     => 0,
            'dp_amount'       => 0,
            'sisa_pembayaran' => $item2->subtotal,
            'created_by'      => $creator->id,
        ]);

        InvoiceItem::create([
            'invoice_id'   => $inv2->id,
            'produk'       => $item2->nama_produk . ' (Tim Arab)',
            'jumlah'       => $item2->quantity,
            'harga_satuan' => $item2->harga_satuan,
            'subtotal'     => $item2->subtotal,
        ]);

        // ----------------------------------------------------------------
        // Order 3 — Nameset MIX: Latin + Jepang + Arab dalam satu PO
        // ----------------------------------------------------------------
        $order3 = Order::create([
            'brand_id'          => $brand->id,
            'no_po'             => $numbers->generateOrderNumber($brand, 'Jersey Mixed International'),
            'nama_po'           => 'Jersey Mixed International 国際 دولي',
            'status_po'         => 'published',
            'is_special_order'  => true,
            'tanggal_masuk'     => now()->subDays(1)->toDateString(),
            'deadline_customer' => now()->addDays(14)->toDateString(),
            'pelanggan_id'      => $customer->id,
            'catatan'           => 'Order campur Latin + Jepang + Arab dalam satu PO. Test rendering multi-font sekaligus.',
            'created_by'        => $creator->id,
            'published_at'      => now()->subDay(),
            'published_by'      => $creator->id,
        ]);

        $item3 = OrderItem::create([
            'order_id'     => $order3->id,
            'product_id'   => $products->id,
            'nama_produk'  => $products->nama,
            'varian_label' => 'International Mixed',
            'quantity'     => count($this->mixedNamesets()),
            'harga_satuan' => $products->harga,
            'subtotal'     => count($this->mixedNamesets()) * $products->harga,
            'bahan_kain_id'=> $bahan?->id,
            'jenis_setelan'=> 'stell',
            'pola'         => 'Slim Fit',
            'logo_id'      => $logo?->id,
            'printing_id'  => $print?->id,
            'resleting_id' => $reslt?->id,
            'warna'        => 'Navy-Gold',
            'jenis_kerah'  => 'Polo',
            'jml_atasan'   => (string) count($this->mixedNamesets()),
            'jml_bawahan'  => (string) count($this->mixedNamesets()),
        ]);

        $order3->update(['total_tagihan' => $item3->subtotal]);

        foreach ($this->mixedNamesets() as $idx => $ns) {
            $size       = $sizes->get($idx % $sizes->count());
            $sizeCelana = $sizes->get(($idx + 1) % $sizes->count());
            OrderNameset::create([
                'order_item_id'     => $item3->id,
                'nama_punggung'     => $ns['nama_punggung'],
                'nomor_punggung'    => $ns['nomor_punggung'],
                'nama_dada'         => $ns['nama_dada'] ?? null,
                'nomor_dada'        => $ns['nomor_dada'] ?? null,
                'nama_lengan'       => $ns['nama_lengan'] ?? null,
                'nama_punggung_2'   => $ns['nama_punggung_2'] ?? null,
                'nomor_punggung_2'  => $ns['nomor_punggung_2'] ?? null,
                'size_id'           => $size?->id,
                'size_label'        => $size?->ukuran,
                'size_celana_id'    => $sizeCelana?->id,
                'size_celana_label' => $sizeCelana?->ukuran,
                'urutan'            => $idx + 1,
                'keterangan'        => $ns['keterangan'] ?? null,
            ]);
        }

        // Invoice untuk order 3
        $inv3 = Invoice::create([
            'brand_id'        => $brand->id,
            'order_id'        => $order3->id,
            'invoice_number'  => $numbers->generateInvoiceNumber($brand, $order3),
            'tanggal_terbit'  => now()->subDay()->toDateString(),
            'jatuh_tempo'     => now()->addDays(13)->toDateString(),
            'status'          => 'published',
            'total_tagihan'   => $item3->subtotal,
            'total_bayar'     => 0,
            'dp_amount'       => 0,
            'sisa_pembayaran' => $item3->subtotal,
            'created_by'      => $creator->id,
        ]);

        InvoiceItem::create([
            'invoice_id'   => $inv3->id,
            'produk'       => $item3->nama_produk . ' (International Mixed)',
            'jumlah'       => $item3->quantity,
            'harga_satuan' => $item3->harga_satuan,
            'subtotal'     => $item3->subtotal,
        ]);

        $this->command->info('✅ MultiFontTestSeeder berhasil!');
        $this->command->table(
            ['Order', 'PO', 'Qty', 'Karakter'],
            [
                [$order1->no_po, $order1->nama_po, $item1->quantity, 'Jepang (CJK)'],
                [$order2->no_po, $order2->nama_po, $item2->quantity, 'Arab (RTL)'],
                [$order3->no_po, $order3->nama_po, $item3->quantity, 'Mixed (Latin + CJK + Arab)'],
            ]
        );
    }

    // ----------------------------------------------------------------
    // DATA: Nameset tim Jepang — Hiragana, Katakana, Kanji
    // ----------------------------------------------------------------
    private function japaneseNamesets(): array
    {
        return [
            // [nama_punggung, nomor, nama_dada, nomor_dada, nama_lengan, keterangan]
            ['nama_punggung' => '田中 健一',    'nomor_punggung' => '10', 'nama_dada' => 'タナカ',   'nomor_dada' => '10', 'nama_lengan' => 'たなか', 'keterangan' => 'Kapten — Kanji + Katakana + Hiragana'],
            ['nama_punggung' => '鈴木 二郎',    'nomor_punggung' => '7',  'nama_dada' => 'スズキ',   'nomor_dada' => '7',  'nama_lengan' => null,     'keterangan' => null],
            ['nama_punggung' => '佐藤 三花',    'nomor_punggung' => '11', 'nama_dada' => 'サトウ',   'nomor_dada' => '11', 'nama_lengan' => null,     'keterangan' => null],
            ['nama_punggung' => '山本 四季',    'nomor_punggung' => '9',  'nama_dada' => 'ヤマモト', 'nomor_dada' => '9',  'nama_lengan' => null,     'keterangan' => null],
            ['nama_punggung' => '松本 五郎',    'nomor_punggung' => '5',  'nama_dada' => 'マツモト', 'nomor_dada' => '5',  'nama_lengan' => null,     'keterangan' => null],
            ['nama_punggung' => 'けんじ',       'nomor_punggung' => '3',  'nama_dada' => null,       'nomor_dada' => null, 'nama_lengan' => null,     'keterangan' => 'Hiragana only'],
            ['nama_punggung' => 'サクラ',       'nomor_punggung' => '8',  'nama_dada' => 'サクラ',   'nomor_dada' => '8',  'nama_lengan' => null,     'keterangan' => 'Katakana only'],
            ['nama_punggung' => '龍 神風',      'nomor_punggung' => '1',  'nama_dada' => '龍',       'nomor_dada' => '1',  'nama_lengan' => '龍神',   'keterangan' => 'Kanji complex'],
            ['nama_punggung' => 'アカギ リョウ','nomor_punggung' => '6',  'nama_dada' => 'アカギ',   'nomor_dada' => '6',  'nama_lengan' => null,     'keterangan' => null],
            ['nama_punggung' => '光 速 丸',     'nomor_punggung' => '2',  'nama_dada' => '光速',     'nomor_dada' => '2',  'nama_lengan' => null,     'keterangan' => 'Mixed Kanji spacing'],
        ];
    }

    // ----------------------------------------------------------------
    // DATA: Nameset tim Arab — berbagai nama Arab dan Unicode
    // ----------------------------------------------------------------
    private function arabicNamesets(): array
    {
        return [
            ['nama_punggung' => 'محمد علي',      'nomor_punggung' => '10', 'nama_dada' => 'محمد',    'nomor_dada' => '10', 'nama_lengan' => 'علي',   'keterangan' => 'Kapten — Arab lengkap'],
            ['nama_punggung' => 'أحمد الكريم',   'nomor_punggung' => '7',  'nama_dada' => 'أحمد',    'nomor_dada' => '7',  'nama_lengan' => null,    'keterangan' => null],
            ['nama_punggung' => 'عبد الرحمن',    'nomor_punggung' => '9',  'nama_dada' => 'عبدالرحمن','nomor_dada' => '9', 'nama_lengan' => null,    'keterangan' => null],
            ['nama_punggung' => 'فيصل الأمين',   'nomor_punggung' => '5',  'nama_dada' => 'فيصل',    'nomor_dada' => '5',  'nama_lengan' => null,    'keterangan' => null],
            ['nama_punggung' => 'سعد المطيري',   'nomor_punggung' => '11', 'nama_dada' => 'سعد',     'nomor_dada' => '11', 'nama_lengan' => null,    'keterangan' => null],
            ['nama_punggung' => 'خالد بن عمر',   'nomor_punggung' => '4',  'nama_dada' => 'خالد',    'nomor_dada' => '4',  'nama_lengan' => 'عمر',   'keterangan' => 'Arab dengan bin'],
            ['nama_punggung' => 'ناصر الدوسري',  'nomor_punggung' => '6',  'nama_dada' => 'ناصر',    'nomor_dada' => '6',  'nama_lengan' => null,    'keterangan' => null],
            ['nama_punggung' => 'راشد الغامدي',  'nomor_punggung' => '2',  'nama_dada' => 'راشد',    'nomor_dada' => '2',  'nama_lengan' => null,    'keterangan' => null],
            ['nama_punggung' => 'عمر الشهري',    'nomor_punggung' => '8',  'nama_dada' => 'عمر',     'nomor_dada' => '8',  'nama_lengan' => null,    'keterangan' => null],
            ['nama_punggung' => 'يوسف النجار',   'nomor_punggung' => '1',  'nama_dada' => 'يوسف',    'nomor_dada' => '1',  'nama_lengan' => null,    'keterangan' => 'Kiper utama'],
        ];
    }

    // ----------------------------------------------------------------
    // DATA: Nameset campuran Latin + Jepang + Arab (satu PO internasional)
    // ----------------------------------------------------------------
    private function mixedNamesets(): array
    {
        return [
            // Latin biasa
            ['nama_punggung' => 'SUPARMAN',     'nomor_punggung' => '1',  'nama_dada' => 'SUPARMAN', 'nomor_dada' => '1',  'nama_lengan' => null,     'nama_punggung_2' => null,  'nomor_punggung_2' => null, 'keterangan' => 'Latin — standar'],
            ['nama_punggung' => 'BUDI SANTOSO', 'nomor_punggung' => '2',  'nama_dada' => 'BUDI',     'nomor_dada' => '2',  'nama_lengan' => null,     'nama_punggung_2' => null,  'nomor_punggung_2' => null, 'keterangan' => null],
            // CJK Jepang
            ['nama_punggung' => '田中 健二',    'nomor_punggung' => '3',  'nama_dada' => 'タナカ',   'nomor_dada' => '3',  'nama_lengan' => 'たなか', 'nama_punggung_2' => '健二', 'nomor_punggung_2' => '3',  'keterangan' => 'Kanji + Katakana + Hiragana'],
            ['nama_punggung' => 'アオイ',       'nomor_punggung' => '4',  'nama_dada' => null,       'nomor_dada' => null, 'nama_lengan' => null,     'nama_punggung_2' => null,  'nomor_punggung_2' => null, 'keterangan' => 'Katakana only'],
            // Arab
            ['nama_punggung' => 'محمد علي',     'nomor_punggung' => '5',  'nama_dada' => 'محمد',    'nomor_dada' => '5',  'nama_lengan' => 'علي',    'nama_punggung_2' => null,  'nomor_punggung_2' => null, 'keterangan' => 'Arabic RTL'],
            ['nama_punggung' => 'أحمد سعيد',    'nomor_punggung' => '6',  'nama_dada' => 'أحمد',    'nomor_dada' => '6',  'nama_lengan' => null,     'nama_punggung_2' => null,  'nomor_punggung_2' => null, 'keterangan' => null],
            // Latin lanjut
            ['nama_punggung' => 'WAHYU P.',     'nomor_punggung' => '7',  'nama_dada' => 'WAHYU',    'nomor_dada' => '7',  'nama_lengan' => null,     'nama_punggung_2' => null,  'nomor_punggung_2' => null, 'keterangan' => null],
            // CJK Kanji complex
            ['nama_punggung' => '龍 神',        'nomor_punggung' => '8',  'nama_dada' => '龍神',     'nomor_dada' => '8',  'nama_lengan' => null,     'nama_punggung_2' => '龍',  'nomor_punggung_2' => '8',  'keterangan' => 'Kanji 2 char'],
            // Arab + nomor latin
            ['nama_punggung' => 'عبد الله',     'nomor_punggung' => '9',  'nama_dada' => 'عبدالله',  'nomor_dada' => '9',  'nama_lengan' => null,     'nama_punggung_2' => null,  'nomor_punggung_2' => null, 'keterangan' => 'Arabic nama panjang'],
            // Latin terakhir
            ['nama_punggung' => 'REZA F.',      'nomor_punggung' => '10', 'nama_dada' => 'REZA',     'nomor_dada' => '10', 'nama_lengan' => null,     'nama_punggung_2' => null,  'nomor_punggung_2' => null, 'keterangan' => null],
            // Mixed dalam satu field
            ['nama_punggung' => 'HIRO 浩',      'nomor_punggung' => '11', 'nama_dada' => 'HIRO',     'nomor_dada' => '11', 'nama_lengan' => '浩',     'nama_punggung_2' => null,  'nomor_punggung_2' => null, 'keterangan' => 'Latin + Kanji dalam satu field'],
            ['nama_punggung' => 'ALI عَلِي',    'nomor_punggung' => '12', 'nama_dada' => 'ALI',      'nomor_dada' => '12', 'nama_lengan' => 'عَلِي',  'nama_punggung_2' => null,  'nomor_punggung_2' => null, 'keterangan' => 'Latin + Arabic dalam satu field'],
        ];
    }
}
