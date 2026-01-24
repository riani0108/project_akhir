@extends('be.master')
@section('sidebar')
@include('be.sidebar')
@endsection

@section('navbar')
@include('be.navbar')
@endsection

@section('content')
<main class="content-wrapper">
    <div class="container-fluid py-4">
        <!-- Judul Dashboard -->
        <div class="row mb-4">
            <div class="col-12">
                <h4 class="text-dark fw-bold">Selamat Datang di Dashboard Admin Tower BTS</h4>
                <p class="text-muted mb-0">Pantau dan kelola data tower dengan mudah.</p>
            </div>
        </div>

        <!-- Kartu Statistik -->
        <div class="row g-4">
            <div class="col-md-3">
                <div class="card shadow-sm text-center p-3 border-0">
                    <div class="card-body">
                        <i class="fas fa-broadcast-tower fa-2x text-warning mb-2"></i>
                        <h6 class="text-muted">Total Tower</h6>
                        <h4 class="fw-bold">128</h4>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm text-center p-3 border-0">
                    <div class="card-body">
                        <i class="fas fa-signal fa-2x text-success mb-2"></i>
                        <h6 class="text-muted">Tower Aktif</h6>
                        <h4 class="fw-bold">94</h4>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm text-center p-3 border-0">
                    <div class="card-body">
                        <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                        <h6 class="text-muted">Tower Nonaktif</h6>
                        <h4 class="fw-bold">34</h4>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm text-center p-3 border-0">
                    <div class="card-body">
                        <i class="fas fa-map-marked-alt fa-2x text-primary mb-2"></i>
                        <h6 class="text-muted">Sebaran Lokasi</h6>
                        <h4 class="fw-bold">12 Kota</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grafik atau Map Preview -->
        <div class="row mt-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm p-3">
                    <h6 class="fw-bold mb-3">Statistik Tower per Wilayah</h6>
                    <canvas id="towerChart" height="120"></canvas>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm p-3">
                    <h6 class="fw-bold mb-3">Peta Sebaran Tower</h6>
                    <div id="map" style="height: 220px; border-radius: 10px;"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('towerChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Jakarta', 'Bogor', 'Depok', 'Tangerang', 'Bekasi'],
                datasets: [{
                    label: 'Jumlah Tower',
                    data: [20, 15, 10, 25, 18],
                    backgroundColor: '#f0ae88'
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>

</main>


@endsection