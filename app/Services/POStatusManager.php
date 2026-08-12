<?php

namespace App\Services;

use App\Models\Master\Progress;
use App\Models\Order\Order;
use App\Models\Order\OrderProgressDetail;
use App\Models\Order\POChangeLog;
use App\Models\Order\POLockStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class POStatusManager
{
    public function publish(Order $order, User $user): Order
    {
        if (! $order->isDraft()) {
            return $order;
        }

        DB::transaction(function () use ($order, $user) {
            $order->update([
                'status_po' => 'published',
                'published_at' => now(),
                'published_by' => $user->id,
            ]);

            $progresses = Progress::active()->ordered()->get();
            foreach ($progresses as $p) {
                OrderProgressDetail::firstOrCreate(
                    ['order_id' => $order->id, 'progress_id' => $p->id],
                    ['status' => 'pending', 'updated_by' => $user->id]
                );
            }
        });

        return $order->fresh();
    }

    /**
     * Update sebuah tahapan progress + recalculate status PO + auto-lock saat tahap pertama on_progress.
     */
    public function updateProgressDetail(
        Order $order,
        OrderProgressDetail $detail,
        string $newStatus,
        ?string $catatan,
        ?string $kendala,
        ?string $skippedReason,
        User $user
    ): OrderProgressDetail {
        DB::transaction(function () use ($order, $detail, $newStatus, $catatan, $kendala, $skippedReason, $user) {
            $detail->status = $newStatus;
            $detail->catatan = $catatan;
            $detail->kendala = $kendala;
            $detail->skipped_reason = $newStatus === 'skipped' ? $skippedReason : null;
            $detail->updated_by = $user->id;

            if ($newStatus === 'on_progress' && ! $detail->started_at) {
                $detail->started_at = \Illuminate\Support\Carbon::now();
            }
            if ($newStatus === 'selesai') {
                $detail->completed_at = \Illuminate\Support\Carbon::now();
                if (!$detail->started_at) {
                    $detail->started_at = \Illuminate\Support\Carbon::now();
                }
            }
            $detail->save();

            // Auto-lock saat tahap pertama jadi on_progress
            if ($newStatus === 'on_progress' && ! $order->lockStatus()->exists()) {
                POLockStatus::create([
                    'order_id' => $order->id,
                    'is_locked' => true,
                    'locked_at' => \Illuminate\Support\Carbon::now(),
                    'locked_by' => $user->id,
                ]);
            }

            $this->recalculateOrderStatus($order);
        });

        return $detail->fresh();
    }

    public function recalculateOrderStatus(Order $order): void
    {
        $details = $order->progressDetails()->with('progress')->get();
        if ($details->isEmpty()) return;

        $hasStarted = $details->contains(fn ($d) => in_array($d->status, ['on_progress', 'selesai', 'skipped'], true));
        $allSelesaiOrSkipped = $details->every(fn ($d) => in_array($d->status, ['selesai', 'skipped'], true));

        // Cek tahap PACKING & SENDING (by nama, sesuai BRD 5.12)
        $packing = $details->first(fn ($d) => str_contains(strtoupper($d->progress->nama_progress), 'PACKING'));
        $sending = $details->first(fn ($d) => str_contains(strtoupper($d->progress->nama_progress), 'SENDING'));

        $newStatus = $order->status_po;

        if (in_array($order->status_po, ['draft', 'selesai'], true)) {
            return;
        }

        if ($sending && $sending->status === 'selesai') {
            $newStatus = 'sudah_dikirim';
        } elseif ($packing && $packing->status === 'selesai') {
            $newStatus = 'siap_dikirim';
        } elseif ($allSelesaiOrSkipped) {
            $newStatus = 'selesai_produksi';
        } elseif ($hasStarted) {
            $newStatus = 'on_progress';
        } else {
            $newStatus = 'published';
        }

        // Delay check: deadline_customer terlewati & produksi/packing belum selesai
        if ($order->deadline_customer && \Carbon\Carbon::parse($order->deadline_customer)->isPast()
            && ! in_array($newStatus, ['selesai_produksi', 'siap_dikirim', 'sudah_dikirim', 'selesai'], true)) {
            $newStatus = 'delay';
        }

        $updateData = [];
        if ($order->status_po !== $newStatus) {
            $updateData['status_po'] = $newStatus;
        }

        // Catat data keterlambatan produksi & packing saat tahapan packing / produksi selesai
        if (in_array($newStatus, ['selesai_produksi', 'siap_dikirim', 'sudah_dikirim', 'selesai'], true)) {
            $packingCompletedAt = $order->packing_completed_at 
                ?? ($packing?->completed_at 
                    ?? ($details->where('status', 'selesai')->sortByDesc('completed_at')->first()?->completed_at ?? now()));

            if (!$order->packing_completed_at) {
                $updateData['packing_completed_at'] = $packingCompletedAt;
            }

            $endProductionDate = $order->end_production_date ?? $packingCompletedAt;
            if (!$order->end_production_date) {
                $updateData['end_production_date'] = $endProductionDate;
            }

            // Hitung Keterlambatan Produksi vs Deadline Produksi (end_production_date / deadline_customer)
            $targetProdDeadline = $order->end_production_date ?? $order->deadline_customer;
            if ($targetProdDeadline) {
                $prodDeadlineDate = \Carbon\Carbon::parse($targetProdDeadline)->startOfDay();
                $prodFinishDate = \Carbon\Carbon::parse($packingCompletedAt)->startOfDay();
                $diffProdDays = (int) $prodDeadlineDate->diffInDays($prodFinishDate, false);

                $updateData['production_days_late'] = $diffProdDays > 0 ? $diffProdDays : 0;
            }

            // Hitung Keterlambatan Customer jika PO sudah di status terminal
            if ($order->deadline_customer) {
                $deadlineDate = \Carbon\Carbon::parse($order->deadline_customer)->startOfDay();
                $completionDate = \Carbon\Carbon::parse($order->completed_at ?? $packingCompletedAt)->startOfDay();
                $diffDays = (int) $deadlineDate->diffInDays($completionDate, false);

                $wasDelayed = $diffDays > 0;
                $daysLate = $wasDelayed ? $diffDays : 0;

                $updateData['customer_days_late'] = $daysLate;
                $updateData['was_delayed_on_completion'] = $wasDelayed;
                $updateData['days_late_on_completion'] = $daysLate;
            }
        }

        if (!empty($updateData)) {
            $order->update($updateData);
        }
    }

    public function unlock(Order $order, User $user): void
    {
        $lock = $order->lockStatus;
        if ($lock) {
            $lock->update(['is_locked' => false]);
        } else {
            POLockStatus::create([
                'order_id' => $order->id,
                'is_locked' => false,
                'locked_at' => now(),
                'locked_by' => $user->id,
            ]);
        }
    }

    public function relock(Order $order, User $user): void
    {
        $lock = $order->lockStatus;
        if ($lock) {
            $lock->update(['is_locked' => true, 'locked_at' => now(), 'locked_by' => $user->id]);
        } else {
            POLockStatus::create([
                'order_id' => $order->id,
                'is_locked' => true,
                'locked_at' => now(),
                'locked_by' => $user->id,
            ]);
        }
    }

    public function logChange(Order $order, User $user, string $reason, string $field, mixed $oldValue, mixed $newValue): void
    {
        if ($order->status_po === 'selesai') {
            return;
        }

        POChangeLog::create([
            'order_id' => $order->id,
            'changed_by' => $user->id,
            'change_reason' => $reason,
            'field_changed' => $field,
            'old_value' => is_scalar($oldValue) || $oldValue === null ? (string) $oldValue : json_encode($oldValue),
            'new_value' => is_scalar($newValue) || $newValue === null ? (string) $newValue : json_encode($newValue),
        ]);
    }
}
