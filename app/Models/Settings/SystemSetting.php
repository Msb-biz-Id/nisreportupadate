<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SystemSetting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'is_encrypted', 'description'];

    protected $casts = ['is_encrypted' => 'boolean'];

    private const CACHE_KEY = 'system_settings.all';

    public static function get(string $group, string $key, $default = null)
    {
        $all = Cache::remember(self::CACHE_KEY, 300, fn () => self::all()->map(function ($s) {
            return [
                'group' => $s->group, 'key' => $s->key,
                'value' => $s->is_encrypted && $s->value ? rescue(fn () => Crypt::decryptString($s->value), null, false) : $s->value,
            ];
        }));

        $row = collect($all)->firstWhere(fn ($r) => $r['group'] === $group && $r['key'] === $key);
        return $row['value'] ?? $default;
    }

    public static function set(string $group, string $key, ?string $value, bool $encrypted = false, ?string $description = null): self
    {
        $stored = $value;
        if ($encrypted && $value !== null && $value !== '') {
            $stored = Crypt::encryptString($value);
        }

        $setting = self::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $stored, 'is_encrypted' => $encrypted, 'description' => $description],
        );

        Cache::forget(self::CACHE_KEY);
        return $setting;
    }

    public static function getGroup(string $group): array
    {
        return self::where('group', $group)->get()->mapWithKeys(function ($s) {
            $val = $s->is_encrypted && $s->value
                ? rescue(fn () => Crypt::decryptString($s->value), null, false)
                : $s->value;
            return [$s->key => $val];
        })->all();
    }

    public static function maskedValue(?string $value): ?string
    {
        if (! $value) return null;
        $len = strlen($value);
        if ($len <= 8) return str_repeat('•', $len);
        return substr($value, 0, 4) . str_repeat('•', max(4, $len - 8)) . substr($value, -4);
    }
}
