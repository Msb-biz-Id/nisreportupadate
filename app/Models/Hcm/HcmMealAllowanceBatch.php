<?php

namespace App\Models\Hcm;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $batch_code
 * @property int $period_month
 * @property int $period_year
 * @property Carbon|null $period_start
 * @property Carbon|null $period_end
 * @property Carbon|null $payout_date
 * @property int $total_employees
 * @property float $total_amount
 * @property float $total_held_amount
 * @property string $status
 * @property int|null $hcm_signed_by
 * @property Carbon|null $hcm_signed_at
 * @property int|null $finance_signed_by
 * @property Carbon|null $finance_signed_at
 * @property string|null $payment_method
 * @property string|null $coa_code
 * @property string|null $finance_notes
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|HcmMealAllowanceItem[] $items
 * @property-read User|null $hcmSigner
 * @property-read User|null $financeSigner
 * @property-read User|null $creator
 */
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
