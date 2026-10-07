<?php

namespace App\Models\Hcm;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property string $uuid
 * @property string $period_code
 * @property string $work_period_month
 * @property string $payout_period_month
 * @property \Carbon\Carbon|null $payout_date
 * @property int $total_employees
 * @property float $total_base_salary
 * @property float $total_meal_allowance
 * @property float $total_overtime_pay
 * @property float $total_adjustments
 * @property float $total_deductions
 * @property float $total_net_payout
 * @property string $status
 * @property \Carbon\Carbon|null $hcm_signed_at
 * @property \Carbon\Carbon|null $finance_signed_at
 * @property string|null $payment_method
 * @property string|null $payment_proof_url
 * @property string|null $notes
 */
class HcmPayroll extends Model
{
    use HasFactory;

    protected $table = 'hcm_payrolls';

    protected $fillable = [
        'uuid',
        'period_code',
        'work_period_month',
        'payout_period_month',
        'payout_date',
        'total_employees',
        'total_base_salary',
        'total_meal_allowance',
        'total_overtime_pay',
        'total_adjustments',
        'total_deductions',
        'total_net_payout',
        'status',
        'hcm_signed_by',
        'hcm_signed_at',
        'finance_signed_by',
        'finance_signed_at',
        'payment_method',
        'payment_proof_url',
        'notes',
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
            ->orWhere('period_code', $value)
            ->orWhere('id', is_numeric($value) ? (int) $value : 0)
            ->firstOrFail();
    }

    protected function casts(): array
    {
        return [
            'payout_date' => 'date',
            'hcm_signed_at' => 'datetime',
            'finance_signed_at' => 'datetime',
            'total_employees' => 'integer',
            'total_base_salary' => 'float',
            'total_meal_allowance' => 'float',
            'total_overtime_pay' => 'float',
            'total_adjustments' => 'float',
            'total_deductions' => 'float',
            'total_net_payout' => 'float',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(HcmPayrollItem::class, 'payroll_id')
            ->orderBy('department')
            ->orderBy('division');
    }

    public function hcmSigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hcm_signed_by');
    }

    public function financeSigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finance_signed_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Rekalkulasi total metrik finansial batch penggajian.
     */
    public function recalculateTotals(): void
    {
        $this->total_employees = $this->items()->count();
        $this->total_base_salary = (float) $this->items()->sum('base_salary');
        $this->total_meal_allowance = (float) $this->items()->sum('meal_allowance');
        $this->total_overtime_pay = (float) $this->items()->sum('overtime_pay');
        $this->total_adjustments = (float) $this->items()->sum('increment_adjustment');
        $this->total_deductions = (float) $this->items()->sum('total_deductions');
        $this->total_net_payout = (float) $this->items()->sum('net_salary');
        $this->save();
    }
}
