<?php

namespace App\Models\Hcm;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HcmMealAllowanceBatch extends Model
{
    use HasFactory;

    protected $table = 'hcm_meal_allowance_batches';

    protected $fillable = [
        'batch_code',
        'period_month',
        'period_year',
        'period_start',
        'period_end',
        'payout_date',
        'total_employees',
        'total_amount',
        'total_held_amount',
        'status',
        'hcm_signed_by',
        'hcm_signed_at',
        'finance_signed_by',
        'finance_signed_at',
        'payment_method',
        'coa_code',
        'finance_notes',
        'created_by',
    ];

    protected $casts = [
        'period_start' => 'date:Y-m-d',
        'period_end' => 'date:Y-m-d',
        'payout_date' => 'date:Y-m-d',
        'total_amount' => 'decimal:2',
        'total_held_amount' => 'decimal:2',
        'hcm_signed_at' => 'datetime',
        'finance_signed_at' => 'datetime',
    ];

    /**
     * Rincian per karyawan di dalam batch ini.
     */
    public function items(): HasMany
    {
        return $this->hasMany(HcmMealAllowanceItem::class, 'batch_id')->orderBy('id');
    }

    /**
     * Sign-off HCM.
     */
    public function hcmSigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hcm_signed_by');
    }

    /**
     * Sign-off Keuangan.
     */
    public function financeSigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finance_signed_by');
    }

    /**
     * Pembuat batch.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Non-ID Base URL: Gunakan batch_code sebagai route key publik (Zero Raw DB ID Exposure).
     */
    public function getRouteKeyName(): string
    {
        return 'batch_code';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? 'batch_code', $value)
            ->orWhere('id', is_numeric($value) ? (int) $value : 0)
            ->firstOrFail();
    }
}
