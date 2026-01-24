<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Verifikasi Email Anda</h4>
                </div>

                <div class="card-body">
                    @if (session('resent'))
                    <div class="alert alert-success" role="alert">
                        Link verifikasi baru telah dikirim ke email Anda.
                    </div>
                    @endif

                    <p>
                        Terima kasih telah mendaftar! Sebelum melanjutkan, silakan periksa email Anda
                        untuk tautan verifikasi.
                    </p>

                    <p>
                        Jika Anda tidak menerima email,
                    <form method="POST" action="{{ route('verification.send') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-link p-0 m-0 align-baseline">
                            klik di sini untuk mengirim ulang
                        </button>.
                    </form>
                    </p>

                    <p>
                        <a href="{{ route('home.index') }}" class="btn btn-secondary">Kembali ke Beranda</a>
                    <form method="POST" action="{{ route('user-pelanggan.logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-link text-danger">Logout</button>
                    </form>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>