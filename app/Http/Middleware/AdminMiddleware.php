<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle($request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('admin.login');
        }

        if (Auth::user()->enRole !== 'Admin') {
            abort(403);
        }

        if (Auth::user()->isbanned) {
            Auth::logout();
            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Account is banned']);
        }

        return $next($request);
    }
}
