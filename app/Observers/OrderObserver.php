<?php

namespace App\Observers;

use App\Models\Finance\KategoriPemasukan;
use App\Models\Finance\Pemasukan;
use App\Models\Order\Order;

class OrderObserver
{
    public function updated(Order $order): void
    {
        // Auto-record pemasukan saat status berubah dari draft → published
        if ($order->isDirty('status_po')
            && $order->getOriginal('status_po') === 'draft'
            && $order->status_po !== 'draft'
        ) {
            $kategori = $this->getOrCreateKategoriPoPublished($order->brand_id);

            // Cek apakah sudah pernah dibuat (idempotent)
            $exists = Pemasukan::where('order_id', $order->id)
                ->where('kategori_pemasukan_id', $kategori->id)
                ->exists();

            if (! $exists) {
                Pemasukan::create([
                    'brand_id' => $order->brand_id,
                    'kategori_pemasukan_id' => $kategori->id,
                    'order_id' => $order->id,
                    'tanggal' => $order->published_at?->toDateString() ?? now()->toDateString(),
                    'nominal' => $order->total_tagihan,
                    'keterangan' => "Pemasukan dari PO {$order->no_po} — {$order->nama_po}",
                    'is_auto' => true,
                    'created_by' => $order->published_by ?? $order->created_by,
                ]);
            }
        }
    }

    private function getOrCreateKategoriPoPublished(string $brandId): KategoriPemasukan
    {
        return KategoriPemasukan::firstOrCreate(
            ['brand_id' => $brandId, 'nama_kategori' => 'PO Published'],
            [
                'deskripsi' => 'Pemasukan otomatis dari PO yang diterbitkan',
                'is_system' => true,
                'is_active' => true,
            ]
        );
    }
}
