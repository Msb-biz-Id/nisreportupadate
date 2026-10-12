<?php

namespace App\Models\Hcm;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $uuid
 * @property int $employee_id
 * @property string|null $contract_number
 * @property int|null $contract_sequence
 * @property string|null $employment_status
 * @property string|null $position
 * @property string|null $legal_entity
 * @property string|null $duration_text
 * @property string|null $trainee_start_month
 * @property string|null $trainee_end_month
 * @property string|null $contract_month
 * @property int|null $start_year
 * @property string|null $end_year
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property string|null $review_status
 * @property string|null $file_contract_url
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $days_remaining
 * @property-read HcmEmployee|null $employee
 */
class HcmContract extends Model
{
    use HasFactory;

    protected $table = 'hcm_contracts';

    protected $fillable = [
        'uuid',
        'employee_id',
        'contract_number',
        'contract_sequence',
        'employment_status',
        'position',
        'legal_entity',
        'duration_text',
        'trainee_start_month',
        'trainee_end_month',
        'contract_month',
        'start_year',
        'end_year',
        'start_date',
        'end_date',
        'review_status',
        'file_contract_url',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'contract_sequence' => 'integer',
        'start_year' => 'integer',
    ];

    protected $appends = ['days_remaining'];

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

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HcmEmployee::class, 'employee_id');
    }

    /**
     * Hitung sisa hari kontrak secara dinamis.
     */
    protected function daysRemaining(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->end_date) {
                    return null; // Kontrak Tetap (tanpa batas waktu)
                }

                $today = Carbon::now()->startOfDay();
                $endDate = Carbon::parse($this->end_date)->startOfDay();

                return (int) $today->diffInDays($endDate, false);
            }
        );
    }
}
