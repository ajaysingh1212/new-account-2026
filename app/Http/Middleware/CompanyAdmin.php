<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CompanyAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        if (!$user || !$user->isCompanyAdmin()) {
            abort(403, 'Admin access required.');
        }
        return $next($request);
    }
}
