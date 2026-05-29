<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TrackingController extends Controller
{
    /**
     * Halaman publik tracking PO — tidak butuh login.
     * Rate-limited via middleware. Data sensitif di-mask.
     */
    public function show(Request $request, string $noPo)
    {
        $order = Order::query()
            ->with(['brand:id,nama_brand,kode,warna_primary', 'pelanggan:id,nama,nomor_hp', 'progressDetails.progress', 'items'])
            ->where('no_po', $noPo)
            ->whereNot('status_po', 'draft')
            ->first();

        return Inertia::render('Public/Track', [
            'po_number' => $noPo,
            'found' => (bool) $order,
            'order' => $order ? $this->maskOrder($order) : null,
        ])->withViewData(['title' => "Tracking $noPo"]);
    }

    private function maskOrder(Order $order): array
    {
        return [
            'no_po' => $order->no_po,
            'nama_po' => $order->nama_po,
            'status_po' => $order->status_po,
            'tanggal_masuk' => $order->tanggal_masuk?->toDateString(),
            'deadline_customer' => $order->deadline_customer?->toDateString(),
            'end_production_date' => $order->end_production_date?->toDateString(),
            'nama_ekspedisi' => $order->nama_ekspedisi,
            'no_resi'        => $order->no_resi,
            'brand' => [
                'nama_brand' => $order->brand->nama_brand,
                'kode' => $order->brand->kode,
                'warna_primary' => $order->brand->warna_primary,
            ],
            'pelanggan' => [
                'nama_initial' => $this->maskName($order->pelanggan->nama),
                'hp_masked' => $this->maskPhone($order->pelanggan->nomor_hp),
            ],
            'items' => $order->items->map(fn ($i) => [
                'nama_produk' => $i->nama_produk,
                'quantity' => $i->quantity,
            ])->all(),
            'progress' => $order->progressDetails->sortBy('progress.urutan')->values()->map(fn ($d) => [
                'nama' => $d->progress->nama_progress,
                'urutan' => $d->progress->urutan,
                'status' => $d->status,
                'warna' => $d->progress->warna,
                'completed_at' => $d->completed_at?->toDateString(),
            ])->all(),
        ];
    }

    private function maskName(string $name): string
    {
        $parts = explode(' ', trim($name));
        return collect($parts)->map(fn ($p) => mb_substr($p, 0, 1) . str_repeat('*', max(0, mb_strlen($p) - 1)))->join(' ');
    }

    private function maskPhone(?string $phone): string
    {
        if (! $phone) return '-';
        $len = strlen($phone);
        if ($len <= 4) return $phone;
        return substr($phone, 0, 3) . str_repeat('*', $len - 6) . substr($phone, -3);
    }
}
