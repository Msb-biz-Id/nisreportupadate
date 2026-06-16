<?php

namespace App\Models\Order;

use App\Models\Concerns\HasUuidAndSoftDeletes;
use App\Models\Master\BahanKain;
use App\Models\Master\Logo;
use App\Models\Master\PolaJahitan;
use App\Models\Master\Printing;
use App\Models\Master\JenisProduk;
use App\Models\Master\JenisSetelan;
use App\Models\Master\PolaProduksi;
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
        'order_id', 'product_id', 'jenis_produk_id', 'is_addon', 'nama_produk', 'varian_label',
        'quantity', 'harga_satuan', 'discount_type', 'discount_value', 'discount_amount', 'subtotal',
        'bahan_kain_id', 'bahan_kain_ids', 'bahan_kain_bawahan_id', 'bahan_kain_bawahan_ids',
        'jenis_setelan', 'jenis_setelan_id',
        'pola', 'pola_produksi_id',
        'logo_id', 'logo_ids', 'printing_id', 'resleting_id',
        'jenis_rib', 'tutup_kerah', 'list_kerah', 'list_lengan',
        'list_samping_celana', 'list_bawah_celana',
        'pola_jahitan_lengan_id', 'pola_jahitan_kerah_id',
        'pola_jahitan_bawah_id', 'pola_jahitan_pundak_id',
        'pola_jahitan_id', 'pola_jahitan_config', 'jahitan_list_lengan',
        'warna', 'jml_atasan', 'jml_bawahan',
        'gambar_desain', 'ket_atasan', 'ket_bawahan',
        'gambar_kerah', 'gambar_ket_tambahan', 'jenis_kerah', 'catatan',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'harga_satuan' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'logo_ids'                => 'array',
        'bahan_kain_ids'          => 'array',
        'bahan_kain_bawahan_ids'  => 'array',
        'pola_jahitan_config'     => 'array',
        'is_addon'                => 'boolean',
    ];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function jenisProduk(): BelongsTo    { return $this->belongsTo(JenisProduk::class, 'jenis_produk_id'); }
    public function jenisSetelan(): BelongsTo   { return $this->belongsTo(JenisSetelan::class, 'jenis_setelan_id'); }
    public function polaProduksi(): BelongsTo   { return $this->belongsTo(PolaProduksi::class, 'pola_produksi_id'); }
    public function bahanKain(): BelongsTo { return $this->belongsTo(BahanKain::class); }
    public function bahanKainBawahan(): BelongsTo { return $this->belongsTo(BahanKain::class, 'bahan_kain_bawahan_id'); }
    public function logo(): BelongsTo { return $this->belongsTo(Logo::class); }
    public function printing(): BelongsTo { return $this->belongsTo(Printing::class); }
    public function resleting(): BelongsTo { return $this->belongsTo(Resleting::class); }
    public function polaJahitan(): BelongsTo { return $this->belongsTo(PolaJahitan::class, 'pola_jahitan_id'); }
    public function polaJahitanLengan(): BelongsTo { return $this->belongsTo(PolaJahitan::class, 'pola_jahitan_lengan_id'); }
    public function namesets(): HasMany { return $this->hasMany(OrderNameset::class); }
}
