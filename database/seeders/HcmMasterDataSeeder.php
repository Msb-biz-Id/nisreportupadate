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
                'code' => 'departemen',
                'name' => 'Departemen',
                'group' => 'Kepegawaian & Struktur',
                'icon' => 'Building2',
                'description' => 'Departemen resmi NIS Group',
                'options' => [
                    ['name' => 'Finance & Accounting', 'code' => 'FIN'],
                    ['name' => 'Human Capital Management', 'code' => 'HCM'],
                    ['name' => 'Brand & Marketing', 'code' => 'BRM'],
                    ['name' => 'Support & Control Produksi', 'code' => 'SCP'],
                    ['name' => 'Produksi', 'code' => 'PRD'],
                    ['name' => 'Media Internal', 'code' => 'MIN'],
                    ['name' => 'Media Eksternal', 'code' => 'MEX'],
                ],
            ],
            [
                'code' => 'divisi',
                'name' => 'Divisi',
                'group' => 'Kepegawaian & Struktur',
                'icon' => 'Layers',
                'description' => 'Divisi operasional di bawah naungan departemen',
                'options' => [
                    // Finance & Accounting
                    ['name' => 'Finance', 'parent_name' => 'Finance & Accounting'],
                    ['name' => 'Accounting', 'parent_name' => 'Finance & Accounting'],
                    ['name' => 'Purchasing', 'parent_name' => 'Finance & Accounting'],
                    // Human Capital Management
                    ['name' => 'Human Capital Management', 'parent_name' => 'Human Capital Management'],
                    ['name' => 'Admin HCM', 'parent_name' => 'Human Capital Management'],
                    // Brand & Marketing
                    ['name' => 'Marketing', 'parent_name' => 'Brand & Marketing'],
                    ['name' => 'Admin Brand', 'parent_name' => 'Brand & Marketing'],
                    ['name' => 'Designer', 'parent_name' => 'Brand & Marketing'],
                    // Support & Control Produksi
                    ['name' => 'Support & Control Produksi', 'parent_name' => 'Support & Control Produksi'],
                    // Produksi
                    ['name' => 'Produksi', 'parent_name' => 'Produksi'],
                    ['name' => 'Admin Produksi', 'parent_name' => 'Produksi'],
                    ['name' => 'Setting Printing', 'parent_name' => 'Produksi'],
                    ['name' => 'Potong Bahan', 'parent_name' => 'Produksi'],
                    ['name' => 'Press Sublime', 'parent_name' => 'Produksi'],
                    ['name' => 'Potong Pola', 'parent_name' => 'Produksi'],
                    ['name' => 'Jahit', 'parent_name' => 'Produksi'],
                    ['name' => 'Quality Control', 'parent_name' => 'Produksi'],
                    ['name' => 'Finishing (Press)', 'parent_name' => 'Produksi'],
                    ['name' => 'Finishing (Steam)', 'parent_name' => 'Produksi'],
                    ['name' => 'Finishing (Packing)', 'parent_name' => 'Produksi'],
                    ['name' => 'Operasional', 'parent_name' => 'Produksi'],
                    // Media Internal
                    ['name' => 'Media Internal', 'parent_name' => 'Media Internal'],
                    ['name' => 'Media Spesialist (Internal)', 'parent_name' => 'Media Internal', 'code' => 'MS_INT'],
                    ['name' => 'Publisher', 'parent_name' => 'Media Internal'],
                    ['name' => 'Editor', 'parent_name' => 'Media Internal'],
                    ['name' => 'Planner', 'parent_name' => 'Media Internal'],
                    // Media Eksternal
                    ['name' => 'Media Eksternal', 'parent_name' => 'Media Eksternal'],
                    ['name' => 'Media Spesialist (Eksternal)', 'parent_name' => 'Media Eksternal', 'code' => 'MS_EXT'],
                    ['name' => 'Web Editor', 'parent_name' => 'Media Eksternal'],
                    ['name' => 'Web Developer', 'parent_name' => 'Media Eksternal'],
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
            [
                'code' => 'kategori_potongan_gaji',
                'name' => 'Kategori Potongan Gaji',
                'group' => 'Kontrak & Kompensasi',
                'icon' => 'Receipt',
                'description' => 'Kategori pemotongan gaji bulanan (Pelanggaran, Cuti, Berjenjang)',
                'options' => [
                    'Pelanggaran (Disciplinary Penalty)',
                    'Kelebihan Pengambilan Cuti (Leave Exceed)',
                    'Cuti Khusus Berjenjang (Maternity Leave)',
                ],
            ],
            [
                'code' => 'nama_bank',
                'name' => 'Nama Bank / Kas Pembayaran',
                'group' => 'Kontrak & Kompensasi',
                'icon' => 'Landmark',
                'description' => 'Daftar bank atau metode transfer payroll karyawan',
                'options' => [
                    'Bank BRI',
                    'Bank Mandiri',
                    'Bank BCA',
                    'Bank BNI',
                    'Bank Syariah Indonesia (BSI)',
                    'Bank Jateng',
                    'Tunai / Kas Kantor',
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
                'code' => 'status_lampiran',
                'name' => 'Status Lampiran',
                'group' => 'Presensi & Operasional',
                'icon' => 'Paperclip',
                'description' => 'Status ketersediaan bukti lampiran perizinan atau berkas',
                'options' => [
                    'Terlampir',
                    'Tidak Terlampir',
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
                $optCode = is_array($optItem) && isset($optItem['code']) ? $optItem['code'] : Str::slug($optName, '_');
                $parentId = null;

                if (is_array($optItem) && !empty($optItem['parent_name'])) {
                    $deptCatId = HcmMasterCategory::where('code', 'departemen')->value('id');
                    $parent = HcmMasterOption::where('category_id', $deptCatId)
                        ->where('name', $optItem['parent_name'])
                        ->first();
                    $parentId = $parent?->id;
                }

                HcmMasterOption::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'name' => $optName,
                    ],
                    [
                        'parent_id' => $parentId,
                        'code' => $optCode,
                        'order_index' => $optIndex + 1,
                        'is_active' => true,
                    ]
                );
            }
        }

        // Hapus kategori posisi lama jika masih ada (karena posisi sudah ditiadakan di HRIS)
        $oldPosisi = HcmMasterCategory::where('code', 'posisi')->first();
        if ($oldPosisi) {
            $oldPosisi->delete();
        }

        // Inisialisasi Pengaturan Baku HCM di tabel system_settings (Zero Hardcoding)
        $defaultSettings = [
            'hcm_profile' => [
                'company_name' => 'NISGroup',
                'division_name' => 'Divisi Human Capital Management',
                'company_tagline' => 'People, Culture & Organizational Development',
                'company_address' => 'Klaten, Jawa Tengah',
                'company_city' => 'Klaten',
                'company_email' => 'hrd@nisgroup.co.id',
                'company_phone' => '0812-3456-7890',
                'company_website' => 'https://nisgroup.co.id',
                'kop_header_line1' => 'DIVISI HUMAN CAPITAL & MANAJEMEN OPERASIONAL',
                'kop_header_line2' => 'No. Izin KBLI 14111 / 14120 - Manajemen SDM Terpadu',
                'document_footer_text' => 'Dokumen resmi diterbitkan otomatis oleh Sistem Kepegawaian terintegrasi.',
                'document_footer_disclaimer' => 'Keabsahan dokumen dapat diverifikasi langsung melalui portal HCM atau QR code tertera.',
                'signer_name' => 'Ahmad Fauzi, S.Psi., CHRP',
                'signer_role' => 'Head of Human Capital Management',
                'signer_nik' => 'HCM-2021-001',
                'show_signature_on_pdf' => '1',
                'show_stamp_on_pdf' => '1',
            ],
            'hcm_overtime' => [
                'weekday_hourly_rate' => '10000',
                'weekday_first_half_rate' => '5000',
                'weekend_hourly_rate' => '15000',
                'weekend_first_half_rate' => '10000',
                'coa_code' => '5-50100',
                'coa_name' => 'Beban Upah Lembur Karyawan Pabrik',
            ],
            'hcm_meal_allowance' => [
                'monthly_rate' => '280000',
                'alpha_deduction_rate' => '14000',
                'half_day_deduction_rate' => '7000',
                'max_late_tolerance' => '3',
                'max_permit_bonus_limit' => '2',
                'coa_code' => '5-50110',
                'coa_name' => 'Beban Uang Makan Karyawan Pabrik',
            ],
            'hcm_payroll' => [
                'cutoff_day' => '25',
                'auto_send_slip_email' => '1',
                'slip_email_delay_minutes' => '60',
                'send_salary_slip_email' => '1',
                'send_meal_slip_email' => '1',
                'send_overtime_slip_email' => '1',
            ],
        ];

        foreach ($defaultSettings as $group => $items) {
            foreach ($items as $key => $value) {
                \App\Models\Settings\SystemSetting::updateOrCreate(
                    ['group' => $group, 'key' => $key],
                    ['value' => $value, 'is_encrypted' => false]
                );
            }
        }
    }
}
