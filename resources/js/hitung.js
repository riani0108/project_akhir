// resources/js/hitung.js - VERSI LENGKAP DIPERBAIKI

// ============== VARIABEL GLOBAL UNTUK DATA ============== //
let globalTerrainData = null;
let globalLosData = null;
let globalFresnelUpper = null;
let globalFresnelLower = null;
let globalEarthBulge = null;
let globalTerrainOnly = null;
let globalDistanceKm = null;
let globalFrequency = null;
let criticalPoints = [];

// ============== LOS CALCULATION HELPERS ============== //
function getMinMax(...arrays) {
    const flat = arrays.flat().filter((v) => isFinite(v));
    const min = Math.min(...flat);
    const max = Math.max(...flat);
    const padding = (max - min) * 0.15 || 10;
    return {
        min: min - padding,
        max: max + padding,
    };
}

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

function calculateElevationAngle(h1, h2, distanceKm) {
    const distanceM = distanceKm * 1000;
    const angleRad = Math.atan((h2 - h1) / distanceM);
    return (angleRad * 180) / Math.PI;
}

async function getElevation(lat, lon) {
    if (!isFinite(lat) || !isFinite(lon)) return 0;
    try {
        const res = await fetch(`/elevation?locations=${lat},${lon}`);
        if (!res.ok) return 0;
        const data = await res.json();
        return data.results?.[0]?.elevation ?? 0;
    } catch (e) {
        console.warn("Gagal ambil elevasi:", e);
        return 0;
    }
}

async function getTerrainProfile(lat1, lon1, lat2, lon2, samples = 100) {
    const points = [];
    for (let i = 0; i <= samples; i++) {
        const t = i / samples;
        const lat = lat1 + (lat2 - lat1) * t;
        const lon = lon1 + (lon2 - lon1) * t;
        if (isFinite(lat) && isFinite(lon)) {
            points.push(`${lat},${lon}`);
        }
    }

    if (points.length === 0) return [];

    try {
        const res = await fetch(`/elevation?locations=${points.join("|")}`);
        if (!res.ok) throw new Error("Bad response");
        const data = await res.json();
        return data.results.map((r) => r.elevation ?? 0);
    } catch (e) {
        console.error("Error fetching terrain profile:", e);
        return Array(points.length).fill(0);
    }
}

function analyzeCriticalPoints(
    terrain,
    losData,
    fresnelLower,
    distanceKm,
    frequency,
) {
    criticalPoints = [];
    const samples = terrain.length - 1;

    for (let i = 1; i < terrain.length - 1; i++) {
        const t = i / samples;
        const distFromA = distanceKm * t;
        const d1 = distFromA;
        const d2 = distanceKm - distFromA;

        // Radius Fresnel zone
        const r = 17.32 * Math.sqrt((d1 * d2) / (frequency * distanceKm));
        const clearanceNeeded = 0.6 * r;
        const actualClearance = losData[i] - terrain[i];
        const clearancePercent = (actualClearance / clearanceNeeded) * 100;

        // Simpan titik kritis
        if (actualClearance < clearanceNeeded) {
            criticalPoints.push({
                index: i,
                distance: distFromA,
                terrain: terrain[i],
                los: losData[i],
                fresnelLower: fresnelLower[i],
                clearanceNeeded: clearanceNeeded,
                actualClearance: actualClearance,
                clearancePercent: clearancePercent,
                status: "BLOCKED",
            });
        } else if (clearancePercent < 80) {
            criticalPoints.push({
                index: i,
                distance: distFromA,
                terrain: terrain[i],
                los: losData[i],
                fresnelLower: fresnelLower[i],
                clearanceNeeded: clearanceNeeded,
                actualClearance: actualClearance,
                clearancePercent: clearancePercent,
                status: "WARNING",
            });
        }
    }

    return criticalPoints;
}

let terrainChartInstance = null;

