<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FileReferenceChecker
{
    /**
     * Periksa apakah path file sedang digunakan oleh setidaknya satu record di database.
     *
     * @param string $path Relative path di storage/app/public/ (misal: "orders/brand/po/ulid.jpg")
     * @return bool True jika file masih digunakan oleh setidaknya 1 referensi di DB.
     */
    public static function isReferenced(string $path): bool
    {
        if (empty($path)) {
            return false;
        }

        $cleanPath = ltrim($path, '/');
        if (str_starts_with($cleanPath, 'storage/')) {
            $cleanPath = substr($cleanPath, 8);
        }

        try {
            // 1. Periksa OrderItem
            $orderItemMatch = DB::table('order_items')
                ->where('gambar_desain', $cleanPath)
                ->orWhere('gambar_desain', 'LIKE', '%' . $cleanPath)
                ->orWhere('gambar_kerah', $cleanPath)
                ->orWhere('gambar_kerah', 'LIKE', '%' . $cleanPath)
                ->orWhere('gambar_ket_tambahan', $cleanPath)
                ->orWhere('gambar_ket_tambahan', 'LIKE', '%' . $cleanPath)
                ->exists();

            if ($orderItemMatch) {
                return true;
            }

            // 2. Periksa Brand
            $brandMatch = DB::table('brands')
                ->where('logo', $cleanPath)
                ->orWhere('logo', 'LIKE', '%' . $cleanPath)
                ->orWhere('favicon', $cleanPath)
                ->orWhere('favicon', 'LIKE', '%' . $cleanPath)
                ->exists();

            if ($brandMatch) {
                return true;
            }

            // 3. Periksa Products jika tabel ada
            if (DB::getSchemaBuilder()->hasTable('products')) {
                $productMatch = DB::table('products')
                    ->where(function ($q) use ($cleanPath) {
                        if (DB::getSchemaBuilder()->hasColumn('products', 'gambar')) {
                            $q->where('gambar', $cleanPath)
                              ->orWhere('gambar', 'LIKE', '%' . $cleanPath);
                        }
                    })
                    ->exists();

                if ($productMatch) {
                    return true;
                }
            }

            // 4. Periksa OrderPayments jika tabel ada
            if (DB::getSchemaBuilder()->hasTable('order_payments')) {
                $paymentMatch = DB::table('order_payments')
                    ->where(function ($q) use ($cleanPath) {
                        if (DB::getSchemaBuilder()->hasColumn('order_payments', 'bukti_transfer')) {
                            $q->where('bukti_transfer', $cleanPath)
                              ->orWhere('bukti_transfer', 'LIKE', '%' . $cleanPath);
                        }
                    })
                    ->exists();

                if ($paymentMatch) {
                    return true;
                }
            }

            // 5. Periksa pencarian JSON pada order_items (logo_ids)
            $driver = DB::connection()->getDriverName();
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $jsonMatch = DB::table('order_items')
                    ->whereRaw("JSON_SEARCH(logo_ids, 'one', ?) IS NOT NULL", [$cleanPath])
                    ->exists();
            } else {
                $jsonMatch = DB::table('order_items')
                    ->where('logo_ids', 'LIKE', '%' . $cleanPath . '%')
                    ->exists();
            }

            if ($jsonMatch) {
                return true;
            }

        } catch (\Throwable $e) {
            Log::warning("FileReferenceChecker::isReferenced error: " . $e->getMessage());
            // Jika ada exception query, anggap aman (return true) agar file tidak terhapus secara tidak sengaja
            return true;
        }

        return false;
    }
}
