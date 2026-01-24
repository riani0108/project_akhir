<title>{{ $title }}</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
    #map {
        height: 400px;
        width: 100%;
        margin-top: 10px;
    }
</style>

<body>
    <div class="container">
        <h1>{{ $title }}</h1>
        <form action="{{ route('input-lokasi.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="nama">Nama Tower</label>
                <input type="text" class="form-control" id="nama" name="nama" required>
            </div>
            <div class="form-group">
                <label for="tinggi_tower">Tinggi Tower</label>
                <input type="number" class="form-control" id="tinggi_tower" name="tinggi_tower" required>
            </div>
            <div class="form-group">
                <label for="alamat">Alamat</label>
                <textarea class="form-control" id="alamat" name="alamat" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label for="koordinat">Koordinat (lat,lng)</label>
                <input type="text" class="form-control" id="koordinat" name="koordinat" readonly>
            </div>

            <div class="form-group">
                <label for="search">Cari Lokasi</label>
                <div class="search-container">
                    <input type="text" class="form-control search-input" id="search" placeholder="Ketik alamat atau nama tempat...">
                    <button type="button" class="btn search-btn" onclick="searchLocation()">🔍 Cari</button>
                </div>
            </div>
            <div id="map"></div>

            <div class="text-end">
                <a href="{{route('hitung')}}" class="btn btn-secondary">
                    <i class="far fa-window-close me-2"></i>Cancel</a>
                <button type="submit" class="btn btn-save" id="save"><i class="far fa-save me-2"></i>Save</button>
            </div>
        </form>
    </div>

    <script>
        let map = L.map('map').setView([-0.789, 100.353], 10);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);

        let marker;

        // Fungsi auto-fill alamat dari koordinat
        function getAddressFromCoords(lat, lng) {
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1&accept-language=id`)
                .then(res => res.json())
                .then(data => {
                    if (data && data.display_name) {
                        document.getElementById('alamat').value = data.display_name;
                    }
                })
                .catch(() => {}); // Silent fail
        }

        map.on('click', function(e) {
            const lat = e.latlng.lat.toFixed(6);
            const lng = e.latlng.lng.toFixed(6);
            document.getElementById('koordinat').value = `${lat},${lng}`;
            getAddressFromCoords(e.latlng.lat, e.latlng.lng); // Auto alamat

            if (marker) map.removeLayer(marker);
            marker = L.marker([lat, lng]).addTo(map).bindPopup(`Lat: ${lat}, Lng: ${lng}`).openPopup();
        });

        function searchLocation() {
            const query = document.getElementById('search').value;
            if (!query) return;
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1&countrycodes=id`)
                .then(res => res.json())
                .then(data => {
                    if (data[0]) {
                        const lat = parseFloat(data[0].lat);
                        const lon = parseFloat(data[0].lon);
                        document.getElementById('koordinat').value = `${lat.toFixed(6)},${lon.toFixed(6)}`;
                        getAddressFromCoords(lat, lon); // Auto alamat

                        map.setView([lat, lon], 15);
                        if (marker) map.removeLayer(marker);
                        marker = L.marker([lat, lon]).addTo(map).bindPopup(data[0].display_name).openPopup();
                    } else {
                        alert('Lokasi tidak ditemukan');
                    }
                });
        }

        // Enter search
        document.getElementById('search').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') searchLocation();
        });

        // Auto-fill alamat saat koordinat berubah manual (opsional)
        document.getElementById('koordinat').addEventListener('change', function() {
            const coords = this.value.split(',');
            if (coords.length === 2) {
                getAddressFromCoords(parseFloat(coords[0]), parseFloat(coords[1]));
            }
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



</body>