<?php

namespace Database\Seeders;

use App\Models\Hcm\HcmEmployee;
use App\Models\Hcm\HcmOfficeExitPermit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HcmOfficeExitPermitSeeder extends Seeder
{
    /**
     * Run database seeds for office exit permits (Surat Izin Keluar Kantor).
     */
    public function run(): void
    {
        $adminUser = User::where('email', 'hcm@nisgroup.id')->first() ?? User::first();
        $employees = HcmEmployee::where('is_active', true)->get();

        if ($employees->isEmpty()) {
            return;
        }

        $empBambang = $employees->firstWhere('nickname', 'Bambang') ?? $employees->get(0);
        $empPuji    = $employees->firstWhere('nickname', 'Puji') ?? $employees->get(1);
        $empDanang  = $employees->firstWhere('nickname', 'Danang') ?? $employees->get(2);
        $empDewi    = $employees->firstWhere('nickname', 'Dewi') ?? $employees->get(3);

        $permits = [
            [
                'employee_id' => $empBambang->id,
                'permit_date' => '2026-10-02',
                'exit_time' => '10:00',
                'return_time' => '11:45',
                'purpose' => 'Urusan Bank BRI & Setor Dokumen Pajak',
                'notes' => 'Menyerahkan berkas mutasi rekening koran dan pembukaan tabungan operasional unit.',
                'attachment_status' => 'Terlampir',
                'attachment_url' => '/storage/permits/bri_receipt_001.jpg',
                'status' => 'Kembali',
            ],
            [
                'employee_id' => $empPuji?->id ?? $empBambang->id,
                'permit_date' => '2026-10-05',
                'exit_time' => '13:30',
                'return_time' => '15:15',
                'purpose' => 'Dinas Luar - Ambil Sample Kain Printing di Vendor',
                'notes' => 'Pengambilan sample warna kain sublime ke workshop PT Maju Textile Jaya.',
                'attachment_status' => 'Terlampir',
                'attachment_url' => '/storage/permits/surat_jalan_sample.jpg',
                'status' => 'Kembali',
            ],
            [
                'employee_id' => $empDanang?->id ?? $empBambang->id,
                'permit_date' => '2026-10-08',
                'exit_time' => '09:15',
                'return_time' => '10:30',
                'purpose' => 'Servis Darurat Mesin Potong (Beli Sparepart)',
                'notes' => 'Membeli belt karet dinamo mesin kaisar di Kalitengah, Lamongan.',
                'attachment_status' => 'Terlampir',
                'attachment_url' => '/storage/permits/nota_sparepart_potong.jpg',
                'status' => 'Kembali',
            ],
            [
                'employee_id' => $empDewi?->id ?? $empBambang->id,
                'permit_date' => now()->toDateString(),
                'exit_time' => '11:00',
                'return_time' => null,
                'purpose' => 'Urusan Administrasi BPJS Kesehatan Karyawan',
                'notes' => 'Konsultasi aktivasi data kepesertaan faskes karyawan baru ke kantor cabang BPJS.',
                'attachment_status' => 'Tidak Terlampir',
                'attachment_url' => null,
                'status' => 'Masih di Luar',
            ],
        ];

        foreach ($permits as $item) {
            HcmOfficeExitPermit::firstOrCreate(
                [
                    'employee_id' => $item['employee_id'],
                    'permit_date' => $item['permit_date'],
                    'exit_time' => $item['exit_time'],
                ],
                array_merge($item, [
                    'uuid' => (string) Str::uuid(),
                    'created_by' => $adminUser?->id,
                ])
            );
        }
    }
}
