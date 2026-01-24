@extends('be.master')
@section('sidebar')
@include('be.sidebar')
@endsection

@section('navbar')
@include('be.navbar')
@endsection

@section('content')
<!-- Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Leaflet Heat + Geocoder + Routing if needed -->
<script src="https://unpkg.com/leaflet.heat/dist/leaflet-heat.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

<!-- Option: Leaflet Geodesic (if available) -->
<script src="https://cdn.jsdelivr.net/npm/leaflet.geodesic@0.2.0/Leaflet.Geodesic.min.js"></script>

<!-- Chart.js for LOS (already used) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<main class="content-wrapper">
    <div class="container-fluid py-4">
        <div class="card mb-4">
            <div class="card-header pb-0">
                <h4>{{$title}}</h4>
            </div>
            <div class="card-body">
                <div id="peta"></div>

                <div id="heatmap-legend" class="heatmap-legend">
                    <span>Weak</span>
                    <div class="heatmap-bar"></div>
                    <span>Strong</span>
                </div>

                <!-- Top PTP Info Panel -->
                <div id="ptp-panel" class="ptp-panel" style="display:none;">
                    <div class="left">
                        <h4 id="ptp-title">PtP Link</h4>
                        <div class="meta">
                            <div><b>From:</b> <span id="ptp-from">-</span></div>
                            <div><b>To:</b> <span id="ptp-to">-</span></div>
                        </div>
                    </div>
                    <div class="right">
                        <div><b id="ptp-distance">-</b> km</div>
                        <div class="muted" id="ptp-capacity">Capacity: - Gbps</div>
                        <div class="ptp-actions" style="margin-top:8px;">
                            <button onclick="clearPtP()">Reset</button>
                            <button class="secondary" onclick="exportLink()">Export</button>
                        </div>
                    </div>
                </div>

                <!-- Bottom summary -->
                <div id="bottom-summary" style="display:none;">
                    <span id="bottom-text">Distance: - km | Capacity: - Gbps</span>
                </div>

                <!-- LOS panel kept from your code -->
                <div id="los-panel" style="display:none; margin-top: 12px;">
                    <h5>Line of Sight (LOS) Result</h5>
                    <canvas id="losChart" height="120"></canvas>
                    <div id="los-info" style="margin-top:10px; font-size:14px;"></div>
                </div>

            </div>
        </div>
    </div>
</main>

