<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TrackingController extends Controller
{
    /**
     * Halaman index pencarian PO publik.
     */
    public function index()
    {
        return Inertia::render('Public/TrackIndex')
            ->withViewData(['title' => 'Lacak Progress Pesanan'])
            ->toResponse(request())
            ->withHeaders([
                'X-Robots-Tag' => 'noindex, nofollow, noarchive, nosnippet',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
            ]);
    }

    /**
     * Halaman publik tracking PO — tidak butuh login.
     * Rate-limited via middleware. Data sensitif di-mask.
     */
    public function show(Request $request, string $noPo)
    {
        $order = Order::query()
            ->with(['brand:id,nama_brand,kode,warna_primary,logo,instagram,whatsapp,no_hp', 'pelanggan:id,nama,nomor_hp', 'progressDetails.progress', 'items'])
            ->where('no_po', $noPo)
            ->where(function ($q) {
                $q->where('status_po', '!=', 'draft')
                  ->orWhereHas('invoices', function ($invQuery) {
                      $invQuery->whereIn('status', ['published', 'sent', 'paid']);
                  });
            })
            ->first();

        $invoices = [];
        if ($order) {
            $user = auth()->user();
            $isAuthorized = $user && (
                $user->isSuperadmin() ||
                $user->hasRole('owner') ||
                $user->hasPermissionTo('finance.view') ||
                $user->hasPermissionTo('finance.manage-invoice') ||
                $user->hasPermissionTo('order.view')
            );

            $invoices = \App\Models\Order\Invoice::where('order_id', $order->id)
                ->when(!$isAuthorized, function ($q) {
                    $q->whereIn('status', ['published', 'sent', 'paid']);
                })
                ->get(['invoice_number'])
                ->map(fn ($inv) => ['invoice_number' => $inv->invoice_number])
                ->all();
        }

        $brand = null;
        if ($order) {
            $brand = $order->brand;
        } else {
            $parts = explode('-', $noPo);
            if (count($parts) >= 2) {
                $brandKode = $parts[1];
                $brand = \App\Models\Brand::where('kode', $brandKode)->first(['id', 'nama_brand', 'kode', 'warna_primary', 'logo', 'instagram', 'whatsapp', 'no_hp']);
            }
        }

        return Inertia::render('Public/Track', [
            'po_number' => $noPo,
            'found' => (bool) $order,
            'order' => $order ? $this->maskOrder($order) : null,
            'invoice' => count($invoices) > 0 ? $invoices[0] : null,
            'invoices' => $invoices,
            'brand' => $brand ? [
                'nama_brand' => $brand->nama_brand,
                'kode' => $brand->kode,
                'warna_primary' => $brand->warna_primary,
                'logo' => $brand->logo,
                'instagram' => $brand->instagram,
                'whatsapp' => $brand->whatsapp ?? $brand->no_hp,
            ] : null,
        ])->withViewData(['title' => "Tracking $noPo"])
          ->toResponse(request())
          ->withHeaders([
              'X-Robots-Tag' => 'noindex, nofollow, noarchive, nosnippet',
              'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
              'Pragma' => 'no-cache',
          ]);
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
                'logo' => $order->brand->logo,
                'instagram' => $order->brand->instagram,
                'whatsapp' => $order->brand->whatsapp ?? $order->brand->no_hp,
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
