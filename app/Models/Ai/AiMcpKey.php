<?php

namespace App\Models\Ai;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AiMcpKey extends Model
{
    use HasFactory;

    protected $table = 'ai_mcp_keys';

    protected $fillable = [
        'name',
        'key_hash',
        'key_prefix',
        'client_type',
        'allowed_brands',
        'is_active',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'allowed_brands' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(AiMcpLog::class, 'ai_mcp_key_id');
    }

    /**
     * Generate token key baru dan kembalikan token rahasia plaintext.
     *
     * @return array{model: self, plain_text_token: string}
     */
    public static function generateKey(
        string $name,
        string $clientType = 'universal',
        ?array $scopes = ['*'],
        ?int $userId = null,
        ?\DateTimeInterface $expiresAt = null
    ): array {
        $rawSecret = Str::random(40);
        $plainToken = 'mcp_live_' . $rawSecret;
        $keyPrefix = substr($plainToken, 0, 16);
        $keyHash = hash('sha256', $plainToken);

        $key = self::create([
            'name' => $name,
            'key_hash' => $keyHash,
            'key_prefix' => $keyPrefix,
            'client_type' => $clientType,
            'allowed_brands' => null,
            'is_active' => true,
            'expires_at' => $expiresAt,
        ]);

        return [
            'model' => $key,
            'plain_text_token' => $plainToken,
        ];
    }

    /**
     * Catat penggunaan token
     */
    public function recordUsage(): void
    {
        $this->update(['last_used_at' => now()]);
    }
}
