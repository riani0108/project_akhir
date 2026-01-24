<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Models\Pelanggan;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


class AuthController extends Controller
{
  public function tampilRegister()
  {
    return view('user-pelanggan.register', [
      'title' => 'Pelanggan Register',
    ]);
  }

  public function submitRegister(Request $request)
  {
    $request->validate([
      'nama_pelanggan' => 'required|string|max:255',
      'email' => 'required|email|unique:pelanggan,email',
      'password' => 'required|min:6|confirmed',
    ]);

    $pelanggan = Pelanggan::create([
      'nama_pelanggan' => $request->nama_pelanggan,
      'email' => $request->email,
      'kata_kunci' => Hash::make($request->password),
    ]);

    $pelanggan->sendEmailVerificationNotification();

    return redirect()->route('user-pelanggan.login')
      ->with('success', 'Registrasi berhasil! Silakan cek email.');
  }




  public function tampilLogin()
  {
    return view('user-pelanggan.login', [
      'title' => 'Pelanggan Login',
    ]); // view form login
  }


  public function submitLogin(Request $request)
{
    $credentials = $request->validate([
        'email' => 'required|email',
        'kata_kunci' => 'required',
    ]);

    if (!Auth::guard('pelanggan')->attempt($credentials)) {
        return back()->withErrors([
            'error' => 'Email atau password salah'
        ]);
    }

    $request->session()->regenerate();

    if (is_null(auth('pelanggan')->user()->email_verified_at)) {
        Auth::guard('pelanggan')->logout();
        return back()->withErrors([
            'error' => 'Email belum diverifikasi'
        ]);
    }

    return redirect()->route('home.index')
        ->with('success', 'Berhasil login');


}


  public function logoutPelanggan(Request $request)
  {
    Auth::guard('pelanggan')->logout();

    // 🔥 PAKSA HAPUS SEMUA SESSION
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('home.index')
      ->with('success', 'Berhasil logout');
  }

  /**
   * Display the specified resource.
   */
  public function show(string $id)
  {
    //
  }

  /**
   * Show the form for editing the specified resource.
   */
  public function edit(string $id)
  {
    //
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, string $id)
  {
    //
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(string $id)
  {
    //
  }
}
