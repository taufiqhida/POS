<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PosAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check() || ! Auth::user()->is_active || ! session('pos_outlet_id')) {
            return redirect()->route('pos.login');
        }

        return $next($request);
    }
}
