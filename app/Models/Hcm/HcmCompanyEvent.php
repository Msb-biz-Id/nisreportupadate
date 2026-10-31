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
        'uuid',
        'title',
        'event_type',
        'organizer_name',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'location',
        'target_audience',
        'reminder_days',
        'is_annual_recurring',
        'invitation_file_url',
        'description',
        'color_code',
        'is_public',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    /**
     * Non-ID Base URL: Gunakan uuid sebagai route key publik (Zero Raw DB ID Exposure).
     */
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

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'is_public' => 'boolean',
        'is_annual_recurring' => 'boolean',
        'reminder_days' => 'integer',
    ];

    /**
     * Relasi ke user pembuat acara.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
