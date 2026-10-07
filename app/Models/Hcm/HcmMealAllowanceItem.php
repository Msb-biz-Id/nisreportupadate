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
        'uuid',
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

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

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
