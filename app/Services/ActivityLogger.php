<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Support\BrandContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ActivityLogger
{
    /**
     * Catat aktivitas. Otomatis ambil user, brand, ip, user_agent dari request.
     *
     * @param  string  $activity  create|update|delete|publish|login|logout|export|...
     * @param  string  $module    brand|user|order|refund|invoice|master|ai|settings|...
     * @param  Model|null  $subject  Eloquent model untuk subject_type+id
     * @param  string|null  $description
     * @param  array|null  $changes  optional before/after data
     */
    public static function log(
        string $activity,
        string $module,
        ?Model $subject = null,
        ?string $description = null,
        ?array $changes = null,
    ): ActivityLog {
        $request = request();
        $user = $request->user();
        $brandId = $request ? BrandContext::current($request) : null;
        if ($brandId === 'all' || !is_numeric($brandId)) {
            $brandId = null;
        }

        return ActivityLog::create([
            'user_id' => $user?->id,
            'brand_id' => $brandId,
            'activity' => $activity,
            'module' => $module,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject ? (string) $subject->getKey() : null,
            'description' => $description,
            'changes' => $changes,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
