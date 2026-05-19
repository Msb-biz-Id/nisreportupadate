<?php

namespace App\Models\Order;

use App\Models\Master\BankAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPayment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'order_id', 'payment_type', 'amount', 'payment_date',
        'bank_id', 'proof_file', 'notes',
        'recorded_by', 'verified_by', 'verified_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'verified_at' => 'datetime',
    ];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function bank(): BelongsTo { return $this->belongsTo(BankAccount::class, 'bank_id'); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }
}
