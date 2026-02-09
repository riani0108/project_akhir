<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{$title}}</title>

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Fugaz+One&family=Poppins:wght@400;500;700&family=Roboto:wght@400;500;700&display=swap"
        rel="stylesheet">
    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <link href="{{asset('front-end/css/auth.css')}}" rel="stylesheet">
</head>

<body>

    <div class="container mt-5" style="max-width: 420px;">
        <h3 class="mb-3 text-center">Reset Password</h3>
        <p class="text-muted text-center">
            Silakan masukkan password baru Anda.
        </p>

        {{-- ERROR --}}
        @if ($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('user-pelanggan.reset-password.submit') }}">
            @csrf

            {{-- TOKEN --}}
            <input type="hidden" name="token" value="{{ $token }}">

            {{-- EMAIL --}}
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input
                    type="email"
                    name="email"
                    class="form-control"
                    value="{{ $email ?? old('email') }}"
                    required>
            </div>

            {{-- PASSWORD --}}
            <div class="mb-3">
                <label class="form-label">Password Baru</label>
                <input
                    type="password"
                    name="kata_kunci"
                    class="form-control"
                    required>
            </div>

            {{-- KONFIRMASI --}}
            <div class="mb-3">
                <label class="form-label">Konfirmasi Password</label>
                <input
                    type="password"
                    name="kata_kunci_confirmation"
                    class="form-control"
                    required>
            </div>

            <button type="submit" class="btn btn-success w-100">
                Reset Password
            </button>
        </form>
    </div>
</body>