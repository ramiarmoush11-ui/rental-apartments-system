<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function showNotifications() //عملتو جمع -_-
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->orderByDesc('created_at') //رتبتو حسب تاريخ الانشاء تبع النوتي
            ->paginate(15);

        if ($notifications->getCollection()->isEmpty()) {
            return response()->json([
                'message' => 'the notification list is empty.', //غيرت من mess to message
                'data' => null
            ], 204); //403 -> 204 
        }

        return response()->json(['mes' => null, 'data' => $notifications]);
    }

    public function showNotification($notificationId) //غيرت الاسم من one  ل هاد
    {
        $notification = Notification::where('user_id', Auth::id())
            ->where('id', $notificationId)
            ->first(); //هون غيرت من firstOrFail ل first 
        //ليش ؟ لانو الافضل نبعد عن كلشي استثناءات ونحن نتعامل مع كل المواضيع
        if (!$notification) {
            return response()->json([
                'message' => 'the notification list is empty.',
                'data' => null
            ], 204); //403 -> 204
        }
    }
}
