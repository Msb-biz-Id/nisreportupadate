<?php

namespace App\Models\Hcm;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HcmEmployeeReward extends Model
{
    use HasFactory;

    protected $table = 'hcm_employee_rewards';

    protected $fillable = [
        'uuid',
        'employee_id',
        'position',
        'reward_name',
        'reward_year',
        'distribution_status',
        'received_date',
        'document_status',
        'budget_amount',
        'proof_url',
        'notes',
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
        'received_date' => 'date:Y-m-d',
        'budget_amount' => 'decimal:2',
        'reward_year' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HcmEmployee::class, 'employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
