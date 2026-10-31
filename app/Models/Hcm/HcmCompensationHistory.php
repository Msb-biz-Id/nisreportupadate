<?php

namespace App\Models\Hcm;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HcmCompensationHistory extends Model
{
    use HasFactory;

    protected $table = 'hcm_compensation_histories';

    protected $fillable = [
        'compensation_id',
        'employee_id',
        'previous_salary',
        'new_salary',
        'increment_amount',
        'effective_date',
        'reason',
        'approved_by',
    ];

    protected $casts = [
        'previous_salary' => 'float',
        'new_salary' => 'float',
        'increment_amount' => 'float',
        'effective_date' => 'date',
    ];

    public function compensation(): BelongsTo
    {
        return $this->belongsTo(HcmCompensation::class, 'compensation_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HcmEmployee::class, 'employee_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
