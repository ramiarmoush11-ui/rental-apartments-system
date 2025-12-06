<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckNotBanned
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
             if (!$user) {
        //لازم تسجل دخول 
        // بس نحنا منحط الميدل وير تبع تسجيل الدخول تنفيذه قبل فما بوصل لهاد الميدل وير اذا مو مسجل 
        }
        if ($user->isbanned && $user->ban_type === 'Permanent') {
            return response()->json([
                'message' => 'You cant do this action , Your account is permanently banned.',
                'data'=>$user->ban_reasons_history
            ], 403);
        }

        if ($user->isbanned && $user->ban_type === 'Temporary') {

            // إذا انتهت فترة الحظر ف شيل الحظر 
            if ($user->banned_until && now()->greaterThanOrEqualTo($user->banned_until)) {
                $user->update([
                    'isbanned' => false,
                    'ban_type' => null,
                    'banned_until' => null,
                ]);
            } else {
                return response()->json([
                    'message' => 'You cant do this action , Your account is temporarily banned until ' . $user->banned_until,
                     'data'=>$user->ban_reasons_history
                ], 403);
            }
        }
        return $next($request);
    }
    
}