function drawTerrainChart(
    terrain,
    heightA,
    heightB,
    distanceKm,
    frequencyGHz = 6,
    kFactor = 1.33,
) {
    const samples = terrain.length - 1;
    const labels = [];
    const losData = [];
    const fresnelUpper = [];
    const fresnelLower = [];
    const earthBulge = [];
    const terrainOnly = [];
    const clearanceLine = []; // Garis clearance minimum 60%
    const warningLine = []; // Garis warning 80%

    // Simpan data ke global
    globalTerrainData = terrain;
    globalDistanceKm = distanceKm;
    globalFrequency = frequencyGHz;

    for (let i = 0; i <= samples; i++) {
        const distFromA = distanceKm * (i / samples);
        labels.push(distFromA.toFixed(2));

        // Simpan elevasi tanah asli
        terrainOnly.push(terrain[i]);

        // Garis LOS
        const losHeight = heightA + (heightB - heightA) * (i / samples);
        losData.push(losHeight);

        const d1 = distFromA;
        const d2 = distanceKm - distFromA;

        // Earth curvature bulge
        const bulge = (d1 * d2) / (12.75 * kFactor);
        earthBulge.push(terrain[i] + bulge);

        // Fresnel zone radius
        const r = 17.32 * Math.sqrt((d1 * d2) / (frequencyGHz * distanceKm));
        fresnelUpper.push(losHeight + r);
        fresnelLower.push(losHeight - r);

        // Clearance lines
        clearanceLine.push(terrain[i] + 0.6 * r);
        warningLine.push(terrain[i] + 0.8 * r);
    }

    // Simpan data global
    globalLosData = losData;
    globalFresnelUpper = fresnelUpper;
    globalFresnelLower = fresnelLower;
    globalEarthBulge = earthBulge;
    globalTerrainOnly = terrainOnly;

    // Analisis titik kritis
    analyzeCriticalPoints(
        terrainOnly,
        losData,
        fresnelLower,
        distanceKm,
        frequencyGHz,
    );

    const ctx = document.getElementById("terrainChart")?.getContext("2d");
    if (!ctx) {
        console.error("Canvas terrainChart tidak ditemukan!");
        return;
    }

    if (terrainChartInstance) {
        terrainChartInstance.destroy();
    }

    const yScale = getMinMax(
        earthBulge,
        losData,
        fresnelUpper,
        fresnelLower,
        warningLine,
    );

    terrainChartInstance = new Chart(ctx, {
        type: "line",
        data: {
            labels,
            datasets: [
                // Dataset 1: Elevasi Tanah Saja
                {
                    label: "Elevasi Tanah",
                    data: terrainOnly,
                    borderColor: "#8B4513",
                    backgroundColor: "rgba(139, 69, 19, 0.3)",
                    borderWidth: 2,
                    fill: true,
                    pointRadius: 0,
                    order: 5,
                },
                // Dataset 2: Garis LOS
                {
                    label: "Garis LOS",
                    data: losData,
                    borderColor: "#FF0000",
                    borderWidth: 4,
                    borderDash: [6, 4],
                    pointRadius: 0,
                    order: 1,
                },
                // Dataset 3: Fresnel Zone Upper
                {
                    label: "Fresnel Zone (Upper)",
                    data: fresnelUpper,
                    borderColor: "#00AA00",
                    backgroundColor: "rgba(0, 170, 0, 0.15)",
                    borderWidth: 1,
                    fill: "+1",
                    pointRadius: 0,
                    order: 3,
                },
                // Dataset 4: Fresnel Zone Lower
                {
                    label: "Fresnel Zone (Lower)",
                    data: fresnelLower,
                    borderColor: "#00AA00",
                    backgroundColor: "rgba(0, 170, 0, 0.15)",
                    borderWidth: 1,
                    fill: "-1",
                    pointRadius: 0,
                    order: 4,
                },
                // Dataset 5: Garis Warning 80%
                {
                    label: "Warning Zone (80% Clearance)",
                    data: warningLine,
                    borderColor: "#FFA500",
                    borderWidth: 1,
                    borderDash: [3, 3],
                    pointRadius: 0,
                    order: 6,
                },
                // Dataset 6: Garis Minimum 60%
                {
                    label: "Minimum Clearance (60%)",
                    data: clearanceLine,
                    borderColor: "#FF4500",
                    borderWidth: 2,
                    borderDash: [5, 5],
                    pointRadius: 0,
                    order: 7,
                },
                // Dataset 7: Titik Kritis
                {
                    label: "Critical Points",
                    data: criticalPoints.map((p) => ({
                        x: p.distance,
                        y: p.los,
                    })),
                    backgroundColor: criticalPoints.map((p) =>
                        p.status === "BLOCKED" ? "#FF0000" : "#FFA500",
                    ),
                    borderColor: "#FFFFFF",
                    borderWidth: 2,
                    pointRadius: 6,
                    pointStyle: "circle",
                    showLine: false,
                    order: 2,
                },
                // Dataset 8: Tower Positions
                {
                    label: "Tower Positions",
                    data: [
                        { x: 0, y: heightA },
                        { x: distanceKm, y: heightB },
                    ],
                    backgroundColor: ["#0000FF", "#0000FF"],
                    borderColor: "#FFFFFF",
                    borderWidth: 2,
                    pointRadius: 8,
                    pointStyle: "rectRot",
                    showLine: false,
                    order: 0,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: "index",
            },
            plugins: {
                title: {
                    display: true,
                    text: `Profil LOS - Tower A: ${heightA.toFixed(0)}m, Tower B: ${heightB.toFixed(0)}m | Jarak: ${distanceKm.toFixed(2)}km`,
                    font: { size: 16, weight: "bold" },
                    padding: { bottom: 20 },
                },
                legend: {
                    position: "top",
                    labels: {
                        filter: function (item, chart) {
                            // Sembunyikan dataset yang tidak perlu di legend
                            const hideLabels = [
                                "Fresnel Zone (Lower)",
                                "Critical Points",
                                "Tower Positions",
                            ];
                            return !hideLabels.includes(item.text);
                        },
                        usePointStyle: true,
                    },
                },
                tooltip: {
                    enabled: true,
                    mode: "index",
                    intersect: false,
                    backgroundColor: "rgba(0, 0, 0, 0.8)",
                    titleColor: "#FFFFFF",
                    bodyColor: "#FFFFFF",
                    padding: 12,
                    cornerRadius: 6,
                    usePointStyle: true,
                    callbacks: {
                        title: function (tooltipItems) {
                            const distance = tooltipItems[0].parsed.x;
                            return `Jarak dari Tower A: ${distance.toFixed(2)} km`;
                        },
                        label: function (context) {
                            const label = context.dataset.label || "";
                            const value = context.parsed.y;

                            // Custom label berdasarkan dataset
                            switch (label) {
                                case "Elevasi Tanah":
                                    return `📌 Tanah: ${value.toFixed(1)} m`;
                                case "Garis LOS":
                                    return `📡 LOS: ${value.toFixed(1)} m`;
                                case "Fresnel Zone (Upper)":
                                    return `📶 Fresnel Upper: ${value.toFixed(1)} m`;
                                case "Fresnel Zone (Lower)":
                                    return `📶 Fresnel Lower: ${value.toFixed(1)} m`;
                                case "Warning Zone (80% Clearance)":
                                    return `⚠️  80% Clearance: ${value.toFixed(1)} m`;
                                case "Minimum Clearance (60%)":
                                    return `⛔ Min 60%: ${value.toFixed(1)} m`;
                                case "Critical Points":
                                    const point = criticalPoints.find(
                                        (p) =>
                                            Math.abs(
                                                p.distance - context.parsed.x,
                                            ) < 0.01,
                                    );
                                    if (point) {
                                        return `🚨 ${point.status}: ${point.clearancePercent.toFixed(1)}% clearance`;
                                    }
                                    return "";
                                case "Tower Positions":
                                    if (context.parsed.x === 0) {
                                        return `🏗️ Tower A: ${value.toFixed(1)} m`;
                                    } else {
                                        return `🏗️ Tower B: ${value.toFixed(1)} m`;
                                    }
                                default:
                                    return `${label}: ${value.toFixed(1)} m`;
                            }
                        },
                        afterLabel: function (context) {
                            const label = context.dataset.label || "";

                            if (label === "Garis LOS") {
                                const distance = context.parsed.x;
                                const d1 = distance;
                                const d2 = globalDistanceKm - distance;

                                // Hitung Fresnel radius di titik ini
                                const r =
                                    17.32 *
                                    Math.sqrt(
                                        (d1 * d2) /
                                            (globalFrequency *
                                                globalDistanceKm),
                                    );
                                const clearanceNeeded = 0.6 * r;

                                // Cari elevasi tanah di titik ini
                                const index = Math.round(
                                    (distance / globalDistanceKm) *
                                        (globalTerrainOnly.length - 1),
                                );
                                const terrainHeight =
                                    globalTerrainOnly[index] || 0;
                                const actualClearance =
                                    context.parsed.y - terrainHeight;
                                const clearancePercent =
                                    (actualClearance / clearanceNeeded) * 100;

                                return [
                                    `📏 Fresnel Radius: ${r.toFixed(1)} m`,
                                    `🎯 Clearance Needed: ${clearanceNeeded.toFixed(1)} m`,
                                    `✅ Actual Clearance: ${actualClearance.toFixed(1)} m`,
                                    `📊 Clearance: ${clearancePercent.toFixed(1)}%`,
                                ];
                            }

                            return null;
                        },
                    },
                },
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: "Jarak dari Tower A (km)",
                        font: { weight: "bold", size: 14 },
                    },
                    grid: {
                        color: "rgba(0,0,0,0.1)",
                    },
                },
                y: {
                    min: yScale.min,
                    max: yScale.max,
                    title: {
                        display: true,
                        text: "Ketinggian (meter dpl)",
                        font: { weight: "bold", size: 14 },
                    },
                    ticks: {
                        callback: (v) => `${Math.round(v)} m`,
                    },
                    grid: {
                        color: "rgba(0,0,0,0.1)",
                    },
                },
            },
            elements: {
                point: {
                    hoverRadius: 8,
                },
            },
        },
    });
}

