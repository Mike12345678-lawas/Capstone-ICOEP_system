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
}
