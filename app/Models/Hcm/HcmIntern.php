<?php

namespace App\Models\Hcm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HcmIntern extends Model
{
    use HasFactory;

    protected $table = 'hcm_interns';

    protected $fillable = [
        'employee_id',
        'school_name',
        'class',
        'major',
        'nis',
        'start_date',
        'end_date',
        'duration_text',
        'mentor_teacher_name',
        'mentor_teacher_phone',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HcmEmployee::class, 'employee_id');
    }
}
