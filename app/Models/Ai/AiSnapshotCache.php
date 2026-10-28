<?php

namespace App\Models\Ai;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiSnapshotCache extends Model
{
    use HasFactory;

    protected $table = 'ai_snapshots_cache';

    protected $fillable = [
        'domain',
        'scope_key',
        'payload_json',
        'record_count',
        'generated_at',
        'expires_at',
    ];

    protected $casts = [
        'record_count' => 'integer',
        'generated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get unexpired cached snapshot payload as array
     */
    public static function getPayload(string $domain, string $scopeKey = 'all'): ?array
    {
        $cached = self::where('domain', $domain)
            ->where('scope_key', $scopeKey)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$cached) {
            return null;
        }

        return json_decode($cached->payload_json, true);
    }

    /**
     * Store or update cached snapshot
     */
    public static function setPayload(string $domain, string $scopeKey, array $data, int $ttlSeconds = 600, int $count = 0): self
    {
        return self::updateOrCreate(
            ['domain' => $domain, 'scope_key' => $scopeKey],
            [
                'payload_json' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'record_count' => $count > 0 ? $count : (isset($data['count']) ? (int)$data['count'] : count($data)),
                'generated_at' => now(),
                'expires_at' => now()->addSeconds($ttlSeconds),
            ]
        );
    }

    /**
     * Clear domain snapshot cache
     */
    public static function purgeDomain(string $domain): void
    {
        self::where('domain', $domain)->delete();
    }
}
