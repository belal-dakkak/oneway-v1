<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param  string $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        $roles = explode('|', $role);
        $userRole = auth()->user()->role_id;

        $hasRole = false;
        foreach ($roles as $r) {
            if ($r == 'admin' && $userRole == User::ROLE_ADMIN) {
                $hasRole = true;
                break;
            }
            if ($r == 'warehouse' && $userRole == User::ROLE_WAREHOUSE) {
                $hasRole = true;
                break;
            }
            if ($r == 'shop' && $userRole == User::ROLE_SHOP) {
                $hasRole = true;
                break;
            }
            if ($r == 'merchant' && $userRole == User::ROLE_MERCHANT) {
                $hasRole = true;
                break;
            }
            if ($r == 'shipper' && $userRole == User::ROLE_SHIPPER) {
                $hasRole = true;
                break;
            }
        }

        if (!$hasRole) {
            abort(403);
        }

        return $next($request);
    }
}
