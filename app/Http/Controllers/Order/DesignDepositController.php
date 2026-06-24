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
            'customer_id' => ['required', 'uuid', 'exists:customers,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_date' => ['required', 'date'],
            'bank_id' => ['nullable', 'uuid', 'exists:bank_accounts,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $customer = \App\Models\Master\Customer::where(['brand_id' => $data['brand_id']])
            ->findOrFail($data['customer_id']);

        $data['customer_name'] = $customer->nama;

        $brand = \App\Models\Brand::findOrFail($data['brand_id']);
        $user = $request->user();

        $deposit = DB::transaction(function () use ($data, $brand, $user) {
            $deposit = DesignDeposit::create([
                ...$data,
                'deposit_number' => $this->numbers->generateDepositNumber($brand),
                'status' => 'pending',
                'recorded_by' => $user->id,
                'verified_by' => null,
                'verified_at' => null,
            ]);

            return $deposit;
        });
        \App\Services\Notifications\IdealNotificationService::dispatch('payment_submitted', [
            'no_po' => 'Tanda Jadi ' . $deposit->deposit_number,
            'brand_id' => $deposit->brand_id,
            'brand_nama' => $brand->nama_brand,
            'nominal' => 'Rp ' . number_format($deposit->amount, 0, ',', '.'),
            'action_url' => "/invoices"
        ]);

        return back()->with('success', 'Tanda Jadi (Design Deposit) berhasil disimpan. Menunggu validasi Admin Keuangan.');
    }

    public function verify(Request $request, DesignDeposit $deposit)
    {
        abort_unless(
            $request->user() && ($request->user()->isSuperadmin() || $request->user()->hasRole('admin_keuangan')),
            403, 'Hanya Admin Keuangan dan Superadmin yang dapat memverifikasi Tanda Jadi.'
        );
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
            /** @var \App\Models\Order\Invoice|null $invoice */
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
        abort_unless(
            $request->user() && ($request->user()->isSuperadmin() || $request->user()->hasRole('admin_keuangan')),
            403, 'Hanya Admin Keuangan dan Superadmin yang dapat melakukan refund Tanda Jadi.'
        );
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

    public function destroy(Request $request, DesignDeposit $deposit)
    {
        abort_unless(
            $request->user() && ($request->user()->isSuperadmin() || $request->user()->hasRole('admin_keuangan')),
            403, 'Hanya Admin Keuangan dan Superadmin yang dapat menghapus Tanda Jadi.'
        );
        abort_unless($deposit->status === 'pending', 422, 'Hanya Tanda Jadi pending yang bisa dihapus.');
        
        $deposit->delete();

        return back()->with('success', 'Tanda Jadi berhasil dihapus.');
    }
}
