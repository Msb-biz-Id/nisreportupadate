<?php

namespace App\Models\Hcm;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HcmAttendance extends Model
{
    use HasFactory;

    protected $table = 'hcm_attendances';

    protected $fillable = [
        'attendance_date',
        'employee_id',
        'position',
        'attendance_category',
        'clock_in',
        'clock_out',
        'notes',
        'attachment_status',
        'attachment_url',
        'created_by',
    ];

    protected $casts = [
        'attendance_date' => 'date:Y-m-d',
    ];

    protected $appends = [
        'late_minutes',
    ];

    /**
     * Hitung estimasi keterlambatan (menit) berdasarkan jam masuk vs standar 08:00.
     */
    public function getLateMinutesAttribute(): int
    {
        if (!$this->clock_in) {
            return 0;
        }

        $clockIn = \Carbon\Carbon::parse($this->clock_in);
        $standard = \Carbon\Carbon::parse('08:00:00');

        return $clockIn->gt($standard) ? $standard->diffInMinutes($clockIn) : 0;
    }

    /**
     * Relasi ke data Master Karyawan.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(HcmEmployee::class, 'employee_id');
    }

    /**
     * Relasi ke user pembuat / logger absensi.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope filter tanggal presensi.
     */
    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('attendance_date', $date);
    }

    /**
     * Scope filter bulan & tahun presensi.
     */
    public function scopeForMonth(Builder $query, int $year, int $month): Builder
    {
        return $query->whereYear('attendance_date', $year)
                     ->whereMonth('attendance_date', $month);
    }
}
