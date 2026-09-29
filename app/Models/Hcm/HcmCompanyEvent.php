<?php

namespace App\Models\Hcm;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HcmCompanyEvent extends Model
{
    use HasFactory;

    protected $table = 'hcm_company_events';

    protected $fillable = [
        'title',
        'event_type',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'location',
        'description',
        'color_code',
        'is_public',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'is_public' => 'boolean',
    ];

    /**
     * Relasi ke user pembuat acara.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
