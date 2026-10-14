<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RestrictToAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && (int) Auth::user()->user_role === 1) {
            return $next($request);
        }
        return redirect()->route('dashboard');
    }
}