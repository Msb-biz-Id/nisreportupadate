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
        'shift_id',
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
        'shift_id' => 'integer',
        'employee_id' => 'integer',
        'created_by' => 'integer',
    ];

    protected $appends = [
        'late_minutes',
    ];

    /**
     * Hitung estimasi keterlambatan (menit) berdasarkan jam masuk shift kerja
     * yang ditugaskan beserta toleransi menitnya (default 15 menit).
     */
    public function getLateMinutesAttribute(): int
    {
        if (!$this->clock_in) {
            return 0;
        }

        $clockIn = \Carbon\Carbon::parse($this->clock_in);

        // Ambil jam masuk & toleransi dari shift yang terikat, atau default 08:00:00
        $shift = $this->shift;
        $standardTimeStr = $shift && $shift->start_time ? $shift->start_time : '08:00:00';
        $toleranceMinutes = $shift ? (int) $shift->late_tolerance_minutes : 15;

        $standardStartTime = \Carbon\Carbon::parse($standardTimeStr);
        $thresholdTime = (clone $standardStartTime)->addMinutes($toleranceMinutes);

        // Jika melebihi batas jam masuk + toleransi, hitung keterlambatan dari jam masuk standar
        return $clockIn->gt($thresholdTime) ? $standardStartTime->diffInMinutes($clockIn) : 0;
    }

    /**
     * Relasi ke Master Shift Kerja.
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(HcmWorkShift::class, 'shift_id');
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
