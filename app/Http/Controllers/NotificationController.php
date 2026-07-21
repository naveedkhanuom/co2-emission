<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * In-app notifications for the signed-in user. Notifications are personal —
 * every query runs through the authenticated user's own relationship, so there
 * is no cross-tenant exposure to guard against here.
 */
class NotificationController extends Controller
{
    /** Full notifications page. */
    public function index()
    {
        $user = auth()->user();

        return view('notifications.index', [
            'notifications' => $user->notifications()->paginate(20),
            'unread'        => $user->unreadNotifications()->count(),
        ]);
    }

    /** JSON feed for the top-bar bell (recent items + unread count). */
    public function feed()
    {
        $user = auth()->user();

        $items = $user->notifications()->latest()->limit(8)->get()->map(function ($n) {
            $d = $n->data;

            return [
                'id'      => $n->id,
                'title'   => $d['title']   ?? 'Notification',
                'message' => $d['message'] ?? '',
                'icon'    => $d['icon']    ?? 'fa-bell',
                'color'   => $d['color']   ?? 'primary',
                'read'    => $n->read_at !== null,
                'time'    => $n->created_at->diffForHumans(),
                'openUrl' => route('notifications.open', $n->id),
            ];
        });

        return response()->json([
            'count' => $user->unreadNotifications()->count(),
            'items' => $items,
        ]);
    }

    /** Mark a single notification read (AJAX). */
    public function markRead(string $id)
    {
        auth()->user()->notifications()->findOrFail($id)->markAsRead();

        return response()->json(['ok' => true]);
    }

    /** Mark every notification read. */
    public function markAllRead(Request $request)
    {
        auth()->user()->unreadNotifications->markAsRead();

        return $request->wantsJson()
            ? response()->json(['ok' => true])
            : back()->with('success', 'All notifications marked as read.');
    }

    /** Open a notification: mark it read, then go to its linked page. */
    public function open(string $id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        return $url ? redirect($url) : redirect()->route('notifications.index');
    }

    /** Delete a single notification. */
    public function destroy(Request $request, string $id)
    {
        auth()->user()->notifications()->findOrFail($id)->delete();

        return $request->wantsJson()
            ? response()->json(['ok' => true])
            : back()->with('success', 'Notification removed.');
    }
}
