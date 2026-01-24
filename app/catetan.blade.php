@extends('be.master')
@section('sidebar')
@include('be.sidebar')
@endsection

@section('navbar')
@include('be.navbar')
@endsection

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script> <!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"> <!-- Heatmap -->
<script src="https://unpkg.com/leaflet.heat/dist/leaflet-heat.js"></script> <!-- cloudflare -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script> <!-- Geocoder CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" /> <!-- Routing Machine -->
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
<script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>

@section('content') <main class="content-wrapper">
    <div class="container-fluid py-4">
        <div class="row g-4">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header pb-0">
                        <div class="row mb-5">
                            <div class="col-auto me-auto mb-4 font-weight-bold h4"> {{$title}} </div>
                        </div>
                        <div class="card-body p-0">
                            <div id="peta"></div> <button class="btn-peta" onclick=fullScreenView()>View Full Screen</button>

                            <div id="los-panel">
                                <h5>Line of Sight (LOS) Result</h5> <canvas id="losChart" height="120"></canvas>
                                <div id="los-info" style="margin-top:10px; font-size:14px;"></div>
                            </div>

                            <div id="heatmap-legend" class="heatmap-legend"> <span>Weak</span>
                                <div class="heatmap-bar"></div> <span>Strong</span>
                            </div>
                            <div class="coordinate"></div> <!-- Tooltip mengikuti mouse -->
                            <div id="mouse-follow"></div>

                            <!-- Context Menu -->
                            <div id="context-menu">
                                <ul>
                                    <li onclick="addMarker()">Tambah Marker</li>
                                    <li onclick="copyCoord()">Copy Koordinat</li>
                                    <li onclick="zoomHere()">Zoom di Sini</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                // ===================== 
                // FUNGSI HAVERSINE // 
                // ===================== 
                function haversineDistance(lat1, lon1, lat2, lon2) {
                    const R = 6371; // km 
                    const dLat = (lat2 - lat1) * Math.PI / 180;
                    const dLon = (lon2 - lon1) * Math.PI / 180;
                    const a = Math.sin(dLat / 2) ** 2 + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLon / 2) ** 2;
                    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                    return R * c; // hasil dalam KM 
                }
            </script>

            <!-- Leaflet Geocoder JS -->
            <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

            <script>
                // Tambah legend ke container map Leaflet 
                const mapContainer = document.querySelector('#peta');
                mapContainer.appendChild(document.getElementById('heatmap-legend'));

                // ============================ /
                // / INISIALISASI map // 
                // ============================ 
                var map = L.map('peta').setView([-6.200000, 106.816666], 13);

                // Tampilkan marker yg sudah diinput 
                var markersGroup = L.featureGroup().addTo(map);

                // DATA TOWER + POLYLINE + HEATmap (fetch hanya 1x!) 
                var towerCoordinates = [];

                fetch('{{ url ("/data/tower")}}')
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(item => {
                            let lat = parseFloat(item.latitude);
                            let lng = parseFloat(item.longitude);
                            if (!lat || !lng) return; // hindari NaN 
                            var latlng = [lat, lng];

                            // Simpan koordinat t
                            towerCoordinates.push(latlng);

                            // Tambah marker 
                            var marker = L.marker(latlng).bindPopup("<b>" + item.nama_tower + "</b>");
                            markersGroup.addLayer(marker);
                        });

                        // Fit map ke marker 
                        if (markersGroup.getLayers().length > 0) {
                            map.fitBounds(markersGroup.getBounds());
                        }

                        var antennaLinksLayer = L.layerGroup().addTo(map);

                        fetch('{{ url ("/data/antenna")}}').then(res => {
                            if (!res.ok) throw new Error("HTTP " + res.status);
                            return res.json();
                        }).then(antennas => {
                            console.log("Data antena dari DB:", antennas);
                            if (!antennas || antennas.length === 0) {
                                console.warn("Belum ada data antena");
                                return;
                            }
                            antennaLinksLayer.clearLayers();
                            // Warna sesuai jenis antena 
                            const warnaAntena = {
                                "Litebeam M5": "#0066ff",
                                "Grid": "#ff8800",
                                "Omni": "#00ff00",
                                "Yagi": "#ff00ff",
                                "Sectoral": "#ff0000"
                            };

                            antennas.forEach((antena, i) => {
                                const fromTower = antena.data_tower_from;
                                const toTower = antena.data_tower_to;
                                if (!fromTower || !toTower) {
                                    console.warn("Relasi tower tidak ditemukan pada:", antena);
                                    return;
                                }

                                const fromLat = parseFloat(fromTower.latitude);
                                const fromLng = parseFloat(fromTower.longitude);
                                const toLat = parseFloat(toTower.latitude);
                                const toLng = parseFloat(toTower.longitude);
                                if (!fromLat || !fromLng || !toLat || !toLng) {
                                    console.warn("Koordinat tidak valid:", antena);
                                    return;
                                }

                                L.polyline([
                                    [fromLat, fromLng],
                                    [toLat, toLng]
                                ], {
                                    color: warnaAntena[antena.jenis_antenna] || "#888888",
                                    weight: 5,
                                    opacity: 0.9,
                                    dashArray: antena.jenis_antenna === "Omni" ? "10, 10" : null // bonus: omni putus-putus 
                                }).bindTooltip(`<b>Jenis Antena:</b> ${antena.jenis_antenna}<br><b>Dari:</b> ${fromTower.nama_tower}<br><b>Ke:</b> ${toTower.nama_tower}`).addTo(antennaLinksLayer);
                            });

                            //Zoom ke semua link 
                            if (antennaLinksLayer.getLayers().length > 0) {
                                map.fitBounds(antennaLinksLayer.getBounds().pad(0.3));
                            }

                            console.log(`Berhasil gambar ${antennaLinksLayer.getLayers().length} link antena!`);

                        }).catch(err => {
                            console.error("Gagal load /data/antenna:", err);
                        });

                        // ============================== 
                        // 2. HEATmap // 
                        // ============================== 
                        let heatPoints = [];
                        fetch('{{ url ("/data/antenna")}}')
                            .then(res => res.json()).then(antennas => {
                                console.log("Generating heatmap dari link antena:", antennas);
                                antennas.forEach(antena => {
                                    const from = antena.data_tower_from;
                                    const to = antena.data_tower_to;
                                    if (!from || !to) return;
                                    const lat1 = parseFloat(from.latitude);
                                    const lng1 = parseFloat(from.longitude || from.longitude);
                                    const lat2 = parseFloat(to.latitude);
                                    const lng2 = parseFloat(to.longitude || to.longitude);
                                    if (!lat1 || !lat2) return;
                                    // Hitung jarak (semakin jauh = intensitas lebih rendah) 
                                    const distance = haversineDistance(lat1, lng1, lat2, lng2);
                                    const maxDistance = 50; // km (bisa diatur) 
                                    const baseIntensity = Math.max(0.3, 1 - (distance / maxDistance)); // minimal 0.3
                                    // Tambahkan intensitas berdasarkan jenis antena (Litebeam = lebih kuat) 
                                    let intensityBonus = 1.0;
                                    if (antena.jenis_antenna === "Litebeam M5") intensityBonus = 1.4;
                                    if (antena.jenis_antenna === "Grid") intensityBonus = 1.2;
                                    if (antena.jenis_antenna === "Omni") intensityBonus = 0.8;
                                    if (antena.jenis_antenna === "Yagi") intensityBonus = 1.0;

                                    // Buat 50 titik interpolasi di sepanjang link 
                                    for (let t = 0; t <= 50; t++) {
                                        const ratio = t / 50;
                                        const lat = lat1 + (lat2 - lat1) * ratio;
                                        const lng = lng1 + (lng2 - lng1) * ratio; // Intensitas menurun di ujung + bonus jenis antena
                                        let intensity = baseIntensity * (1 - ratio * 0.6) * intensityBonus;
                                        intensity = Math.min(1.0, Math.max(0.2, intensity)); // batas 0.2 - 1.0 
                                        heatPoints.push([lat, lng, intensity]);
                                    }
                                });

                                // Tambahkan titik kuat di setiap tower (biar pusat sinyal terlihat) 
                                antennas.forEach(antena => {
                                    const from = antena.data_tower_from;
                                    const to = antena.data_tower_to;
                                    if (from) heatPoints.push([parseFloat(from.latitude), parseFloat(from.longitude || from.longitude), 1.0]);
                                    if (to) heatPoints.push([parseFloat(to.latitude), parseFloat(to.longitude || to.longitude), 1.0]);
                                });

                                console.log("Total heat points:", heatPoints.length);

                                // GAMBAR HEATmap 
                                if (heatPoints.length > 0) {
                                    const heatLayer = L.heatLayer(heatPoints, {
                                        radius: 45,
                                        blur: 30,
                                        maxZoom: 16,
                                        minOpacity: 0.4,
                                        gradient: {
                                            0.0: '#1e40af', // biru tua (lemah) 
                                            0.3: '#3b82f6', // biru 
                                            0.5: '#10b981', // hijau 
                                            0.7: '#facc15', // kuning 
                                            0.9: '#f97316', // orange 
                                            1.0: '#ef4444' // merah (sangat kuat) 
                                        }
                                    }).addTo(map);

                                    // Optional: tambah toggle di layer control 
                                    // overlaymap["Coverage Heatmap"] = heatLayer; 
                                }
                            }).catch(err => console.error(err));
                    });

                // =============================== 
                // BASEmap LAYERS // 
                // =============================== 
                var osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19
                }).addTo(map);
                var satelit = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/mapServer/tile/{z}/{y}/{x}', {
                    maxZoom: 19
                });
                L.control.layers({
                    "OpenStreetmap": osm,
                    "Satelit": satelit
                }).addTo(map);

                // ========================= 
                // SCALE + FULLSCREEN 
                // ========================= 

                L.control.scale({
                    position: 'bottomleft'
                }).addTo(map);
                var mapId = document.getElementById('peta');

                function fullScreenView() {
                    mapId.requestFullscreen();
                }


                // ========================= 
                // MOUSE TRACKING TOOLTIP  
                // ========================= 

                var tooltipDiv = document.getElementById("mouse-follow");
                var contextMenu = document.getElementById("context-menu");
                var lastLatLng = null;
                map.on("mousemove", function(e) {
                    const mapPos = map.getContainer().getBoundingClientRect();
                    tooltipDiv.style.left = (e.originalEvent.clientX - mapPos.left + 15) + "px";
                    tooltipDiv.style.top = (e.originalEvent.clientY - mapPos.top + 15) + "px";
                    const lat = e.latlng.lat.toFixed(6);
                    const lng = e.latlng.lng.toFixed(6);
                    tooltipDiv.innerHTML = lat + ", " + lng;
                    tooltipDiv.style.display = "block";
                    lastLatLng = e.latlng;
                });

                map.on("mouseout", function() {
                    tooltipDiv.style.display = "none";
                }); // Disable browser context menu 
                map.getContainer().addEventListener("contextmenu", (e) => e.preventDefault());

                // CUSTOM CONTEXT MENU

                map.on("contextmenu", function(e) {
                    const mapPos = map.getContainer().getBoundingClientRect();
                    contextMenu.style.left = (e.originalEvent.clientX - mapPos.left) + "px";
                    contextMenu.style.top = (e.originalEvent.clientY - mapPos.top) + "px";
                    contextMenu.style.display = "block";
                    lastLatLng = e.latlng;
                });

                // map.on("click", function() { 
                // contextMenu.style.display = "none"; 
                // }); // document.body.addEventListener("click", function() { 
                // contextMenu.style.display = "none"; 
                // }); 
                // Add marker function addMarker() { L.marker(lastLatLng).addTo(map); 
                // contextMenu.style.display = "none"; 
                // });


                // Add marker 
                function addMarker() {
                    L.marker(lastLatLng).addTo(map);
                    contextMenu.style.display = "none";
                }

                // =============================== 
                // GET ELEVATION FROM OPENELEVATION // 
                // =============================== 
                async function getElevation(lat, lon) {
                    try {
                        const response = await fetch(`https://api.open-elevation.com/api/v1/lookup?locations=${lat},${lon}`);
                        const data = await response.json();
                        if (data && data.results && data.results.length > 0) {
                            return data.results[0].elevation;
                        } else {
                            return null;
                        }
                    } catch (error) {
                        console.error("Error fetching elevation:", error);
                        return null;
                    }


                    // ===================== 
                    // LOS (Line of Sight) 
                    // ===================== 
                    let pointA = null;
                    let pointB = null;
                    let losLine = null;
                    map.on("click", async function(e) {
                                const lat = e.latlng.lat.toFixed(6);
                                const lng = e.latlng.lng.toFixed(6);
                                // ============ AMBIL ELEVASI UNTUK POPUP ============ 
                                const elev = await getElevation(lat, lng);
                                // Popup informasi titik 
                                L.popup()
                                    .setLatLng(e.latlng)
                                    .setContent(
                                        `<b>Koordinat:</b> ${lat}, ${lng}<br><b>Elevasi:</b> ${elev !== null ? elev + " m" : "Tidak tersedia"}<br><br>`
                                    ) <
                                    b > < b > Koordinat: < /b> ${lat}, ${lng}<br><b>Elevasi:</b > $ {
                                        elev !== null ? elev + " m" : "Tidak tersedia"
                                    } < br > < br >
                            )
                            .openOn(map);



                        // ============ LOGIKA LOS ============ 
                        if (!pointA) {
                            pointA = e.latlng;
                            L.marker(pointA).addTo(map).bindTooltip("Point A").openTooltip();
                            return;
                        }
                        if (!pointB) {
                            pointB = e.latlng;
                            L.marker(pointB).addTo(map).bindTooltip("Point B").openTooltip();
                            if (losLine) {
                                map.removeLayer(losLine);
                            }
                            losLine = L.polyline([pointA, pointB], {
                                color: "red",
                                weight: 3
                            }).addTo(map); // Jalankan perhitungan LOS lama 
                            hitungLOS();
                            return;
                        }
                    });


                // Copy koordinat 
                function copyCoord() {
                    const lat = lastLatLng.lat.toFixed(6);
                    const lng = lastLatLng.lng.toFixed(6);
                    const coordText = `${lat}, ${lng}`;
                    navigator.clipboard.writeText(coordText).then(() => {
                        alert("Koordinat disalin: " + coordText);
                    }).catch(err => {
                        console.error("Gagal menyalin koordinat:", err);
                    });
                    contextMenu.style.display = "none";
                }

                // Zoom here 
                function zoomHere() {
                    map.setView(lastLatLng, map.getZoom() + 2);
                    contextMenu.style.display = "none";
                }
                // Ambil elevasi dari A ke B (100 titik) 
                async function hitungLOS() {
                    const steps = 100;
                    let locations = [];
                    for (let i = 0; i <= steps; i++) {
                        let lat = pointA.lat + (pointB.lat - pointA.lat) * (i / steps);
                        let lng = pointA.lng + (pointB.lng - pointA.lng) * (i / steps);
                        locations.push(lat + "," + lng);
                    }

                    const url = https: //api.open-elevation.com/api/v1/lookup?locations=${locations.join("|")};
                        const res = await
                    fetch(url);
                    const json = await res.json();
                    const elevations = json.results.map(r => r.elevation);
                    tampilkanGrafikLOS(elevations);
                }

                function tampilkanGrafikLOS(elev) {
                    const distanceKm = haversineDistance(pointA.lat, pointA.lng, pointB.lat, pointB.lng);
                    const stepDistance = distanceKm * 1000 / elev.length;
                    // Tinggi antenna bisa diganti sesuai tower 
                    /const towerA = 30; / / meter
                    const towerB = 30; // meter 
                    // Hitung horizon + refraction 
                    /const R = 6371000 * 0.87; let losLine = elev.map((h, i) => h + towerA + ((towerB - towerA) * (i /
                    elev.length)));
                let earthCurve = elev.map((_, i) => {
                    const d = stepDistance * i;
                    return (d * d) / (2 * R);
                });

                // LOS yang memperhitungkan bumi 
                let losClearance = losLine.map((l, i) => l - earthCurve[i]);
                // Cek apakah ada tanah yang lebih tinggi dari LOS 
                let blocked = false;
                for (let i = 0; i < elev.length; i++) {
                    if (elev[i] >= losClearance[i]) {
                        blocked = true;
                    }
                }
                // Tampilkan panel LOS 
                document.getElementById("los-panel").style.display = "block";

                // Gambar grafik 
                new Chart(document.getElementById("losChart"), {
                    type: 'line',
                    data: {
                        labels: elev.map((_, i) => (i * stepDistance).toFixed(0)),
                        datasets: [{
                            label: "Elevasi Tanah (m)",
                            data: elev,
                            borderWidth: 2,
                            tension: 0.3
                        }, {
                            label: "LOS Line (m)",
                            data: losClearance,
                            borderWidth: 2,
                            borderDash: [5, 5],
                            tension: 0.3
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: false
                            }
                        }
                    }
                });
                }
            </script>
            @endsection