<script>
    /* =========================
   INIT MAP & LAYERS
   ========================= */
    const map = L.map('peta').setView([-6.200000, 106.816666], 12);

    // base layers
    const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19
    }).addTo(map);
    const sat = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/mapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19
    });

    L.control.layers({
        'OSM': osm,
        'Satellite': sat
    }).addTo(map);
    L.control.scale({
        position: 'bottomleft'
    }).addTo(map);

    /* =========================
   SEARCH CONTROL
   ========================= */
    L.Control.geocoder({
            defaultMarkGeocode: false,
            placeholder: "Cari lokasi...",
        })
        .on('markgeocode', function(e) {
            const center = e.geocode.center;

            // Tambah marker hasil pencarian
            L.marker(center)
                .addTo(map)
                .bindPopup(`<b>${e.geocode.name}</b><br>${center.lat.toFixed(6)}, ${center.lng.toFixed(6)}`)
                .openPopup();

            // Zoom ke lokasi
            map.setView(center, 16);
        })
        .addTo(map);


    /* =========================
       Utility: haversine distance
       ========================= */
    function haversineDistance(lat1, lon1, lat2, lon2) {
        const R = 6371;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) ** 2 + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLon / 2) ** 2;
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    /* =========================
       Global state for PtP creation
       ========================= */
    let pointA = null,
        pointB = null;
    let markerA = null,
        markerB = null;
    let ptpLine = null; // geodesic or polyline
    let namedIndex = 1;

    /* =========================
       Helper: create named marker
       - permanent label tooltip using tooltip options
       ========================= */
    function createNamedMarker(latlng, name, iconUrl = null) {
        const options = {};
        if (iconUrl) {
            options.icon = L.icon({
                iconUrl,
                iconSize: [34, 34],
                iconAnchor: [17, 34],
                popupAnchor: [0, -30]
            });
        }

        const mk = L.marker(latlng, options).addTo(map);
        mk.bindTooltip(name, {
            permanent: true,
            direction: 'right',
            className: 'my-label'
        });
        mk.on('click', () => mk.openPopup && mk.openPopup());
        return mk;
    }



    /* =========================
       Load existing towers & antennas (kept from original)
       ========================= */
    const markersGroup = L.featureGroup().addTo(map);
    const antennaLinksLayer = L.layerGroup().addTo(map);

    // fetch towers
    fetch('{{ url("/data/tower") }}')
        .then(r => r.json())
        .then(data => {

            data.forEach(t => {
                const lat = parseFloat(t.latitude),
                    lng = parseFloat(t.longitude);
                if (!lat || !lng) return;
                markersGroup.addLayer(L.marker([lat, lng]).bindPopup(`<b>${t.nama_tower}</b>`));
            });
            if (markersGroup.getLayers().length) map.fitBounds(markersGroup.getBounds().pad(0.25));
        })
        .catch(e => console.warn('Error loading towers', e));


    // fetch antenna links (as in your code)
    fetch('{{ url("/data/antenna") }}')
        .then(r => r.json())
        .then(antennas => {

            const warnaAntena = {
                "Litebeam M5": "#0066ff",
                "Grid": "#ff8800",
                "Omni": "#00ff00",
                "Yagi": "#ff00ff",
                "Sectoral": "#ff0000"
            };

            antennas.forEach(a => {
                const from = a.data_tower_from;
                const to = a.data_tower_to;

                if (!from || !to) return;

                const fromLat = parseFloat(from.latitude);
                const fromLng = parseFloat(from.longitude || from.longitude);
                const toLat = parseFloat(to.latitude);
                const toLng = parseFloat(to.longitude || to.longitude);

                if (!fromLat || !fromLng || !toLat || !toLng) {
                    console.warn("Koordinat tidak valid:", antena);
                    return;
                }

                // Jika ingin multi segment (lebih panjang), tinggal push ke array segments

                L.polyline([
                        [fromLat, fromLng],
                        [toLat, toLng]
                    ], {
                        color: warnaAntena[antena.jenis_antenna] || "#888888",
                        weight: 5,
                        opacity: 0.9,
                        dashArray: antena.jenis_antenna === "Omni" ? "10, 10" : null // bonus: omni putus-putus
                    })
                    .bindTooltip(`
                <b>${antena.jenis_antenna}</b><br>
                ${fromTower.nama_tower} → ${toTower.nama_tower}<br>
                Jarak: ${haversineDistance(fromLat, fromLng, toLat, toLng).toFixed(2)} km
            `, {
                        sticky: true
                    })
                    .bindPopup(`
                <div style="font-size:13px">
                    <h6>Link #${i+1}</h6>
                    <b>Jenis:</b> ${antena.jenis_antenna}<br>
                    <b>Dari:</b> ${fromTower.nama_tower}<br>
                    <b>Ke:</b> ${toTower.nama_tower}<br>
                    <small>${fromLat.toFixed(5)}, ${fromLng.toFixed(5)} → ${toLat.toFixed(5)}, ${toLng.toFixed(5)}</small>
                </div>
            `)
                    .addTo(antennaLinksLayer);
            });

            // Zoom ke semua link
            if (antennaLinksLayer.getLayers().length > 0) {
                map.fitBounds(antennaLinksLayer.getBounds().pad(0.3));
            }

            console.log(`Berhasil gambar ${antennaLinksLayer.getLayers().length} link antena!`);
        })
        .catch(err => {
            console.error("Gagal load /data/antenna:", err);
        });

    // ========================= Heatmap Generation =========================

    let heatPoints = [];

    fetch('/data/antenna')
        .then(res => res.json())
        .then(antennas => {
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
                    const lng = lng1 + (lng2 - lng1) * ratio;

                    // Intensitas menurun di ujung + bonus jenis antena
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

            // GAMBAR HEATMAP
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
                // overlayMaps["Coverage Heatmap"] = heatLayer;
            }


        })
        .catch(err => console.error(err));


    /* =========================
       PtP interaction
       - Click once => set Point A (named)
       - Click second => set Point B, draw line & update panel
       - Click third => reset & start new
       ========================= */
    map.on('click', async function(e) {
        // if both exist, reset to start new link
        if (pointA && pointB) {
            clearPtP();
        }

        // get latlng
        const lat = e.latlng.lat,
            lng = e.latlng.lng;

        // if not pointA, set pointA
        if (!pointA) {
            pointA = e.latlng;
            const name = `Main${namedIndex}`;
            markerA = createNamedMarker(pointA, name, 'https://cdn-icons-png.flaticon.com/512/684/684908.png');
            markerA.bindPopup(`<b>${name}</b><br>${pointA.lat.toFixed(6)}, ${pointA.lng.toFixed(6)}`).openPopup();
            updateTopPanel(); // will show partial info
            return;
        }

        // if pointA exists but not B, set B and create PtP
        if (!pointB) {
            pointB = e.latlng;
            const name = `Station${namedIndex}`;
            markerB = createNamedMarker(pointB, name, 'https://cdn-icons-png.flaticon.com/512/684/684910.png');
            markerB.bindPopup(`<b>${name}</b><br>${pointB.lat.toFixed(6)}, ${pointB.lng.toFixed(6)}`);

            // draw line (prefer geodesic if available)
            drawPtPLine(pointA, pointB);

            // compute distance & fake capacity based on device choice (you can expand)
            const dist = haversineDistance(pointA.lat, pointA.lng, pointB.lat, pointB.lng);
            const capacityGbps = estimateCapacity(dist, '60 GHz'); // example

            // set UI
            document.getElementById('ptp-from').innerText = markerA.getTooltip().getContent();
            document.getElementById('ptp-to').innerText = markerB.getTooltip().getContent();
            document.getElementById('ptp-distance').innerText = dist.toFixed(2);
            document.getElementById('ptp-capacity').innerText = `Capacity: ${capacityGbps.toFixed(2)} Gbps`;
            document.getElementById('ptp-title').innerText = `PtP Link #${namedIndex}`;

            document.getElementById('ptp-panel').style.display = 'flex';
            document.getElementById('bottom-summary').style.display = 'block';
            document.getElementById('bottom-text').innerText = `Distance: ${dist.toFixed(2)} km | Capacity: ${capacityGbps.toFixed(2)} Gbps`;

            // compute LOS & show chart area (optional)
            await hitungLOS(); // uses pointA & pointB from earlier, will show LOS panel

            namedIndex += 1;
            return;
        }
    });

    /* =========================
       Draw PtP line (geodesic fallback to polyline)
       ========================= */
    function drawPtPLine(a, b) {
        // remove existing
        if (ptpLine) map.removeLayer(ptpLine);

        try {
            if (typeof L.geodesic === 'function' || L.Geodesic) {
                // plugin loaded
                ptpLine = L.geodesic([
                    [
                        [a.lat, a.lng],
                        [b.lat, b.lng]
                    ]
                ], {
                    weight: 4,
                    color: '#06b6d4',
                    opacity: 0.95
                }).addTo(map);
            } else {
                ptpLine = L.polyline([
                    [a.lat, a.lng],
                    [b.lat, b.lng]
                ], {
                    color: '#06b6d4',
                    weight: 4,
                    opacity: 0.95
                }).addTo(map);
            }
        } catch (err) {
            ptpLine = L.polyline([
                [a.lat, a.lng],
                [b.lat, b.lng]
            ], {
                color: '#06b6d4',
                weight: 4,
                opacity: 0.95
            }).addTo(map);
        }

        // fit bounds a bit
        map.fitBounds(L.featureGroup([L.marker([a.lat, a.lng]), L.marker([b.lat, b.lng])]).getBounds().pad(0.25));
    }

    /* =========================
       Simple capacity estimator (demo)
       - you can replace with real device DB
       ========================= */
    function estimateCapacity(distanceKm, band = '60 GHz') {
        // simplistic model: capacity decays with distance
        let base = 2.0; // Gbps baseline for short link
        if (band.includes('60')) base = 2.5;
        if (distanceKm > 10) base *= 0.5;
        if (distanceKm > 20) base *= 0.3;
        // clamp
        return Math.max(0.05, base - distanceKm * 0.02);
    }

    /* =========================
       UI helpers
       ========================= */
    function updateTopPanel() {
        if (!pointA) {
            document.getElementById('ptp-panel').style.display = 'none';
            return;
        }
        document.getElementById('ptp-panel').style.display = 'flex';
        document.getElementById('ptp-from').innerText = markerA ? markerA.getTooltip().getContent() : '-';
        document.getElementById('ptp-to').innerText = pointB ? (markerB.getTooltip().getContent()) : '-';
        document.getElementById('ptp-distance').innerText = pointB ? haversineDistance(pointA.lat, pointA.lng, pointB.lat, pointB.lng).toFixed(2) : '-';
    }

    /* Reset/clear the current PtP (keeps existing tower data) */
    function clearPtP() {
        if (markerA) {
            map.removeLayer(markerA);
            markerA = null;
        }
        if (markerB) {
            map.removeLayer(markerB);
            markerB = null;
        }
        if (ptpLine) {
            map.removeLayer(ptpLine);
            ptpLine = null;
        }
        pointA = null;
        pointB = null;
        document.getElementById('ptp-panel').style.display = 'none';
        document.getElementById('bottom-summary').style.display = 'none';
        document.getElementById('los-panel').style.display = 'none';
    }

    /* Export PtP (simple JSON download) */
    function exportLink() {
        if (!pointA || !pointB) {
            alert('Create a PtP first');
            return;
        }
        const payload = {
            from: {
                name: markerA.getTooltip().getContent(),
                lat: pointA.lat,
                lng: pointA.lng
            },
            to: {
                name: markerB.getTooltip().getContent(),
                lat: pointB.lat,
                lng: pointB.lng
            },
            distance_km: haversineDistance(pointA.lat, pointA.lng, pointB.lat, pointB.lng)
        };
        const blob = new Blob([JSON.stringify(payload, null, 2)], {
            type: 'application/json'
        });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'ptp-link.json';
        a.click();
        URL.revokeObjectURL(url);
    }

    /* =========================
       Mouse tooltip & context menu (kept from your code)
       ========================= */
    const tooltipDiv = L.DomUtil.create('div', '', map.getContainer());
    tooltipDiv.style.position = 'absolute';
    tooltipDiv.style.pointerEvents = 'none';
    tooltipDiv.style.padding = '6px 8px';
    tooltipDiv.style.background = 'rgba(255,255,255,0.95)';
    tooltipDiv.style.borderRadius = '6px';
    tooltipDiv.style.fontSize = '12px';
    tooltipDiv.style.display = 'none';
    tooltipDiv.style.zIndex = 9999;

    map.on('mousemove', (e) => {
        const mapPos = map.getContainer().getBoundingClientRect();
        tooltipDiv.style.left = (e.originalEvent.clientX - mapPos.left + 15) + 'px';
        tooltipDiv.style.top = (e.originalEvent.clientY - mapPos.top + 15) + 'px';
        tooltipDiv.innerHTML = `${e.latlng.lat.toFixed(6)}, ${e.latlng.lng.toFixed(6)}`;
        tooltipDiv.style.display = 'block';
    });
    map.on('mouseout', () => tooltipDiv.style.display = 'none');

    /* =========================
       Elevation & LOS (kept & integrated)
       - uses open-elevation (same as your code)
       ========================= */
    async function getElevation(lat, lon) {
        try {
            const r = await fetch(`https://api.open-elevation.com/api/v1/lookup?locations=${lat},${lon}`);
            const j = await r.json();
            return j.results && j.results[0] ? j.results[0].elevation : null;
        } catch (err) {
            console.warn('elev err', err);
            return null;
        }
    }

    async function hitungLOS() {
        if (!pointA || !pointB) return;
        const steps = 100;
        const locations = [];
        for (let i = 0; i <= steps; i++) {
            const lat = pointA.lat + (pointB.lat - pointA.lat) * (i / steps);
            const lng = pointA.lng + (pointB.lng - pointA.lng) * (i / steps);
            locations.push(`${lat},${lng}`);
        }
        try {
            const url = `https://api.open-elevation.com/api/v1/lookup?locations=${locations.join('|')}`;
            const res = await fetch(url);
            const json = await res.json();
            const elevations = json.results.map(r => r.elevation);
            tampilkanGrafikLOS(elevations);
        } catch (err) {
            console.warn('LOS fail', err);
        }
    }

    function tampilkanGrafikLOS(elev) {
        document.getElementById('los-panel').style.display = 'block';
        const distanceKm = haversineDistance(pointA.lat, pointA.lng, pointB.lat, pointB.lng);
        const stepDistance = distanceKm * 1000 / elev.length;
        const towerA = 30,
            towerB = 30; // example heights (you can make UI)
        const R = 6371000 * 0.87; // effective
        const losLine = elev.map((h, i) => h + towerA + ((towerB - towerA) * (i / elev.length)));
        const earthCurve = elev.map((_, i) => {
            const d = stepDistance * i;
            return (d * d) / (2 * R);
        });
        const losClearance = losLine.map((l, i) => l - earthCurve[i]);

        // check blocked
        let blocked = false;
        for (let i = 0; i < elev.length; i++) {
            if (elev[i] >= losClearance[i]) {
                blocked = true;
                break;
            }
        }

        // update los info
        document.getElementById('los-info').innerHTML = `<b>Status:</b> ${blocked ? '<span style="color:red">Blocked</span>' : '<span style="color:green">Clear</span>'}`;

        // draw chart
        new Chart(document.getElementById('losChart'), {
            type: 'line',
            data: {
                labels: elev.map((_, i) => (i * stepDistance).toFixed(0)),
                datasets: [{
                        label: 'Elevasi Tanah (m)',
                        data: elev,
                        borderWidth: 2,
                        tension: 0.3
                    },
                    {
                        label: 'LOS Line (m)',
                        data: losClearance,
                        borderWidth: 2,
                        borderDash: [5, 5],
                        tension: 0.3
                    }
                ]
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

    /* =========================
       Misc helpers (copy coord, zoom) - keep existing behaviour if you want
       ========================= */
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => alert('Copied: ' + text));
    }
</script>
@endsection