// === FUNGSI UTAMA ===
// ============== UPDATE FUNGSI UTAMA ============== //
async function performCalculation() {
    try {
        // Show loading state in new layout
        document.getElementById("output").innerHTML = `
            <div class="loading-compact">
                <div class="spinner-compact"></div>
                <p>Calculating LOS and analyzing terrain...</p>
            </div>
        `;

        // Ambil nilai dari form
        const coord1Input =
            document.getElementById("coord1")?.value.trim() || "";
        const coord2Input =
            document.getElementById("coord2")?.value.trim() || "";

        if (!coord1Input || !coord2Input) {
            alert("Please enter coordinates for both towers!");
            return;
        }

        // Parse koordinat
        const coord1 = coord1Input.split(",").map((s) => parseFloat(s.trim()));
        const coord2 = coord2Input.split(",").map((s) => parseFloat(s.trim()));

        const [lat1, lon1] = coord1;
        const [lat2, lon2] = coord2;

        if (![lat1, lon1, lat2, lon2].every(isFinite)) {
            alert("Invalid coordinate format! Use: latitude,longitude");
            return;
        }

        // Ambil input values
        const height1 =
            parseFloat(document.getElementById("height1")?.value) || 30;
        const height2 =
            parseFloat(document.getElementById("height2")?.value) || 30;
        const frequency =
            parseFloat(document.getElementById("frequency")?.value) || 6;
        const kFactor =
            parseFloat(document.getElementById("kfactor")?.value) || 1.33;

        // Ambil elevasi tanah
        const [ground1, ground2] = await Promise.all([
            getElevation(lat1, lon1),
            getElevation(lat2, lon2),
        ]);

        const totalHeight1 = ground1 + height1;
        const totalHeight2 = ground2 + height2;
        const distance = haversineDistance(lat1, lon1, lat2, lon2);

        // Ambil terrain profile
        const terrain = await getTerrainProfile(lat1, lon1, lat2, lon2, 100);

        // Gambar chart
        drawTerrainChart(
            terrain,
            totalHeight1,
            totalHeight2,
            distance,
            frequency,
            kFactor,
        );

        // Tampilkan hasil analisis dengan layout baru
        displayResultsNewLayout(
            lat1,
            lon1,
            lat2,
            lon2,
            ground1,
            ground2,
            height1,
            height2,
            totalHeight1,
            totalHeight2,
            distance,
            frequency,
            kFactor,
            terrain,
        );
    } catch (error) {
        console.error("Error in calculation:", error);
        document.getElementById("output").innerHTML = `
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <h4>Error</h4>
                <p>An error occurred: ${error.message}</p>
                <p>Please check your input and try again.</p>
            </div>
        `;
    }
}

