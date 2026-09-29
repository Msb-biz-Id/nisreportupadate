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
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HcmEmployee::class, 'employee_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(HcmCompensationHistory::class, 'compensation_id')->orderBy('effective_date', 'desc');
    }
}
