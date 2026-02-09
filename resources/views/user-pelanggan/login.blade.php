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
    <div class="container">
        <h2>Login</h2>
        <form method="POST" action="{{route('user-pelanggan.login.submit')}}">
            @csrf
            @if ($errors->any())
            <div class="alert alert-danger" style="color: red;">
                {{ $errors->first() }}
            </div>
            @endif

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required placeholder="Masukkan email">
            </div>

            <div class="from-group">
                <label for="password">Password</label>
                <input type="password" id="kata_kunci" name="kata_kunci" required placeholder="Masukkan password">
            </div>

            <button type="submit" class="btn-login" href="/">Login</button>
            <button type="button" class="btn-cancel" onclick="window.location.href='/'">Cancel</button>
            <!-- Tambahan Forgot Password -->
            <p class="forgot-password-text">
                <a href="{{ route('user-pelanggan.lupa-password') }}" class="forgot-password-link">Lupa password?</a>
            </p>
        </form>

        <p class="register-text">
            Belum punya akun?
            <a href="{{ route('user-pelanggan.register') }}" class="register-link">Daftar di sini</a>
        </p>
    </div>
</body>

</html>