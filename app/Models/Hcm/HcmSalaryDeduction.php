<?php

namespace App\Models\Hcm;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class HcmSalaryDeduction extends Model
{
    use HasFactory;

    protected $table = 'hcm_salary_deductions';

    protected $fillable = [
        'uuid',
        'employee_id',
        'effective_payroll_month',
        'deduction_category',
        'calculation_type',
        'percentage_rate',
        'deduction_amount',
        'base_salary_snapshot',
        'net_salary_snapshot',
        'notes',
        'status',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
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
            'employee_id' => 'integer',
            'created_by' => 'integer',
            'percentage_rate' => 'float',
            'deduction_amount' => 'float',
            'base_salary_snapshot' => 'float',
            'net_salary_snapshot' => 'float',
        ];
    }

    /**
     * Karyawan yang dikenakan pemotongan gaji.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(HcmEmployee::class, 'employee_id');
    }

    /**
     * User yang menginput/mencatat pemotongan gaji.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope filter bulan penggajian berlaku (YYYY-MM).
     */
    public function scopeForMonth(Builder $query, string $month): Builder
    {
        return $query->where('effective_payroll_month', $month);
    }

    /**
     * Scope potongan yang disetujui / aktif berlaku.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['APPROVED', 'APPLIED']);
    }
}
