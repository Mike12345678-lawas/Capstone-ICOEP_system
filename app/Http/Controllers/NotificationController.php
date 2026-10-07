<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function getUnread()
    {
        $user = Auth::user();
        $unreadCount = $user->notifications()->unread()->count();
        $notifications = $user->notifications()->latest()->limit(5)->get();

        $notifications->transform(function ($n) {
            $n->time_ago = $n->created_at->diffForHumans();
            return $n;
        });

        return response()->json([
            'unread_count'  => $unreadCount,
            'notifications' => $notifications,
        ]);
    }

    public function markAllRead()
    {
        Auth::user()->notifications()->unread()->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * Delete a single notification. Users may only delete their own.
     */
    public function destroy(Notification $notification)
    {
        abort_unless($notification->user_id === Auth::id(), 403);

        $notification->delete();

        return $this->counts();
    }

    /**
     * Delete all of the current user's notifications.
     * Pass ?only=read to clear only already-read notifications.
     */
    public function destroyAll(Request $request)
    {
        $query = Auth::user()->notifications();

        if ($request->query('only') === 'read') {
            $query->where('is_read', true);
        }

        $deleted = $query->delete();

        return $this->counts(['deleted' => $deleted]);
    }

    private function counts(array $extra = [])
    {
        $user = Auth::user();

        return response()->json(array_merge([
            'success'      => true,
            'unread_count' => $user->notifications()->unread()->count(),
            'total_count'  => $user->notifications()->count(),
        ], $extra));
    }
}
