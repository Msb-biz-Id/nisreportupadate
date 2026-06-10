<?php

namespace App\Models\Order;

use App\Models\Brand;
use App\Models\Concerns\HasUuidAndSoftDeletes;
use App\Models\Master\Customer;
use App\Models\Master\Iklan;
use App\Models\Master\JenisOrder;
use App\Models\Master\KategoriOrder;
use App\Models\Master\JenisSetelan;
use App\Models\Master\PaketOrder;
use App\Models\Master\PolaProduksi;
use App\Models\Master\SumberOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory, HasUuidAndSoftDeletes;

    public const STATUSES = [
        'draft', 'published', 'on_progress', 'selesai_produksi',
        'siap_dikirim', 'sudah_dikirim', 'delay', 'hold',
    ];

    protected $fillable = [
        'brand_id', 'no_po', 'nama_po', 'status_po', 'is_special_order',
        'tanggal_masuk', 'deadline_customer', 'start_production_date', 'end_production_date',
        'kategori_order_id', 'jenis_order_id', 'sumber_order_id', 'paket_order_id',
        'jenis_setelan_id', 'pola_produksi_id',
        'pelanggan_id', 'printing_ids', 'iklan_id',
        'nama_ekspedisi', 'no_resi',
        'repeat_from_po_id', 'is_repeat_order',
        'published_at', 'published_by',
        'total_tagihan', 'catatan',
        'is_lunas', 'lunas_at', 'lunas_by',
        'is_dp_bypassed', 'dp_bypassed_by', 'dp_bypassed_at',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'tanggal_masuk' => 'date',
        'deadline_customer' => 'date',
        'start_production_date' => 'date',
        'end_production_date' => 'date',
        'published_at' => 'datetime',
        'is_special_order' => 'boolean',
        'is_repeat_order' => 'boolean',
        'is_lunas' => 'boolean',
        'is_dp_bypassed' => 'boolean',
        'lunas_at' => 'datetime',
        'dp_bypassed_at' => 'datetime',
        'total_tagihan' => 'decimal:2',
        'printing_ids' => 'array',
    ];

    public function brand(): BelongsTo { return $this->belongsTo(Brand::class); }
    public function pelanggan(): BelongsTo { return $this->belongsTo(Customer::class, 'pelanggan_id'); }
    public function kategoriOrder(): BelongsTo { return $this->belongsTo(KategoriOrder::class, 'kategori_order_id'); }
    public function jenisOrder(): BelongsTo { return $this->belongsTo(JenisOrder::class, 'jenis_order_id'); }
    public function sumberOrder(): BelongsTo { return $this->belongsTo(SumberOrder::class, 'sumber_order_id'); }
    public function paketOrder(): BelongsTo  { return $this->belongsTo(PaketOrder::class, 'paket_order_id'); }
    public function jenisSetelan(): BelongsTo { return $this->belongsTo(JenisSetelan::class, 'jenis_setelan_id'); }
    public function polaProduksi(): BelongsTo { return $this->belongsTo(PolaProduksi::class, 'pola_produksi_id'); }
    public function iklan(): BelongsTo { return $this->belongsTo(Iklan::class, 'iklan_id'); }
    public function repeatFrom(): BelongsTo { return $this->belongsTo(Order::class, 'repeat_from_po_id'); }
    public function repeats(): HasMany { return $this->hasMany(Order::class, 'repeat_from_po_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function publisher(): BelongsTo { return $this->belongsTo(User::class, 'published_by'); }
    public function dpBypasser(): BelongsTo { return $this->belongsTo(User::class, 'dp_bypassed_by'); }

    public function items(): HasMany { return $this->hasMany(OrderItem::class); }
    public function payments(): HasMany { return $this->hasMany(OrderPayment::class); }
    public function progressDetails(): HasMany { return $this->hasMany(OrderProgressDetail::class); }
    public function rijeks(): HasMany { return $this->hasMany(Rijek::class); }
    public function lockStatus(): HasOne { return $this->hasOne(POLockStatus::class); }
    public function changeLogs(): HasMany { return $this->hasMany(POChangeLog::class); }
    public function invoices(): HasMany { return $this->hasMany(Invoice::class); }
    public function refunds(): HasMany { return $this->hasMany(Refund::class); }

    public function isLocked(): bool
    {
        if ($this->isDraft()) {
            return false;
        }
        if ($this->lockStatus === null) {
            return true;
        }
        return (bool) $this->lockStatus->is_locked;
    }

    public function isDraft(): bool { return $this->status_po === 'draft'; }
    public function isPublished(): bool { return ! $this->isDraft(); }

    public function totalTagihan(): float
    {
        $subtotal = (float) $this->items()->sum('subtotal');
        
        $penambahan = (float) $this->payments()
            ->whereNotNull('verified_at')
            ->whereHas('masterJenisPembayaran', function ($q) {
                $q->where('efek_tagihan', 'penambahan');
            })
            ->sum('amount');
            
        $pengurangan = (float) $this->payments()
            ->whereNotNull('verified_at')
            ->whereHas('masterJenisPembayaran', function ($q) {
                $q->where('efek_tagihan', 'pengurangan');
            })
            ->sum('amount');
        
        // Fallback for old data without master_jenis_pembayaran_id
        $ongkir = (float) $this->payments()->whereNull('master_jenis_pembayaran_id')->where('payment_type', 'ongkir')->whereNotNull('verified_at')->sum('amount');
        $tambahan = (float) $this->payments()->whereNull('master_jenis_pembayaran_id')->where('payment_type', 'tambahan_produk')->whereNotNull('verified_at')->sum('amount');
        $cashback = (float) $this->payments()->whereNull('master_jenis_pembayaran_id')->where('payment_type', 'cashback')->whereNotNull('verified_at')->sum('amount');
        $return = (float) $this->payments()->whereNull('master_jenis_pembayaran_id')->where('payment_type', 'return')->whereNotNull('verified_at')->sum('amount');
        
        return max(0, $subtotal + $penambahan + $ongkir + $tambahan - $pengurangan - $cashback - $return);
    }

    public function totalPaid(): float
    {
        $pemasukan = (float) $this->payments()
            ->whereNotNull('verified_at')
            ->whereHas('masterJenisPembayaran', function ($q) {
                $q->where('tipe_keuangan', 'pemasukan');
            })
            ->sum('amount');
            
        $pengeluaran = (float) $this->payments()
            ->whereNotNull('verified_at')
            ->whereHas('masterJenisPembayaran', function ($q) {
                $q->where('tipe_keuangan', 'pengeluaran');
            })
            ->sum('amount');
        
        // Fallback for old data without master_jenis_pembayaran_id
        $dp = (float) $this->payments()->whereNull('master_jenis_pembayaran_id')->where('payment_type', 'dp')->whereNotNull('verified_at')->sum('amount');
        $pelunasan = (float) $this->payments()->whereNull('master_jenis_pembayaran_id')->where('payment_type', 'pelunasan')->whereNotNull('verified_at')->sum('amount');
        $ongkir = (float) $this->payments()->whereNull('master_jenis_pembayaran_id')->where('payment_type', 'ongkir')->whereNotNull('verified_at')->sum('amount');
        $tambahan = (float) $this->payments()->whereNull('master_jenis_pembayaran_id')->where('payment_type', 'tambahan_produk')->whereNotNull('verified_at')->sum('amount');
        $lainnya = (float) $this->payments()->whereNull('master_jenis_pembayaran_id')->where('payment_type', 'lainnya')->whereNotNull('verified_at')->sum('amount');
        
        $return = (float) $this->payments()->whereNull('master_jenis_pembayaran_id')->where('payment_type', 'return')->whereNotNull('verified_at')->sum('amount');
        $cashback = (float) $this->payments()->whereNull('master_jenis_pembayaran_id')->where('payment_type', 'cashback')->whereNotNull('verified_at')->sum('amount');
        
        return max(0, $pemasukan + $dp + $pelunasan + $ongkir + $tambahan + $lainnya - $pengeluaran - $return - $cashback);
    }

    public function sisaTagihan(): float
    {
        return max(0, $this->totalTagihan() - $this->totalPaid());
    }

    public function scopeForBrand($q, string|array|null $brandId)
    {
        if (empty($brandId)) return $q;
        if (is_array($brandId)) {
            return empty($brandId) ? $q->whereRaw('0=1') : $q->whereIn('brand_id', $brandId);
        }
        return $q->where('brand_id', $brandId);
    }

    public function scopePublished($q)
    {
        return $q->where('status_po', '!=', 'draft');
    }
}
