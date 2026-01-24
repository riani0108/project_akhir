<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerifiedPelanggan
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Cek apakah user sudah login sebagai pelanggan
        $user = Auth::guard('pelanggan')->user();

        if (!$user) {
            // Jika belum login, redirect ke halaman login
            return redirect()->route('user-pelanggan.login')
                ->with('error', 'Silakan login terlebih dahulu.');
        }

        // Cek apakah email sudah diverifikasi
        if (!$user->hasVerifiedEmail()) {
            // Jika belum diverifikasi, redirect ke halaman notice
            return redirect()->route('verification.notice')
                ->with('warning', 'Silakan verifikasi email Anda terlebih dahulu.');
        }

        return $next($request);
    }
}
