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
 * @property Carbon|null $period_start
 * @property Carbon|null $period_end
 * @property Carbon|null $payout_date
 * @property float $total_hours
 * @property float $total_amount
 * @property string $status
 * @property int|null $hcm_signed_by
 * @property Carbon|null $hcm_signed_at
 * @property int|null $finance_signed_by
 * @property Carbon|null $finance_signed_at
 * @property string|null $payment_method
 * @property string|null $coa_code
 * @property string|null $finance_notes
 * @property string|null $payout_proof_url
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|HcmOvertime[] $overtimes
 * @property-read \Illuminate\Database\Eloquent\Collection|HcmOvertime[] $items
 * @property-read User|null $hcmSigner
 * @property-read User|null $financeSigner
 * @property-read User|null $creator
 */
class HcmOvertimeBatch extends Model
{
    use HasFactory;

    protected $table = 'hcm_overtime_batches';

    protected $fillable = [
        'batch_code',
        'period_start',
        'period_end',
        'payout_date',
        'total_hours',
        'total_amount',
        'status',
        'hcm_signed_by',
        'hcm_signed_at',
        'finance_signed_by',
        'finance_signed_at',
        'payment_method',
        'coa_code',
        'finance_notes',
        'payout_proof_url',
        'created_by',
    ];

    protected $casts = [
        'period_start' => 'date:Y-m-d',
        'period_end' => 'date:Y-m-d',
        'payout_date' => 'date:Y-m-d',
        'total_hours' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'hcm_signed_at' => 'datetime',
        'finance_signed_at' => 'datetime',
    ];

    /**
     * Rincian lembur karyawan di dalam batch ini.
     */
    public function overtimes(): HasMany
    {
        return $this->hasMany(HcmOvertime::class, 'batch_id')->orderBy('overtime_date')->orderBy('id');
    }

    /**
     * Alias relasi items untuk template voucher / laporan.
     */
    public function items(): HasMany
    {
        return $this->overtimes();
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
     * User pembuat batch.
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
