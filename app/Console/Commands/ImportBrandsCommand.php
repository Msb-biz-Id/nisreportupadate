<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Master\BankAccount;
use App\Models\Settings\SystemSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportBrandsCommand extends Command
{
    protected $signature = 'import:brands {file : Path ke file CSV} {--force : Mengabaikan pengaturan sistem yang menonaktifkan impor}';

    protected $description = 'Import master data brand/reseller dari file CSV format baku';

    public function handle(): int
    {
        $force = $this->option('force');
        $importEnabled = (bool) SystemSetting::get('system', 'customer_import_enabled', false);

        if (!$importEnabled && !$force) {
            $this->error("Impor brand dinonaktifkan di pengaturan sistem. Gunakan opsi --force untuk mengabaikan.");
            return 1;
        }

        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File tidak ditemukan: {$filePath}");
            return 1;
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->error("Gagal membuka file CSV.");
            return 1;
        }

        $headers = fgetcsv($handle, 1000, ',');
        if ($headers) {
            $headers[0] = preg_replace('/[\x{FEFF}\x{FFFE}\x{EFBB}\x{BFB0}]/u', '', $headers[0]);
            $headers = array_map('trim', $headers);
        }

        $headerMap = array_flip($headers);
        $required = ['kode', 'nama_brand'];
        foreach ($required as $req) {
            if (!isset($headerMap[$req])) {
                $this->error("Format CSV tidak valid. Kolom '{$req}' wajib ada.");
                fclose($handle);
                return 1;
            }
        }

        $imported = 0;
        $rowNum = 1;

        $this->info("Memulai proses impor brand...");

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $rowNum++;
                if (empty(array_filter($row))) continue;

                $data = [];
                foreach ($headerMap as $col => $index) {
                    $data[$col] = isset($row[$index]) ? trim($row[$index]) : null;
                }

                $brandCode = Str::upper($data['kode']);
                $brandName = $data['nama_brand'];

                if (!$brandCode || !$brandName) {
                    $this->warn("Baris {$rowNum}: Lewati karena kode atau nama brand kosong.");
                    continue;
                }

                $brandType = strtolower($data['brand_type'] ?? 'regular');
                if (!in_array($brandType, ['regular', 'reseller_hub', 'reseller_branch'])) {
                    $brandType = 'regular';
                }

                $brand = Brand::updateOrCreate(
                    ['kode' => $brandCode],
                    [
                        'nama_brand' => $brandName,
                        'brand_type' => $brandType,
                        'tagline' => $data['tagline'] ?? '',
                        'deskripsi' => $data['deskripsi'] ?? '',
                        'email' => $data['email'] ?? strtolower($brandCode) . '@brand.local',
                        'no_hp' => $data['no_hp'] ?? '',
                        'alamat' => $data['alamat'] ?? '',
                        'warna_primary' => $data['warna_primary'] ?? '#000000',
                        'is_active' => true,
                    ]
                );

                // Auto-create CASH bank account if not exists
                if (!$brand->bankAccounts()->where('bank', 'CASH')->exists()) {
                    BankAccount::create([
                        'brand_id' => $brand->id,
                        'bank' => 'CASH',
                        'atas_nama' => 'Cash',
                        'nomor_rekening' => 'CASH',
                        'is_active' => true,
                    ]);
                }

                $imported++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            $this->error("Gagal melakukan impor: " . $e->getMessage());
            return 1;
        }

        fclose($handle);
        $this->info("Sukses! Berhasil mengimpor {$imported} brand.");
        return 0;
    }
}
