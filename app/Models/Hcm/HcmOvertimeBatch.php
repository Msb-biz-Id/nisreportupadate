<?php

namespace App\Models\Hcm;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
