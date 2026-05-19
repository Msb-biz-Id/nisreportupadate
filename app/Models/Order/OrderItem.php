<?php

namespace App\Models\Order;

use App\Models\Concerns\HasUuidAndSoftDeletes;
use App\Models\Master\BahanKain;
use App\Models\Master\Logo;
use App\Models\Master\PolaJahitan;
use App\Models\Master\Printing;
use App\Models\Master\Product;
use App\Models\Master\Resleting;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'order_id', 'product_id', 'nama_produk', 'varian_label',
        'quantity', 'harga_satuan', 'subtotal',
        'bahan_kain_id', 'jenis_setelan',
        'logo_id', 'printing_id', 'resleting_id',
        'pola_jahitan_lengan_id', 'pola_jahitan_kerah_id',
        'pola_jahitan_bawah_id', 'pola_jahitan_pundak_id',
        'warna', 'gambar_desain', 'gambar_kerah', 'jenis_kerah', 'catatan',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'harga_satuan' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function bahanKain(): BelongsTo { return $this->belongsTo(BahanKain::class); }
    public function logo(): BelongsTo { return $this->belongsTo(Logo::class); }
    public function printing(): BelongsTo { return $this->belongsTo(Printing::class); }
    public function resleting(): BelongsTo { return $this->belongsTo(Resleting::class); }
    public function namesets(): HasMany { return $this->hasMany(OrderNameset::class); }
}
