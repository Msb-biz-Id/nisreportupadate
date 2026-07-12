<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Master\BahanKain;
use App\Models\Master\Customer;
use App\Models\Master\CustomerType;
use App\Models\Master\KategoriOrder;
use App\Models\Master\Logo;
use App\Models\Master\PolaJahitan;
use App\Models\Master\Printing;
use App\Models\Master\Product;
use App\Models\Master\Progress;
use App\Models\Master\Resleting;
use App\Models\Master\Size;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Order\OrderNameset;
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
        OrderNameset::truncate();
        OrderItem::truncate();
        Order::truncate();
        Schema::enableForeignKeyConstraints();

        $this->seedDemoScenarioOrders();
    }

    private function seedDemoScenarioOrders(): void
    {
        $numbers = app(NumberGenerator::class);
        $superadmin = User::where('email', 'superadmin@nisreport.local')->first() ?? User::first();
        
        $brands = Brand::all();
        if ($brands->isEmpty()) {
            return;
        }

        $idwBrand = Brand::where('kode', 'IDW')->first() ?? $brands->first();
        $algBrand = Brand::where('kode', 'ALG')->first() ?? $brands->first();
        $crlBrand = Brand::where('kode', 'CRL')->first() ?? $brands->first();
        $drvBrand = Brand::where('kode', 'DRV')->first() ?? $brands->first();

        // Fetch master data
        $kategori = KategoriOrder::first();
        $bahan = BahanKain::active()->first();
        $logo = Logo::active()->first();
        $printing = Printing::active()->first();
        $resleting = Resleting::active()->first();
        $pola = PolaJahitan::first();
        $sizes = Size::orderBy('urutan')->limit(5)->get();

        // Customer helper
        $getCustomer = function ($brand) use ($superadmin) {
            return Customer::where('brand_id', $brand->id)->first() ?? Customer::create([
                'brand_id' => $brand->id,
                'kode' => $brand->kode . '-CUST-DEMO',
                'nama' => 'Klub ' . $brand->nama_brand . ' Demo',
                'nomor_hp' => '081234567890',
                'provinsi_code' => '32',
                'provinsi_nama' => 'JAWA BARAT',
                'kabupaten_code' => '3273',
                'kabupaten_nama' => 'KOTA BANDUNG',
                'is_active' => true,
            ]);
        };

        // SCENARIO 1: PO Free Ongkir Tanpa Jersey Free
        $this->createScenarioOrder([
            'brand' => $algBrand,
            'nama_po' => 'PO Free Ongkir Tanokir',
            'is_free_ongkir' => true,
            'ongkir' => 0.0,
            'customer' => $getCustomer($algBrand),
            'creator' => $superadmin,
            'numbers' => $numbers,
            'kategori' => $kategori,
            'items' => [
                [
                    'nama_produk' => 'Jersey Premium',
                    'quantity' => 5,
                    'harga_satuan' => 120000.0,
                    'is_free' => false,
                    'specs' => [
                        'bahan_kain_id' => $bahan?->id,
                        'warna' => 'Hitam Emas',
                        'jenis_rib' => 'Rib Premium',
                        'jenis_kerah' => 'V-Neck',
                    ]
                ]
            ],
            'sizes' => $sizes
        ]);

        // SCENARIO 2: PO Ongkir Berbayar Tanpa Jersey Free
        $this->createScenarioOrder([
            'brand' => $drvBrand,
            'nama_po' => 'PO Ongkir Berbayar Tanokir',
            'is_free_ongkir' => false,
            'ongkir' => 25000.0,
            'customer' => $getCustomer($drvBrand),
            'creator' => $superadmin,
            'numbers' => $numbers,
            'kategori' => $kategori,
            'items' => [
                [
                    'nama_produk' => 'Jersey Slimfit',
                    'quantity' => 8,
                    'harga_satuan' => 90000.0,
                    'is_free' => false,
                    'specs' => [
                        'bahan_kain_id' => $bahan?->id,
                        'warna' => 'Merah Putih',
                        'jenis_rib' => 'Rib Biasa',
                        'jenis_kerah' => 'O-Neck',
                    ]
                ]
            ],
            'sizes' => $sizes
        ]);

        // SCENARIO 3: PO Free Ongkir Dengan Jersey Free (Identical Specs)
        $specs3 = [
            'bahan_kain_id' => $bahan?->id,
            'warna' => 'Navy Blue',
            'jenis_rib' => 'Rib Tebal',
            'jenis_kerah' => 'Polo',
            'gambar_desain' => 'demo_desain.png',
        ];
        $this->createScenarioOrder([
            'brand' => $idwBrand,
            'nama_po' => 'PO Free Ongkir Dengan Jersey Free',
            'is_free_ongkir' => true,
            'ongkir' => 0.0,
            'customer' => $getCustomer($idwBrand),
            'creator' => $superadmin,
            'numbers' => $numbers,
            'kategori' => $kategori,
            'items' => [
                [
                    'nama_produk' => 'Jersey Futsal',
                    'quantity' => 10,
                    'harga_satuan' => 80000.0,
                    'is_free' => false,
                    'specs' => $specs3
                ],
                [
                    'nama_produk' => 'Jersey Futsal',
                    'quantity' => 1,
                    'harga_satuan' => 0.0,
                    'is_free' => true,
                    'specs' => $specs3
                ]
            ],
            'sizes' => $sizes
        ]);

        // SCENARIO 4: PO Ongkir Berbayar Dengan Jersey Free (Identical Specs)
        $specs4 = [
            'bahan_kain_id' => $bahan?->id,
            'warna' => 'Putih Gold',
            'jenis_rib' => 'Rib Tipis',
            'jenis_kerah' => 'V-Neck Custom',
            'gambar_desain' => 'demo_esport.png',
        ];
        $this->createScenarioOrder([
            'brand' => $crlBrand,
            'nama_po' => 'PO Ongkir Berbayar Dengan Jersey Free',
            'is_free_ongkir' => false,
            'ongkir' => 35000.0,
            'customer' => $getCustomer($crlBrand),
            'creator' => $superadmin,
            'numbers' => $numbers,
            'kategori' => $kategori,
            'items' => [
                [
                    'nama_produk' => 'Jersey Esport',
                    'quantity' => 12,
                    'harga_satuan' => 85000.0,
                    'is_free' => false,
                    'specs' => $specs4
                ],
                [
                    'nama_produk' => 'Jersey Esport',
                    'quantity' => 1,
                    'harga_satuan' => 0.0,
                    'is_free' => true,
                    'specs' => $specs4
                ]
            ],
            'sizes' => $sizes
        ]);
    }

    private function createScenarioOrder(array $params): void
    {
        $brand = $params['brand'];
        $namaPo = $params['nama_po'];
        $isFreeOngkir = $params['is_free_ongkir'];
        $ongkir = $params['ongkir'];
        $customer = $params['customer'];
        $creator = $params['creator'];
        $numbers = $params['numbers'];
        $kategori = $params['kategori'];
        $itemsData = $params['items'];
        $sizes = $params['sizes'];

        $tanggalMasuk = Carbon::now()->subDays(2);
        $deadline = Carbon::now()->addDays(14);

        $order = Order::create([
            'brand_id' => $brand->id,
            'no_po' => $numbers->generateOrderNumber($brand, $namaPo, $tanggalMasuk),
            'nama_po' => $namaPo,
            'status_po' => 'draft',
            'is_special_order' => false,
            'is_free_ongkir' => $isFreeOngkir,
            'ongkir' => $ongkir,
            'tanggal_masuk' => $tanggalMasuk->toDateString(),
            'deadline_customer' => $deadline->toDateString(),
            'kategori_order_id' => $kategori?->id,
            'pelanggan_id' => $customer->id,
            'created_by' => $creator->id,
        ]);

        $totalTagihan = 0.0;
        $allCreatedItems = [];

        foreach ($itemsData as $idx => $itemData) {
            $subtotal = $itemData['quantity'] * $itemData['harga_satuan'];
            $totalTagihan += $subtotal;

            $specs = $itemData['specs'];

            $item = OrderItem::create([
                'order_id' => $order->id,
                'nama_produk' => $itemData['nama_produk'],
                'varian_label' => $itemData['is_free'] ? 'Bonus Jersey' : 'Jersey Utama',
                'quantity' => $itemData['quantity'],
                'harga_satuan' => $itemData['harga_satuan'],
                'subtotal' => $subtotal,
                'is_addon' => false,
                'bahan_kain_id' => $specs['bahan_kain_id'] ?? null,
                'warna' => $specs['warna'] ?? '',
                'jenis_rib' => $specs['jenis_rib'] ?? '',
                'jenis_kerah' => $specs['jenis_kerah'] ?? '',
                'gambar_desain' => $specs['gambar_desain'] ?? null,
            ]);

            // Seed Namesets
            for ($k = 0; $k < $itemData['quantity']; $k++) {
                $size = $sizes[$k % $sizes->count()];
                OrderNameset::create([
                    'order_item_id' => $item->id,
                    'nama_punggung' => ($itemData['is_free'] ? 'BONUS PLAYER ' : 'PLAYER ') . ($k + 1),
                    'nomor_punggung' => (string)($k + 10),
                    'size_id' => $size->id,
                    'size_label' => $size->ukuran,
                    'keterangan' => $itemData['is_free'] ? 'Jersey Gratis' : 'Jersey Regular',
                ]);
            }

            $allCreatedItems[] = $item;
        }

        // Recalculate order total
        $order->update(['total_tagihan' => $order->totalTagihan()]);

        // Create Invoice
        $invoice = Invoice::create([
            'brand_id' => $order->brand_id,
            'order_id' => $order->id,
            'invoice_number' => $numbers->generateInvoiceNumber($brand, $order),
            'tanggal_terbit' => $order->tanggal_masuk,
            'jatuh_tempo' => $order->deadline_customer,
            'status' => 'draft',
            'biaya_pengiriman' => $order->is_free_ongkir ? 0.0 : (float)$order->ongkir,
            'total_tagihan' => $order->total_tagihan,
            'sisa_pembayaran' => $order->total_tagihan,
            'created_by' => $creator->id,
        ]);

        foreach ($allCreatedItems as $item) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'produk' => $item->nama_produk . ($item->varian_label ? " ({$item->varian_label})" : '') . ((float)$item->harga_satuan === 0.0 ? ' (Bonus)' : ''),
                'jumlah' => $item->quantity,
                'harga_satuan' => $item->harga_satuan,
                'subtotal' => $item->subtotal,
                'is_addon' => false,
            ]);
        }
    }
}
