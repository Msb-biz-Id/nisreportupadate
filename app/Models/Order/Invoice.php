<?php

namespace App\Models\Order;

use App\Models\Brand;
use App\Models\Concerns\HasUuidAndSoftDeletes;
use App\Models\Master\BankAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory, HasUuidAndSoftDeletes;

    public const STATUSES = ['draft', 'validated', 'published', 'sent', 'paid', 'overdue', 'cancel'];

    protected $fillable = [
        'brand_id', 'order_id', 'invoice_number',
        'tanggal_terbit', 'jatuh_tempo', 'status',
        'total_tagihan', 'total_bayar', 'dp_amount', 'sisa_pembayaran',
        'diskon_type', 'diskon_value', 'biaya_pengiriman', 'jasa_pengiriman',
        'bank_id', 'catatan', 'peraturan', 'faq', 'qr_code',
        'sent_via', 'sent_at', 'created_by',
    ];

    protected $casts = [
        'tanggal_terbit' => 'date',
        'jatuh_tempo' => 'date',
        'sent_at' => 'datetime',
        'faq' => 'array',
        'total_tagihan' => 'decimal:2',
        'total_bayar' => 'decimal:2',
        'dp_amount' => 'decimal:2',
        'sisa_pembayaran' => 'decimal:2',
        'diskon_value' => 'decimal:2',
        'biaya_pengiriman' => 'decimal:2',
    ];

    public function brand(): BelongsTo { return $this->belongsTo(Brand::class); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function bank(): BelongsTo { return $this->belongsTo(BankAccount::class, 'bank_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany { return $this->hasMany(InvoiceItem::class); }
}
