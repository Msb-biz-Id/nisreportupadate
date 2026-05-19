<?php

namespace App\Models\Order;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class POChangeLog extends Model
{
    use HasUuids;

    protected $table = 'po_change_logs';

    protected $fillable = [
        'order_id', 'changed_by', 'change_reason',
        'field_changed', 'old_value', 'new_value', 'approved_by',
    ];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function changer(): BelongsTo { return $this->belongsTo(User::class, 'changed_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
}
