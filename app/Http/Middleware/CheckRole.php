<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CheckRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $userRole = Session::get('Role');

        if (!$userRole) {
            return redirect()->route('user-pelanggan.login')
                ->with('error', 'Silakan login terlebih dahulu');
        }

        if (!in_array($userRole, $roles)) {
            return redirect()->route('home.index')
                ->with('error', 'Anda tidak memiliki akses ke halaman ini');
        }

        return $next($request);
    }
}
