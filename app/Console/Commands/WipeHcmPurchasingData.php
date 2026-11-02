<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WipeHcmPurchasingData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'data:wipe-operational 
                            {--module=all : Modul yang ingin dibersihkan (all, hcm, purchasing)}
                            {--with-vendors : Ikut bersihkan data master vendor purchasing}
                            {--dry-run : Hitung dan tampilkan data tanpa melakukan penghapusan}
                            {--force : Jalankan tanpa konfirmasi interaktif}';

    /**
     * The console command description.
     */
    protected $description = 'Menghapus seluruh data operasional/transaksional HCM dan Purchasing dengan tetap mempertahankan Master Data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $module = strtolower($this->option('module') ?: 'all');
        $withVendors = (bool) $this->option('with-vendors');
        $isDryRun = (bool) $this->option('dry-run');
        $isForce = (bool) $this->option('force');

        $this->info("=====================================================================");
        $this->info("   PEMBERSIHAN DATA OPERASIONAL HCM & PURCHASING (PRESERVE MASTER)   ");
        $this->info("=====================================================================");

        if ($isDryRun) {
            $this->warn("[MODE DRY-RUN] Data tidak akan dihapus, hanya menampilkan simulasi jumlah baris.");
        }

        $hcmTables = [
            'hcm_applicant_interviews' => 'Wawancara Pelamar',
            'hcm_job_applicants' => 'Data Pelamar Kerja',
            'hcm_job_postings' => 'Lowongan Pekerjaan',
            'hcm_payroll_items' => 'Item Slip Gaji',
            'hcm_payrolls' => 'Rekap Payroll Bulanan',
            'hcm_meal_allowance_items' => 'Item Uang Makan',
            'hcm_meal_allowance_batches' => 'Batch Uang Makan',
            'hcm_overtimes' => 'Rekam Lembur Karyawan',
            'hcm_overtime_batches' => 'Batch Lembur Mingguan',
            'hcm_salary_deductions' => 'Potongan Gaji / Sanksi',
            'hcm_office_exit_permits' => 'Izin Keluar Kantor',
            'hcm_employee_rewards' => 'Catatan Reward & Apresiasi',
            'hcm_leave_requests' => 'Pengajuan Cuti & Izin',
            'hcm_attendances' => 'Log & Rekap Presensi',
            'hcm_shift_rosters' => 'Jadwal Roster Shift',
            'hcm_compensation_histories' => 'Riwayat Kenaikan Gaji',
            'hcm_compensations' => 'Setting Gaji Karyawan',
            'hcm_contracts' => 'Kontrak Kerja PKWT/PKWTT',
            'hcm_onboardings' => 'Berkas & Checklist Onboarding',
            'hcm_offboardings' => 'Clearance & Offboarding',
            'hcm_interns' => 'Data Detail Siswa Magang / PKL',
            'hcm_employees' => 'Data Induk Karyawan',
            'hcm_company_events' => 'Agenda & Event Perusahaan',
            'hcm_external_letters' => 'Surat Keluar / Eksternal',
            'hcm_internal_documents' => 'Dokumen Internal / SOP',
            'hcm_agenda_letters' => 'Buku Agenda Surat',
        ];

        $purchasingTables = [
            'purchasing_payments' => 'Pembayaran PO Purchasing',
            'purchasing_order_items' => 'Item Barang PO Purchasing',
            'purchasing_orders' => 'Purchase Orders (PO)',
            'purchasing_material_usages' => 'Pemakaian Bahan Baku',
            'purchasing_material_stocks' => 'Stok Bahan Baku Masuk',
            'purchasing_asset_maintenances' => 'Pemeliharaan Aset',
            'purchasing_asset_mutations' => 'Mutasi Lokasi Aset',
            'purchasing_asset_retirements' => 'Penghapusan Aset Rusak/Afkir',
            'purchasing_asset_suggestions' => 'Pengajuan Pembelian Aset',
            'purchasing_assets' => 'Data Aset & Inventaris',
        ];

        if ($withVendors) {
            $purchasingTables['purchasing_vendor_contacts'] = 'Kontak PIC Vendor';
            $purchasingTables['purchasing_vendors'] = 'Master Vendor / Rekanan';
        }

        $targetTables = [];
        if (in_array($module, ['all', 'hcm'])) {
            $targetTables = array_merge($targetTables, $hcmTables);
        }
        if (in_array($module, ['all', 'purchasing'])) {
            $targetTables = array_merge($targetTables, $purchasingTables);
        }

        // Hitung total baris yang akan dibersihkan
        $previewData = [];
        $totalRows = 0;
        foreach ($targetTables as $table => $label) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                $totalRows += $count;
                $previewData[] = [$table, $label, $count];
            } else {
                $previewData[] = [$table, $label . ' (Tabel Tidak Ditemukan)', 0];
            }
        }

        $this->newLine();
        $this->table(['Nama Tabel', 'Deskripsi Data', 'Jumlah Baris Saat Ini'], $previewData);
        $this->info("Total data yang akan dikosongkan: {$totalRows} baris.");

        $this->newLine();
        $this->info("=== MASTER DATA YANG TETAP DIJAGA / AMAN (TIDAK DIHAPUS) ===");
        $masterList = [
            'hcm_master_categories' => 'Kategori Master HRIS (Departemen, Divisi, Level, Entitas CV, Bank, dll)',
            'hcm_master_options' => 'Daftar Pilihan Master HRIS',
            'hcm_work_shifts' => 'Master Jam Kerja & Shift Kantor',
            'purchasing_master_options' => 'Master Kategori & Pengaturan Purchasing',
        ];
        if (!$withVendors) {
            $masterList['purchasing_vendors'] = 'Master Rekanan / Vendor Supplier (Aman)';
            $masterList['purchasing_vendor_contacts'] = 'Master Kontak Vendor (Aman)';
        }

        $masterTableRows = [];
        foreach ($masterList as $tbl => $desc) {
            $c = Schema::hasTable($tbl) ? DB::table($tbl)->count() : 0;
            $masterTableRows[] = [$tbl, $desc, "{$c} baris (DIPERTAHANKAN)"];
        }
        $this->table(['Master Tabel', 'Fungsi Master Data', 'Status'], $masterTableRows);

        if ($isDryRun) {
            $this->info("Simulasi selesai. Tidak ada perubahan yang dilakukan pada database.");
            return Command::SUCCESS;
        }

        if (!$isForce) {
            $this->newLine();
            if (!$this->confirm("Apakah Anda yakin ingin MENGHAPUS SEMUA DATA OPERASIONAL di atas? Tindakan ini tidak dapat dibatalkan!", false)) {
                $this->warn("Operasi dibatalkan oleh pengguna.");
                return Command::SUCCESS;
            }
        }

        $this->newLine();
        $this->info("Memulai proses pengosongan tabel...");

        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        try {
            Schema::disableForeignKeyConstraints();

            foreach ($targetTables as $table => $label) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();

                    // Reset auto-increment sequence jika SQLite
                    if ($isSqlite && Schema::hasTable('sqlite_sequence')) {
                        DB::table('sqlite_sequence')->where('name', $table)->delete();
                    }

                    $this->line(" <fg=green>✓</> Mengosongkan tabel: <fg=yellow>{$table}</> ({$label})");
                }
            }

            // Bersihkan notification / activity log terkait hcm jika ada
            if (Schema::hasTable('notifications')) {
                DB::table('notifications')->where('type', 'like', '%Hcm%')->delete();
            }

            Schema::enableForeignKeyConstraints();

            $this->newLine();
            $this->info("BERHASIL! Seluruh data operasional HCM dan Purchasing telah dibersihkan.");
            $this->info("Seluruh Master Data (Opsi Dropdown, Jam Kerja, Master Purchasing) tetap utuh.");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            Schema::enableForeignKeyConstraints();
            $this->error("Terjadi kegagalan saat membersihkan data: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
