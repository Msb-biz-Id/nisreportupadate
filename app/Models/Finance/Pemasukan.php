<?php

namespace App\Models\Finance;

use App\Models\Brand;
use App\Models\Concerns\HasUuidAndSoftDeletes;
use App\Models\Order\Invoice;
use App\Models\Order\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pemasukan extends Model
{
    use HasFactory, HasUuidAndSoftDeletes;

    protected $table = 'pemasukan';

    protected $fillable = [
        'brand_id', 'kategori_pemasukan_id', 'order_id', 'invoice_id',
        'tanggal', 'nominal', 'keterangan', 'bukti', 'is_auto', 'created_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'nominal' => 'decimal:2',
        'bukti' => 'array',
        'is_auto' => 'boolean',
    ];

    public function brand(): BelongsTo { return $this->belongsTo(Brand::class); }
    public function kategori(): BelongsTo { return $this->belongsTo(KategoriPemasukan::class, 'kategori_pemasukan_id'); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
