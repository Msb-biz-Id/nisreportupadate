<?php

namespace App\Models\Finance;

use App\Models\Brand;
use App\Models\Concerns\HasUuidAndSoftDeletes;
use App\Models\Order\Refund;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengeluaran extends Model
{
    use HasFactory, HasUuidAndSoftDeletes;

    protected $table = 'pengeluaran';

    protected $fillable = [
        'brand_id', 'kategori_pengeluaran_id', 'refund_id', 'source_payment_id',
        'tanggal', 'nominal', 'keterangan', 'bukti', 'is_auto', 'created_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'nominal' => 'decimal:2',
        'bukti' => 'array',
        'is_auto' => 'boolean',
    ];

    public function brand(): BelongsTo { return $this->belongsTo(Brand::class); }
    public function kategori(): BelongsTo { return $this->belongsTo(KategoriPengeluaran::class, 'kategori_pengeluaran_id'); }
    public function refund(): BelongsTo { return $this->belongsTo(Refund::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
