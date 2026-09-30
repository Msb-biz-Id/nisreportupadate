<?php

namespace App\Models\Hcm;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HcmLeaveRequest extends Model
{
    use HasFactory;

    protected $table = 'hcm_leave_requests';

    protected $fillable = [
        'uuid',
        'employee_id',
        'leave_type',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'attachment_url',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'reviewed_at' => 'datetime',
        'total_days' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    /**
     * Non-ID Base URL: Gunakan uuid sebagai route key publik (Zero Raw DB ID Exposure).
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'uuid', $value)
            ->orWhere('id', is_numeric($value) ? (int) $value : 0)
            ->firstOrFail();
    }

    /**
     * Relasi ke data Master Karyawan.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(HcmEmployee::class, 'employee_id');
    }

    /**
     * Relasi ke reviewer (HCM Manager / Supervisor).
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Relasi ke user pembuat tiket.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope pengajuan pending.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'PENDING_REVIEW');
    }

    /**
     * Scope pengajuan approved.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'APPROVED');
    }

    /**
     * Scope pengajuan aktif pada tanggal tertentu (Hari H Trigger).
     */
    public function scopeActiveOnDate(Builder $query, string $date): Builder
    {
        return $query->where('status', 'APPROVED')
                     ->where('start_date', '<=', $date)
                     ->where('end_date', '>=', $date);
    }
}
