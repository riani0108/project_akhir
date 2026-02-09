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
    <div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
        <div class="card shadow-sm border-0" style="max-width: 420px; width: 100%;">
            <div class="card-body p-4">

                <div class="text-center mb-4">
                    <div class="mb-2" style="font-size: 42px;">🔒</div>
                    <h4 class="fw-bold">Lupa Password</h4>
                    <p class="text-muted small">
                        Masukkan email yang terdaftar untuk menerima link reset password.
                    </p>
                </div>

                {{-- ALERT SUCCESS --}}
                @if (session('success'))
                <div class="alert alert-success small">
                    {{ session('success') }}
                </div>
                @endif

                {{-- ALERT ERROR --}}
                @if ($errors->any())
                <div class="alert alert-danger small">
                    {{ $errors->first() }}
                </div>
                @endif

                <form method="POST" action="{{ route('user-pelanggan.lupa-password.submit') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Email</label>
                        <input
                            type="email"
                            name="email"
                            class="form-control form-control-lg"
                            placeholder="nama@email.com"
                            value="{{ old('email') }}"
                            required
                            autofocus>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                        Kirim Link Reset
                    </button>
                </form>

                <div class="text-center mt-4">
                    <a href="{{ route('user-pelanggan.login') }}" class="text-decoration-none small">
                        ← Kembali ke Login
                    </a>
                </div>

            </div>
        </div>
    </div>
</body>