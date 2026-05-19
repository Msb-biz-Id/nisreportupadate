<?php

namespace App\Models\Order;

use App\Models\Master\Progress;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderProgressDetail extends Model
{
    use HasFactory, HasUuids;

    public const STATUSES = ['pending', 'on_progress', 'selesai', 'skipped'];

    protected $fillable = [
        'order_id', 'progress_id', 'status', 'catatan', 'kendala',
        'has_reject', 'started_at', 'completed_at', 'skipped_reason', 'updated_by',
    ];

    protected $casts = [
        'has_reject' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function progress(): BelongsTo { return $this->belongsTo(Progress::class); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
}
