<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function showNotification()
    {
        $notifications = Notification::where('user_id', '=', Auth::id())->paginate(15); //order
        if ($notifications->getCollection()->isEmpty()) {
            return response()->json([
                'mes' => 'the notification list is empty.',
                'data' => null
            ], 404);
        }
        return response()->json(['mes' => null, 'data' => $notifications]);
    }
    public function showOneNotification($id)
    {
        $notification = Notification::where('user_id', '=', Auth::id())
            ->where('id', '=', $id)->firstOrFail();
        return response()->json(['mes' => null, 'data' => $notification]);
    }
}
