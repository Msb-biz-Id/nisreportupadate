<?php

namespace App\Models\Order;

use App\Models\Brand;
use App\Models\Concerns\HasUuidAndSoftDeletes;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    use HasFactory, HasUuidAndSoftDeletes;

    public const JENIS_MASALAH = [
        'produk_cacat', 'ukuran_salah', 'warna_tidak_sesuai',
        'bahan_salah', 'printing_error', 'jahitan_rusak', 'lainnya',
    ];

    public const STATUSES = ['draft', 'pending_review', 'approved', 'published', 'rejected'];

    protected $fillable = [
        'brand_id', 'order_id', 'refund_number',
        'alasan', 'jenis_masalah', 'jumlah_item', 'nominal_refund',
        'bukti', 'catatan', 'status', 'rejected_reason',
        'reviewed_by', 'reviewed_at', 'published_by', 'published_at', 'created_by',
    ];

    protected $casts = [
        'bukti' => 'array',
        'reviewed_at' => 'datetime',
        'published_at' => 'datetime',
        'nominal_refund' => 'decimal:2',
        'jumlah_item' => 'integer',
    ];

    public function brand(): BelongsTo { return $this->belongsTo(Brand::class); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function publisher(): BelongsTo { return $this->belongsTo(User::class, 'published_by'); }
}
