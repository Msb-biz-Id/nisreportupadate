<?php

namespace Database\Seeders\Purchasing;

use App\Models\Purchasing\PurchasingVendor;
use App\Models\Purchasing\PurchasingVendorContact;
use Illuminate\Database\Seeder;

class PurchasingVendorSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = [
            [
                'vendor_code' => 'VND-001',
                'name' => 'PT Maju Textile Jaya',
                'category' => 'Vendor Kontrak / Langganan',
                'item_category' => 'Kain Putih',
                'item_name' => 'Kain Putih Milano & Benang',
                'specification' => 'Bahan roll jersey grade A',
                'address' => 'Kawasan Industri Tekstil No. 12, Bandung',
                'bank_name' => 'BCA',
                'bank_account_no' => '8890123456',
                'bank_account_holder' => 'PT Maju Textile Jaya',
                'default_top_days' => 30,
                'notes' => 'Supplier utama bahan kain putih dan benang jahit',
                'contacts' => [
                    [
                        'pic_name' => 'Bpk. Hendra',
                        'role_title' => 'Sales & Key Account Manager',
                        'phone' => '081234567890',
                        'email' => 'hendra@majutextile.com',
                        'is_primary' => true,
                    ],
                    [
                        'pic_name' => 'Ibu Rina',
                        'role_title' => 'Finance & Penagihan',
                        'phone' => '081899887766',
                        'email' => 'finance@majutextile.com',
                        'is_primary' => false,
                    ],
                ],
            ],
            [
                'vendor_code' => 'VND-002',
                'name' => 'Gudang Budi Mesin',
                'category' => 'Pembelian Langsung / Offline',
                'item_category' => 'Mesin & Peralatan Produksi',
                'item_name' => 'Mesin Potong & Mesin Jahit',
                'specification' => 'Peralatan potong kain dan mesin industri',
                'address' => 'Kalitengah, Lamongan',
                'bank_name' => 'Mandiri',
                'bank_account_no' => '1420098765432',
                'bank_account_holder' => 'Budi Sarjono',
                'default_top_days' => 14,
                'notes' => 'Penyedia mesin potong kain, mesin jahit, dan perlengkapan servis bengkel',
                'contacts' => [
                    [
                        'pic_name' => 'Bpk. Sarjono',
                        'role_title' => 'Pemilik / Sales Support',
                        'phone' => '085234567891',
                        'email' => 'sarjonogood@gmail.com',
                        'is_primary' => true,
                    ],
                ],
            ],
            [
                'vendor_code' => 'VND-003',
                'name' => 'Shopee / Marketplace Online',
                'category' => 'E-Commerce / Marketplace Online',
                'item_category' => 'Operasional Kantor',
                'item_name' => 'Sparepart & ATK Online',
                'specification' => 'Pengadaan via marketplace checkout online',
                'address' => 'Official Marketplace E-Commerce',
                'notes' => 'Pengadaan spareparts kecil, ATK urgent, langganan software via marketplace',
                'contacts' => [
                    [
                        'pic_name' => 'CS Official E-Commerce',
                        'role_title' => 'Customer Care',
                        'phone' => '02180864200',
                        'email' => 'support@shopee.co.id',
                        'is_primary' => true,
                    ],
                ],
            ],
            [
                'vendor_code' => 'VND-004',
                'name' => 'Toko Elektronik Jaya',
                'category' => 'Pembelian Langsung / Offline',
                'item_category' => 'Perangkat IT (Laptop/PC/Server)',
                'item_name' => 'Hardware & Printer Sublim',
                'specification' => 'Perangkat komputer dan printer Epson sublimasi',
                'address' => 'Jl. Embong Malang No. 45, Surabaya',
                'bank_name' => 'BCA',
                'bank_account_no' => '0188997766',
                'bank_account_holder' => 'Toko Elektronik Jaya',
                'notes' => 'Penyedia hardware komputer, printer sublim, aksesoris IT',
                'contacts' => [
                    [
                        'pic_name' => 'Bpk. Andi Wijaya',
                        'role_title' => 'Sales Hardware IT',
                        'phone' => '081398765432',
                        'email' => 'andi.jaya@gmail.com',
                        'is_primary' => true,
                    ],
                ],
            ],
            [
                'vendor_code' => 'VND-005',
                'name' => 'PT Cyber Solusi Nusantara',
                'category' => 'Langganan Digital / Software',
                'item_category' => 'Software / Lisensi',
                'item_name' => 'Lisensi & Cloud Server',
                'specification' => 'Software Canva Pro, Workspace, Cloud Database',
                'address' => 'Sudirman Office Tower Lt. 18, Jakarta',
                'bank_name' => 'BCA',
                'bank_account_no' => '5420119988',
                'bank_account_holder' => 'PT Cyber Solusi Nusantara',
                'notes' => 'Penyedia lisensi software, Google Workspace, server & hosting',
                'contacts' => [
                    [
                        'pic_name' => 'Bpk. Kevin Pratama',
                        'role_title' => 'Account Manager',
                        'phone' => '081298761122',
                        'email' => 'kevin@cybersolusi.com',
                        'is_primary' => true,
                    ],
                    [
                        'pic_name' => 'Ibu Dewi',
                        'role_title' => 'Billing & Invoicing',
                        'phone' => '081822334455',
                        'email' => 'dewi@cybersolusi.com',
                        'is_primary' => false,
                    ],
                ],
            ],
            [
                'vendor_code' => 'VND-006',
                'name' => 'CV Bintang Percetakan & Promosi',
                'category' => 'Vendor Kontrak / Langganan',
                'item_category' => 'Aset Marketing / Branding',
                'item_name' => 'Spanduk & Banner Event',
                'specification' => 'Percetakan banner flexi dan standing banner',
                'address' => 'Jl. Gejayan No. 88, Yogyakarta',
                'bank_name' => 'BRI',
                'bank_account_no' => '002901009876501',
                'bank_account_holder' => 'CV Bintang Percetakan',
                'notes' => 'Penyedia spanduk acara, banner branding, stiker logo kemasan',
                'contacts' => [
                    [
                        'pic_name' => 'Bpk. Joko',
                        'role_title' => 'Kepala Setting & Produksi',
                        'phone' => '087788990011',
                        'email' => 'joko.bintang@gmail.com',
                        'is_primary' => true,
                    ],
                ],
            ],
            [
                'vendor_code' => 'VND-007',
                'name' => 'Supplier Langganan Tekstil Solo',
                'category' => 'Vendor Kontrak / Langganan',
                'item_category' => 'Kain Warna',
                'item_name' => 'Kain Warna Meteran',
                'specification' => 'Kain warna Airwalk, Milano, Waffle, Poliester',
                'address' => 'Pasar Klewer Blok B No. 12, Surakarta',
                'bank_name' => 'BCA',
                'bank_account_no' => '0155443322',
                'bank_account_holder' => 'Sri Wahyuni',
                'notes' => 'Supplier kain warna meteran (Airwalk, Milano, Waffle) dan poliester',
                'contacts' => [
                    [
                        'pic_name' => 'Ibu Sri Wahyuni',
                        'role_title' => 'Owner / Admin Pemesanan',
                        'phone' => '081122334455',
                        'email' => 'sri.langganan@gmail.com',
                        'is_primary' => true,
                    ],
                ],
            ],
            [
                'vendor_code' => 'VND-008',
                'name' => 'Toko ATK Sentosa Lamongan',
                'category' => 'Pembelian Langsung / Offline',
                'item_category' => 'ATK',
                'item_name' => 'Kertas & Perlengkapan Kantor',
                'specification' => 'Kertas HVS, lakban, cutter, dan kebutuhan packing',
                'address' => 'Jl. Lamongrejo No. 15, Lamongan',
                'bank_name' => 'BCA',
                'bank_account_no' => '0188776655',
                'bank_account_holder' => 'Toko ATK Sentosa',
                'notes' => 'Supplier kertas HVS, alat tulis kantor, lakban, dan consumable pantry',
                'contacts' => [
                    [
                        'pic_name' => 'Ibu Maya',
                        'role_title' => 'Kasir & Pengelola Toko',
                        'phone' => '085611223344',
                        'email' => 'maya.sentosa@gmail.com',
                        'is_primary' => true,
                    ],
                ],
            ],
        ];

        foreach ($vendors as $vData) {
            $contacts = $vData['contacts'];
            unset($vData['contacts']);

            $vendor = PurchasingVendor::firstOrCreate(
                ['vendor_code' => $vData['vendor_code']],
                array_merge($vData, ['is_active' => true])
            );

            foreach ($contacts as $cData) {
                PurchasingVendorContact::firstOrCreate(
                    [
                        'vendor_id' => $vendor->id,
                        'pic_name' => $cData['pic_name'],
                    ],
                    $cData
                );
            }
        }
    }
}
