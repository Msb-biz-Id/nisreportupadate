<?php

namespace App\Models\Order;

use App\Models\Master\Progress;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rijek extends Model
{
    use HasFactory, HasUuids;

    public const JENIS = ['sablon', 'printing', 'jahit', 'ukuran', 'lain'];
    public const TINGKAT = ['ringan', 'sedang', 'berat'];
    public const STATUSES = ['pending', 'proses', 'selesai'];

    protected $fillable = [
        'order_id', 'progress_id', 'order_item_id',
        'jumlah', 'jenis', 'tingkat', 'kendala', 'penanganan',
        'biaya_ganti', 'status', 'verified_by', 'created_by',
    ];

    protected $casts = [
        'jumlah' => 'integer',
        'biaya_ganti' => 'decimal:2',
    ];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function progress(): BelongsTo { return $this->belongsTo(Progress::class); }
    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
