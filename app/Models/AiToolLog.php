<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiToolLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'tool_slug', 'input', 'output',
        'tokens_used', 'model', 'status', 'error_message',
    ];

    protected $casts = [
        'input' => 'array',
        'tokens_used' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
