<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Role
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!$request->user()) {
            // Redirect ke halaman login.tampil kalau belum login
            return redirect()->route('login');
        }

        if ($request->user()->role !== $role) {
            // Kalau role-nya gak sesuai, bisa redirect ke login juga (atau ke halaman lain)
            return redirect()->route('user-pelanggan.login'); // atau abort(403);
        }
        return $next($request);
    }
}
