<?php

namespace App\Models\Order;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class POLockStatus extends Model
{
    use HasUuids;

    protected $table = 'po_lock_status';

    protected $fillable = [
        'order_id',
        'is_locked',
        'locked_at',
        'locked_by',
        'unlock_requested_by',
        'unlock_request_reason',
        'unlock_requested_at',
        'relock_requested_by',
        'relock_request_reason',
        'relock_requested_at',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
        'unlock_requested_at' => 'datetime',
        'relock_requested_at' => 'datetime',
    ];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function lockedBy(): BelongsTo { return $this->belongsTo(User::class, 'locked_by'); }
    public function unlockRequestedBy(): BelongsTo { return $this->belongsTo(User::class, 'unlock_requested_by'); }
    public function relockRequestedBy(): BelongsTo { return $this->belongsTo(User::class, 'relock_requested_by'); }
}
