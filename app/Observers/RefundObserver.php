<?php

namespace App\Observers;

use App\Models\Finance\KategoriPengeluaran;
use App\Models\Finance\Pengeluaran;
use App\Models\Order\Refund;

class RefundObserver
{
    public function created(Refund $refund): void
    {
        \App\Services\Notifications\IdealNotificationService::dispatch('refund_submitted', [
            'no_po' => $refund->order?->no_po ?? '-',
            'brand_id' => $refund->brand_id,
            'brand_nama' => $refund->brand?->nama_brand ?? 'Circle Sportwear',
            'nominal' => 'Rp ' . number_format((float) $refund->nominal_refund, 0, ',', '.'),
            'action_url' => route('refunds.index'),
        ]);
    }

    public function updated(Refund $refund): void
    {
        // Auto-record pengeluaran saat refund berstatus published
        if ($refund->isDirty('status')) {
            if ($refund->status === 'published') {
                $kategori = $this->getOrCreateKategoriRefund($refund->brand_id);

                $exists = Pengeluaran::where([
                    'refund_id' => $refund->id,
                    'kategori_pengeluaran_id' => $kategori->id,
                ])->exists();

                if (! $exists) {
                    Pengeluaran::create([
                        'brand_id' => $refund->brand_id,
                        'kategori_pengeluaran_id' => $kategori->id,
                        'refund_id' => $refund->id,
                        'tanggal' => $refund->published_at?->toDateString() ?? now()->toDateString(),
                        'nominal' => $refund->nominal_refund,
                        'keterangan' => "Refund {$refund->refund_number} PO {$refund->order?->no_po} — {$refund->alasan}",
                        'is_auto' => true,
                        'created_by' => $refund->published_by ?? $refund->created_by,
                    ]);
                }

                $bankId = $refund->bank_id;
                if (!$bankId) {
                    $bankId = \App\Models\Order\OrderPayment::where('order_id', $refund->order_id)
                        ->whereNotNull('bank_id')
                        ->orderByDesc('payment_date')
                        ->value('bank_id');
                    
                    if (!$bankId) {
                        $bankId = \App\Models\Master\BankAccount::where('brand_id', $refund->brand_id)
                            ->where('is_active', true)
                            ->value('id');
                    }
                }

                // Automatically create a verified OrderPayment of type 'return' to deduct from the order's balance
                \App\Models\Order\OrderPayment::create([
                    'order_id' => $refund->order_id,
                    'payment_type' => 'return',
                    'amount' => $refund->nominal_refund,
                    'payment_date' => $refund->published_at?->toDateString() ?? now()->toDateString(),
                    'bank_id' => $bankId,
                    'recorded_by' => $refund->published_by ?? $refund->created_by,
                    'verified_by' => $refund->published_by ?? $refund->created_by,
                    'verified_at' => $refund->published_at ?? now(),
                    'notes' => "Refund otomatis dari pengajuan: {$refund->refund_number} — {$refund->alasan}",
                    'customer_bank_name' => $refund->customer_bank_name,
                    'customer_bank_account' => $refund->customer_bank_account,
                ]);

                \App\Services\Notifications\IdealNotificationService::dispatch('refund_processed', [
                    'no_po' => $refund->order?->no_po ?? '-',
                    'brand_id' => $refund->brand_id,
                    'brand_nama' => $refund->brand?->nama_brand ?? 'Circle Sportwear',
                    'status' => 'Diterima (Published)',
                    'action_url' => route('refunds.index'),
                ]);
            } elseif ($refund->status === 'rejected') {
                \App\Services\Notifications\IdealNotificationService::dispatch('refund_processed', [
                    'no_po' => $refund->order?->no_po ?? '-',
                    'brand_id' => $refund->brand_id,
                    'brand_nama' => $refund->brand?->nama_brand ?? 'Circle Sportwear',
                    'status' => 'Ditolak',
                    'action_url' => route('refunds.index'),
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
