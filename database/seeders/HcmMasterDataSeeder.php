<?php

namespace Database\Seeders;

use App\Models\Hcm\HcmMasterCategory;
use App\Models\Hcm\HcmMasterOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HcmMasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // 1. Kepegawaian & Struktur
            [
                'code' => 'job_level',
                'name' => 'Job Level',
                'group' => 'Kepegawaian & Struktur',
                'icon' => 'Briefcase',
                'description' => 'Jenjang karir / level jabatan karyawan',
                'options' => [
                    'Direksi',
                    'Manager',
                    'PIC',
                    'Supervisor',
                    'Leader',
                    'Staff',
                    'Trainee',
                    'Harian',
                    'Borongan',
                ],
            ],
            [
                'code' => 'divisi',
                'name' => 'Divisi',
                'group' => 'Kepegawaian & Struktur',
                'icon' => 'Building2',
                'description' => 'Divisi atau unit departemen kerja perusahaan',
                'options' => [
                    ['name' => 'Human Capital Management', 'code' => 'HCM'],
                    ['name' => 'Finance & Accounting', 'code' => 'FIN'],
                    ['name' => 'Brand & Marketing', 'code' => 'BRM'],
                    ['name' => 'Support & Control Produksi', 'code' => 'SCP'],
                    ['name' => 'Produksi', 'code' => 'PRD'],
                    ['name' => 'Media Internal', 'code' => 'MIN'],
                    ['name' => 'Media Eksternal', 'code' => 'MEX'],
                ],
            ],
            [
                'code' => 'posisi',
                'name' => 'Posisi / Jabatan',
                'group' => 'Kepegawaian & Struktur',
                'icon' => 'BadgeCheck',
                'description' => 'Posisi penugasan operasional dan staf kantor',
                'options' => [
                    'Finance',
                    'Accounting',
                    'Purchasing',
                    'Human Capital Management',
                    'Admin HCM',
                    'Marketing',
                    'Admin Brand',
                    'Designer',
                    'Produksi',
                    'Admin Produksi',
                    'Setting Printing',
                    'Potong Bahan',
                    'Press Sublime',
                    'Potong Pola',
                    'Jahit',
                    'Quality Control',
                    'Finishing (Press)',
                    'Finishing (Steam)',
                    'Finishing (Packing)',
                    'Operasional',
                    'Media Internal',
                    'Media Spesialist',
                    'Publisher',
                    'Editor',
                    'Planner',
                    'Media Eksternal',
                    'Web Editor',
                    'Web Developer',
                ],
            ],
            [
                'code' => 'status_ketenagakerjaan',
                'name' => 'Status Ketenagakerjaan',
                'group' => 'Kepegawaian & Struktur',
                'icon' => 'FileText',
                'description' => 'Jenis hubungan kerja formal karyawan',
                'options' => [
                    'Tetap (PKWTT)',
                    'Kontrak (PKWT)',
                    'PKWT Lanjutan',
                    'Freelance / Lepas',
                    'Trainee (Probation)',
                    'Paruh Waktu (Part-Time)',
                    'Harian',
                    'Borongan',
                    'Magang (Internship)',
                ],
            ],
            [
                'code' => 'entitas_cv',
                'name' => 'Entitas Legal (CV)',
                'group' => 'Kepegawaian & Struktur',
                'icon' => 'Landmark',
                'description' => 'Badan usaha penerbit kontrak kerja karyawan',
                'options' => [
                    'CV Jersey Ekonomis',
                    'CV Apparel Allegiant',
                    'CV Bawang Merah',
                    'CV Bawang Putih',
                ],
            ],

            // 2. Kontrak & Kompensasi
            [
                'code' => 'status_review_kontrak',
                'name' => 'Status Review Kontrak',
                'group' => 'Kontrak & Kompensasi',
                'icon' => 'FileCheck',
                'description' => 'Status tindak lanjut dan evaluasi kontrak PKWT',
                'options' => [
                    'Aktif (Aman / Jauh dari Masa Berakhir)',
                    'Mendekati Evaluasi (H-60 Kontrak Berakhir)',
                    'Wajib Review & Tindak Lanjut (H-30 Kontrak Berakhir)',
                    'Masa Tenggang / Proses Keputusan (H-14 s.d. Hari H)',
                    'Pengajuan Perpanjangan (Renewal Process)',
                    'Disetujui untuk Diperpanjang',
                    'Pengangkatan Menjadi Karyawan Tetap (Converted to Permanent)',
                    'Kontrak Selesai & Tidak Diperpanjang (Non-Renewal / Offboarding)',
                    'Resign / Berhenti atas Permintaan Sendiri selama Masa Kontrak',
                ],
            ],
            [
                'code' => 'status_pengajuan_honor',
                'name' => 'Status Pengajuan / Honor',
                'group' => 'Kontrak & Kompensasi',
                'icon' => 'DollarSign',
                'description' => 'Status persetujuan penyesuaian gaji / upah',
                'options' => [
                    'Draft',
                    'Sedang Diajukan / Pending',
                    'Menunggu Persetujuan Atasan / Manager',
                    'Menunggu Verifikasi HR / Finance',
                    'Revisi / Perbaikan',
                    'Disetujui (Menunggu Masa Berlaku)',
                    'Aktif / Berlaku Bulan Ini (Ready to Pay)',
                    'Selesai (Paid)',
                    'Berakhir / Expired',
                ],
            ],

            // 3. Presensi & Operasional
            [
                'code' => 'kategori_kehadiran',
                'name' => 'Kategori Kehadiran',
                'group' => 'Presensi & Operasional',
                'icon' => 'CalendarCheck',
                'description' => 'Klasifikasi absensi dan presensi harian',
                'options' => [
                    'Hadir',
                    'Terlambat',
                    'Pulang Cepat',
                    'Cuti',
                    'Izin',
                    'Sakit',
                    'Dinas Luar',
                    'Alpha/Mangkir',
                    'Libur/Cuti Bersama',
                ],
            ],
            [
                'code' => 'jenis_hari_lembur',
                'name' => 'Jenis Hari Lembur',
                'group' => 'Presensi & Operasional',
                'icon' => 'Clock',
                'description' => 'Pembeda tarif lembur kerja vs libur',
                'options' => [
                    'Lembur Hari Kerja',
                    'Lembur Hari Libur',
                ],
            ],

            // 4. Dokumen & Persuratan
            [
                'code' => 'kategori_dokumen_pengajuan',
                'name' => 'Dokumen Pengajuan',
                'group' => 'Dokumen & Persuratan',
                'icon' => 'FileUp',
                'description' => 'Kategori dokumen usulan biaya dan kegiatan',
                'options' => [
                    'Pengajuan RAB (Rencana Anggaran Biaya)',
                    'Proposal Kegiatan / Acara',
                    'Pengajuan Pembelian Aset / Inventaris',
                    'Pengajuan Perjalanan Dinas / Surat Tugas',
                ],
            ],
            [
                'code' => 'kategori_dokumen_realisasi',
                'name' => 'Dokumen Realisasi',
                'group' => 'Dokumen & Persuratan',
                'icon' => 'FileCheck2',
                'description' => 'Kategori dokumen pertanggungjawaban anggaran',
                'options' => [
                    'LPJ (Laporan Pertanggungjawaban) Kegiatan',
                    'Realisasi Pembelian Aset & Nota/Faktur Pembelanjaan',
                    'Laporan & Bukti Pengeluaran Perjalanan Dinas (Reimburse / Settlement)',
                ],
            ],
            [
                'code' => 'dokumen_administratif_kebijakan',
                'name' => 'Dokumen Kebijakan & SK',
                'group' => 'Dokumen & Persuratan',
                'icon' => 'FolderArchive',
                'description' => 'Surat keputusan dan pedoman kebijakan operasional',
                'options' => [
                    'Surat Keputusan (SK) & Kebijakan Internal',
                    'Kontrak / Perjanjian Kerjasama (Vendor / Partner)',
                    'Standard Operating Procedure (SOP)',
                    'Surat Peringatan (SP 1 / SP 2 / SP 3)',
                    'Surat Keputusan / Pemberitahuan PHK',
                    'Surat Pengalaman Kerja (Paklaring)',
                    'Surat Pengumuman Internal (Mutasi, Promosi, atau Kebijakan)',
                    'Berita Acara / Surat Klarifikasi',
                    'Surat Tugas & Perjalanan Dinas (SPPD)',
                ],
            ],

            // 5. Offboarding & Transisi
            [
                'code' => 'kepatuhan_notice_period',
                'name' => 'Kepatuhan Notice Period',
                'group' => 'Offboarding & Transisi',
                'icon' => 'Hourglass',
                'description' => 'Kepatuhan jangka waktu pemberitahuan berhenti kerja',
                'options' => [
                    'Sesuai Ketentuan (1 Bulan / Full Notice)',
                    'Kurang dari Ketentuan (Short Notice < 1 Bulan)',
                    'Tanpa Notice (Immediate / Walk Out)',
                    'Garden Leave (Dibebastugaskan)',
                    'Pemutusan oleh Perusahaan (Immediate Termination)',
                ],
            ],
            [
                'code' => 'hak_karyawan',
                'name' => 'Hak Sisa Karyawan',
                'group' => 'Offboarding & Transisi',
                'icon' => 'Coins',
                'description' => 'Penyelesaian sisa gaji, lembur, dan kompensasi',
                'options' => [
                    'Lunas & Dibayarkan Penuh (Full Settlement Paid)',
                    'Dipotong / Ada Penyesuaian (Deducted / Adjusted)',
                    'Ditahan Sebagian (Partially Held)',
                    'Belum Dibayarkan / Pending (Unpaid)',
                ],
            ],
            [
                'code' => 'pengembalian_aset_paklaring',
                'name' => 'Pengembalian Aset & Paklaring',
                'group' => 'Offboarding & Transisi',
                'icon' => 'PackageCheck',
                'description' => 'Status inventaris alat kerja dan penerbitan paklaring',
                'options' => [
                    'Lengkap & Terbit',
                    'Belum Lengkap / Aset Ditahan',
                    'Tidak Terbit',
                ],
            ],
            [
                'code' => 'status_clearance_sheet',
                'name' => 'Status Clearance Sheet',
                'group' => 'Offboarding & Transisi',
                'icon' => 'ClipboardCheck',
                'description' => 'Status lembar bebas tanggungan keluar',
                'options' => [
                    'Pending',
                    'Selesai (Clear)',
                ],
            ],

            // 6. Apresiasi & Demografi
            [
                'code' => 'status_penyaluran_reward',
                'name' => 'Status Penyaluran Reward',
                'group' => 'Apresiasi & Demografi',
                'icon' => 'Gift',
                'description' => 'Status distribusi barang apresiasi / reward',
                'options' => [
                    'Belum Diterima',
                    'Sudah Diterima (Serah Terima Langsung)',
                    'Sudah Ditransfer',
                    'Tertunda / Pending',
                    'Dibatalkan',
                ],
            ],
            [
                'code' => 'jenis_kelamin',
                'name' => 'Jenis Kelamin',
                'group' => 'Apresiasi & Demografi',
                'icon' => 'Users2',
                'description' => 'Jenis kelamin karyawan',
                'options' => [
                    'Laki-Laki',
                    'Perempuan',
                ],
            ],
            [
                'code' => 'agama',
                'name' => 'Agama',
                'group' => 'Apresiasi & Demografi',
                'icon' => 'HeartHandshake',
                'description' => 'Agama dan kepercayaan',
                'options' => [
                    'Islam',
                    'Kristen',
                    'Katolik',
                    'Hindu',
                    'Buddha',
                    'Konghucu',
                ],
            ],
            [
                'code' => 'pendidikan_terakhir',
                'name' => 'Tingkat Pendidikan',
                'group' => 'Apresiasi & Demografi',
                'icon' => 'GraduationCap',
                'description' => 'Jenjang pendidikan formal terakhir',
                'options' => [
                    'SD / Sederajat',
                    'SMP / Sederajat',
                    'SMA / SMK / Sederajat',
                    'Diploma 1 (D1)',
                    'Diploma 2 (D2)',
                    'Diploma 3 (D3)',
                    'Diploma 4 (D4)',
                    'Strata 1 (S1)',
                    'Strata 2 (S2)',
                    'Strata 3 (S3)',
                ],
            ],
            [
                'code' => 'status_pernikahan',
                'name' => 'Status Pernikahan',
                'group' => 'Apresiasi & Demografi',
                'icon' => 'Heart',
                'description' => 'Status perkawinan karyawan',
                'options' => [
                    'Belum Menikah',
                    'Menikah',
                    'Cerai Hidup',
                    'Cerai Mati',
                ],
            ],
            [
                'code' => 'ukuran_baju_seragam',
                'name' => 'Ukuran Baju Seragam',
                'group' => 'Apresiasi & Demografi',
                'icon' => 'Shirt',
                'description' => 'Ukuran baju seragam pabrik dan kantor',
                'options' => [
                    'S',
                    'M',
                    'L',
                    'XL',
                    'XXL',
                    'XXXL',
                    'XXXXL',
                ],
            ],
        ];

        foreach ($categories as $catIndex => $catData) {
            $options = $catData['options'];
            unset($catData['options']);

            $catData['order_index'] = $catIndex + 1;
            $category = HcmMasterCategory::updateOrCreate(
                ['code' => $catData['code']],
                $catData
            );

            foreach ($options as $optIndex => $optItem) {
                $optName = is_array($optItem) ? $optItem['name'] : $optItem;
                $optCode = is_array($optItem) ? $optItem['code'] : Str::slug($optName, '_');

                HcmMasterOption::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'name' => $optName,
                    ],
                    [
                        'code' => $optCode,
                        'order_index' => $optIndex + 1,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
