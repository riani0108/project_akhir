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
                <div class="row g-4">
                    <div class="col-12">
                        <div class="card mb-4">
                            <div class="card-header pb-0">
                                <div class="row mb-5">
                                    <div class="container">
                                        <h2 class="fw-bold">Tambah Data Tower</h2>
                                    </div>
                                    <form action="@isset($data){{route('data-tower.update', $data->id)}}@endisset" method="POST" id="frmdata" enctype="multipart/form-data">
                                        @method('PUT')
                                        @csrf

                                        <div class="form-group">
                                            <label for="nama_tower" class="form-label">Nama Tower</label>
                                            <input class="form-control" type="text" aria-label="Default select example"
                                                name="nama_tower" id="nama_tower" value="{{$data->nama_tower ? $data->nama_tower : old('nama_tower')}}" required>
                                            <div id="datatowerHelp" class="form-text" style="color: #2f271d"> Text must be filled in maximal 100 characters</div>
                                        </div>

                                        <div class="form-group">
                                            <label for="alamat_tower" class="form-label">Nama Tower</label>
                                            <input class="form-control" type="text" aria-label="Default select example"
                                                name="alamat_tower" id="alamat_tower" value="{{$data->alamat_tower ? $data->alamat_tower : old('alamat_tower')}}" required>
                                            <div id="datatowerHelp" class="form-text" style="color: #2f271d"> Text must be filled in maximal 100 characters</div>
                                        </div>


                                        <div class="form-group">
                                            <label for="tinggi_tower" class="form-label">Tinggi Tower (m)</label>
                                            <input class="form-control" type="number" name="tinggi_tower" id="tinggi_tower" value="{{$data->tinggi_tower ? $data->tinggi_tower : old('tinggi_tower')}}" min="0" required>
                                            <div id="datatowerHelp" class="form-text" style="color: #2f271d"> Text must be filled in as a number with units (m)</div>
                                        </div>

                                        <div class="form-group">
                                            <label for="latitude" class="form-label">Latitude Tower</label>
                                            <input class="form-control" type="text" name="latitude" id="latitude" value="{{$data->latitude ? $data->latitude : old('latitude')}}" required>
                                            <div id="datatowerHelp" class="form-text" style="color: #2f271d"> Text must be filled in as a number with units (m)</div>
                                        </div>

                                        <div class="form-group">
                                            <label for="longitude" class="form-label">longitude Tower</label>
                                            <input class="form-control" type="text" name="longitude" id="longitude" value="{{$data->longitude ? $data->longitude : old('longitude')}}" required>
                                            <div id="datatowerHelp" class="form-text" style="color: #2f271d"> Text must be filled in as a number with units (m)</div>
                                        </div>

                                        <div class="form-group">
                                            <label for="keterangan" class="form-label">Keterangan</label>
                                            <div class="form-floating">
                                                <textarea class="form-control" id="keterangan" name="keterangan" row="10">
                                                {{$data->keterangan ? $data->keterangan : old('keterangan')}}
                                                </textarea>
                                            </div>
                                            <div id="datatowerHelp" class="form-text" style="color: #2f271d">Description can be left blank if not necessary .</div>
                                        </div>

                                        <div class="text-end">
                                            <div class="mt-4">
                                                <label class="form-label fw-bold">Pilih Lokasi Tower di Map</label>
                                                <div id="map" style="height: 400px; border-radius: 10px;"></div>
                                            </div>
                                            <a href="{{route('data-tower.index')}}" class="btn btn-secondary">
                                                <i class="far fa-window-close me-2"></i>Cancel</a>
                                            <button type="submit" class="btn btn-save" id="save"><i class="far fa-save me-2"></i>Save Data Tower</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
                            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
                            <script>
                                document.addEventListener("DOMContentLoaded", function() {

                                    // Atur posisi awal map
                                    var map = L.map('map').setView([-6.200, 106.816], 13);

                                    // Base map OpenStreetMap
                                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                        maxZoom: 19,
                                    }).addTo(map);

                                    var markersGroup = L.featureGroup().addTo(map);

                                    fetch('/data/tower')
                                        .then(response => response.json())
                                        .then(data => {
                                            data.forEach(item => {
                                                // Buat marker untuk tiap tower
                                                var marker = L.marker([item.latitude, item.longitude])
                                                    .bindPopup('<b>' + item.nama_tower + '</b>');
                                                marker.addTo(map);
                                                markersGroup.addLayer(marker);
                                            });

                                            map.fitBounds(markersGroup.getBounds())
                                                .catch(error => console.error(error));
                                        });

                                    // === Tampilkan marker dari database jika sedang edit ===
                                    var initialLat = "{{ $data->latitude }}";
                                    var initialLon = "{{ $data->longitude }}";

                                    if (initialLat && initialLon) {
                                        marker = L.marker([initialLat, initialLon]).addTo(map)
                                            .bindPopup("<b>{{ $data->nama_tower }}</b>").openPopup();

                                        map.setView([initialLat, initialLon], 15);
                                    }

                                    // === Marker untuk klik pengguna ===
                                    map.on('click', function(e) {

                                        // Hapus marker lama
                                        if (marker) {
                                            map.removeLayer(marker);
                                        }

                                        // Buat marker baru
                                        marker = L.marker(e.latlng).addTo(map);

                                        // Isi input form
                                        document.getElementById('latitude').value = e.latlng.lat.toFixed(6);
                                        document.getElementById('longitude').value = e.latlng.lng.toFixed(6);
                                    });

                                });
                            </script>

                            @if(session('error'))
                            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Gagal!',
                                        text: '{{ session("error") }}',
                                        confirmButtonColor: '#d33'
                                    });
                                });
                            </script>
                            @endif

                            @if(session('success'))
                            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Berhasil!',
                                        text: '{{ session("success") }}',
                                        confirmButtonColor: 'green'
                                    });
                                });
                            </script>
                            @endif

                            @endsection