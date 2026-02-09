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
    <div class="register-container">
        <h2>Register</h2>
        <form action="{{ route('user-pelanggan.register.submit') }}" method="POST">
            @csrf

            @if(session('success'))
            <div class="success-message">
                {{ session('success') }}
            </div>
            @endif  

            <div class="form-group">
                <div class="input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" name="nama_pelanggan" value="{{ old('nama_pelanggan') }}" placeholder="Nama Lengkap" required>
                </div>
                @error('nama_pelanggan')
                <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <div class="input-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" required>
                </div>
                @error('email')
                <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="kata_kunci" value="{{ old('kata_kunci') }}" placeholder="Password" required>
                </div>
                @error('kata_kunci')
                <div class="error-message">{{ $message }}</div>
                @enderror
            </div>



            <button type="submit" class="btn-register" href="{{route('user-pelanggan.login')}}">Register</button>

            <button type="button" class="btn-cancel" onclick="window.location.href='/'">Cancel</button>

            <div class="login-link">
                Sudah punya akun? <a href="/user-pelanggan/login">Login di sini</a>
            </div>
</body>

</html>