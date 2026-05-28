<?php

namespace App\Observers;

use App\Models\Finance\KategoriPemasukan;
use App\Models\Finance\Pemasukan;
use App\Models\Order\OrderPayment;

class OrderPaymentObserver
{
    public function created(OrderPayment $payment): void
    {
        $order = $payment->order;
        if (! $order) return;

        $label = match ($payment->payment_type) {
            'dp'         => 'DP',
            'pelunasan'  => 'Pelunasan',
            default      => 'Pembayaran Lainnya',
        };

        $kategori = KategoriPemasukan::firstOrCreate(
            ['brand_id' => $order->brand_id, 'nama_kategori' => 'Pembayaran PO'],
            [
                'deskripsi' => 'Pembayaran masuk dari PO (DP / pelunasan)',
                'is_system'  => true,
                'is_active'  => true,
            ]
        );

        Pemasukan::create([
            'brand_id'             => $order->brand_id,
            'kategori_pemasukan_id' => $kategori->id,
            'order_id'             => $order->id,
            'tanggal'              => $payment->payment_date,
            'nominal'              => $payment->amount,
            'keterangan'           => "{$label} PO {$order->no_po} — {$order->nama_po}",
            'is_auto'              => true,
            'created_by'           => $payment->recorded_by,
        ]);
    }
}
