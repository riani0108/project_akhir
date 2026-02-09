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
        if (!$user->email_verified_at) {
            return redirect()->route('verification.notice')
                ->with('error', 'Silakan verifikasi email Anda terlebih dahulu.');
        }

        return $next($request);
    }
}
