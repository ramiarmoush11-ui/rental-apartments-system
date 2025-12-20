<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function showNotifications()
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate(15);

        if ($notifications->getCollection()->isEmpty()) {
            return response()->json([
                'message' => __('notification.empty'),
                'data' => []
            ], 200);
        }

        return response()->json(['message' => __('notification.list_retrieved'), 'data' => $notifications]);
    }

    public function showNotification($notificationId)
    {
        $notification = Notification::where('user_id', Auth::id())
            ->where('id', $notificationId)
            ->first();
        if (!$notification) {
            return response()->json([
                'message' => __('notification.not_found'),
                'data' => null
            ], 404);
        }
        $notification->seen = true;
        $notification->save();
        return response()->json(['message' => __('notification.retrieved'), 'data' => $notification], 200);
    }
}
