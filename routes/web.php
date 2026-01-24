<?php

use App\Http\Controllers\AdminController;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\HitungController;
use App\Http\Controllers\DataTowerController;
use App\Http\Controllers\DataAntennaController;
use App\Http\Controllers\InputLokasiController;
use App\Models\Pelanggan;
use Illuminate\Support\Facades\Auth;


Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/home', [HomeController::class, 'index'])->name('home.index');
Route::resource('/about', App\Http\Controllers\AboutController::class);
Route::middleware(['auth:pelanggan', 'verified.pelanggan'])->group(function () {
    Route::get('/hitung', [HitungController::class, 'index'])->name('hitung');
});


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

Route::get('/elevation', function (Request $request) {
    $locations = $request->query('locations');
    if (!$locations) return response()->json(['results' => []]);

    $url = "https://api.opentopodata.org/v1/srtm90m?locations={$locations}";
    return Http::timeout(10)->get($url)->json();
});


Route::resource('/informasi', App\Http\Controllers\InformasiController::class)->name('index', 'informasi');
Route::resource('/admin', App\Http\Controllers\AdminController::class);
Route::resource('/data-tower', App\Http\Controllers\DataTowerController::class);
Route::resource('/data-antenna', App\Http\Controllers\DataAntennaController::class);
Route::get('/data/tower', [DataTowerController::class, 'all'])->name('data-tower.all');
Route::get('/data/antenna', [DataAntennaController::class, 'all'])->name('data-antenna.all');
Route::resource('input-lokasi', App\Http\Controllers\InputLokasiController::class);
Route::get('/data/input-lokasi', [InputLokasiController::class, 'all'])->name('input-lokasi.all');



Route::resource('/peta-sebaran', App\Http\Controllers\PetaSebaranController::class);




Route::prefix('user-pelanggan')->name('user-pelanggan.')->group(function () {

    // ===== AUTH =====
    Route::get('/register', [AuthController::class, 'tampilRegister'])
        ->name('register');

    Route::post('/register/submit', [AuthController::class, 'submitRegister'])
        ->name('register.submit');

    Route::get('/login', [AuthController::class, 'tampilLogin'])
        ->name('login');

    Route::post('/login/submit', [AuthController::class, 'submitLogin'])
        ->name('login.submit');


    // ===== PROTECTED =====
    Route::middleware('auth:pelanggan')->group(function () {

        Route::post('/logout', [AuthController::class, 'logoutPelanggan'])
            ->name('logout');

        Route::get('/dashboard', function () {
            return view('user-pelanggan.dashboard');
        })->name('dashboard');
    });
});


/*
|--------------------------------------------------------------------------
| EMAIL VERIFICATION ROUTES
|--------------------------------------------------------------------------
*/

// 1. Halaman notice (untuk menampilkan pesan "cek email Anda")
Route::get('/email/verify', function () {
    return view('user-pelanggan.verify-email', [
        'title' => 'Verifikasi Email'
    ]);
})->middleware('auth:pelanggan')->name('verification.notice');

// 2. Proses verifikasi email (klik link dari email)
Route::get('/email/verify/{id}/{hash}', function ($id, $hash, Request $request) {
    // Cari user berdasarkan ID
    $user = Pelanggan::findOrFail($id);

    // Cek apakah sudah diverifikasi
    if ($user->hasVerifiedEmail()) {
        // Jika sudah login, redirect ke home
        if (Auth::guard('pelanggan')->check()) {
            return redirect()->route('home.index')
                ->with('info', 'Email sudah diverifikasi sebelumnya.');
        }
        // Jika belum login, redirect ke login
        return redirect()->route('user-pelanggan.login')
            ->with('info', 'Email sudah diverifikasi. Silakan login.');
    }

    // Verifikasi menggunakan method bawaan Laravel
    if (!$user->markEmailAsVerified()) {
        abort(500, 'Gagal memverifikasi email.');
    }

    // Trigger event verified
    event(new Verified($user));

    // Login user otomatis setelah verifikasi
    Auth::guard('pelanggan')->login($user);

    // Regenerate session untuk keamanan
    $request->session()->regenerate();

    return redirect()->route('home.index')
        ->with('success', 'Email berhasil diverifikasi! Selamat datang, ' . $user->nama_pelanggan . '.');
})->middleware(['signed'])->name('verification.verify');

// 3. Kirim ulang email verifikasi
Route::post('/email/verification-notification', function (Request $request) {
    $user = $request->user('pelanggan');

    if ($user->hasVerifiedEmail()) {
        return redirect()->route('home.index')
            ->with('info', 'Email sudah diverifikasi.');
    }

    $user->sendEmailVerificationNotification();

    return back()->with('success', 'Link verifikasi baru telah dikirim ke email Anda.');
})->middleware(['auth:pelanggan', 'throttle:6,1'])->name('verification.send');
