<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Support\BrandContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('audit.view');

        $brandId = BrandContext::current($request);
        $isSuperadmin = $request->user()->isSuperadmin();

        $query = ActivityLog::with(['user:id,name,email', 'brand:id,nama_brand,kode']);

        if (! $isSuperadmin && $brandId) {
            $query->where(function ($q) use ($brandId) {
                $q->where('brand_id', $brandId)->orWhereNull('brand_id');
            });
        }

        if ($module = $request->string('module')->toString()) {
            $query->where('module', $module);
        }
        if ($activity = $request->string('activity')->toString()) {
            $query->where('activity', $activity);
        }
        if ($userId = $request->string('user_id')->toString()) {
            $query->where('user_id', $userId);
        }
        if ($from = $request->string('from')->toString()) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->string('to')->toString()) {
            $query->whereDate('created_at', '<=', $to);
        }
        if ($search = $request->string('q')->toString()) {
            $query->where('description', 'like', "%{$search}%");
        }

        $logs = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        return Inertia::render('Audit/Index', [
            'logs' => $logs,
            'filters' => [
                'module' => $request->string('module')->toString(),
                'activity' => $request->string('activity')->toString(),
                'user_id' => $request->string('user_id')->toString(),
                'from' => $request->string('from')->toString(),
                'to' => $request->string('to')->toString(),
                'q' => $request->string('q')->toString(),
            ],
            'modules' => ['brand', 'user', 'master', 'order', 'production', 'refund', 'invoice', 'finance', 'ai', 'settings', 'auth'],
            'activities' => ['create', 'update', 'delete', 'publish', 'login', 'logout', 'export', 'test_connection', 'unlock', 'relock'],
        ]);
    }
}
