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
        'bahan_kain_id', 'jenis_setelan', 'pola',
        'logo_id', 'printing_id', 'resleting_id',
        'jenis_rib', 'tutup_kerah', 'list_kerah', 'list_lengan',
        'list_samping_celana', 'list_bawah_celana',
        'pola_jahitan_lengan_id', 'pola_jahitan_kerah_id',
        'pola_jahitan_bawah_id', 'pola_jahitan_pundak_id',
        'pola_jahitan_id', 'jahitan_list_lengan',
        'warna', 'jml_atasan', 'jml_bawahan',
        'gambar_desain', 'ket_atasan', 'ket_bawahan',
        'gambar_kerah', 'jenis_kerah', 'catatan',
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
    public function polaJahitan(): BelongsTo { return $this->belongsTo(PolaJahitan::class, 'pola_jahitan_id'); }
    public function namesets(): HasMany { return $this->hasMany(OrderNameset::class); }
}
