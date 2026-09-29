<?php

namespace App\Models\Hcm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HcmMealAllowanceItem extends Model
{
    use HasFactory;

    protected $table = 'hcm_meal_allowance_items';

    protected $fillable = [
        'batch_id',
        'employee_id',
        'position',
        'department',
        'base_allowance',
        'present_days',
        'late_days',
        'half_days',
        'alpha_days',
        'leave_days',
        'permit_days',
        'deduction_amount',
        'previous_hold_amount',
        'payable_amount',
        'is_hold',
        'bonus_eligible',
        'notes',
    ];

    protected $casts = [
        'base_allowance' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
        'previous_hold_amount' => 'decimal:2',
        'payable_amount' => 'decimal:2',
        'is_hold' => 'boolean',
        'bonus_eligible' => 'boolean',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(HcmMealAllowanceBatch::class, 'batch_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HcmEmployee::class, 'employee_id');
    }
}
