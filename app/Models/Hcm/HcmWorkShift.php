<?php

namespace App\Models\Hcm;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class HcmWorkShift extends Model
{
    use HasFactory;

    protected $table = 'hcm_work_shifts';

    protected $fillable = [
        'uuid',
        'name',
        'shift_code',
        'start_time',
        'end_time',
        'late_tolerance_minutes',
        'color_code',
        'is_overnight',
        'is_off_day',
        'description',
        'is_active',
        'created_by',
    ];

    protected $appends = [
        'time_range_formatted',
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
            'late_tolerance_minutes' => 'integer',
            'is_overnight' => 'boolean',
            'is_off_day' => 'boolean',
            'is_active' => 'boolean',
            'created_by' => 'integer',
        ];
    }

    /**
     * Label rentang jam kerja format ramah tampilan.
     */
    public function getTimeRangeFormattedAttribute(): string
    {
        if ($this->is_off_day) {
            return 'Libur / Off';
        }

        $start = $this->start_time ? substr($this->start_time, 0, 5) : '--:--';
        $end = $this->end_time ? substr($this->end_time, 0, 5) : '--:--';

        return "{$start} - {$end}" . ($this->is_overnight ? ' (+1)' : '');
    }

    public function rosters(): HasMany
    {
        return $this->hasMany(HcmShiftRoster::class, 'shift_id');
    }

    public function defaultEmployees(): HasMany
    {
        return $this->hasMany(HcmEmployee::class, 'default_shift_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(HcmAttendance::class, 'shift_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
