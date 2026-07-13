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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory, HasUuidAndSoftDeletes;



    public const STATUSES = [
        'draft', 'published', 'on_progress', 'selesai_produksi',
        'siap_dikirim', 'sudah_dikirim', 'delay', 'hold', 'selesai',
    ];

    protected $fillable = [
        'brand_id', 'reseller_display_brand_id', 'no_po', 'nama_po', 'status_po', 'is_special_order', 'is_free_ongkir', 'ongkir',
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
        'is_free_ongkir' => 'boolean',
        'ongkir' => 'decimal:2',
        'is_repeat_order' => 'boolean',
        'is_lunas' => 'boolean',
        'is_dp_bypassed' => 'boolean',
        'lunas_at' => 'datetime',
        'dp_bypassed_at' => 'datetime',
        'total_tagihan' => 'decimal:2',
        'printing_ids' => 'array',
    ];

    public function brand(): BelongsTo { return $this->belongsTo(Brand::class); }
    public function resellerDisplayBrand(): BelongsTo { return $this->belongsTo(Brand::class, 'reseller_display_brand_id'); }
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
    public function versions(): HasMany { return $this->hasMany(POVersion::class); }

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
        // Ensure relations are loaded to prevent N+1 and minimize queries
        if (!$this->relationLoaded('items')) {
            $this->load('items');
        }
        if (!$this->relationLoaded('payments') || $this->payments->contains(fn($p) => !$p->relationLoaded('masterJenisPembayaran'))) {
            $this->load('payments.masterJenisPembayaran');
        }

        $subtotal = (float) $this->items->sum('subtotal');
        
        $penambahan = (float) $this->payments
            ->filter(function ($p) {
                if ($p->verified_at === null || $p->masterJenisPembayaran?->efek_tagihan !== 'penambahan') {
                    return false;
                }
                if ($this->is_free_ongkir && strtolower($p->masterJenisPembayaran?->nama ?? '') === 'ongkir') {
                    return false;
                }
                return true;
            })
            ->sum('amount');
            
        $pengurangan = (float) $this->payments
            ->filter(fn($p) => $p->verified_at !== null && $p->masterJenisPembayaran?->efek_tagihan === 'pengurangan')
            ->sum('amount');
        
        // Fallback for old data without master_jenis_pembayaran_id
        $ongkir_payment = 0.0;
        if (!$this->is_free_ongkir) {
            $ongkir_payment = (float) $this->payments
                ->filter(fn($p) => $p->verified_at !== null && $p->master_jenis_pembayaran_id === null && $p->payment_type === 'ongkir')
                ->sum('amount');
        }
            
        $tambahan = (float) $this->payments
            ->filter(fn($p) => $p->verified_at !== null && $p->master_jenis_pembayaran_id === null && $p->payment_type === 'tambahan_produk')
            ->sum('amount');
            
        $cashback = (float) $this->payments
            ->filter(fn($p) => $p->verified_at !== null && $p->master_jenis_pembayaran_id === null && $p->payment_type === 'cashback')
            ->sum('amount');
            
        $return = (float) $this->payments
            ->filter(fn($p) => $p->verified_at !== null && $p->master_jenis_pembayaran_id === null && $p->payment_type === 'return')
            ->sum('amount');
        
        $ongkirCharge = $this->is_free_ongkir ? 0.0 : (float) ($this->ongkir > 0 ? $this->ongkir : $ongkir_payment);
        
        return max(0, $subtotal + $penambahan + $ongkirCharge + $tambahan - $pengurangan - $cashback - $return);
    }

    public function totalPaid(): float
    {
        // Ensure relations are loaded to prevent N+1 and minimize queries
        if (!$this->relationLoaded('payments') || $this->payments->contains(fn($p) => !$p->relationLoaded('masterJenisPembayaran'))) {
            $this->load('payments.masterJenisPembayaran');
        }

        $pemasukan = (float) $this->payments
            ->filter(fn($p) => $p->verified_at !== null && $p->masterJenisPembayaran?->tipe_keuangan === 'pemasukan')
            ->sum('amount');
            
        $pengeluaran = (float) $this->payments
            ->filter(fn($p) => $p->verified_at !== null && $p->masterJenisPembayaran?->tipe_keuangan === 'pengeluaran')
            ->sum('amount');
        
        // Fallback for old data without master_jenis_pembayaran_id
        $dp = (float) $this->payments
            ->filter(fn($p) => $p->verified_at !== null && $p->master_jenis_pembayaran_id === null && $p->payment_type === 'dp')
            ->sum('amount');
            
        $pelunasan = (float) $this->payments
            ->filter(fn($p) => $p->verified_at !== null && $p->master_jenis_pembayaran_id === null && $p->payment_type === 'pelunasan')
            ->sum('amount');
            
        $ongkir = (float) $this->payments
            ->filter(fn($p) => $p->verified_at !== null && $p->master_jenis_pembayaran_id === null && $p->payment_type === 'ongkir')
            ->sum('amount');
            
        $tambahan = (float) $this->payments
            ->filter(fn($p) => $p->verified_at !== null && $p->master_jenis_pembayaran_id === null && $p->payment_type === 'tambahan_produk')
            ->sum('amount');
            
        $lainnya = (float) $this->payments
            ->filter(fn($p) => $p->verified_at !== null && $p->master_jenis_pembayaran_id === null && $p->payment_type === 'lainnya')
            ->sum('amount');
        
        $return = (float) $this->payments
            ->filter(fn($p) => $p->verified_at !== null && $p->master_jenis_pembayaran_id === null && $p->payment_type === 'return')
            ->sum('amount');
            
        $cashback = (float) $this->payments
            ->filter(fn($p) => $p->verified_at !== null && $p->master_jenis_pembayaran_id === null && $p->payment_type === 'cashback')
            ->sum('amount');
        
        return max(0, $pemasukan + $dp + $pelunasan + $ongkir + $tambahan + $lainnya - $pengeluaran - $return - $cashback);
    }

    public function totalReceived(): float
    {
        if (!$this->relationLoaded('payments') || $this->payments->contains(fn($p) => !$p->relationLoaded('masterJenisPembayaran'))) {
            $this->load('payments.masterJenisPembayaran');
        }

        $pemasukan = (float) $this->payments
            ->filter(fn($p) => $p->verified_at !== null && $p->masterJenisPembayaran?->tipe_keuangan === 'pemasukan')
            ->sum('amount');

        $dp = (float) $this->payments
            ->filter(fn($p) => $p->verified_at !== null && $p->master_jenis_pembayaran_id === null && $p->payment_type === 'dp')
            ->sum('amount');
            
        $pelunasan = (float) $this->payments
            ->filter(fn($p) => $p->verified_at !== null && $p->master_jenis_pembayaran_id === null && $p->payment_type === 'pelunasan')
            ->sum('amount');
            
        $ongkir = (float) $this->payments
            ->filter(fn($p) => $p->verified_at !== null && $p->master_jenis_pembayaran_id === null && $p->payment_type === 'ongkir')
            ->sum('amount');
            
        $tambahan = (float) $this->payments
            ->filter(fn($p) => $p->verified_at !== null && $p->master_jenis_pembayaran_id === null && $p->payment_type === 'tambahan_produk')
            ->sum('amount');
            
        $lainnya = (float) $this->payments
            ->filter(fn($p) => $p->verified_at !== null && $p->master_jenis_pembayaran_id === null && $p->payment_type === 'lainnya')
            ->sum('amount');

        return $pemasukan + $dp + $pelunasan + $ongkir + $tambahan + $lainnya;
    }

    public function totalRefunded(): float
    {
        if (!$this->relationLoaded('payments') || $this->payments->contains(fn($p) => !$p->relationLoaded('masterJenisPembayaran'))) {
            $this->load('payments.masterJenisPembayaran');
        }

        $pengeluaran = (float) $this->payments
            ->filter(fn($p) => $p->verified_at !== null && $p->masterJenisPembayaran?->tipe_keuangan === 'pengeluaran')
            ->sum('amount');

        $return = (float) $this->payments
            ->filter(fn($p) => $p->verified_at !== null && $p->master_jenis_pembayaran_id === null && $p->payment_type === 'return')
            ->sum('amount');
            
        $cashback = (float) $this->payments
            ->filter(fn($p) => $p->verified_at !== null && $p->master_jenis_pembayaran_id === null && $p->payment_type === 'cashback')
            ->sum('amount');

        return $pengeluaran + $return + $cashback;
    }

    public function sisaTagihan(): float
    {
        return max(0, $this->totalTagihan() - $this->totalPaid());
    }

    public function getTotalTagihanAttribute(mixed $value)
    {
        return (float) $value;
    }

    public function scopeForBrand(Builder $q, string|array|null $brandId)
    {
        if (empty($brandId) || $brandId === 'all') return $q;
        if (is_array($brandId)) {
            return empty($brandId) ? $q->whereRaw('0=1') : $q->whereIn('brand_id', $brandId);
        }
        return $q->where(['brand_id' => $brandId]);
    }

    public function scopePublished(Builder $q)
    {
        $statusPoCol = 'status_po';
        return $q->where($statusPoCol, '!=', 'draft');
    }

    public function resolveResellerBrand(): ?Brand
    {
        // 1. If the order's brand is already a reseller brand entity, return it.
        $currentBrand = $this->brand;
        if ($currentBrand && $currentBrand->isReseller()) {
            return $currentBrand;
        }

        // 2. Otherwise, check if the pelanggan is a reseller brand by name.
        if ($this->pelanggan) {
            $matchedBrand = Brand::whereIn('brand_type', [Brand::TYPE_RESELLER_HUB, Brand::TYPE_RESELLER_BRANCH])
                ->where('parent_brand_id', $this->brand_id)
                ->whereRaw('LOWER(nama_brand) = ?', [strtolower($this->pelanggan->nama)])
                ->first();
            if ($matchedBrand) {
                return $matchedBrand;
            }
        }

        // 3. Otherwise, check if the creator belongs to a reseller brand
        //    that is a child of THIS order's brand (prevent cross-brand contamination).
        if ($this->creator) {
            $creatorResellerBrand = $this->creator->brands()
                ->whereIn('brand_type', [Brand::TYPE_RESELLER_HUB, Brand::TYPE_RESELLER_BRANCH])
                ->where('parent_brand_id', $this->brand_id)
                ->first();
            if ($creatorResellerBrand) {
                return $creatorResellerBrand;
            }
        }

        return null;
    }

    /**
     * Check if the order has missed its deadline (admin brand or shipping).
     */
    public function isMissedDeadline(): bool
    {
        if ($this->status_po === 'delay') {
            return true;
        }

        if ($this->deadline_customer && $this->deadline_customer instanceof \Carbon\Carbon && $this->deadline_customer->isPast()) {
            if (!in_array($this->status_po, ['sudah_dikirim', 'selesai'], true)) {
                return true;
            }
        }

        if ($this->end_production_date && $this->end_production_date instanceof \Carbon\Carbon && $this->end_production_date->isPast()) {
            if (!in_array($this->status_po, ['selesai_produksi', 'siap_dikirim', 'sudah_dikirim', 'selesai'], true)) {
                return true;
            }
        }

        return false;
    }
}

