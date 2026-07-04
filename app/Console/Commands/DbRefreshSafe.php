<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DbRefreshSafe extends Command
{
    /**
     * Nama command Artisan.
     */
    protected $signature = 'db:refresh-safe';

    /**
     * Deskripsi command.
     */
    protected $description = 'Melakukan backup data transaksi/order manual, me-refresh database (migrate:fresh), lalu memulihkan data tersebut secara otomatis';

    /**
     * Eksekusi command.
     */
    public function handle()
    {
        $this->info('=== MEMULAI REFRESH DATABASE SECARA AMAN ===');

        $backupPath = app()->environment('testing')
            ? database_path('backup_orders_test.json')
            : database_path('backup_orders.json');
        
        $tables = [
            'kategori_pemasukan',
            'kategori_pengeluaran',
            'customers',
            'orders',
            'order_items',
            'order_namesets',
            'order_payments',
            'order_progress_details',
            'po_lock_statuses',
            'po_versions',
            'invoices',
            'invoice_items',
            'rijeks',
            'refunds',
            'pemasukan',
            'pengeluaran',
        ];

        $backupData = [];
        $hasData = false;

        $this->info('Membaca data dari database...');
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $rows = DB::table($table)->get()->map(function ($row) {
                    return (array) $row;
                })->toArray();

                if (!empty($rows)) {
                    $backupData[$table] = $rows;
                    $hasData = true;
                    $this->line("- Mengamankan " . count($rows) . " baris dari tabel {$table}");
                }
            }
        }

        if ($hasData) {
            file_put_contents($backupPath, json_encode($backupData, JSON_PRETTY_PRINT));
            $this->info("Backup berhasil disimpan ke: " . $backupPath);
        } else {
            $this->warn("Tidak ada data transaksi manual untuk dibackup.");
            if (file_exists($backupPath)) {
                @unlink($backupPath);
            }
        }

        $this->info('Menjalankan perintah migrate:fresh --seed...');
        $this->call('migrate:fresh', [
            '--seed' => true,
        ]);

        if (file_exists($backupPath)) {
            $this->info('Memulihkan data transaksi/order dari backup...');
            $backupData = json_decode(file_get_contents($backupPath), true);
            
            Schema::disableForeignKeyConstraints();
            foreach ($tables as $table) {
                if (isset($backupData[$table]) && !empty($backupData[$table])) {
                    DB::table($table)->truncate();
                    
                    $chunks = array_chunk($backupData[$table], 100);
                    foreach ($chunks as $chunk) {
                        DB::table($table)->insert($chunk);
                    }
                    $this->line("- Berhasil memulihkan " . count($backupData[$table]) . " baris ke tabel {$table}");
                }
            }
            Schema::enableForeignKeyConstraints();

            @unlink($backupPath);
        }

        $this->info('=== PROSES SELESAI DENGAN SUKSES ===');
        return self::SUCCESS;
    }
}
