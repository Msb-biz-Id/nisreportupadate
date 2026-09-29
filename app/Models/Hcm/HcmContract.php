<?php

namespace App\Models\Hcm;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HcmContract extends Model
{
    use HasFactory;

    protected $table = 'hcm_contracts';

    protected $fillable = [
        'employee_id',
        'contract_number',
        'contract_sequence',
        'employment_status',
        'position',
        'legal_entity',
        'duration_text',
        'trainee_start_month',
        'trainee_end_month',
        'contract_month',
        'start_year',
        'end_year',
        'start_date',
        'end_date',
        'review_status',
        'file_contract_url',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'contract_sequence' => 'integer',
        'start_year' => 'integer',
    ];

    protected $appends = ['days_remaining'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HcmEmployee::class, 'employee_id');
    }

    /**
     * Hitung sisa hari kontrak secara dinamis.
     */
    protected function daysRemaining(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->end_date) {
                    return null; // Kontrak Tetap (tanpa batas waktu)
                }

                $today = Carbon::now()->startOfDay();
                $endDate = Carbon::parse($this->end_date)->startOfDay();

                return (int) $today->diffInDays($endDate, false);
            }
        );
    }
}
