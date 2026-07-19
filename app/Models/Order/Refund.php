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
        'customer_bank_name', 'customer_bank_account', 'bank_id',
    ];

    protected $casts = [
        'bukti' => 'array',
        'reviewed_at' => 'datetime',
        'published_at' => 'datetime',
        'nominal_refund' => 'decimal:2',
        'jumlah_item' => 'integer',
    ];

    public function getBuktiAttribute(mixed $value)
    {
        if (empty($value)) {
            return null;
        }

        $bukti = is_string($value) ? json_decode($value, true) : $value;
        if (!is_array($bukti)) {
            return $bukti;
        }

        $usesR2 = !empty(config('filesystems.disks.r2.key'));

        foreach ($bukti as &$item) {
            if (isset($item['type']) && $item['type'] === 'file') {
                if (isset($item['path'])) {
                    $diskName = $item['disk'] ?? ($usesR2 ? 'r2' : 'public');
                    try {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
                        $disk = \Illuminate\Support\Facades\Storage::disk($diskName);
                        if ($diskName === 'r2') {
                            if (env('R2_URL')) {
                                $item['url'] = rtrim(env('R2_URL'), '/') . '/' . $item['path'];
                            } else {
                                $item['url'] = $disk->temporaryUrl($item['path'], now()->addMinutes(15));
                            }
                        } else {
                            $item['url'] = '/storage/' . ltrim($item['path'], '/');
                        }
                    } catch (\Throwable $e) {
                        // Fallback to stored URL
                    }
                }
                if (isset($item['url'])) {
                    $item['url'] = \App\Support\UrlHelper::clean($item['url']);
                }
            }
        }

        return $bukti;
    }

    public function brand(): BelongsTo { return $this->belongsTo(Brand::class); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function publisher(): BelongsTo { return $this->belongsTo(User::class, 'published_by'); }
    public function bank(): BelongsTo { return $this->belongsTo(\App\Models\Master\BankAccount::class, 'bank_id'); }
}
