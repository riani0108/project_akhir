// resources/js/map.js
let routeLine = null;
let routeHeatLayer = null;
let coverageHeatLayer = null;
let routingMachine = null;
let startMarker = null;
let destMarker = null;
let lastLatLng = null;
let map = null;

// =============================
// STORAGE KEY PER PELANGGAN
// =============================
function getPtMPStorageKey() {
    if (!window.PELANGGAN_ID) return null;
    return `ptmp_state_user_${window.PELANGGAN_ID}`;
}

// ============================
// LINK BUDGET CONFIG (PtMP)
// ============================
const radioProfile = {
    txPower: 23, // dBm
    // txGain: 17, // dBi AP
    // rxGain: 17, // dBi Client
    cableLoss: 1.5, // dB
    noiseFloor: -95, // dBm
};

const LINK_FREQ_GHZ = 6;

const antennaDB = {
    // ───────────────────────────────────────────────
    //          ANTENA ACCESS POINT (TOWER / AP)
    // ───────────────────────────────────────────────
    sector_90_17: {
        name: "Sector 90° 17 dBi (5 GHz)",
        type: "ap",
        gain: 17,
        beamwidth: 90, // lebar sektor 90°
        maxDistanceKm: 5, // realistis PtMP di kota/pedesaan
        polarization: "dual",
        recommendedFor: "PtMP 10–30 client",
        color: "#3b82f6", // biru sector
    },

    sector_120_16: {
        name: "Sector 120° 16 dBi (5 GHz)",
        type: "ap",
        gain: 16,
        beamwidth: 120,
        maxDistanceKm: 4,
        polarization: "dual",
        recommendedFor: "PtMP area luas",
        color: "#2563eb",
    },

    omni_12: {
        name: "Omni 12 dBi (5 GHz)",
        type: "ap",
        gain: 12,
        beamwidth: 360, // full circle
        maxDistanceKm: 2.5,
        polarization: "vertical",
        recommendedFor: "Hotspot kecil / coverage 360°",
        color: "#8b5cf6",
    },

    grid_27_backhaul: {
        name: "Grid 27 dBi (Backhaul PtP)",
        type: "ap",
        gain: 27,
        beamwidth: 8, // sangat sempit
        maxDistanceKm: 20, // jarak jauh PtP
        polarization: "horizontal or vertical",
        recommendedFor: "Point-to-Point jarak jauh",
        color: "#dc2626", // merah = backhaul
    },

    // ───────────────────────────────────────────────
    //          ANTENA CLIENT (PELANGGAN / CPE)
    // ───────────────────────────────────────────────
    nanobeam_19: {
        name: "NanoBeam 19 dBi (5 GHz)",
        type: "client",
        gain: 19,
        beamwidth: 30,
        maxDistanceKm: 5,
        maxTxPower: 23,
        polarization: "dual (slant ±45°)",
        sensitivity: { mcs0: -96, mcs8: -70 },
        recommendedFor: "PtMP jarak sedang",
    },

    litebeam_23: {
        name: "LiteBeam AC 23 dBi",
        type: "client",
        gain: 23,
        beamwidth: 10,
        maxDistanceKm: 10,
        maxTxPower: 25,
        polarization: "dual",
        sensitivity: { mcs0: -96, mcs9: -65 },
        recommendedFor: "PtMP jarak menengah-jauh",
    },

    powerbeam_25: {
        name: "PowerBeam 25 dBi",
        type: "client",
        gain: 25,
        beamwidth: 8,
        maxDistanceKm: 15,
        maxTxPower: 27,
        polarization: "dual (slant ±45°)",
        sensitivity: { mcs0: -96, mcs9: -65 },
        recommendedFor: "PtMP / PtP jarak jauh",
    },

    dish_30: {
        name: "Dish / Parabolic 30 dBi",
        type: "client",
        gain: 30,
        beamwidth: 5,
        maxDistanceKm: 25,
        maxTxPower: 27,
        polarization: "dual or single",
        sensitivity: { mcs0: -97, mcs9: -68 },
        recommendedFor: "Link backbone / PtP sangat jauh",
    },

    grid_24_client: {
        name: "Grid 24 dBi (Client)",
        type: "client",
        gain: 24,
        beamwidth: 9,
        maxDistanceKm: 12,
        maxTxPower: 23,
        polarization: "horizontal or vertical",
        recommendedFor: "PtP atau PtMP jarak jauh",
    },
};

let selectedAPAntenna = antennaDB["sector_90_17"];
let selectedClientAntenna = antennaDB["litebeam_23"];

// ============================
// LINK BUDGET FUNCTIONS
// ============================
function calculateFSPL(distanceKm, freqGHz) {
    const freqMHz = freqGHz * 1000;
    return 32.44 + 20 * Math.log10(distanceKm) + 20 * Math.log10(freqMHz);
}

function calculateLinkBudget(distanceKm, freqGHz, radio) {
    const fspl = calculateFSPL(distanceKm, freqGHz);

    const rxPower =
        radio.txPower + radio.txGain + radio.rxGain - fspl - radio.cableLoss;

    const snr = rxPower - radio.noiseFloor;

    return { fspl, rxPower, snr };
}

// ============================
// FRESNEL ZONE CALCULATION
// ============================
function fresnelRadius(d1Km, d2Km, freqGHz) {
    const D = d1Km + d2Km;
    if (D === 0) return 0;

    return 17.32 * Math.sqrt((d1Km * d2Km) / (freqGHz * D));
}

function getLinkColor(rxPower) {
    if (rxPower > -60) return "#22c55e"; // hijau
    if (rxPower > -75) return "#facc15"; // kuning
    return "#ef4444"; // merah
}

// ============================
// FRESNEL STATUS HELPER
// ============================
function fresnelStatus(clearanceRatio) {
    if (clearanceRatio >= 0.6) return "🟢 Clear";
    if (clearanceRatio >= 0.4) return "🟡 Marginal";
    return "🔴 Blocked";
}

// ============================
// PTMP SIMULATION MODE
// ============================
let ptmpMode = true;
let apMarker = null;
let clientMarkers = [];
let ptmpLines = [];
let ptmpSector = null;
let ptmpBeamwidth = selectedAPAntenna.beamwidth;
let ptmpRadiusKm = selectedAPAntenna.maxDistanceKm;

