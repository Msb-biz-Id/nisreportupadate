<?php

namespace App\Models\Hcm;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HcmOvertime extends Model
{
    use HasFactory;

    protected $table = 'hcm_overtimes';

    protected $fillable = [
        'batch_id',
        'overtime_date',
        'employee_id',
        'position',
        'day_type',
        'duration_hours',
        'hourly_rate',
        'first_half_rate',
        'total_amount',
        'task_description',
        'created_by',
    ];

    protected $casts = [
        'overtime_date' => 'date:Y-m-d',
        'duration_hours' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'first_half_rate' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Batch mingguan terkait.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(HcmOvertimeBatch::class, 'batch_id');
    }

    /**
     * Karyawan yang melaksanakan lembur.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(HcmEmployee::class, 'employee_id');
    }

    /**
     * User yang menginput data lembur.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
