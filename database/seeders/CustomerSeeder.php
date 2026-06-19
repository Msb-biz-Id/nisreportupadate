<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Master\Customer;
use App\Models\Master\CustomerType;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $targetBrands = Brand::whereIn('kode', ['ALG', 'CRL', 'DRV', 'IDW'])->get();
        foreach ($targetBrands as $brand) {
            $type = CustomerType::where(function ($q) use ($brand) {
                $q->where('brand_id', $brand->id)->orWhereNull('brand_id');
            })->where('nama', 'Reguler')->first();

            $samples = [
                ['nama' => 'Klub Garuda Mei', 'nomor_hp' => '081234567001'],
                ['nama' => 'SMA Negeri 1', 'nomor_hp' => '081234567002'],
                ['nama' => 'Komunitas Jogging Pagi', 'nomor_hp' => '081234567003'],
                ['nama' => 'PT Maju Bersama', 'nomor_hp' => '081234567004'],
                ['nama' => 'Tim Futsal Kantor', 'nomor_hp' => '081234567005'],
                ['nama' => 'Karang Taruna Desa', 'nomor_hp' => '081234567006'],
                ['nama' => 'Dinas Pemuda & Olahraga', 'nomor_hp' => '081234567007'],
                ['nama' => 'Klub Sepeda Nusantara', 'nomor_hp' => '081234567008'],
            ];

            foreach ($samples as $idx => $s) {
                $kode = $brand->kode . '-CUST-' . str_pad((string) ($idx + 1), 3, '0', STR_PAD_LEFT);
                Customer::updateOrCreate(
                    ['brand_id' => $brand->id, 'kode' => $kode],
                    [
                        'nama' => $s['nama'],
                        'nomor_hp' => $s['nomor_hp'],
                        'email' => strtolower(str_replace([' ', '&'], ['', ''], $s['nama'])) . '@example.test',
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
}
