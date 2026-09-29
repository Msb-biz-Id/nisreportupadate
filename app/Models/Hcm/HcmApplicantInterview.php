<?php

namespace App\Models\Hcm;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HcmApplicantInterview extends Model
{
    use HasFactory;

    protected $table = 'hcm_applicant_interviews';

    protected $fillable = [
        'applicant_id',
        'interview_round',
        'interviewer_name',
        'interview_date',
        'interview_result',
        'age',
        'marital_status',
        'education',
        'last_experience',
        'daily_activity',
        'core_skills',
        'salary_expectation',
        'offering_status',
        'interview_decision',
        'offering_notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'interview_date' => 'date',
            'age' => 'integer',
            'salary_expectation' => 'decimal:2',
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(HcmJobApplicant::class, 'applicant_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
