<?php

namespace App\Observers;

use App\Models\Finance\KategoriPengeluaran;
use App\Models\Finance\Pengeluaran;
use App\Models\Order\Refund;

class RefundObserver
{
    public function updated(Refund $refund): void
    {
        // Auto-record pengeluaran saat refund berstatus published
        if ($refund->isDirty('status') && $refund->status === 'published') {
            $kategori = $this->getOrCreateKategoriRefund($refund->brand_id);

            $exists = Pengeluaran::where('refund_id', $refund->id)
                ->where('kategori_pengeluaran_id', $kategori->id)
                ->exists();

            if (! $exists) {
                Pengeluaran::create([
                    'brand_id' => $refund->brand_id,
                    'kategori_pengeluaran_id' => $kategori->id,
                    'refund_id' => $refund->id,
                    'tanggal' => $refund->published_at?->toDateString() ?? now()->toDateString(),
                    'nominal' => $refund->nominal_refund,
                    'keterangan' => "Refund {$refund->refund_number} — {$refund->alasan}",
                    'is_auto' => true,
                    'created_by' => $refund->published_by ?? $refund->created_by,
                ]);
            }
        }
    }

    private function getOrCreateKategoriRefund(string $brandId): KategoriPengeluaran
    {
        return KategoriPengeluaran::firstOrCreate(
            ['brand_id' => $brandId, 'nama_kategori' => 'Refund PO'],
            [
                'deskripsi' => 'Pengeluaran otomatis dari refund PO yang diterbitkan',
                'is_system' => true,
                'is_active' => true,
            ]
        );
    }
}
