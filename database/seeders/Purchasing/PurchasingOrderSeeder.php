<?php

namespace Database\Seeders\Purchasing;

use App\Models\Purchasing\PurchasingMasterOption;
use App\Models\Purchasing\PurchasingOrder;
use App\Models\Purchasing\PurchasingPayment;
use App\Models\Purchasing\PurchasingVendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PurchasingOrderSeeder extends Seeder
{
    public function run(): void
    {
        $locations = PurchasingMasterOption::whereIn('category', ['location', 'lokasi'])->pluck('id', 'name')->toArray();
        $itemCategories = PurchasingMasterOption::where('category', 'item_category')->pluck('id', 'name')->toArray();

        $larasLirisId = $locations['Laras Liris'] ?? null;
        $artomoroId = $locations['Artomoro'] ?? null;
        $greenHcmId = $locations['Green HCM'] ?? null;
        $greenAdminId = $locations['Green Admin'] ?? null;
        $walisongoId = $locations['Walisongo'] ?? null;

        $vTextile = PurchasingVendor::where('vendor_code', 'VND-001')->first();
        $vBudi = PurchasingVendor::where('vendor_code', 'VND-002')->first();
        $vShopee = PurchasingVendor::where('vendor_code', 'VND-003')->first();
        $vElektronik = PurchasingVendor::where('vendor_code', 'VND-004')->first();
        $vCyber = PurchasingVendor::where('vendor_code', 'VND-005')->first();
        $vBintang = PurchasingVendor::where('vendor_code', 'VND-006')->first();
        $vSolo = PurchasingVendor::where('vendor_code', 'VND-007')->first();
        $vSentosa = PurchasingVendor::where('vendor_code', 'VND-008')->first();

        $orders = [
            // 1. Dari Excel Sheet 3 Row 5: Kain Warna
            [
                'po_number' => 'PO-202603-0001',
                'transaction_date' => '2026-03-17',
                'order_type' => 'OPEX',
                'department' => 'Produksi',
                'division' => 'Potong Bahan',
                'position' => 'Potong Bahan',
                'requester_name' => 'Operator Potong',
                'location_id' => $larasLirisId,
                'vendor_id' => $vSolo?->id,
                'item_category_id' => $itemCategories['Kain Warna'] ?? null,
                'item_name' => '(Warna Airwalk) Abu Tua',
                'specification' => 'airwalk abu tua kain roll untuk pesanan jersey batch 1',
                'unit' => 'kg',
                'quantity' => 1.00,
                'unit_price' => 69000.00,
                'subtotal' => 69000.00,
                'discount_amount' => 0.00,
                'shipping_cost' => 20000.00,
                'tax_amount' => 0.00,
                'grand_total' => 89000.00,
                'payment_type' => 'Transfer Bank (Bank Transfer / Virtual Account)',
                'status' => 'PAID_COMPLETED',
                'notes' => 'Pencatatan dari Sheet Database resmi',
            ],
            // 2. Dari Excel Sheet 3 Row 6: Spanduk acara
            [
                'po_number' => 'PO-202603-0002',
                'transaction_date' => '2026-03-25',
                'order_type' => 'OPEX',
                'department' => 'Brand & Marketing',
                'division' => 'Marketing',
                'position' => 'Admin Brand',
                'requester_name' => 'Kepala Marketing',
                'location_id' => $artomoroId,
                'vendor_id' => $vBintang?->id,
                'item_category_id' => $itemCategories['Printing / Percetakan'] ?? null,
                'item_name' => 'Spanduk Acara Banner',
                'specification' => 'banner 3x1 m outdoor flexi 280 gsm',
                'unit' => 'pcs',
                'quantity' => 1.00,
                'unit_price' => 304000.00,
                'subtotal' => 304000.00,
                'discount_amount' => 0.00,
                'shipping_cost' => 0.00,
                'tax_amount' => 0.00,
                'grand_total' => 304000.00,
                'payment_type' => 'Transfer Bank (Bank Transfer / Virtual Account)',
                'status' => 'PAID_COMPLETED',
                'notes' => 'Spanduk promosi exhibition expo',
            ],
            // 3. Dari Excel Sheet 3 Row 7: Software ChatGPT Pro
            [
                'po_number' => 'PO-202603-0003',
                'transaction_date' => '2026-03-30',
                'order_type' => 'OPEX',
                'department' => 'Human Capital Management',
                'division' => 'HCM',
                'position' => 'Admin HCM',
                'requester_name' => 'Staff HCM',
                'location_id' => $greenHcmId,
                'vendor_id' => $vShopee?->id,
                'item_category_id' => $itemCategories['Operasional Kantor'] ?? null,
                'item_name' => 'Langganan Software ChatGPT Pro',
                'specification' => 'chat gpt pro bulanan untuk riset kurikulum training',
                'unit' => 'pcs',
                'quantity' => 1.00,
                'unit_price' => 100000.00,
                'subtotal' => 100000.00,
                'discount_amount' => 0.00,
                'shipping_cost' => 0.00,
                'tax_amount' => 0.00,
                'grand_total' => 100000.00,
                'payment_type' => 'Tunai / Kas Kecil (Cash / Petty Cash)',
                'status' => 'PENDING_PIC_CHECK',
                'notes' => 'Menunggu verifikasi manager HCM',
            ],
            // 4. Dari Excel Sheet 3 Row 12: Mesin Jahit Industri dengan Termin TOP
            [
                'po_number' => 'PO-202603-0004',
                'transaction_date' => '2026-03-20',
                'order_type' => 'CAPEX',
                'department' => 'Produksi',
                'division' => 'Potong Bahan',
                'position' => 'Potong Bahan',
                'requester_name' => 'Budi Santoso',
                'location_id' => $larasLirisId,
                'vendor_id' => $vBudi?->id,
                'item_category_id' => $itemCategories['Operasional Kantor'] ?? null,
                'item_name' => 'Mesin Jahit Industri High-Speed Juki',
                'specification' => 'Juki DDL-8700 Full Set Meja + Dinamo Servo Hemat Listrik',
                'unit' => 'Unit',
                'quantity' => 1.00,
                'unit_price' => 4500000.00,
                'subtotal' => 4500000.00,
                'discount_amount' => 0.00,
                'shipping_cost' => 0.00,
                'tax_amount' => 0.00,
                'grand_total' => 4500000.00,
                'payment_type' => 'Tempo / Kredit (Term of Payment / TOP)',
                'status' => 'APPROVED_BY_PIC',
                'notes' => 'Pengadaan aset mesin dengan 3 termin pembayaran',
                'payments' => [
                    [
                        'term_step' => 1,
                        'term_name' => 'Termin 1 (DP 30%)',
                        'term_percentage' => 30.00,
                        'amount' => 1350000.00,
                        'due_date' => '2026-03-20',
                        'status' => 'PAID',
                        'paid_at' => '2026-03-20',
                        'payment_method' => 'Transfer Bank',
                    ],
                    [
                        'term_step' => 2,
                        'term_name' => 'Termin 2 (Cicilan 30%)',
                        'term_percentage' => 30.00,
                        'amount' => 1350000.00,
                        'due_date' => now()->toDateString(), // Hari ini (DUE TODAY alert!)
                        'status' => 'DUE_TODAY',
                        'paid_at' => null,
                        'payment_method' => null,
                    ],
                    [
                        'term_step' => 3,
                        'term_name' => 'Termin 3 (Pelunasan 40%)',
                        'term_percentage' => 40.00,
                        'amount' => 1800000.00,
                        'due_date' => now()->addDays(14)->toDateString(),
                        'status' => 'PENDING',
                        'paid_at' => null,
                        'payment_method' => null,
                    ],
                ],
            ],
            // 5. Dari Excel Sheet 3 Row 19: Meja Print TS100
            [
                'po_number' => 'PO-202603-0005',
                'transaction_date' => '2026-03-22',
                'order_type' => 'CAPEX',
                'department' => 'Produksi',
                'division' => 'Setting Printing',
                'position' => 'Setting Printing',
                'requester_name' => 'Admin Setting',
                'location_id' => $larasLirisId,
                'vendor_id' => $vBudi?->id,
                'item_category_id' => $itemCategories['Operasional Kantor'] ?? null,
                'item_name' => 'Meja Print TS100 Ergonomis',
                'specification' => 'MEJA PRINT TS100 Khusus Printer Sublim Lebar 1.8 Meter',
                'unit' => 'Unit',
                'quantity' => 1.00,
                'unit_price' => 3285037.00,
                'subtotal' => 3285037.00,
                'discount_amount' => 0.00,
                'shipping_cost' => 12000.00,
                'tax_amount' => 0.00,
                'grand_total' => 3297037.00,
                'payment_type' => 'Transfer Bank (Bank Transfer / Virtual Account)',
                'status' => 'PAID_COMPLETED',
                'notes' => 'Aset meja mesin cetak nomor inventaris FUR-PRD-014',
            ],
            // 6. Kain Putih Milano Alle (PT Maju Textile Jaya) - Jatuh Tempo Hari Ini Rp 4.500.000
            [
                'po_number' => 'PO-202603-0006',
                'transaction_date' => now()->subDays(14)->toDateString(),
                'order_type' => 'OPEX',
                'department' => 'Produksi',
                'division' => 'Potong Bahan',
                'position' => 'Potong Bahan',
                'requester_name' => 'Budi Santoso',
                'location_id' => $larasLirisId,
                'vendor_id' => $vTextile?->id,
                'item_category_id' => $itemCategories['Kain Putih'] ?? null,
                'item_name' => 'Kain Putih Milano Alle Roll',
                'specification' => 'Bahan dasar jersey printing grade A 5 roll x 25 kg',
                'unit' => 'Kilogram (Kg)',
                'quantity' => 125.00,
                'unit_price' => 36000.00,
                'subtotal' => 4500000.00,
                'discount_amount' => 0.00,
                'shipping_cost' => 0.00,
                'tax_amount' => 0.00,
                'grand_total' => 4500000.00,
                'payment_type' => 'Tempo / Kredit (Term of Payment / TOP)',
                'status' => 'APPROVED_BY_PIC',
                'notes' => 'Tagihan termin kain putih jatuh tempo hari ini untuk segera diproses pencairan',
                'payments' => [
                    [
                        'term_step' => 1,
                        'term_name' => 'Pelunasan TOP Kain Putih (Jatuh Tempo Hari Ini)',
                        'term_percentage' => 100.00,
                        'amount' => 4500000.00,
                        'due_date' => now()->toDateString(), // Hari Ini (DUE TODAY alert!)
                        'status' => 'DUE_TODAY',
                        'paid_at' => null,
                        'payment_method' => null,
                    ],
                ],
            ],
            // 7. Laptop ThinkPad T14 (Capex IT)
            [
                'po_number' => 'PO-202603-0007',
                'transaction_date' => now()->subDays(10)->toDateString(),
                'order_type' => 'CAPEX',
                'department' => 'Human Capital Management',
                'division' => 'HCM',
                'position' => 'Admin HCM',
                'requester_name' => 'Admin HCM NISGroup',
                'location_id' => $greenHcmId,
                'vendor_id' => $vElektronik?->id,
                'item_category_id' => $itemCategories['Consumable Printer / IT'] ?? null,
                'item_name' => 'Laptop Lenovo ThinkPad T14 Gen 4',
                'specification' => 'Core i7-1355U, 16GB RAM, 512GB NVMe, Win 11 Pro',
                'unit' => 'Unit',
                'quantity' => 1.00,
                'unit_price' => 16500000.00,
                'subtotal' => 16500000.00,
                'discount_amount' => 500000.00,
                'shipping_cost' => 0.00,
                'tax_amount' => 0.00,
                'grand_total' => 16000000.00,
                'payment_type' => 'Transfer Bank (Bank Transfer / Virtual Account)',
                'status' => 'PAID_COMPLETED',
                'notes' => 'Aset inventaris kerja kode IT.HCM.026.001',
            ],
            // 8. Tinta Sublimasi Cyan & Magenta (Opex)
            [
                'po_number' => 'PO-202603-0008',
                'transaction_date' => now()->subDays(5)->toDateString(),
                'order_type' => 'OPEX',
                'department' => 'Produksi',
                'division' => 'Setting Printing',
                'position' => 'Setting Printing',
                'requester_name' => 'Admin Setting',
                'location_id' => $larasLirisId,
                'vendor_id' => $vElektronik?->id,
                'item_category_id' => $itemCategories['Printing / Percetakan'] ?? null,
                'item_name' => 'Tinta Sublimasi Epson HD Cyan & Magenta',
                'specification' => 'Original Botol 1 Liter (2 Botol Cyan, 2 Botol Magenta)',
                'unit' => 'Botol',
                'quantity' => 4.00,
                'unit_price' => 450000.00,
                'subtotal' => 1800000.00,
                'discount_amount' => 0.00,
                'shipping_cost' => 25000.00,
                'tax_amount' => 0.00,
                'grand_total' => 1825000.00,
                'payment_type' => 'Transfer Bank (Bank Transfer / Virtual Account)',
                'status' => 'PURCHASE_COMPLETED',
                'notes' => 'Barang sudah tiba dan masuk inventaris tinta',
            ],
            // 9. Tagihan Terlambat (Overdue): INV-9021-offline Toko Elektronik Jaya
            [
                'po_number' => 'PO-202603-0009',
                'invoice_number' => 'INV-9021-offline',
                'transaction_date' => now()->subDays(16)->toDateString(),
                'order_type' => 'OPEX',
                'department' => 'Umum & Operasional',
                'division' => 'Maintenance Ruko',
                'position' => 'Teknisi Listrik',
                'requester_name' => 'Ahmad Teknisi',
                'location_id' => $greenAdminId,
                'vendor_id' => $vElektronik?->id,
                'item_category_id' => $itemCategories['Operasional Kantor'] ?? null,
                'item_name' => 'Kabel Listrik NYM & Komponen Panel',
                'specification' => 'Penggantian MCB Schneider dan instalasi kabel ruko',
                'unit' => 'Set',
                'quantity' => 1.00,
                'unit_price' => 2450000.00,
                'subtotal' => 2450000.00,
                'discount_amount' => 0.00,
                'shipping_cost' => 0.00,
                'tax_amount' => 0.00,
                'grand_total' => 2450000.00,
                'payment_type' => 'Tempo / Kredit (Term of Payment / TOP)',
                'status' => 'APPROVED_BY_PIC',
                'notes' => 'INV-9021-offline toko fisik melewati batas tempo 2 hari',
                'payments' => [
                    [
                        'term_step' => 1,
                        'term_name' => 'Pelunasan Faktur Fisik INV-9021-offline',
                        'term_percentage' => 100.00,
                        'amount' => 2450000.00,
                        'due_date' => now()->subDays(2)->toDateString(), // Overdue 2 hari
                        'status' => 'OVERDUE',
                        'paid_at' => null,
                        'payment_method' => null,
                    ],
                ],
            ],
            // 10. Pengajuan PR Media Internal 1 (Menunggu Persetujuan)
            [
                'po_number' => 'PO-202603-0010',
                'transaction_date' => now()->toDateString(),
                'order_type' => 'CAPEX',
                'department' => 'Media Internal',
                'division' => 'Media Internal',
                'position' => 'Media Specialist',
                'requester_name' => 'Fajar Media',
                'location_id' => $walisongoId,
                'vendor_id' => $vElektronik?->id,
                'item_category_id' => $itemCategories['Consumable Printer / IT'] ?? null,
                'item_name' => 'Lensa Kamera Sony FE 70-200mm F2.8 GM OSS II',
                'specification' => 'Lensa telezoom produksi konten dokumentasi & liputan resmi',
                'unit' => 'Unit',
                'quantity' => 1.00,
                'unit_price' => 38500000.00,
                'subtotal' => 38500000.00,
                'discount_amount' => 0.00,
                'shipping_cost' => 0.00,
                'tax_amount' => 0.00,
                'grand_total' => 38500000.00,
                'payment_type' => 'Transfer Bank (Bank Transfer / Virtual Account)',
                'status' => 'PENDING_PIC_CHECK',
                'notes' => 'Pengajuan Purchase Requisition baru dari tim Media Internal',
            ],
            // 11. Pengajuan PR Media Internal 2 (Menunggu Persetujuan)
            [
                'po_number' => 'PO-202603-0011',
                'transaction_date' => now()->toDateString(),
                'order_type' => 'CAPEX',
                'department' => 'Media Internal',
                'division' => 'Media Internal',
                'position' => 'Videographer',
                'requester_name' => 'Rizky Media',
                'location_id' => $walisongoId,
                'vendor_id' => $vElektronik?->id,
                'item_category_id' => $itemCategories['Consumable Printer / IT'] ?? null,
                'item_name' => 'Drone DJI Mini 4 Pro Fly More Combo Plus',
                'specification' => 'Drone rekaman aerial resolusi 4K HDR',
                'unit' => 'Unit',
                'quantity' => 1.00,
                'unit_price' => 15800000.00,
                'subtotal' => 15800000.00,
                'discount_amount' => 0.00,
                'shipping_cost' => 0.00,
                'tax_amount' => 0.00,
                'grand_total' => 15800000.00,
                'payment_type' => 'Transfer Bank (Bank Transfer / Virtual Account)',
                'status' => 'PENDING_PIC_CHECK',
                'notes' => 'Pengajuan Purchase Requisition baru untuk dokumentasi workshop dan pabrik',
            ],
            // 12. Langganan Canva Pro 1 Tahun (HCM/Branding) - Habis dalam 5 hari
            [
                'po_number' => 'PO-202603-0012',
                'transaction_date' => now()->subYear()->addDays(5)->toDateString(),
                'order_type' => 'OPEX',
                'department' => 'Brand & Marketing',
                'division' => 'Marketing',
                'position' => 'Admin Brand',
                'requester_name' => 'Tim Desain Grafis',
                'location_id' => $greenHcmId,
                'vendor_id' => $vShopee?->id,
                'item_category_id' => $itemCategories['Operasional Kantor'] ?? null,
                'item_name' => 'Canva Pro 1 Tahun (HCM/Branding)',
                'specification' => 'Lisensi tim tahunan Canva Pro untuk desain jersey dan social media',
                'unit' => 'Tahun',
                'quantity' => 1.00,
                'unit_price' => 1290000.00,
                'subtotal' => 1290000.00,
                'discount_amount' => 0.00,
                'shipping_cost' => 0.00,
                'tax_amount' => 0.00,
                'grand_total' => 1290000.00,
                'payment_type' => 'Transfer Bank (Bank Transfer / Virtual Account)',
                'status' => 'PURCHASE_COMPLETED',
                'notes' => 'Periode 1 Tahun. Jatuh tempo perpanjangan dalam 5 hari.',
            ],
        ];

        foreach ($orders as $oData) {
            $payments = $oData['payments'] ?? [];
            unset($oData['payments']);

            $order = PurchasingOrder::updateOrCreate(
                ['po_number' => $oData['po_number']],
                $oData
            );

            if (!empty($payments)) {
                foreach ($payments as $pData) {
                    PurchasingPayment::firstOrCreate(
                        [
                            'order_id' => $order->id,
                            'term_step' => $pData['term_step'],
                        ],
                        array_merge($pData, [
                            'invoice_number' => 'INV-' . $order->po_number . '-T' . $pData['term_step'],
                        ])
                    );
                }
            }
        }
    }
}
