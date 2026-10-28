<?php

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMcpLog extends Model
{
    use HasFactory;

    protected $table = 'ai_mcp_logs';

    public $timestamps = false;

    protected $fillable = [
        'ai_mcp_key_id',
        'client_type',
        'tool_name',
        'arguments_json',
        'execution_time_ms',
        'status',
        'error_message',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'arguments_json' => 'array',
        'execution_time_ms' => 'integer',
        'created_at' => 'datetime',
    ];

    public function key(): BelongsTo
    {
        return $this->belongsTo(AiMcpKey::class, 'ai_mcp_key_id');
    }
}
