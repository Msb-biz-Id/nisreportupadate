<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Master\Customer;
use App\Models\Master\CustomerType;
use App\Models\Settings\SystemSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ImportCustomersCommand extends Command
{
    protected $signature = 'import:customers {file : Path ke file CSV} {--force : Mengabaikan pengaturan sistem yang menonaktifkan impor}';

    protected $description = 'Import master data pelanggan dari file CSV format baku (Brand harus sudah terdaftar)';

    public function handle(): int
    {
        $force = $this->option('force');
        $importEnabled = (bool) SystemSetting::get('system', 'customer_import_enabled', false);

        if (!$importEnabled && !$force) {
            $this->error("Impor pelanggan dinonaktifkan di pengaturan sistem. Gunakan opsi --force untuk mengabaikan.");
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
            // Hapus UTF-8 BOM jika ada
            $headers[0] = preg_replace('/[\x{FEFF}\x{FFFE}\x{EFBB}\x{BFB0}]/u', '', $headers[0]);
            $headers = array_map('trim', $headers);
        }

        $headerMap = array_flip($headers);
        $required = ['customer_nama', 'customer_nomor_hp', 'brand_code'];
        foreach ($required as $req) {
            if (!isset($headerMap[$req])) {
                $this->error("Format CSV tidak valid. Kolom '{$req}' wajib ada.");
                fclose($handle);
                return 1;
            }
        }

        $imported = 0;
        $rowNum = 1;

        $this->info("Memulai proses impor pelanggan...");

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $rowNum++;
                if (empty(array_filter($row))) continue;

                $data = [];
                foreach ($headerMap as $col => $index) {
                    $data[$col] = isset($row[$index]) ? trim($row[$index]) : null;
                }

                $custNama = $data['customer_nama'];
                $custHp = $data['customer_nomor_hp'];
                $brandCode = strtoupper($data['brand_code']);

                if (!$custNama || !$custHp || !$brandCode) {
                    $this->warn("Baris {$rowNum}: Lewati karena Nama, No HP, atau Kode Brand kosong.");
                    continue;
                }

                // 1. Process Brand (Must exist)
                $brand = Brand::where('kode', $brandCode)->first();
                if (!$brand) {
                    $this->error("Baris {$rowNum}: Brand dengan kode '{$brandCode}' tidak ditemukan. Silakan buat atau impor brand terlebih dahulu.");
                    continue;
                }

                // 2. Customer Type
                $typeId = null;
                $custTypeName = $data['customer_type'] ?? 'Reguler';
                if ($custTypeName) {
                    $cType = CustomerType::where('brand_id', $brand->id)
                        ->where('nama', $custTypeName)
                        ->first();
                    if (!$cType) {
                        $cType = CustomerType::create([
                            'brand_id' => $brand->id,
                            'nama' => $custTypeName,
                            'diskon_default' => 0,
                            'is_active' => true,
                        ]);
                    }
                    $typeId = $cType->id;
                }

                // 3. Regional names to codes using local database
                $provCode = null; $provNama = $data['provinsi_nama'];
                $kabCode = null; $kabNama = $data['kabupaten_nama'];
                $kecCode = null; $kecNama = $data['kecamatan_nama'];
                $desaCode = null; $desaNama = $data['desa_nama'];

                $prefix = config('laravolt.indonesia.table_prefix', 'indonesia_');

                if ($provNama && Schema::hasTable($prefix . 'provinces')) {
                    $dbProv = DB::table($prefix . 'provinces')->where('name', 'like', "%{$provNama}%")->first();
                    if ($dbProv) {
                        $provCode = $dbProv->code;
                        $provNama = $dbProv->name;
                    }
                }
                if ($kabNama && $provCode && Schema::hasTable($prefix . 'cities')) {
                    $dbKab = DB::table($prefix . 'cities')
                        ->where('province_code', $provCode)
                        ->where('name', 'like', "%{$kabNama}%")
                        ->first();
                    if ($dbKab) {
                        $kabCode = $dbKab->code;
                        $kabNama = $dbKab->name;
                    }
                }
                if ($kecNama && $kabCode && Schema::hasTable($prefix . 'districts')) {
                    $dbKec = DB::table($prefix . 'districts')
                        ->where('city_code', $kabCode)
                        ->where('name', 'like', "%{$kecNama}%")
                        ->first();
                    if ($dbKec) {
                        $kecCode = $dbKec->code;
                        $kecNama = $dbKec->name;
                    }
                }
                if ($desaNama && $kecCode && Schema::hasTable($prefix . 'villages')) {
                    $dbDesa = DB::table($prefix . 'villages')
                        ->where('district_code', $kecCode)
                        ->where('name', 'like', "%{$desaNama}%")
                        ->first();
                    if ($dbDesa) {
                        $desaCode = $dbDesa->code;
                        $desaNama = $dbDesa->name;
                    }
                }

                // Generate customer code
                $custCode = $data['customer_code'] ?? $data['customer_kode'];
                if (!$custCode) {
                    $custCode = Customer::generateUniqueKode($brand->id);
                }

                // 4. Create or Update Customer
                Customer::updateOrCreate(
                    [
                        'brand_id' => $brand->id,
                        'nomor_hp' => $custHp,
                    ],
                    [
                        'nama' => $custNama,
                        'kode' => $custCode,
                        'email' => $data['customer_email'],
                        'type_pelanggan_id' => $typeId,
                        'provinsi_code' => $provCode,
                        'provinsi_nama' => $provNama ?: $data['provinsi_nama'],
                        'kabupaten_code' => $kabCode,
                        'kabupaten_nama' => $kabNama ?: $data['kabupaten_nama'],
                        'kecamatan_code' => $kecCode,
                        'kecamatan_nama' => $kecNama ?: $data['kecamatan_nama'],
                        'desa_code' => $desaCode,
                        'desa_nama' => $desaNama ?: $data['desa_nama'],
                        'detail_alamat' => $data['customer_detail_alamat'],
                        'kodepos' => $data['customer_kodepos'],
                        'notes' => $data['customer_notes'],
                        'is_active' => true,
                    ]
                );

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
        $this->info("Sukses! Berhasil mengimpor {$imported} pelanggan.");
        return 0;
    }
}
