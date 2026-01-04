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

        // Add translated title for each notification
        $notifications->getCollection()->transform(function ($notification) {
            $notification->title = __('notification.' . $notification->type);
            return $notification;
        });

        return response()->json([
            'message' => __('notification.list_retrieved'),
            'data' => $notifications
        ], 200);
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

        // Add translated title
        $notification->title = __('notification.' . $notification->type);

        return response()->json([
            'message' => __('notification.retrieved'),
            'data' => $notification
        ], 200);
    }
}
