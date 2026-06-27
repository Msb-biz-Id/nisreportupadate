<?php
 
namespace App\Models\Order;
 
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
 
class POVersion extends Model
{
    use HasUuids;
 
    protected $table = 'po_versions';
 
    protected $fillable = [
        'order_id',
        'version',
        'metadata',
        'created_by',
        'change_reason',
    ];
 
    protected $casts = [
        'metadata' => 'array',
    ];
 
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
 
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