// ================================
// 🎯 Cursor Mode (UISP Style)
// ================================
function updateCursorMode() {
    const mapEl = document.getElementById("map");

    mapEl.classList.remove("cursor-ap", "cursor-client");

    if (!ptmpMode) return;

    if (placingAP) {
        mapEl.classList.add("cursor-ap");
    } else if (placingClient) {
        mapEl.classList.add("cursor-client");
    }
}

// ============================
// PTMP ACCESS POINT ICON
// ============================
const apIcon = new L.Icon({
    iconUrl: "https://cdn-icons-png.flaticon.com/512/1048/1048953.png",
    shadowUrl: "https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png",
    iconSize: [38, 38],
    iconAnchor: [19, 19],
    popupAnchor: [0, -15],
});

const destIcon = new L.Icon({
    iconUrl:
        "https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png",
    shadowUrl: "https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png",
    iconSize: [25, 41],
    iconAnchor: [12, 41],
});

document.addEventListener("DOMContentLoaded", function () {
    filterAPAntennaOptions();
    // ============================
    // 🔥 RESET STATE SAAT HALAMAN DIBUKA
    // ============================
    routeLine = null;
    routeHeatLayer = null;
    coverageHeatLayer = null;
    routingMachine = null;
    startMarker = null;
    destMarker = null;
    lastLatLng = null;

    apMarker = null;
    clientMarkers = [];
    ptmpLines = [];
    ptmpSector = null;

    const mapContainer = document.querySelector("#map");
    const legend = document.getElementById("heatmap-legend");
    if (mapContainer && legend) {
        mapContainer.appendChild(legend);
    }

    // ============================
    // UISP SECTOR FUNCTIONS
    // ============================

    function getAzimuth(lat1, lng1, lat2, lng2) {
        const dLon = ((lng2 - lng1) * Math.PI) / 180;
        lat1 *= Math.PI / 180;
        lat2 *= Math.PI / 180;

        const y = Math.sin(dLon) * Math.cos(lat2);
        const x =
            Math.cos(lat1) * Math.sin(lat2) -
            Math.sin(lat1) * Math.cos(lat2) * Math.cos(dLon);

        let brng = (Math.atan2(y, x) * 180) / Math.PI;
        return (brng + 360) % 360;
    }

    function getSectorColor(distanceKm) {
        if (distanceKm < 3) return "#22c55e"; // hijau
        if (distanceKm < 6) return "#facc15"; // kuning
        return "#ef4444"; // merah
    }

    function createSector(center, azimuth, beamwidth, radiusKm, color) {
        const points = [];
        const steps = 60;

        const start = azimuth - beamwidth / 2;
        const end = azimuth + beamwidth / 2;

        for (let i = 0; i <= steps; i++) {
            const angle = start + (i / steps) * (end - start);

            const destination = L.GeometryUtil.destination(
                L.latLng(center),
                angle,
                radiusKm * 1000,
            );

            points.push([destination.lat, destination.lng]);
        }

        points.unshift(center);

        return L.polygon(points, {
            pane: "sectorPane",
            color: color,
            fillColor: color,
            fillOpacity: 0.35,
            weight: 0,
            clickable: false,
            interactive: false,
        });
    }

    function drawPtMPSector() {
        if (!apMarker) return;

        if (ptmpSector) map.removeLayer(ptmpSector);

        if (clientMarkers.length === 0) return;

        const ap = apMarker.getLatLng();

        // arah sector = rata-rata azimuth client
        let sum = 0;
        clientMarkers.forEach((c) => {
            const cl = c.getLatLng();
            sum += getAzimuth(ap.lat, ap.lng, cl.lat, cl.lng);
        });

        const avgAzimuth = sum / clientMarkers.length;

        const color =
            selectedAPAntenna.beamwidth <= 15
                ? "#dc2626" // merah = grid / backhaul
                : "#3b82f6"; // biru = sector

        ptmpSector = createSector(
            [ap.lat, ap.lng],
            avgAzimuth,
            ptmpBeamwidth,
            ptmpRadiusKm,
            color,
        ).addTo(map);
    }

    let map;
    let towerLayer;
    let linkLayer;
    let heatLayer;

    // ============================
    // INISIALISASI MAP (AWAL)
    // ============================
    map = L.map("map");

    // Tile layer WAJIB
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
    }).addTo(map);

    // Layer group
    towerLayer = L.layerGroup().addTo(map); // khusus tower PtP
    linkLayer = L.layerGroup().addTo(map); // khusus garis PtP
    heatLayer = L.layerGroup().addTo(map);

    // ============================
    // GEOLOCATION USER
    // ============================
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;

                map.setView([lat, lng], 15);

                L.marker([lat, lng])
                    .addTo(map)
                    .bindPopup("📍 Lokasi Anda")
                    .openPopup();
            },
            () => {
                // fallback
                map.setView([-6.2, 106.816666], 13);
            },
            {
                enableHighAccuracy: true,
                timeout: 8000,
                maximumAge: 0,
            },
        );
    } else {
        map.setView([-6.2, 106.816666], 13);
    }

    const losLegend = L.control({ position: "bottomright" });

    losLegend.onAdd = function () {
        const div = L.DomUtil.create("div", "map-legend");
        div.innerHTML = `
        <b>LOS Status</b><br>
        <div><span class="legend-box clear"></span> Clear</div>
        <div><span class="legend-box marginal"></span> Marginal</div>
        <div><span class="legend-box blocked"></span> Blocked</div>
    `;
        return div;
    };

    losLegend.addTo(map);

    // ============================
    // PANE LAYER
    // ============================
    map.createPane("heatPane");
    map.getPane("heatPane").style.zIndex = 300;

    map.createPane("routePane");
    map.getPane("routePane").style.zIndex = 400;

    map.createPane("markerPaneCustom");
    map.getPane("markerPaneCustom").style.zIndex = 500;
    map.createPane("sectorPane");
    map.getPane("sectorPane").style.zIndex = 450;

    const osm = L.tileLayer(
        "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
        { maxZoom: 19 },
    ).addTo(map);

    const satelit = L.tileLayer(
        "https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",
        { maxZoom: 19 },
    );

    L.control.layers({ OpenStreetMap: osm, Satelit: satelit }).addTo(map);

    const straightLineLayer = L.featureGroup().addTo(map);
    const sectorLayer = L.featureGroup().addTo(map);

    setTimeout(() => {
        const savedState = loadPtMPState();
        if (savedState) restorePtMPState(savedState);
    }, 0);

    // ============================
    // 📡 PtMP Horizontal Control
    // ============================
    const PtMPControlHorizontal = L.Control.extend({
        options: {
            position: "topright", // aman, tidak tabrakan zoom
        },

        onAdd: function () {
            const container = L.DomUtil.create(
                "div",
                "leaflet-bar ptmp-control-horizontal",
            );

            container.innerHTML = `
            <a id="ui-place-ap" title="Place Access Point">
                <i class="fas fa-broadcast-tower"></i>
            </a>
            <a id="ui-add-client" title="Add Client">
                <i class="fas fa-satellite-dish"></i>
            </a>
            <a id="ui-clear" title="Clear PtMP">
                <i class="fas fa-trash"></i>
            </a>
        `;

            // prevent map drag
            L.DomEvent.disableClickPropagation(container);
            L.DomEvent.disableScrollPropagation(container);

            return container;
        },
    });

    map.addControl(new PtMPControlHorizontal());

    fetch("/data/input-lokasi")
        .then((res) => res.json())
        .then((data) => {
            console.log("INPUT LOKASI:", data);
            data.forEach((tower) => {
                if (!tower.koordinat) return;

                const [lat, lng] = tower.koordinat.split(",");

                L.marker([lat, lng]).addTo(map).bindPopup(`
                        <b>${tower.nama}</b><br>
                        Tinggi: ${tower.tinggi_tower} m<br>
                        ${tower.alamat ?? ""}
                    `);
            });
        });

    // Tampilkan marker tower
    const markersGroup = L.featureGroup().addTo(map);
    const towerCoordinates = [];
    const towerMarkers = [];

    fetch("/data/tower")
        .then((response) => response.json())
        .then((data) => {
            data.forEach((item) => {
                let lat = parseFloat(item.latitude);
                let lng = parseFloat(item.longitude);

                if (!lat || !lng) return;

                const latlng = [lat, lng];
                towerCoordinates.push(latlng);

                const marker = L.marker(latlng);

                let popupHTML = `
<div class="tower-popup">
    <h4>${item.nama_tower}</h4>
    <div class="tower-info"><b>ID Tower:</b> ${item.id}</div>
    <div class="tower-info"><b>Alamat:</b> ${item.alamat_tower}</div>
    <div class="tower-info"><b>Latitude:</b> ${lat.toFixed(6)}</div>
    <div class="tower-info"><b>Longitude:</b> ${lng.toFixed(6)}</div>

    <hr>
    <div class="tower-info"><b>Ketinggian Tower:</b> ${
        item.tinggi_tower || "Tidak ada data"
    } m</div>
    <div class="tower-info"><b>Jenis Antenna:</b> ${
        item.jenis_antenna || "Tidak ada data"
    }</div>

    <hr>
    <button onclick="focusTower(${lat}, ${lng})" class="btn-focus-map">
        Fokuskan Map
    </button>
</div>
`;
                marker.bindPopup(popupHTML);

                marker.towerData = item;
                towerMarkers.push(marker);
                markersGroup.addLayer(marker);
            });

            document
                .getElementById("search-btn")
                .addEventListener("click", searchAll);
            document
                .getElementById("search-input")
                .addEventListener("keypress", function (e) {
                    if (e.key === "Enter") {
                        e.preventDefault();
                        searchAll();
                    }
                });

            if (markersGroup.getLayers().length > 0) {
                map.fitBounds(markersGroup.getBounds());
            }

            const antennaLinksLayer = L.featureGroup().addTo(map);

            fetch("/data/antenna")
                .then((res) => res.json())
                .then((antennas) => {
                    console.log("Data antena dari DB:", antennas);

                    if (!antennas || antennas.length === 0) {
                        console.warn("Belum ada data antena");
                        return;
                    }

                    antennaLinksLayer.clearLayers();

                    const groupedLinks = {};

                    antennas.forEach((a) => {
                        const from = a.data_tower_from;
                        const to = a.data_tower_to;

                        if (!from || !to) return;

                        const key = `${from.id}-${to.id}`;

                        if (!groupedLinks[key]) {
                            groupedLinks[key] = { from, to, list: [] };
                        }
                        groupedLinks[key].list.push(a);
                    });

                    Object.values(groupedLinks).forEach((group) => {
                        const from = group.from;
                        const to = group.to;

                        const fromLat = parseFloat(from.latitude);
                        const fromLng = parseFloat(from.longitude);
                        const toLat = parseFloat(to.latitude);
                        const toLng = parseFloat(to.longitude);

                        if (!fromLat || !fromLng || !toLat || !toLng) return;

                        // =============================================
                        // 1. Buat daftar antena untuk popup
                        // =============================================
                        let listHTML = "<b>Antena Terhubung:</b><br><ul>";
                        group.list.forEach((a) => {
                            const jarak = haversineDistance(
                                fromLat,
                                fromLng,
                                toLat,
                                toLng,
                            );
                            listHTML += `<li><b>${a.jenis_antenna}</b> — ${jarak.toFixed(2)} km</li>`;
                        });
                        listHTML += "</ul>";

                        // Polyline link (tetap sama)
                        L.polyline(
                            [
                                [fromLat, fromLng],
                                [toLat, toLng],
                            ],
                            {
                                color: "#00aaff",
                                weight: 5,
                                opacity: 0.9,
                            },
                        )
                            .bindPopup(
                                `
            <h5>Link Tower</h5>
            <b>Dari:</b> ${from.nama_tower}<br>
            <b>Ke:</b> ${to.nama_tower}<br>
            <b>Jarak:</b> ${haversineDistance(fromLat, fromLng, toLat, toLng).toFixed(2)} km<br><br>
            ${listHTML}
        `,
                            )
                            .bindTooltip(
                                `
            ${from.nama_tower} → ${to.nama_tower}<br>
            <small>${group.list.length} antena terhubung</small>
        `,
                                { sticky: true },
                            )
                            .addTo(antennaLinksLayer);

                        // =============================================
                        // 2. Tentukan beamwidth, warna, dan radius berdasarkan antena
                        // =============================================
                        let beamwidth = 60; // default
                        let sectorColor = "#3b82f6"; // default biru sector
                        let maxRadiusKm = 10; // default aman

                        // Ambil antena pertama (atau yang paling relevan)
                        if (group.list.length > 0) {
                            const firstAntenna = group.list[0];
                            const jenis = (firstAntenna.jenis_antenna || "")
                                .trim()
                                .toLowerCase();

                            let matchedKey = null;

                            // Mapping nama antena dari database ke key di antennaDB
                            if (
                                jenis.includes("sector") &&
                                (jenis.includes("90") || jenis.includes("90°"))
                            ) {
                                matchedKey = "sector_90_17";
                            } else if (
                                jenis.includes("sector") &&
                                (jenis.includes("120") ||
                                    jenis.includes("120°"))
                            ) {
                                matchedKey = "sector_120_16";
                            } else if (jenis.includes("omni")) {
                                matchedKey = "omni_12";
                            } else if (
                                jenis.includes("grid") &&
                                (jenis.includes("27") ||
                                    jenis.includes("backhaul"))
                            ) {
                                matchedKey = "grid_27_backhaul";
                            } else if (
                                jenis.includes("grid") &&
                                !jenis.includes("27")
                            ) {
                                matchedKey = "grid_24_client";
                            } else if (jenis.includes("litebeam")) {
                                matchedKey = "litebeam_23";
                            } else if (jenis.includes("powerbeam")) {
                                matchedKey = "powerbeam_25";
                            } else if (jenis.includes("nanobeam")) {
                                matchedKey = "nanobeam_19";
                            } else if (
                                jenis.includes("dish") ||
                                jenis.includes("parabolic") ||
                                jenis.includes("30 dbi")
                            ) {
                                matchedKey = "dish_30";
                            }

                            // Jika cocok, ambil properti dari antennaDB
                            if (matchedKey && antennaDB[matchedKey]) {
                                const ant = antennaDB[matchedKey];
                                beamwidth = ant.beamwidth || 60;
                                sectorColor =
                                    ant.color ||
                                    (beamwidth <= 15 ? "#dc2626" : "#3b82f6");
                                maxRadiusKm = ant.maxDistanceKm || 10;
                            }
                        }

                        // Hitung jarak aktual
                        const distanceKm = haversineDistance(
                            fromLat,
                            fromLng,
                            toLat,
                            toLng,
                        );

                        // Radius sector = jarak link, tapi tidak boleh melebihi maxDistanceKm antena
                        const displayRadius = Math.min(distanceKm, maxRadiusKm);

                        // Hitung azimuth
                        const azimuth = getAzimuth(
                            fromLat,
                            fromLng,
                            toLat,
                            toLng,
                        );

                        // Buat sector dengan parameter yang sudah disesuaikan
                        createSector(
                            [fromLat, fromLng],
                            azimuth,
                            beamwidth,
                            displayRadius,
                            sectorColor,
                        ).addTo(sectorLayer);
                    });

                    if (antennaLinksLayer.getLayers().length > 0) {
                        map.fitBounds(antennaLinksLayer.getBounds().pad(0.3));
                    }
                })
                .catch((err) =>
                    console.error("Gagal load /data/antenna:", err),
                );

            // HEATMAP
            let heatPoints = [];

            fetch("/data/antenna")
                .then((res) => res.json())
                .then((antennas) => {
                    antennas.forEach((antena) => {
                        const from = antena.data_tower_from;
                        const to = antena.data_tower_to;
                        if (!from || !to) return;

                        const lat1 = parseFloat(from.latitude);
                        const lng1 = parseFloat(from.longitude);
                        const lat2 = parseFloat(to.latitude);
                        const lng2 = parseFloat(to.longitude);
                        if (!lat1 || !lat2) return;

                        const distance = haversineDistance(
                            lat1,
                            lng1,
                            lat2,
                            lng2,
                        );
                        const maxDistance = 50;
                        const baseIntensity = Math.max(
                            0.3,
                            1 - distance / maxDistance,
                        );

                        let intensityBonus = 1.0;
                        if (antena.jenis_antenna === "Litebeam M5")
                            intensityBonus = 1.4;
                        if (antena.jenis_antenna === "Grid")
                            intensityBonus = 1.2;
                        if (antena.jenis_antenna === "Microwave")
                            intensityBonus = 0.8;

                        for (let t = 0; t <= 50; t++) {
                            const ratio = t / 50;
                            const lat = lat1 + (lat2 - lat1) * ratio;
                            const lng = lng1 + (lng2 - lng1) * ratio;
                            let intensity =
                                baseIntensity *
                                (1 - ratio * 0.6) *
                                intensityBonus;
                            intensity = Math.min(1.0, Math.max(0.2, intensity));
                            heatPoints.push([lat, lng, intensity]);
                        }
                    });

                    antennas.forEach((antena) => {
                        const from = antena.data_tower_from;
                        const to = antena.data_tower_to;
                        if (from)
                            heatPoints.push([
                                parseFloat(from.latitude),
                                parseFloat(from.longitude),
                                1.0,
                            ]);
                        if (to)
                            heatPoints.push([
                                parseFloat(to.latitude),
                                parseFloat(to.longitude),
                                1.0,
                            ]);
                    });

                    if (heatPoints.length > 0) {
                        if (coverageHeatLayer)
                            map.removeLayer(coverageHeatLayer);
                        coverageHeatLayer = L.heatLayer(heatPoints, {
                            pane: "heatPane",
                            radius: 45,
                            blur: 30,
                            maxZoom: 16,
                            minOpacity: 0.4,
                            gradient: {
                                0.0: "#1e40af",
                                0.3: "#3b82f6",
                                0.5: "#10b981",
                                0.7: "#facc15",
                                0.9: "#f97316",
                                1.0: "#ef4444",
                            },
                        }).addTo(map);
                    }
                });
        });

    // SEARCH FUNCTIONALITY
    function searchAll() {
        let query = document
            .getElementById("search-input")
            .value.toLowerCase()
            .trim();

        if (!query) {
            alert("Masukkan nama tower atau lokasi!");
            return;
        }

        let foundTower = false;
        towerMarkers.forEach((marker) => {
            let nama = marker.towerData.nama_tower.toLowerCase();
            let alamat = marker.towerData.alamat_tower.toLowerCase();
            let id = String(marker.towerData.id).toLowerCase();

            if (
                nama.includes(query) ||
                alamat.includes(query) ||
                id.includes(query)
            ) {
                foundTower = true;
                map.setView(marker.getLatLng(), 18);
                marker.openPopup();
            }
        });

        if (foundTower) return;

        let url = `https://nominatim.openstreetmap.org/search?q=${query}&format=jsonv2`;

        fetch(url)
            .then((res) => res.json())
            .then((data) => {
                if (data.length > 0) {
                    let lokasi = data[0];
                    map.setView(
                        [parseFloat(lokasi.lat), parseFloat(lokasi.lon)],
                        18,
                    );

                    if (window.searchMarker)
                        map.removeLayer(window.searchMarker);
                    window.searchMarker = L.marker([lokasi.lat, lokasi.lon])
                        .addTo(map)
                        .bindPopup(`Hasil Lokasi:<br>${lokasi.display_name}`)
                        .openPopup();
                } else {
                    alert("Tidak ditemukan di tower maupun lokasi.");
                }
            })
            .catch(() => alert("Terjadi kesalahan pencarian lokasi."));
    }

    // ROUTING
    const routingForm = document.querySelector(".formBlock form");

    if (routingForm) {
        routingForm.addEventListener("submit", function (e) {
            e.preventDefault();

            let start = document.getElementById("start").value;
            let end = document.getElementById("destination").value;

            if (!start || !end) {
                alert("Isi titik awal dan tujuan!");
                return;
            }

            let startSplit = start.split(",");
            let endSplit = end.split(",");

            let startLatLng = L.latLng(
                parseFloat(startSplit[0]),
                parseFloat(startSplit[1]),
            );
            let endLatLng = L.latLng(
                parseFloat(endSplit[0]),
                parseFloat(endSplit[1]),
            );

            if (routingMachine) map.removeControl(routingMachine);

            routingMachine = L.Routing.control({
                waypoints: [startLatLng, endLatLng],
                routeWhileDragging: false,
                show: true,
            })
                .on("routesfound", function (e) {
                    const route = e.routes[0];
                    const coords = route.coordinates;

                    if (routeLine) map.removeLayer(routeLine);
                    routeLine = L.polyline(
                        coords.map((c) => [c.lat, c.lng]),
                        {
                            pane: "routePane",
                            color: "#2563eb",
                            weight: 5,
                        },
                    ).addTo(map);

                    straightLineLayer.clearLayers();
                    const losLine = L.polyline([startLatLng, endLatLng], {
                        pane: "routePane",
                        color: "grey",
                        weight: 6,
                        dashArray: "6,6",
                    }).bindPopup(`
                    <b>📡 Link LOS (Point-to-Point)</b><br>
                    Jarak: ${haversineDistance(
                        startLatLng.lat,
                        startLatLng.lng,
                        endLatLng.lat,
                        endLatLng.lng,
                    ).toFixed(2)} km
                `);
                    straightLineLayer.addLayer(losLine);

                    let losHeatPoints = [];
                    for (let i = 0; i <= 60; i++) {
                        const t = i / 60;
                        const lat =
                            startLatLng.lat +
                            (endLatLng.lat - startLatLng.lat) * t;
                        const lng =
                            startLatLng.lng +
                            (endLatLng.lng - startLatLng.lng) * t;
                        const intensity = Math.max(0.3, 1 - t * 0.7);
                        losHeatPoints.push([lat, lng, intensity]);
                    }

                    if (routeHeatLayer) map.removeLayer(routeHeatLayer);
                    routeHeatLayer = L.heatLayer(losHeatPoints, {
                        pane: "routePane",
                        radius: 35,
                        blur: 25,
                        maxZoom: 17,
                    }).addTo(map);

                    map.fitBounds(routeLine.getBounds().pad(0.3));
                    if (coverageHeatLayer) coverageHeatLayer.bringToFront();
                })
                .addTo(map);
        });
    }

    // BASEMAP

    function fullScreenView() {
        document.getElementById("map").requestFullscreen();
    }

    // MOUSE TOOLTIP + ELEVASI
    const tooltipDiv = document.getElementById("mouse-follow");
    const contextMenu = document.getElementById("context-menu");

    function closeContextMenu() {
        contextMenu.style.display = "none";
    }

    let currentElevation = null;
    let lastElevationTime = 0;
    let isFetchingElevation = false;

    map.on("mousemove", async (e) => {
        tooltipDiv.style.left = e.originalEvent.pageX + 15 + "px";
        tooltipDiv.style.top = e.originalEvent.pageY + 15 + "px";

        const lat = e.latlng.lat.toFixed(6);
        const lng = e.latlng.lng.toFixed(6);

        tooltipDiv.innerHTML = `${lat}, ${lng} | Elevasi: ${
            currentElevation ?? "..."
        }`;
        tooltipDiv.style.display = "block";

        const now = Date.now();
        if (now - lastElevationTime < 700 || isFetchingElevation) return;

        lastElevationTime = now;
        isFetchingElevation = true;

        try {
            const elev = await fetch(`/elevation?locations=${lat},${lng}`)
                .then((r) => (r.ok ? r.json() : null))
                .then((d) => d?.results?.[0]?.elevation ?? 0);
            currentElevation = Math.round(elev);
        } catch {
            currentElevation = null;
        } finally {
            isFetchingElevation = false;
        }
    });

    map.on("mouseout", () => (tooltipDiv.style.display = "none"));
    map.getContainer().addEventListener("contextmenu", (e) =>
        e.preventDefault(),
    );

    map.on("contextmenu", (e) => {
        contextMenu.style.left = e.originalEvent.pageX + "px";
        contextMenu.style.top = e.originalEvent.pageY + "px";
        contextMenu.style.display = "block";
        lastLatLng = e.latlng;
    });

    // ============================
    // PTMP CLIENT (KLIK KIRI MAP)
    // ============================
    map.on("click", function (e) {
        if (!ptmpMode) return;
        if (e.originalEvent.button !== 0) return;

        // PLACE AP
        if (placingAP) {
            if (apMarker) map.removeLayer(apMarker);

            apMarker = L.marker(e.latlng, {
                icon: apIcon,
                draggable: true,
            }).addTo(map);

            apMarker.on("drag", updatePtMPLines);

            placingClient = false;
            setActiveUIButton(null);
            updateCursorMode();
            updatePtMPLines();
            savePtMPState();
            return;
        }

        // ADD CLIENT
        if (placingClient && apMarker) {
            const clientId = Date.now();

            const client = L.marker(e.latlng, {
                draggable: true,
            }).addTo(map);

            client.clientId = clientId;

            client.on("click", () => {
                openClientPanel(client.clientId);
                updateSignalUI(client._rxPower);
            });

            client.on("drag", updatePtMPLines);

            clientMarkers.push(client);
            updatePtMPLines();
            savePtMPState();
        }
    });

    function removeClient(clientId) {
        const idx = clientMarkers.findIndex((c) => c.clientId === clientId);
        if (idx === -1) return;

        // hapus marker client
        map.removeLayer(clientMarkers[idx]);
        clientMarkers.splice(idx, 1);

        hideAntennaPanel();

        // 🔥 JIKA SUDAH TIDAK ADA CLIENT → HAPUS AP JUGA
        if (clientMarkers.length === 0) {
            if (apMarker) {
                map.removeLayer(apMarker);
                apMarker = null;
            }

            if (ptmpSector) {
                map.removeLayer(ptmpSector);
                ptmpSector = null;
            }

            ptmpLines.forEach((l) => map.removeLayer(l));
            ptmpLines = [];

            savePtMPState();
            return;
        }

        updatePtMPLines();
        savePtMPState();
    }

    function copyCoord() {
        const text =
            lastLatLng.lat.toFixed(6) + ", " + lastLatLng.lng.toFixed(6);
        navigator.clipboard.writeText(text);
        alert("Koordinat disalin:\n" + text);
        contextMenu.style.display = "none";
    }

    function zoomHere() {
        map.setView(lastLatLng, map.getZoom() + 2);
        contextMenu.style.display = "none";
    }

    function focusTower(lat, lng) {
        map.setView([lat, lng], 18);
    }

    function removeMarkers() {
        if (startMarker) map.removeLayer(startMarker);
        if (destMarker) map.removeLayer(destMarker);
        startMarker = destMarker = null;
        document.getElementById("start").value = "";
        document.getElementById("destination").value = "";
        if (routingMachine) map.removeControl(routingMachine);
        routingMachine = null;
        contextMenu.style.display = "none";
    }

    function startingPointHere() {
        if (startMarker) map.removeLayer(startMarker);
        startMarker = L.marker(lastLatLng, { pane: "markerPaneCustom" })
            .addTo(map)
            .bindPopup("📍 Starting Point")
            .openPopup();

        document.getElementById("start").value =
            lastLatLng.lat.toFixed(6) + ", " + lastLatLng.lng.toFixed(6);
        contextMenu.style.display = "none";
    }

    function destinationHere() {
        if (destMarker) map.removeLayer(destMarker);
        destMarker = L.marker(lastLatLng, {
            pane: "markerPaneCustom",
            icon: destIcon,
        })
            .addTo(map)
            .bindPopup("🏁 Destination")
            .openPopup();

        document.getElementById("destination").value =
            lastLatLng.lat.toFixed(6) + ", " + lastLatLng.lng.toFixed(6);
        contextMenu.style.display = "none";
    }

    // Haversine untuk polyline & sector
    function haversineDistance(lat1, lon1, lat2, lon2) {
        const R = 6371;
        const dLat = ((lat2 - lat1) * Math.PI) / 180;
        const dLon = ((lon2 - lon1) * Math.PI) / 180;
        const a =
            Math.sin(dLat / 2) ** 2 +
            Math.cos((lat1 * Math.PI) / 180) *
                Math.cos((lat2 * Math.PI) / 180) *
                Math.sin(dLon / 2) ** 2;
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    // =============================
    // UI FUNCTION (TARUH DI SINI)
    // =============================

    function getSignalStatus(rxPower) {
        if (rxPower > -60) {
            return { text: "Strong Signal", class: "good" };
        }
        if (rxPower > -75) {
            return { text: "Fair Signal", class: "weak" };
        }
        if (rxPower > -85) {
            return { text: "Weak Signal", class: "weak" };
        }
        return { text: "No Link", class: "bad" };
    }

    function updateSignalUI(rxPower) {
        const el = document.getElementById("link-status");
        if (!el) return;

        const status = getSignalStatus(rxPower);

        el.textContent = status.text;
        el.className = `link-status ${status.class}`;
    }

    function showAntennaPanel(rxPower) {
        const panel = document.querySelector(".antenna-panel");
        panel.classList.add("show");

        const status = panel.querySelector(".link-status");

        if (rxPower > -60) {
            status.textContent = "Strong Signal";
            status.className = "link-status good";
        } else if (rxPower > -75) {
            status.textContent = "Fair Signal";
            status.className = "link-status weak";
        } else {
            status.textContent = "Poor Signal";
            status.className = "link-status bad";
        }
    }

    function hideAntennaPanel() {
        document.querySelector(".antenna-panel")?.classList.remove("show");
    }

    function openClientPanel(clientId) {
        const panel = document.querySelector(".antenna-panel");
        if (!panel) return;

        panel.classList.add("show");

        // simpan client aktif
        window.activeClientId = clientId;

        // optional: update judul panel
        const header = panel.querySelector(".panel-header");
        if (header) {
            header.querySelector("span")?.remove();
            const span = document.createElement("span");
            span.style.fontSize = "12px";
            span.style.marginLeft = "8px";
            header.appendChild(span);
        }
    }

    function updatePtMPLines() {
        ptmpLines.forEach((l) => map.removeLayer(l));
        ptmpLines = [];

        if (!apMarker || clientMarkers.length === 0) return;
        if (!apMarker) return;

        // ============================
        // ⭐ BATASI GRID HANYA 1 CLIENT
        // ============================
        if (selectedAPAntenna.beamwidth <= 15 && clientMarkers.length > 1) {
            alert("⚠️ Antena GRID hanya direkomendasikan untuk 1 client (PtP)");
        }

        const apLatLng = apMarker.getLatLng();

        // ============================
        // ⭐ HITUNG AZIMUTH SECTOR (AVG)
        // ============================
        let sumAz = 0;
        clientMarkers.forEach((c) => {
            const cl = c.getLatLng();
            sumAz += getAzimuth(apLatLng.lat, apLatLng.lng, cl.lat, cl.lng);
        });
        const avgAzimuth = clientMarkers.length
            ? sumAz / clientMarkers.length
            : 0;

        clientMarkers.forEach((client, index) => {
            const cl = client.getLatLng();

            const distance = haversineDistance(
                apLatLng.lat,
                apLatLng.lng,
                cl.lat,
                cl.lng,
            );

            const azimuth = getAzimuth(
                apLatLng.lat,
                apLatLng.lng,
                cl.lat,
                cl.lng,
            );

            const result = calculateLinkBudget(distance, LINK_FREQ_GHZ, {
                ...radioProfile,
                txGain: selectedAPAntenna.gain,
                rxGain: selectedClientAntenna.gain,
            });

            // 🔥 SIMPAN RX POWER KE CLIENT
            client._rxPower = result.rxPower;

            // ============================
            // ⭐ BATAS JARAK ANTENNA AP
            // ============================
            let linkValid = true;
            if (
                selectedAPAntenna.maxDistanceKm &&
                distance > selectedAPAntenna.maxDistanceKm
            ) {
                linkValid = false;
            }

            // ============================
            // ⭐ CEK DI DALAM SECTOR
            // ============================
            const deltaAz = Math.abs(
                ((azimuth - avgAzimuth + 540) % 360) - 180,
            );

            if (deltaAz > selectedAPAntenna.beamwidth / 2) {
                linkValid = false;
            }

            // ============================
            // ⭐ FRESNEL
            // ============================
            const d1 = distance / 2;
            const d2 = distance / 2;
            const fresnel = fresnelRadius(d1, d2, LINK_FREQ_GHZ);
            const fresnelRequired = fresnel * 0.6;

            // ============================
            // ⭐ STATUS LINK
            // ============================
            let status = "🟢 Good";
            if (result.rxPower < -75) status = "🟡 Weak";
            if (result.rxPower < -85) status = "🔴 No Link";
            if (!linkValid) status = "🚫 Outside Sector";

            // ============================
            // ⭐ WARNA LINK
            // ============================
            let color = getLinkColor(result.rxPower);
            if (!linkValid) color = "#9ca3af";

            // ============================
            // ⭐ DRAW LINE
            // ============================
            const line = L.polyline(
                [
                    [apLatLng.lat, apLatLng.lng],
                    [cl.lat, cl.lng],
                ],
                {
                    color,
                    weight: 4,
                    interactive: true,
                },
            ).addTo(map).bindPopup(`
                <b>📶 Client ${index + 1}</b><br>
                Status: <b>${status}</b><br>
                Jarak: ${distance.toFixed(2)} km<br>
                Azimuth: ${azimuth.toFixed(1)}°<br>
                FSPL: ${result.fspl.toFixed(2)} dB<br>
                RX Power: <b>${result.rxPower.toFixed(2)} dBm</b><br>
                SNR: ${result.snr.toFixed(2)} dB<br>
                <hr>
                <b>Fresnel Radius:</b> ${fresnel.toFixed(2)} m<br>
                <b>Required Clearance (60%):</b> ${fresnelRequired.toFixed(2)} m
            `);

            line.bringToFront();
            // line.on("click", (e) => line.openPopup(e.latlng));

            line.on("click", () => {
                updateSignalUI(result.rxPower);
            });

            ptmpLines.push(line);
        });

        drawPtMPSector();
    }

    function loadPtMPState() {
        const key = getPtMPStorageKey();
        if (!key) return null;

        const raw = localStorage.getItem(key);
        if (!raw) return null;

        return JSON.parse(raw);
    }

    function savePtMPState() {
        const key = getPtMPStorageKey();
        if (!key) return;

        const state = {
            ap: apMarker ? apMarker.getLatLng() : null,
            clients: clientMarkers.map((c) => ({
                id: c.clientId,
                latlng: c.getLatLng(),
            })),
            apAntennaKey: document.getElementById("ap-antenna").value,
            clientAntennaKey: document.getElementById("client-antenna").value,
            savedAt: new Date().toISOString(),
        };

        localStorage.setItem(key, JSON.stringify(state));
    }

    function clearPtMPLayersOnly() {
        if (apMarker) map.removeLayer(apMarker);
        clientMarkers.forEach((c) => map.removeLayer(c));
        ptmpLines.forEach((l) => map.removeLayer(l));
        if (ptmpSector) map.removeLayer(ptmpSector);

        apMarker = null;
        clientMarkers = [];
        ptmpLines = [];
        ptmpSector = null;
    }

    let placingAP = false;
    let placingClient = false;

    function setActiveUIButton(activeId) {
        document
            .querySelectorAll("#ptmp-ui a")
            .forEach((b) => b.classList.remove("active"));

        if (activeId) {
            document.getElementById(activeId)?.classList.add("active");
        }
    }

    document.getElementById("ui-place-ap").addEventListener("click", () => {
        placingAP = true;
        placingClient = false;
        ptmpMode = true;
        setActiveUIButton("ui-place-ap");
        updateCursorMode();
    });

    document.getElementById("ui-add-client").addEventListener("click", () => {
        if (!apMarker) {
            alert("Pasang AP terlebih dahulu");
            return;
        }
        placingClient = true;
        placingAP = false;
        ptmpMode = true;
        setActiveUIButton("ui-add-client");
        updateCursorMode();
    });
    document.getElementById("ui-clear").addEventListener("click", () => {
        clearPtMPLayersOnly();
        placingAP = placingClient = false;
        ptmpMode = true;
        setActiveUIButton(null);
        updateCursorMode();
    });

    document.getElementById("ap-antenna").addEventListener("change", (e) => {
        const antenna = antennaDB[e.target.value];

        // ❌ AP TIDAK BOLEH ANTENA CLIENT
        if (antenna.type !== "ap") {
            alert(
                "❌ Antena ini hanya boleh digunakan di Tower Kedua (Client)",
            );
            e.target.value = Object.keys(antennaDB).find(
                (k) => antennaDB[k].type === "ap",
            );
            return;
        }

        selectedAPAntenna = antenna;
        ptmpBeamwidth = selectedAPAntenna.beamwidth;
        ptmpRadiusKm = selectedAPAntenna.maxDistanceKm;

        updatePtMPLines();
        savePtMPState();
    });

    document
        .getElementById("client-antenna")
        .addEventListener("change", (e) => {
            selectedClientAntenna = antennaDB[e.target.value];
            updatePtMPLines();
            savePtMPState();

            refreshActiveClientSignal();

            // 🔄 UPDATE STATUS PANEL JIKA ADA CLIENT AKTIF
            if (window.activeClientId) {
                const c = clientMarkers.find(
                    (x) => x.clientId === window.activeClientId,
                );
                if (c && c._rxPower !== undefined) {
                    updateSignalUI(c._rxPower);
                }
            }
        });

    // =============================
    // 🔄 RESTORE PtMP STATE (MANUAL)
    // =============================
    function restorePtMPState(state) {
        if (!state) return;

        clearPtMPLayersOnly();

        if (state.ap) {
            apMarker = L.marker(state.ap, {
                icon: apIcon,
                draggable: true,
            }).addTo(map);

            apMarker.on("drag", updatePtMPLines);
        }

        if (state.clients) {
            state.clients.forEach((c) => {
                const client = L.marker(c.latlng, {
                    draggable: true,
                }).addTo(map);

                client.clientId = c.id;
                client.on("drag", updatePtMPLines);
                client.on("click", () => openClientPanel(client.clientId));

                clientMarkers.push(client);
            });
        }

        if (state.apAntennaKey)
            selectedAPAntenna = antennaDB[state.apAntennaKey];

        if (state.clientAntennaKey)
            selectedClientAntenna = antennaDB[state.clientAntennaKey];

        ptmpBeamwidth = selectedAPAntenna.beamwidth;

        updatePtMPLines();
    }

    function filterAPAntennaOptions() {
        const select = document.getElementById("ap-antenna");

        [...select.options].forEach((opt) => {
            const ant = antennaDB[opt.value];
            if (!ant) return;

            opt.disabled = ant.type !== "ap";
        });
    }

    function refreshActiveClientSignal() {
        if (!window.activeClientId) return;

        const c = clientMarkers.find(
            (x) => x.clientId === window.activeClientId,
        );

        if (c && c._rxPower !== undefined) {
            updateSignalUI(c._rxPower);
        }
    }

    function renderLinkOnMap(
        lat1,
        lon1,
        lat2,
        lon2,
        distanceKm,
        losStatus,
        height1,
        height2,
    ) {
        // bersihkan layer lama
        if (window.linkLayerGroup) {
            map.removeLayer(window.linkLayerGroup);
        }

        window.linkLayerGroup = L.layerGroup().addTo(map);

        // ======================
        // WARNA LINK
        // ======================
        let color = "green";
        if (losStatus === "MARGINAL") color = "orange";
        if (losStatus === "BLOCKED") color = "red";

        // ======================
        // GARIS LINK
        // ======================
        L.polyline(
            [
                [lat1, lon1],
                [lat2, lon2],
            ],
            {
                color,
                weight: 4,
                dashArray: losStatus === "CLEAR" ? null : "6,6",
            },
        ).addTo(window.linkLayerGroup);

        // =================================================
        // === NO 4: TITIK KRITIS LOS (TARUH DI SINI)
        // =================================================
        if (Array.isArray(criticalPoints)) {
            criticalPoints.forEach((p) => {
                if (p.status !== "CLEAR") {
                    const ratio = p.distance / distanceKm;

                    const lat = lat1 + (lat2 - lat1) * ratio;
                    const lon = lon1 + (lon2 - lon1) * ratio;

                    L.circleMarker([lat, lon], {
                        radius: 6,
                        color: p.status === "BLOCKED" ? "red" : "orange",
                        fillOpacity: 0.8,
                    }).addTo(window.linkLayerGroup).bindPopup(`
                    <b>${p.status}</b><br>
                    Clearance: ${p.clearancePercent.toFixed(1)} %
                `);
                }
            });
        }

        // ======================
        // MARKER TOWER A
        // ======================
        L.marker([lat1, lon1]).addTo(window.linkLayerGroup).bindPopup(`
        <b>📡 Tower A</b><br>
        Lat: ${lat1.toFixed(6)}<br>
        Lon: ${lon1.toFixed(6)}<br>
        Tinggi Tower: ${height1} m<br>
        Gain Antena: ${globalTxGain} dBi
    `);

        // ======================
        // MARKER TOWER B
        // ======================
        L.marker([lat2, lon2]).addTo(window.linkLayerGroup).bindPopup(`
        <b>📡 Tower B</b><br>
        Lat: ${lat2.toFixed(6)}<br>
        Lon: ${lon2.toFixed(6)}<br>
        Tinggi Tower: ${height2} m<br>
        Gain Antena: ${globalRxGain} dBi
    `);

        // ======================
        // AUTO ZOOM
        // ======================
        map.fitBounds(
            [
                [lat1, lon1],
                [lat2, lon2],
            ],
            { padding: [40, 40] },
        );
    }

    function renderHeatmap(lat1, lon1, lat2, lon2, criticalPoints) {
        if (!map || !criticalPoints || criticalPoints.length === 0) return;

        if (routeHeatLayer) map.removeLayer(routeHeatLayer);

        const heatData = criticalPoints.map((p) => {
            const t = p.distance / globalDistanceKm;

            const lat = lat1 + (lat2 - lat1) * t;
            const lon = lon1 + (lon2 - lon1) * t;

            let intensity = 0.5;
            if (p.status === "BLOCKED") intensity = 1;
            else if (p.status === "WARNING") intensity = 0.7;

            return [lat, lon, intensity];
        });

        routeHeatLayer = L.heatLayer(heatData, {
            pane: "heatPane",
            radius: 25,
            blur: 18,
            maxZoom: 15,
        }).addTo(map);
    }

    window.renderHeatmap = renderHeatmap;
    window.renderLinkOnMap = renderLinkOnMap;

    // EXPOSE SEMUA FUNGSI KE WINDOW AGAR onclick DI HTML TETAP JALAN
    window.copyCoord = copyCoord;
    window.zoomHere = zoomHere;
    window.startingPointHere = startingPointHere;
    window.destinationHere = destinationHere;
    window.removeMarkers = removeMarkers;
    window.focusTower = focusTower;
    window.closeContextMenu = closeContextMenu;
    window.fullScreenView = fullScreenView;
    window.removeClient = removeClient;
    window.openClientPanel = openClientPanel;
    window.hideAntennaPanel = hideAntennaPanel;
});
