<?php

namespace App\Models\Hcm;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class HcmShiftRoster extends Model
{
    use HasFactory;

    protected $table = 'hcm_shift_rosters';

    protected $fillable = [
        'uuid',
        'employee_id',
        'shift_id',
        'roster_date',
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
            ->orWhere('id', is_numeric($value) ? (int) $value : 0)
            ->firstOrFail();
    }

    protected function casts(): array
    {
        return [
            'roster_date' => 'date:Y-m-d',
            'employee_id' => 'integer',
            'shift_id' => 'integer',
            'created_by' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HcmEmployee::class, 'employee_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(HcmWorkShift::class, 'shift_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('roster_date', $date);
    }

    public function scopeForDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('roster_date', [$startDate, $endDate]);
    }

    public function scopeForMonth(Builder $query, int $year, int $month): Builder
    {
        return $query->whereYear('roster_date', $year)
                     ->whereMonth('roster_date', $month);
    }
}
