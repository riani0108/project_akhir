<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pelanggan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;




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
      'kata_kunci' => 'required',
    ]);

    $pelanggan = Pelanggan::create([
      'nama_pelanggan' => $request->nama_pelanggan,
      'email' => $request->email,
      'kata_kunci' => Hash::make($request->kata_kunci),
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
    $request->validate([
      'email' => 'required|email',
      'kata_kunci' => 'required',
    ]);

    if (!Auth::guard('pelanggan')->attempt([
      'email' => $request->email,
      'password' => $request->kata_kunci, // 🔥 HARUS 'password'
    ])) {
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

  public function tampilLupaPassword()
  {
    return view('user-pelanggan.lupa-password', [
      'title' => 'Lupa Password',
      'subtitle' => 'Masukkan email Anda untuk menerima tautan reset password.'
    ]);
  }


  public function kirimLinkResetPassword(Request $request)
  {
    $request->validate([
      'email' => 'required|email|exists:pelanggan,email',
    ]);

    $status = Password::broker('pelanggan')->sendResetLink(
      $request->only('email')
    );

    return $status === Password::RESET_LINK_SENT
      ? back()->with('success', 'Link reset password telah dikirim ke email.')
      : back()->withErrors(['email' => 'Gagal mengirim link reset password']);
  }


  public function tampilResetPassword($token)
  {
    return view('user-pelanggan.reset-password', [
      'title' => 'Reset Password',
      'token' => $token,
      'email' => request('email'),
    ]);
  }


  public function submitResetPassword(Request $request)
  {
    $request->validate([
      'token' => 'required',
      'email' => 'required|email|exists:pelanggan,email',
      'kata_kunci' => 'required|min:6|confirmed',
    ]);

    $status = Password::broker('pelanggan')->reset(
      [
        'email' => $request->email,
        'password' => $request->kata_kunci,
        'password_confirmation' => $request->kata_kunci_confirmation,
        'token' => $request->token,
      ],
      function ($pelanggan, $password) {
        $pelanggan->kata_kunci = Hash::make($password);
        $pelanggan->setRememberToken(Str::random(60));
        $pelanggan->save();
      }
    );

    if ($status !== Password::PASSWORD_RESET) {
      return back()->withErrors([
        'email' => 'Token reset password tidak valid atau sudah kadaluarsa',
      ]);
    }

    return redirect()->route('user-pelanggan.login')
      ->with('success', 'Password berhasil direset, silakan login');
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
