<?php

namespace App\Models\Hcm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HcmCompensation extends Model
{
    use HasFactory;

    protected $table = 'hcm_compensations';

    protected $fillable = [
        'uuid',
        'employee_id',
        'employment_status',
        'legal_entity',
        'contract_number',
        'duration_text',
        'trainee_duration_months',
        'evaluation_cycle_months',
        'initial_salary',
        'current_salary',
        'salary_increment_count',
        'increment_1_amount',
        'increment_2_amount',
        'increment_3_amount',
        'salary_status',
        'planned_increment',
        'decision_status',
        'effective_date',
        'custom_milestone_date',
        'decision_notes',
    ];

    protected $casts = [
        'initial_salary' => 'float',
        'current_salary' => 'float',
        'salary_increment_count' => 'integer',
        'trainee_duration_months' => 'integer',
        'evaluation_cycle_months' => 'integer',
        'increment_1_amount' => 'float',
        'increment_2_amount' => 'float',
        'increment_3_amount' => 'float',
        'planned_increment' => 'float',
        'effective_date' => 'date',
        'custom_milestone_date' => 'date',
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

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HcmEmployee::class, 'employee_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(HcmCompensationHistory::class, 'compensation_id')->orderBy('effective_date', 'desc');
    }
}
