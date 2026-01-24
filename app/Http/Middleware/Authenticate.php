<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        // Tentukan redirect berdasarkan guard
        $guard = $request->route()?->middleware() ?? [];

        if (in_array('auth:pelanggan', $guard) || $request->is('pelanggan/*')) {
            return route('user-pelanggan.login');
        }

        // Default untuk guard web biasa (jika ada)
        return route('user-pelanggan.login'); // atau route('user-pelanggan.login')
    }
}
