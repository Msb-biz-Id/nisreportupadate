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