// ============== FUNGSI BARU UNTUK LAYOUT ============== //
function displayResultsNewLayout(
    lat1,
    lon1,
    lat2,
    lon2,
    ground1,
    ground2,
    height1,
    height2,
    total1,
    total2,
    distance,
    freq,
    kFactor,
    terrain,
) {
    // Hitung analysis data
    let minClearance = Infinity;
    let maxClearance = -Infinity;
    let maxBulge = 0;
    let maxFresnel = 0;
    let criticalCount = 0;
    let warningCount = 0;

    criticalPoints.forEach((point) => {
        if (point.status === "BLOCKED") criticalCount++;
        if (point.status === "WARNING") warningCount++;

        if (point.clearancePercent < minClearance)
            minClearance = point.clearancePercent;
        if (point.clearancePercent > maxClearance)
            maxClearance = point.clearancePercent;
    });

    // Calculate max earth bulge
    if (globalEarthBulge && globalTerrainOnly) {
        maxBulge = Math.max(
            ...globalEarthBulge.map((bulge, i) => bulge - globalTerrainOnly[i]),
        );
    }

    // Calculate max fresnel radius
    if (globalFresnelUpper && globalLosData) {
        maxFresnel = Math.max(
            ...globalFresnelUpper.map(
                (fresnel, i) => fresnel - globalLosData[i],
            ),
        );
    }

    const losStatus =
        criticalCount > 0 ? "BLOCKED" : warningCount > 0 ? "MARGINAL" : "CLEAR";

    // Update waktu terakhir
    const now = new Date();
    document.getElementById("lastUpdated").textContent =
        `Last updated: ${now.toLocaleTimeString()}`;

    // Update quick stats bar
    document.getElementById("quickStats").style.display = "flex";
    document.getElementById("statDistance").textContent =
        `${distance.toFixed(2)} km`;
    document.getElementById("statStatus").textContent = losStatus;
    document
        .getElementById("statStatus")
        .setAttribute("data-status", losStatus.toLowerCase());
    document.getElementById("statClearance").textContent =
        `${minClearance.toFixed(1)}%`;
    document.getElementById("statFreq").textContent = `${freq} GHz`;

    // Update detailed analysis tabs
    updateOverviewTab(
        ground1,
        ground2,
        height1,
        height2,
        total1,
        total2,
        freq,
        kFactor,
        maxBulge,
        maxFresnel,
    );
    updateCriticalPointsTab();
    updateRecommendationsTab(
        losStatus,
        criticalCount,
        warningCount,
        minClearance,
        total1,
        total2,
        distance,
    );

    // Show analysis container
    document.getElementById("detailedAnalysis").style.display = "block";

    // Update results content dengan tab system
    document.getElementById("output").innerHTML = `
        <div class="results-tabs">
            <div class="tab-buttons">
                <button class="tab-btn active" data-tab="summary">Summary</button>
                <button class="tab-btn" data-tab="details">Details</button>
                <button class="tab-btn" data-tab="export">Export</button>
            </div>
            
            <div class="tab-content active" id="tab-summary">
                <div class="summary-status ${losStatus.toLowerCase()}">
                    <div class="status-icon">
                        ${
                            losStatus === "CLEAR"
                                ? '<i class="fas fa-check-circle"></i>'
                                : losStatus === "MARGINAL"
                                  ? '<i class="fas fa-exclamation-triangle"></i>'
                                  : '<i class="fas fa-times-circle"></i>'
                        }
                    </div>
                    <div class="status-content">
                        <h3>${
                            losStatus === "CLEAR"
                                ? "Link is Clear"
                                : losStatus === "MARGINAL"
                                  ? "Link is Marginal"
                                  : "Link is Blocked"
                        }</h3>
                        <p>${getStatusDescription(losStatus, criticalCount, warningCount, minClearance)}</p>
                    </div>
                </div>
                
                <div class="summary-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-ruler-combined"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value">${distance.toFixed(2)} km</div>
                            <div class="stat-label">Distance</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value">${minClearance.toFixed(1)}%</div>
                            <div class="stat-label">Min Clearance</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-tower-broadcast"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value">${total1.toFixed(0)} / ${total2.toFixed(0)} m</div>
                            <div class="stat-label">Tower Heights</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-wave-square"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value">${freq} GHz</div>
                            <div class="stat-label">Frequency</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="tab-content" id="tab-details">
                <div class="details-grid">
                    <div class="details-card">
                        <h4><i class="fas fa-mountain"></i> Elevation Data</h4>
                        <table class="details-table">
                            <tr>
                                <td>Tower A Elevation</td>
                                <td>${ground1.toFixed(1)} m</td>
                            </tr>
                            <tr>
                                <td>Tower B Elevation</td>
                                <td>${ground2.toFixed(1)} m</td>
                            </tr>
                            <tr>
                                <td>Tower A Height</td>
                                <td>${height1} m</td>
                            </tr>
                            <tr>
                                <td>Tower B Height</td>
                                <td>${height2} m</td>
                            </tr>
                            <tr class="highlight">
                                <td><strong>Total Height A</strong></td>
                                <td><strong>${total1.toFixed(1)} m</strong></td>
                            </tr>
                            <tr class="highlight">
                                <td><strong>Total Height B</strong></td>
                                <td><strong>${total2.toFixed(1)} m</strong></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="details-card">
                        <h4><i class="fas fa-cogs"></i> Link Parameters</h4>
                        <table class="details-table">
                            <tr>
                                <td>Distance</td>
                                <td>${distance.toFixed(2)} km</td>
                            </tr>
                            <tr>
                                <td>Frequency</td>
                                <td>${freq} GHz</td>
                            </tr>
                            <tr>
                                <td>K-Factor</td>
                                <td>${kFactor}</td>
                            </tr>
                            <tr>
                                <td>Max Earth Bulge</td>
                                <td>${maxBulge.toFixed(1)} m</td>
                            </tr>
                            <tr>
                                <td>Max Fresnel Radius</td>
                                <td>${maxFresnel.toFixed(1)} m</td>
                            </tr>
                            <tr>
                                <td>Terrain Samples</td>
                                <td>${terrain.length} points</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="tab-content" id="tab-export">
                <div class="export-options">
                    <h4>Export Results</h4>
                    <div class="export-buttons">
                        <button class="export-btn" onclick="exportAsPDF()">
                            <i class="fas fa-file-pdf"></i> PDF Report
                        </button>
                        <button class="export-btn" onclick="exportAsCSV()">
                            <i class="fas fa-file-csv"></i> CSV Data
                        </button>
                        <button class="export-btn" onclick="copyToClipboard()">
                            <i class="fas fa-copy"></i> Copy Summary
                        </button>
                    </div>
                    <div class="export-preview">
                        <pre id="exportPreview">${generateExportText(lat1, lon1, lat2, lon2, ground1, ground2, height1, height2, total1, total2, distance, freq, kFactor, losStatus, minClearance, criticalCount, warningCount)}</pre>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Add event listeners for tabs
    document.querySelectorAll(".tab-btn").forEach((btn) => {
        btn.addEventListener("click", function () {
            const tabId = this.getAttribute("data-tab");
            switchTab(tabId, this);
        });
    });
}

function updateOverviewTab(
    ground1,
    ground2,
    height1,
    height2,
    total1,
    total2,
    freq,
    kFactor,
    maxBulge,
    maxFresnel,
) {
    const container = document.querySelector("#tab-overview .data-grid");
    if (!container) return;

    container.innerHTML = `
        <div class="data-card">
            <h5>Tower Information</h5>
            <table class="data-table">
                <tr>
                    <td>Elevation A</td>
                    <td>${ground1.toFixed(1)} m</td>
                </tr>
                <tr>
                    <td>Elevation B</td>
                    <td>${ground2.toFixed(1)} m</td>
                </tr>
                <tr>
                    <td>Tower Height A</td>
                    <td>${height1} m</td>
                </tr>
                <tr>
                    <td>Tower Height B</td>
                    <td>${height2} m</td>
                </tr>
                <tr class="highlight">
                    <td><strong>Total Height A</strong></td>
                    <td><strong>${total1.toFixed(1)} m</strong></td>
                </tr>
                <tr class="highlight">
                    <td><strong>Total Height B</strong></td>
                    <td><strong>${total2.toFixed(1)} m</strong></td>
                </tr>
            </table>
        </div>
        
        <div class="data-card">
            <h5>Link Parameters</h5>
            <table class="data-table">
                <tr>
                    <td>Frequency</td>
                    <td>${freq} GHz</td>
                </tr>
                <tr>
                    <td>K-Factor</td>
                    <td>${kFactor}</td>
                </tr>
                <tr>
                    <td>Max Earth Bulge</td>
                    <td>${maxBulge.toFixed(1)} m</td>
                </tr>
                <tr>
                    <td>Max Fresnel Radius</td>
                    <td>${maxFresnel.toFixed(1)} m</td>
                </tr>
            </table>
        </div>
    `;
}

function updateCriticalPointsTab() {
    const container = document.getElementById("criticalList");
    if (!container) return;

    if (criticalPoints.length === 0) {
        container.innerHTML = `
            <div class="no-critical">
                <i class="fas fa-check-circle"></i>
                <p>No critical points found. All clearance levels are above 80%.</p>
            </div>
        `;
        return;
    }

    const items = criticalPoints
        .map(
            (point) => `
        <div class="critical-item ${point.status.toLowerCase()}">
            <div class="critical-info">
                <div class="critical-distance">At ${point.distance.toFixed(2)} km from Tower A</div>
                <div class="critical-details">
                    LOS: ${point.los.toFixed(1)}m | Terrain: ${point.terrain.toFixed(1)}m | 
                    Clearance: ${point.actualClearance.toFixed(1)}m / ${point.clearanceNeeded.toFixed(1)}m
                </div>
            </div>
            <div class="critical-percent ${point.status.toLowerCase()}">
                ${point.clearancePercent.toFixed(1)}%
            </div>
        </div>
    `,
        )
        .join("");

    container.innerHTML = items;
}

function updateRecommendationsTab(
    losStatus,
    blockedCount,
    warningCount,
    minClearance,
    heightA,
    heightB,
    distance,
) {
    const container = document.getElementById("recommendationsList");
    if (!container) return;

    let recommendations = [];

    if (losStatus === "CLEAR") {
        recommendations.push({
            icon: "fas fa-check-circle",
            text: `Link is clear with minimum ${minClearance.toFixed(1)}% Fresnel clearance.`,
            type: "success",
        });

        if (minClearance > 100) {
            recommendations.push({
                icon: "fas fa-lightbulb",
                text: `Consider reducing tower heights by ${Math.round(heightA * 0.1)}m for optimization.`,
                type: "warning",
            });
        }
    } else if (losStatus === "MARGINAL") {
        recommendations.push({
            icon: "fas fa-exclamation-triangle",
            text: `Link is marginal with ${minClearance.toFixed(1)}% clearance at critical points.`,
            type: "warning",
        });

        recommendations.push({
            icon: "fas fa-arrow-up",
            text: `Increase tower heights by ${Math.round((80 - minClearance) * 0.5)}m to improve clearance.`,
            type: "warning",
        });
    } else if (losStatus === "BLOCKED") {
        recommendations.push({
            icon: "fas fa-times-circle",
            text: `Link is blocked at ${blockedCount} points.`,
            type: "critical",
        });

        recommendations.push({
            icon: "fas fa-tower-broadcast",
            text: `Add a repeater at approximately ${(distance / 2).toFixed(1)} km from Tower A.`,
            type: "critical",
        });

        recommendations.push({
            icon: "fas fa-sliders-h",
            text: "Consider using lower frequency (2-3 GHz) for better obstacle penetration.",
            type: "warning",
        });
    }

    // General recommendations
    recommendations.push({
        icon: "fas fa-cloud",
        text: "During heavy rain, signal degradation of 10-20% may occur.",
        type: "info",
    });

    const items = recommendations
        .map(
            (rec) => `
        <div class="recommendation-item ${rec.type}">
            <div class="recommendation-icon">
                <i class="${rec.icon}"></i>
            </div>
            <div class="recommendation-text">${rec.text}</div>
        </div>
    `,
        )
        .join("");

    container.innerHTML = items;
}

// ============== HELPER FUNCTIONS ============== //
function getStatusDescription(
    losStatus,
    criticalCount,
    warningCount,
    minClearance,
) {
    if (losStatus === "CLEAR") {
        return `All points have at least 60% Fresnel clearance. Minimum clearance is ${minClearance.toFixed(1)}%.`;
    } else if (losStatus === "MARGINAL") {
        return `${warningCount} points have clearance between 60-80%. Minimum clearance is ${minClearance.toFixed(1)}%.`;
    } else {
        return `${criticalCount} points are blocked with clearance below 60%. Minimum clearance is ${minClearance.toFixed(1)}%.`;
    }
}

function switchTab(tabId, button) {
    // Hide all tabs
    document.querySelectorAll(".tab-content").forEach((tab) => {
        tab.classList.remove("active");
    });

    // Remove active class from all buttons
    document.querySelectorAll(".tab-btn").forEach((btn) => {
        btn.classList.remove("active");
    });

    // Show selected tab
    document.getElementById(`tab-${tabId}`).classList.add("active");

    // Activate corresponding button
    button.classList.add("active");
}

function showTab(tabId) {
    // Hide all tabs
    document.querySelectorAll(".tab-content").forEach((tab) => {
        tab.classList.remove("active");
    });

    // Remove active class from all buttons
    document.querySelectorAll(".tab-btn").forEach((btn) => {
        btn.classList.remove("active");
    });

    // Show selected tab
    document.getElementById(tabId).classList.add("active");

    // Activate corresponding button
    event.target.classList.add("active");
}

function generateExportText(
    lat1,
    lon1,
    lat2,
    lon2,
    ground1,
    ground2,
    height1,
    height2,
    total1,
    total2,
    distance,
    freq,
    kFactor,
    losStatus,
    minClearance,
    criticalCount,
    warningCount,
) {
    return `POINT-TO-POINT LINK ANALYSIS REPORT
=========================================
Analysis Date: ${new Date().toLocaleString()}
Link Status: ${losStatus}

TOWER A:
--------
Coordinates: ${lat1.toFixed(6)}, ${lon1.toFixed(6)}
Ground Elevation: ${ground1.toFixed(1)} m
Tower Height: ${height1} m
Total Height: ${total1.toFixed(1)} m

TOWER B:
--------
Coordinates: ${lat2.toFixed(6)}, ${lon2.toFixed(6)}
Ground Elevation: ${ground2.toFixed(1)} m
Tower Height: ${height2} m
Total Height: ${total2.toFixed(1)} m

LINK PARAMETERS:
----------------
Distance: ${distance.toFixed(2)} km
Frequency: ${freq} GHz
K-Factor: ${kFactor}

ANALYSIS RESULTS:
-----------------
Status: ${losStatus}
Minimum Fresnel Clearance: ${minClearance.toFixed(1)}%
Critical Points: ${criticalCount}
Warning Points: ${warningCount}

RECOMMENDATIONS:
----------------
${
    losStatus === "CLEAR"
        ? "✓ Link is feasible with current parameters."
        : losStatus === "MARGINAL"
          ? "⚠ Link may experience intermittent issues."
          : "✗ Link is not feasible with current parameters."
}

END OF REPORT`;
}

// Export functions
function exportAsPDF() {
    alert("PDF export feature would be implemented here.");
}

function exportAsCSV() {
    alert("CSV export feature would be implemented here.");
}

function copyToClipboard() {
    const exportText = generateExportText(...arguments);
    navigator.clipboard.writeText(exportText).then(() => {
        alert("Summary copied to clipboard!");
    });
}

// ============== INITIALIZE ON LOAD ============== //
document.addEventListener("DOMContentLoaded", function () {
    // Add sample data button
    const form = document.getElementById("calcForm");
    if (form) {
        const sampleBtn = document.createElement("button");
        sampleBtn.type = "button";
        sampleBtn.className = "btn-sample";
        sampleBtn.innerHTML = '<i class="fas fa-vial"></i> Load Sample Data';
        sampleBtn.onclick = loadSampleData;

        form.querySelector(".uisp-action").appendChild(sampleBtn);
    }
});

function loadSampleData() {
    document.getElementById("coord1").value = "-6.2088, 106.8456";
    document.getElementById("height1").value = "50";
    document.getElementById("coord2").value = "-7.7956, 110.3695";
    document.getElementById("height2").value = "70";
    document.getElementById("frequency").value = "6";
    document.getElementById("kfactor").value = "1.33";

    // Show notification
    const notification = document.createElement("div");
    notification.className = "sample-notification";
    notification.innerHTML =
        '<i class="fas fa-check-circle"></i> Sample data loaded. Click "Calculate Link" to analyze.';
    document.querySelector(".uisp-form").appendChild(notification);

    setTimeout(() => notification.remove(), 3000);
}

// Export functions to window
window.performCalculation = performCalculation;
window.showTab = showTab;
window.switchTab = switchTab;
window.exportAsPDF = exportAsPDF;
window.exportAsCSV = exportAsCSV;
window.copyToClipboard = copyToClipboard;
window.loadSampleData = loadSampleData;
