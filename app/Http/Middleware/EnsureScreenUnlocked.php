<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureScreenUnlocked
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->session()->get('screen_locked') && !$request->is('screen-lock/*')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Screen locked.'], 423);
            }

            return response()->view('auth.screen-locked', ['user' => $request->user()], 423);
        }

        return $next($request);
    }
}
