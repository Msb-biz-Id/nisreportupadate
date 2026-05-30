<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Display a listing of the notifications.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $notifications = $user->notifications()
            ->paginate(20);

        return response()->json([
            'notifications' => $notifications
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, Notification $notification)
    {
        if ($notification->user_id !== Auth::id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $notification->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'unread_count' => Auth::user()->notifications()->where('is_read', false)->count()
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        Auth::user()->notifications()->where('is_read', false)->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'unread_count' => 0
        ]);
    }

    /**
     * Remove the specified notification.
     */
    public function destroy(Request $request, Notification $notification)
    {
        if ($notification->user_id !== Auth::id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'unread_count' => Auth::user()->notifications()->where('is_read', false)->count()
        ]);
    }
}
