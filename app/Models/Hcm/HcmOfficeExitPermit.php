<?php

namespace App\Models\Hcm;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class HcmOfficeExitPermit extends Model
{
    use HasFactory;

    protected $table = 'hcm_office_exit_permits';

    protected $fillable = [
        'uuid',
        'employee_id',
        'permit_date',
        'exit_time',
        'return_time',
        'purpose',
        'notes',
        'attachment_status',
        'attachment_url',
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
            'permit_date' => 'date',
            'employee_id' => 'integer',
            'created_by' => 'integer',
        ];
    }

    /**
     * Karyawan yang mengajukan / diberikan izin keluar.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(HcmEmployee::class, 'employee_id');
    }

    /**
     * User admin / HCM yang mencatat izin keluar.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope untuk menyaring berdasarkan tanggal izin.
     */
    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->where('permit_date', $date);
    }

    /**
     * Scope untuk menyaring yang masih berada di luar kantor.
     */
    public function scopeOutside(Builder $query): Builder
    {
        return $query->where('status', 'Masih di Luar');
    }

    /**
     * Scope untuk menyaring yang sudah kembali ke kantor.
     */
    public function scopeReturned(Builder $query): Builder
    {
        return $query->where('status', 'Kembali');
    }

    /**
     * Durasi keluar kantor (jika sudah kembali) dalam format jam & menit.
     */
    public function getDurationTextAttribute(): ?string
    {
        if (!$this->exit_time || !$this->return_time) {
            return null;
        }

        try {
            $dateStr = Carbon::parse($this->permit_date)->format('Y-m-d');
            $exit = Carbon::parse($dateStr . ' ' . $this->exit_time);
            $return = Carbon::parse($dateStr . ' ' . $this->return_time);

            if ($return->lessThan($exit)) {
                $return->addDay();
            }

            $diffMinutes = $exit->diffInMinutes($return);
            $hours = intdiv($diffMinutes, 60);
            $minutes = $diffMinutes % 60;

            if ($hours > 0) {
                return "{$hours} jam {$minutes} mnt";
            }
            return "{$minutes} menit";
        } catch (\Throwable $e) {
            return null;
        }
    }
}
