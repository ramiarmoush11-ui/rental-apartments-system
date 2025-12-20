<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            abort(403);
        }

        if (auth()->user()->enRole !== 'Admin') {
            abort(403);
        }

        return $next($request);
    }
}
