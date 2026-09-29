<?php

namespace App\Models\Hcm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HcmOffboarding extends Model
{
    use HasFactory;

    protected $table = 'hcm_offboardings';

    protected $fillable = [
        'employee_id',
        'position',
        'exit_date',
        'exit_reason',
        'notice_compliance',
        'rights_status',
        'asset_clearance',
        'clearance_status',
        'offboarding_notes',
    ];

    protected function casts(): array
    {
        return [
            'exit_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HcmEmployee::class, 'employee_id');
    }

    /**
     * Buka rekap offboarding saat karyawan dinonaktifkan (idempotent).
     */
    public static function openFor(HcmEmployee $employee): self
    {
        return self::firstOrCreate(
            ['employee_id' => $employee->id],
            [
                'position' => $employee->position,
                'exit_date' => now()->toDateString(),
            ]
        );
    }
}
