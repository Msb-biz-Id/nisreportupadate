<?php

namespace App\Models\Hcm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class HcmPayrollItem extends Model
{
    use HasFactory;

    protected $table = 'hcm_payroll_items';

    protected $fillable = [
        'uuid',
        'payroll_id',
        'employee_id',
        'department',
        'division',
        'employment_status',
        'legal_entity',
        'bank_name',
        'bank_account_no',
        'bank_account_name',
        'base_salary',
        'meal_allowance',
        'overtime_pay',
        'increment_adjustment',
        'total_earnings',
        'penalty_deduction',
        'leave_deduction',
        'tiered_deduction',
        'total_deductions',
        'net_salary',
        'slip_token',
        'is_paid',
        'notes',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->slip_token)) {
                $model->slip_token = Str::random(40);
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

    protected function casts(): array
    {
        return [
            'base_salary' => 'float',
            'meal_allowance' => 'float',
            'overtime_pay' => 'float',
            'increment_adjustment' => 'float',
            'total_earnings' => 'float',
            'penalty_deduction' => 'float',
            'leave_deduction' => 'float',
            'tiered_deduction' => 'float',
            'total_deductions' => 'float',
            'net_salary' => 'float',
            'is_paid' => 'boolean',
        ];
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(HcmPayroll::class, 'payroll_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HcmEmployee::class, 'employee_id');
    }

    /**
     * Hitung total earnings, deductions, dan net salary.
     */
    public function computeNet(): void
    {
        $this->total_earnings = (float) ($this->base_salary + $this->meal_allowance + $this->overtime_pay + $this->increment_adjustment);
        $this->total_deductions = (float) ($this->penalty_deduction + $this->leave_deduction + $this->tiered_deduction);
        $this->net_salary = max(0, (float) ($this->total_earnings - $this->total_deductions));
    }
}
