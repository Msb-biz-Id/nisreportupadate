<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order\DesignDeposit;
use App\Models\Order\Order;
use App\Models\Order\OrderPayment;
use App\Models\Finance\KategoriPemasukan;
use App\Models\Finance\Pemasukan;
use App\Models\Finance\KategoriPengeluaran;
use App\Models\Finance\Pengeluaran;
use App\Services\NumberGenerator;
use App\Support\BrandContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class DesignDepositController extends Controller
{
    public function __construct(private readonly NumberGenerator $numbers) {}

    public function store(Request $request)
    {
        $data = $request->validate([
            'brand_id' => ['required', 'uuid', 'exists:brands,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_date' => ['required', 'date'],
            'bank_id' => ['nullable', 'uuid', 'exists:bank_accounts,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $brand = \App\Models\Brand::findOrFail($data['brand_id']);
        $user = $request->user();
        $isFinanceOrAdmin = $user->hasRole('superadmin') || $user->hasRole('owner') || $user->hasRole('admin_keuangan');

        $deposit = DB::transaction(function () use ($data, $brand, $user, $isFinanceOrAdmin) {
            $deposit = DesignDeposit::create([
                ...$data,
                'deposit_number' => $this->numbers->generateDepositNumber($brand),
                'status' => $isFinanceOrAdmin ? 'verified' : 'pending',
                'recorded_by' => $user->id,
                'verified_by' => $isFinanceOrAdmin ? $user->id : null,
                'verified_at' => $isFinanceOrAdmin ? now() : null,
            ]);

            if ($isFinanceOrAdmin) {
                $this->recordLedgerPemasukan($deposit);
            }

            return $deposit;
        });

        if (!$isFinanceOrAdmin) {
            \App\Services\Notifications\DynamicNotificationService::dispatch('payment_submitted', [
                'no_po' => 'Tanda Jadi ' . $deposit->deposit_number,
                'brand_id' => $deposit->brand_id,
                'brand_nama' => $brand->nama_brand,
                'nominal' => 'Rp ' . number_format($deposit->amount, 0, ',', '.'),
                'action_url' => "/invoices"
            ]);
            return back()->with('success', 'Tanda Jadi (Design Deposit) berhasil disimpan. Menunggu validasi Admin Keuangan.');
        }

        return back()->with('success', 'Tanda Jadi (Design Deposit) berhasil disimpan dan diverifikasi.');
    }

    public function verify(Request $request, DesignDeposit $deposit)
    {
        Gate::authorize('finance.manage-invoice');
        abort_unless($deposit->status === 'pending', 422, 'Hanya status pending yang bisa diverifikasi.');

        DB::transaction(function () use ($deposit, $request) {
            $deposit->update([
                'status' => 'verified',
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
            ]);

            $this->recordLedgerPemasukan($deposit);
        });

        return back()->with('success', 'Tanda Jadi berhasil diverifikasi.');
    }

    public function convertToOrder(Request $request, DesignDeposit $deposit)
    {
        Gate::authorize('order.update');
        abort_unless($deposit->status === 'verified', 422, 'Hanya Tanda Jadi terverifikasi yang bisa dikonversi ke PO.');

        $data = $request->validate([
            'order_id' => ['required', 'uuid', 'exists:orders,id'],
        ]);

        $order = Order::findOrFail($data['order_id']);

        DB::transaction(function () use ($deposit, $order, $request) {
            // Update deposit
            $deposit->update([
                'status' => 'converted',
                'converted_to_order_id' => $order->id,
                'converted_at' => now(),
            ]);

            // Create Order Payment of type 'dp'
            $payment = OrderPayment::create([
                'order_id' => $order->id,
                'payment_type' => 'dp',
                'amount' => $deposit->amount,
                'payment_date' => now()->toDateString(),
                'bank_id' => $deposit->bank_id,
                'notes' => "Konversi Tanda Jadi #{$deposit->deposit_number}",
                'recorded_by' => $request->user()->id,
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
            ]);

            // Recalculate order totals
            $order->update(['total_tagihan' => $order->totalTagihan()]);

            // Recalculate associated invoice totals if it exists
            $invoice = $order->invoices()->first();
            if ($invoice) {
                $totalTagihan = $order->totalTagihan();
                $totalPaid = $order->totalPaid();
                $newSisa = max(0, $totalTagihan - $totalPaid);
                $invoice->update([
                    'total_tagihan' => $totalTagihan,
                    'total_bayar' => $totalPaid,
                    'sisa_pembayaran' => $newSisa,
                    'status' => $newSisa <= 0 ? 'paid' : $invoice->status,
                ]);
            }
        });

        return back()->with('success', "Tanda Jadi berhasil dikonversi ke PO {$order->no_po}.");
    }

    public function refund(Request $request, DesignDeposit $deposit)
    {
        Gate::authorize('finance.manage-invoice');
        abort_unless(in_array($deposit->status, ['pending', 'verified']), 422, 'Hanya Tanda Jadi pending atau verified yang bisa direfund.');

        DB::transaction(function () use ($deposit, $request) {
            $deposit->update([
                'status' => 'refunded',
            ]);

            // Record as Pengeluaran
            $kategori = KategoriPengeluaran::firstOrCreate(
                ['brand_id' => $deposit->brand_id, 'nama_kategori' => 'Refund Tanda Jadi'],
                [
                    'deskripsi' => 'Pengembalian uang / refund tanda jadi desain',
                    'is_system'  => true,
                    'is_active'  => true,
                ]
            );

            Pengeluaran::create([
                'brand_id'               => $deposit->brand_id,
                'kategori_pengeluaran_id' => $kategori->id,
                'tanggal'                => now()->toDateString(),
                'nominal'                => $deposit->amount,
                'keterangan'             => "Refund Tanda Jadi #{$deposit->deposit_number} — {$deposit->customer_name}",
                'is_auto'                => true,
                'created_by'             => $request->user()->id,
            ]);
        });

        return back()->with('success', 'Tanda Jadi berhasil di-refund.');
    }

    private function recordLedgerPemasukan(DesignDeposit $deposit): void
    {
        $kategori = KategoriPemasukan::firstOrCreate(
            ['brand_id' => $deposit->brand_id, 'nama_kategori' => 'Tanda Jadi / DP Desain'],
            [
                'deskripsi' => 'Pembayaran tanda jadi / DP Desain sebelum PO',
                'is_system'  => true,
                'is_active'  => true,
            ]
        );

        Pemasukan::create([
            'brand_id'             => $deposit->brand_id,
            'kategori_pemasukan_id' => $kategori->id,
            'tanggal'              => $deposit->payment_date,
            'nominal'              => $deposit->amount,
            'keterangan'           => "Tanda Jadi Desain #{$deposit->deposit_number} — {$deposit->customer_name} ({$deposit->description})",
            'is_auto'              => true,
            'created_by'           => $deposit->recorded_by,
        ]);
    }
}
