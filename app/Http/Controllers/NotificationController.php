<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends Controller
{
    /**
     * Get paginated notifications for the authenticated user.
     */
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'all');
        $query = $request->user()->notifications();

        if ($filter === 'unread') {
            $query = $request->user()->unreadNotifications();
        } elseif ($filter === 'read') {
            $query = $request->user()->notifications()->whereNotNull('read_at');
        }

        $notifications = $query->paginate(15)->withQueryString();

        // Map standard database notification properties to structure expected by React frontend
        $mapped = $notifications->getCollection()->map(fn ($n) => [
            'id' => $n->id,
            'type' => $n->data['type'] ?? $n->data['event_key'] ?? $n->type ?? '',
            'title' => $n->data['title'] ?? '',
            'body' => $n->data['body'] ?? '',
            'no_po' => $n->data['no_po'] ?? '',
            'action_url' => $n->data['action_url'] ?? '',
            'sound' => $n->data['sound'] ?? 'bell-chime',
            'is_read' => ! is_null($n->read_at),
            'created_at' => $n->created_at->toIso8601String(),
        ]);

        $notifications->setCollection($mapped);

        // If it's a JSON request from axios/polling (not Inertia), return JSON response
        if ($request->wantsJson() && !$request->hasHeader('X-Inertia')) {
            return response()->json([
                'notifications' => $notifications,
                'unread_count' => $request->user()->unreadNotifications()->count(),
            ]);
        }

        // Otherwise, render Inertia Page for notification history
        return \Inertia\Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'filters' => [
                'filter' => $filter,
            ],
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'total_count' => $request->user()->notifications()->count(),
            'read_count' => $request->user()->notifications()->whereNotNull('read_at')->count(),
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'unread_count' => $request->user()->unreadNotifications()->count(),
            ]);
        }

        return redirect()->back()->with('success', 'Notifikasi ditandai telah dibaca.');
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'unread_count' => 0,
            ]);
        }

        return redirect()->back()->with('success', 'Semua notifikasi ditandai telah dibaca.');
    }

    /**
     * Delete a notification.
     */
    public function destroy(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'unread_count' => $request->user()->unreadNotifications()->count(),
            ]);
        }

        return redirect()->back()->with('success', 'Notifikasi berhasil dihapus.');
    }

    /**
     * Delete all notifications for the user.
     */
    public function clearAll(Request $request)
    {
        $request->user()->notifications()->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'unread_count' => 0,
            ]);
        }

        return redirect()->back()->with('success', 'Semua riwayat notifikasi telah dihapus.');
    }
}

