<?php

namespace App\Observers;

use App\Models\Finance\KategoriPemasukan;
use App\Models\Finance\Pemasukan;
use App\Models\Order\Order;
use Illuminate\Support\Facades\Storage;

class OrderObserver
{
    public function deleting(Order $order): void
    {
        // Delete order item images
        foreach ($order->items as $item) {
            foreach (['gambar_desain', 'gambar_kerah', 'gambar_ket_tambahan'] as $field) {
                if ($item->$field) {
                    $this->deleteFile($item->$field);
                }
            }
        }

        // Delete order payment proof files
        foreach ($order->payments as $payment) {
            if ($payment->proof_file) {
                $this->deleteFile($payment->proof_file);
            }
        }
    }

    private function deleteFile(string $path): void
    {
        $normalizedPath = $path;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            $parsed = parse_url($path);
            $normalizedPath = ltrim($parsed['path'] ?? '', '/');
        }

        if (str_starts_with($normalizedPath, 'storage/')) {
            $normalizedPath = substr($normalizedPath, 8);
        }

        if (Storage::disk('public')->exists($normalizedPath)) {
            Storage::disk('public')->delete($normalizedPath);
        }
    }

    public function saving(Order $order): void
    {
        // Jangan setel lunas otomatis untuk pesanan yang masih draft
        if ($order->isDraft()) {
            return;
        }

        // Paksa reload relasi agar mendapatkan data item dan pembayaran paling mutakhir
        $order->load(['items', 'payments.masterJenisPembayaran']);

        // Hitung sisa tagihan secara dinamis
        $totalTagihan = $order->totalTagihan();
        $totalPaid = $order->totalPaid();
        $sisa = max(0.0, $totalTagihan - $totalPaid);

        // Jika sisa tagihan <= 0 (dan order memiliki item agar tidak melunasi order kosong dalam test/draft), otomatis tandai Lunas
        if ($order->items->isNotEmpty() && $sisa <= 0) {
            if (!$order->is_lunas) {
                $order->is_lunas = true;
                $order->lunas_at = now();
                $order->lunas_by = \Illuminate\Support\Facades\Auth::id() ?? $order->published_by ?? $order->created_by;
            }
        }
        // Jika sisa tagihan > 0, kami TIDAK mencabut status is_lunas secara otomatis
        // untuk mengamankan tindakan "Tandai Lunas" manual oleh admin keuangan (toleransi manual).

        // Bersihkan cache relasi agar tidak menahan data kosong dalam memori
        $order->unsetRelation('items');
        $order->unsetRelation('payments');
    }

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
