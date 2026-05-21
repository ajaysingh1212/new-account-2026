<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CompanyAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        if (!$user || !in_array($user->user_type, ['super_admin', 'admin'])) {
            abort(403, 'Admin access required.');
        }
        return $next($request);
    }
}
