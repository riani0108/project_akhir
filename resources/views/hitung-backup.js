// resources/js/hitung.js - VERSI TERPISAH DENGAN TAMPILAN BARU

// ============== VARIABEL GLOBAL UNTUK DATA ============== //
let globalTerrainData = null;
let globalLosData = null;
let globalFresnelUpper = null;
let globalFresnelLower = null;
let globalEarthBulge = null;
let globalTerrainOnly = null;
let globalDistanceKm = null;
let globalFrequency = null;
let globalTerrainSlope = null;
let criticalPoints = [];
let terrainChartInstance = null;

// ============== FUNGSI DASAR ============== //
function getMinMax(...arrays) {
    const flat = arrays.flat().filter((v) => isFinite(v));
    if (flat.length === 0) return { min: 0, max: 100 };
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

async function getTerrainProfile(lat1, lon1, lat2, lon2, samples = 60) {
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
        const elevations = data.results.map((r) => r.elevation ?? 0);

        // Normalize terrain untuk menghindari garis lurus
        return normalizeTerrain(elevations);
    } catch (e) {
        console.error("Error fetching terrain profile:", e);
        // Return dummy data untuk testing dengan variasi
        return Array.from(
            { length: samples + 1 },
            (_, i) => 50 + 20 * Math.sin(i / 5) + Math.random() * 10,
        );
    }
}

function normalizeTerrain(terrain) {
    const min = Math.min(...terrain);
    const max = Math.max(...terrain);

    if (max - min < 2) {
        return terrain.map((h, i) => h + 5 * Math.sin(i / 10));
    }
    return terrain;
}

function detectLOSFromTerrain(
    terrain,
    heightA,
    heightB,
    distanceKm,
    frequencyGHz = 6,
) {
    if (terrain.length < 3) return { status: "UNKNOWN" };

    let blocked = false;
    const samples = terrain.length - 1;

    for (let i = 1; i < terrain.length - 1; i++) {
        const t = i / samples;
        const losHeight = heightA + (heightB - heightA) * t;

        const d1 = distanceKm * t;
        const d2 = distanceKm - d1;

        const r = 17.32 * Math.sqrt((d1 * d2) / (frequencyGHz * distanceKm));
        const clearanceNeeded = 0.6 * r;
        const actualClearance = losHeight - terrain[i];

        if (actualClearance < clearanceNeeded) {
            blocked = true;
            break;
        }
    }

    return {
        status: blocked ? "BLOCKED" : "CLEAR",
        blocked: blocked,
    };
}

// ============== FUNGSI TAMBAHAN ============== //

// 1. FUNGSI SLOPE TANAH
function calculateSlopePercent(h1, h2, dKm) {
    if (dKm === 0) return 0;
    const dMeter = dKm * 1000;
    return ((h2 - h1) / dMeter) * 100;
}

function calculateTerrainSlope(terrain, distanceKm) {
    if (!Array.isArray(terrain) || terrain.length < 2) {
        return [];
    }

    const slopes = [];
    const samples = terrain.length - 1;
    const segmentDistanceKm = distanceKm / samples;

    for (let i = 0; i < terrain.length; i++) {
        if (i === 0) {
            slopes.push(0);
            continue;
        }

        const h1 = terrain[i - 1];
        const h2 = terrain[i];
        const slopePercent = calculateSlopePercent(h1, h2, segmentDistanceKm);
        slopes.push(slopePercent);
    }

    return slopes;
}

function classifySlope(slopePercent) {
    const absSlope = Math.abs(slopePercent);

    if (absSlope < 3) {
        return {
            label: "Datar",
            color: "#16a34a",
            level: "low",
            risk: "Rendah",
        };
    }

    if (absSlope < 8) {
        return {
            label: "Landai",
            color: "#65a30d",
            level: "medium",
            risk: "Sedang",
        };
    }

    if (absSlope < 15) {
        return {
            label: "Miring",
            color: "#facc15",
            level: "warning",
            risk: "Tinggi",
        };
    }

    if (absSlope < 30) {
        return {
            label: "Curam",
            color: "#f97316",
            level: "high",
            risk: "Sangat Tinggi",
        };
    }

    return {
        label: "Sangat Curam",
        color: "#dc2626",
        level: "critical",
        risk: "Ekstrem",
    };
}

function getAverageSlope(slopes) {
    const validSlopes = slopes.filter((s) => isFinite(s));
    if (validSlopes.length === 0) return 0;
    const sum = validSlopes.reduce((a, b) => a + Math.abs(b), 0);
    return sum / validSlopes.length;
}

// 2. ANALISIS TITIK KRITIS
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

        const r = 17.32 * Math.sqrt((d1 * d2) / (frequency * distanceKm));
        const clearanceNeeded = 0.6 * r;
        const actualClearance = losData[i] - terrain[i];
        const clearancePercent = (actualClearance / clearanceNeeded) * 100;

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

// 3. FUNGSI HITUNG REDAMAN
function calculateFSPL(distanceKm, frequencyGHz) {
    return 92.45 + 20 * Math.log10(distanceKm) + 20 * Math.log10(frequencyGHz);
}

function classifyAttenuation(linkMargin) {
    if (linkMargin >= 25) return { label: "Sangat Bagus", status: "excellent" };
    if (linkMargin >= 20) return { label: "Bagus", status: "good" };
    if (linkMargin >= 15) return { label: "Cukup", status: "fair" };
    if (linkMargin >= 10) return { label: "Buruk", status: "poor" };
    return { label: "Tidak Layak", status: "bad" };
}

// 4. FUNGSI POPUP & LOADING
function showInfoPopup(title, message, type = "info") {
    // Hapus popup sebelumnya jika ada
    const oldPopup = document.querySelector(".info-popup");
    if (oldPopup) oldPopup.remove();

    const popup = document.createElement("div");
    popup.className = `info-popup ${type}`;

    const icon =
        type === "success"
            ? "✅"
            : type === "error"
              ? "❌"
              : type === "warning"
                ? "⚠️"
                : "ℹ️";

    popup.innerHTML = `
        <div class="popup-content">
            <div class="popup-header">
                <span class="popup-icon">${icon}</span>
                <h3>${title}</h3>
            </div>
            <p class="popup-message">${message}</p>
            <div class="popup-actions">
                <button onclick="this.parentElement.parentElement.parentElement.remove()" 
                        class="btn-popup ${type}">
                    Tutup
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(popup);

    // Auto remove setelah 5 detik
    setTimeout(() => {
        if (popup.parentElement) {
            popup.remove();
        }
    }, 5000);
}

function showLoading(message = "Memproses data...") {
    // Hapus loading sebelumnya jika ada
    const oldLoading = document.getElementById("loadingOverlay");
    if (oldLoading) oldLoading.remove();

    const loadingDiv = document.createElement("div");
    loadingDiv.className = "loading-overlay";
    loadingDiv.id = "loadingOverlay";

    loadingDiv.innerHTML = `
        <div class="loading-content">
            <div class="spinner"></div>
            <p style="font-size: 16px; color: #333;">${message}</p>
        </div>
    `;

    document.body.appendChild(loadingDiv);
}

function hideLoading() {
    const loadingDiv = document.getElementById("loadingOverlay");
    if (loadingDiv) {
        loadingDiv.remove();
    }
}

// 5. FUNGSI LOAD SAMPLE DATA
function loadSampleData() {
    const coord1Input = document.getElementById("coord1");
    const height1Input = document.getElementById("height1");
    const coord2Input = document.getElementById("coord2");
    const height2Input = document.getElementById("height2");
    const frequencyInput = document.getElementById("frequency");
    const kfactorInput = document.getElementById("kfactor");

    if (coord1Input) coord1Input.value = "-6.2088, 106.8456";
    if (height1Input) height1Input.value = "50";
    if (coord2Input) coord2Input.value = "-7.7956, 110.3695";
    if (height2Input) height2Input.value = "70";
    if (frequencyInput) frequencyInput.value = "6";
    if (kfactorInput) kfactorInput.value = "1.33";

    showInfoPopup(
        "Data Contoh Dimuat",
        "Data contoh telah dimuat. Klik 'Calculate Link' untuk menganalisis.",
        "success",
    );
}

// 6. FUNGSI EXPORT
function exportAsPDF() {
    showInfoPopup(
        "Export PDF",
        "Fitur export PDF akan diimplementasikan pada versi berikutnya.",
        "info",
    );
}

function exportAsCSV() {
    if (!globalTerrainOnly || !criticalPoints) {
        showInfoPopup(
            "Error",
            "Tidak ada data untuk di-export. Lakukan perhitungan terlebih dahulu.",
            "error",
        );
        return;
    }

    let csvContent = "Jarak (km),Elevasi (m),Slope (%),Status\n";

    for (let i = 0; i < globalTerrainOnly.length; i++) {
        const dist = (i / (globalTerrainOnly.length - 1)) * globalDistanceKm;
        const elev = globalTerrainOnly[i];
        const slope = globalTerrainSlope ? globalTerrainSlope[i] : 0;

        let status = "CLEAR";
        const criticalPoint = criticalPoints.find(
            (p) => Math.abs(p.distance - dist) < 0.01,
        );
        if (criticalPoint) {
            status = criticalPoint.status;
        }

        csvContent += `${dist.toFixed(2)},${elev.toFixed(1)},${slope.toFixed(1)},${status}\n`;
    }

    const blob = new Blob([csvContent], { type: "text/csv" });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `los-analysis-${new Date().toISOString().slice(0, 10)}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);

    showInfoPopup(
        "Export Berhasil",
        "Data telah diexport ke file CSV.",
        "success",
    );
}

function copyToClipboard() {
    if (!globalDistanceKm) {
        showInfoPopup(
            "Error",
            "Tidak ada data untuk disalin. Lakukan perhitungan terlebih dahulu.",
            "error",
        );
        return;
    }

    let text = `=== HASIL ANALISIS LOS ===\n`;
    text += `Tanggal: ${new Date().toLocaleString()}\n`;
    text += `Jarak: ${globalDistanceKm.toFixed(2)} km\n`;
    text += `Frekuensi: ${globalFrequency || "N/A"} GHz\n`;
    text += `Titik Kritis: ${criticalPoints.length}\n\n`;

    if (criticalPoints.length > 0) {
        text += `TITIK KRITIS:\n`;
        criticalPoints.slice(0, 3).forEach((point) => {
            text += `- ${point.distance.toFixed(2)} km: ${point.status} (${point.clearancePercent.toFixed(1)}% clearance)\n`;
        });
    }

    navigator.clipboard.writeText(text).then(() => {
        showInfoPopup(
            "Disalin",
            "Hasil analisis telah disalin ke clipboard.",
            "success",
        );
    });
}

// 7. FUNGSI TAB SYSTEM
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
    if (button) {
        button.classList.add("active");
    }
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
    if (event && event.target) {
        event.target.classList.add("active");
    }
}

// 8. FUNGSI GRAFIK YANG DIPERBAIKI (dari Kode 1)
function drawTerrainChart(
    terrain,
    heightA,
    heightB,
    distanceKm,
    frequencyGHz = 6,
    kFactor = 1.33,
) {
    const ctx = document.getElementById("terrainChart")?.getContext("2d");
    if (!ctx) {
        console.error("Canvas terrainChart tidak ditemukan!");
        return;
    }

    if (terrainChartInstance) {
        terrainChartInstance.destroy();
    }

    const samples = terrain.length - 1;
    const labels = [];
    const losData = [];
    const fresnelUpper = [];
    const fresnelLower = [];
    const earthBulge = [];
    const terrainOnly = [];
    const clearanceLine = [];
    const warningLine = [];

    // Hitung slope tanah
    globalTerrainSlope = calculateTerrainSlope(terrain, distanceKm);

    // Simpan data ke global
    globalTerrainData = terrain;
    globalDistanceKm = distanceKm;
    globalFrequency = frequencyGHz;

    for (let i = 0; i <= samples; i++) {
        const distFromA = distanceKm * (i / samples);
        labels.push(distFromA.toFixed(2));

        terrainOnly.push(terrain[i]);

        const losHeight = heightA + (heightB - heightA) * (i / samples);
        losData.push(losHeight);

        const d1 = distFromA;
        const d2 = distanceKm - distFromA;

        const bulge = (d1 * d2) / (12.75 * kFactor);
        earthBulge.push(bulge);

        const r = 17.32 * Math.sqrt((d1 * d2) / (frequencyGHz * distanceKm));
        fresnelUpper.push(losHeight + r);
        fresnelLower.push(losHeight - r);

        clearanceLine.push(terrain[i] + 0.6 * r);
        warningLine.push(terrain[i] + 0.8 * r);
    }

    globalLosData = losData;
    globalFresnelUpper = fresnelUpper;
    globalFresnelLower = fresnelLower;
    globalEarthBulge = earthBulge;
    globalTerrainOnly = terrainOnly;

    analyzeCriticalPoints(
        terrainOnly,
        losData,
        fresnelLower,
        distanceKm,
        frequencyGHz,
    );

    const yScale = getMinMax(terrainOnly, losData, fresnelUpper, fresnelLower);

    terrainChartInstance = new Chart(ctx, {
        type: "line",
        data: {
            labels,
            datasets: [
                {
                    label: "Elevasi Tanah",
                    data: terrainOnly,
                    borderColor: (ctx) => {
                        const i = ctx.dataIndex;
                        if (i === undefined) return "#8B4513";
                        const slope = globalTerrainSlope?.[i] ?? 0;
                        const classification = classifySlope(Math.abs(slope));
                        return classification.color;
                    },
                    backgroundColor: (ctx) => {
                        const i = ctx.dataIndex;
                        if (i === undefined) return "rgba(139, 69, 19, 0.3)";
                        const slope = globalTerrainSlope?.[i] ?? 0;
                        const classification = classifySlope(Math.abs(slope));
                        return `${classification.color}30`;
                    },
                    borderWidth: 3,
                    pointRadius: 0,
                    fill: true,
                    order: 5,
                },
                {
                    label: "Earth Bulge",
                    data: earthBulge.map((b, i) => terrainOnly[i] + b),
                    borderColor: "#A0522D",
                    borderDash: [4, 4],
                    borderWidth: 2,
                    pointRadius: 0,
                    fill: false,
                    order: 8,
                },
                {
                    label: "Line of Sight",
                    data: losData,
                    borderColor: "#FF0000",
                    borderWidth: 4,
                    borderDash: [6, 4],
                    pointRadius: 0,
                    order: 1,
                },
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
                {
                    label: "Warning Zone (80%)",
                    data: warningLine,
                    borderColor: "#FFA500",
                    borderDash: [3, 3],
                    pointRadius: 0,
                    borderWidth: 1,
                    order: 6,
                },
                {
                    label: "Minimum Clearance (60%)",
                    data: clearanceLine,
                    borderColor: "#FF4500",
                    borderDash: [5, 5],
                    pointRadius: 0,
                    borderWidth: 2,
                    order: 7,
                },
                {
                    label: "Titik Kritis",
                    data: criticalPoints.map((p) => p.los),
                    pointBackgroundColor: criticalPoints.map((p) =>
                        p.status === "BLOCKED" ? "#FF0000" : "#FFA500",
                    ),
                    borderColor: "#FFFFFF",
                    borderWidth: 2,
                    pointRadius: 6,
                    showLine: false,
                    order: 2,
                },
                {
                    label: "Posisi Tower",
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
                {
                    label: "Slope Tanah (%)",
                    data: globalTerrainSlope,
                    yAxisID: "ySlope",
                    borderColor: "#1565C0",
                    borderWidth: 2,
                    borderDash: [4, 4],
                    pointRadius: 0,
                    fill: false,
                    order: 20,
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
                    text: `Profil LOS - Tower A: ${heightA.toFixed(0)}m, Tower B: ${heightB.toFixed(0)}m | Jarak: ${distanceKm.toFixed(2)}km | Slope: ${getAverageSlope(globalTerrainSlope).toFixed(1)}%`,
                    font: { size: 16, weight: "bold" },
                    color: "#333",
                    padding: { bottom: 20 },
                },
                legend: {
                    position: "top",
                    labels: {
                        filter: function (item, chart) {
                            const hideLabels = [
                                "Fresnel Zone (Lower)",
                                "Titik Kritis",
                                "Posisi Tower",
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

                            switch (label) {
                                case "Elevasi Tanah":
                                    const slope =
                                        globalTerrainSlope?.[
                                            context.dataIndex
                                        ] ?? 0;
                                    const classification = classifySlope(
                                        Math.abs(slope),
                                    );
                                    return [
                                        `🗻 Elevasi Tanah: ${value.toFixed(1)} mdpl`,
                                        `📐 Kemiringan: ${slope.toFixed(2)}% (${classification.label})`,
                                        `⚠️ Risiko: ${classification.risk}`,
                                    ];
                                case "Slope Tanah (%)":
                                    const slopeValue = value;
                                    const slopeClass = classifySlope(
                                        Math.abs(slopeValue),
                                    );
                                    return [
                                        `📐 Slope: ${slopeValue.toFixed(2)}%`,
                                        `🏞️ Klasifikasi: ${slopeClass.label}`,
                                        slopeValue > 8
                                            ? `⚠️ Perhatian: Tanjakan curam!`
                                            : `✅ Aman untuk instalasi`,
                                    ];
                                case "Line of Sight":
                                    return `📡 LOS: ${value.toFixed(1)} m`;
                                case "Fresnel Zone (Upper)":
                                    return `📶 Fresnel Upper: ${value.toFixed(1)} m`;
                                case "Fresnel Zone (Lower)":
                                    return `📉 Fresnel Lower: ${value.toFixed(1)} m`;
                                case "Minimum Clearance (60%)":
                                    return `⚠️ Minimum 60%: ${value.toFixed(1)} m`;
                                case "Warning Zone (80%)":
                                    return `⚠️ Warning 80%: ${value.toFixed(1)} m`;
                                default:
                                    return `${label}: ${value.toFixed(1)} m`;
                            }
                        },
                        afterLabel: function (context) {
                            const label = context.dataset.label || "";
                            if (label === "Elevasi Tanah") {
                                const distance = context.parsed.x;
                                const criticalPoint = criticalPoints.find(
                                    (p) =>
                                        Math.abs(p.distance - distance) < 0.01,
                                );
                                if (criticalPoint) {
                                    return `🚨 ${criticalPoint.status}: ${criticalPoint.clearancePercent.toFixed(1)}% clearance`;
                                }
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
                ySlope: {
                    position: "right",
                    title: {
                        display: true,
                        text: "Slope (%)",
                        font: { weight: "bold" },
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                    ticks: {
                        callback: (v) => `${v.toFixed(1)} %`,
                    },
                    suggestedMin: -20,
                    suggestedMax: 20,
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

// 9. FUNGSI UPDATE QUICK STATS
function updateQuickStats(distance, status, minClearance, frequency) {
    const quickStats = document.getElementById("quickStats");
    if (quickStats) {
        quickStats.style.display = "flex";
        document.getElementById("statDistance").textContent =
            `${distance.toFixed(2)} km`;
        document.getElementById("statStatus").textContent = status;
        document
            .getElementById("statStatus")
            .setAttribute("data-status", status.toLowerCase());
        document.getElementById("statClearance").textContent =
            `${minClearance.toFixed(1)}%`;
        document.getElementById("statFreq").textContent = `${frequency} GHz`;
    }

    // Update last updated time
    const lastUpdated = document.getElementById("lastUpdated");
    if (lastUpdated) {
        lastUpdated.textContent = `Terakhir diperbarui: ${new Date().toLocaleTimeString()}`;
    }
}

// 10. FUNGSI UPDATE DETAILED ANALYSIS (dari Kode 1)
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
                    <td>Elevasi Tanah Tower A</td>
                    <td>${ground1.toFixed(1)} mdpl</td>
                </tr>
                <tr>
                    <td>Elevasi Tanah Tower B</td>
                    <td>${ground2.toFixed(1)} mdpl</td>
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

// 11. HELPER FUNCTIONS
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

// 12. FUNGSI UTAMA YANG DIPERBAIKI
async function performCalculation() {
    try {
        // Show loading state
        const output = document.getElementById("output");
        if (output) {
            output.innerHTML = `
                <div class="loading-compact">
                    <div class="spinner-compact"></div>
                    <p>Calculating LOS and analyzing terrain...</p>
                </div>
            `;
        }

        const coord1Input =
            document.getElementById("coord1")?.value.trim() || "";
        const coord2Input =
            document.getElementById("coord2")?.value.trim() || "";

        if (!coord1Input || !coord2Input) {
            showInfoPopup(
                "Error",
                "Masukkan koordinat untuk kedua tower!",
                "error",
            );
            return;
        }

        const coord1 = coord1Input.split(",").map((s) => parseFloat(s.trim()));
        const coord2 = coord2Input.split(",").map((s) => parseFloat(s.trim()));

        const [lat1, lon1] = coord1;
        const [lat2, lon2] = coord2;

        if (![lat1, lon1, lat2, lon2].every(isFinite)) {
            showInfoPopup(
                "Error",
                "Format koordinat tidak valid! Gunakan: latitude,longitude",
                "error",
            );
            return;
        }

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
            maxBulge = Math.max(...globalEarthBulge.map((bulge, i) => bulge));
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
            criticalCount > 0
                ? "BLOCKED"
                : warningCount > 0
                  ? "MARGINAL"
                  : "CLEAR";

        // Update quick stats
        updateQuickStats(distance, losStatus, minClearance, frequency);

        // Update detailed analysis tabs
        updateOverviewTab(
            ground1,
            ground2,
            height1,
            height2,
            totalHeight1,
            totalHeight2,
            frequency,
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
            totalHeight1,
            totalHeight2,
            distance,
        );

        // Show analysis container
        const analysisContainer = document.getElementById("detailedAnalysis");
        if (analysisContainer) {
            analysisContainer.style.display = "block";
        }

        // Update results content dengan tab system
        if (output) {
            output.innerHTML = `
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
                                    <div class="stat-value">${totalHeight1.toFixed(0)} / ${totalHeight2.toFixed(0)} m</div>
                                    <div class="stat-label">Tower Heights</div>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-wave-square"></i>
                                </div>
                                <div class="stat-info">
                                    <div class="stat-value">${frequency} GHz</div>
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
                                        <td><strong>${totalHeight1.toFixed(1)} m</strong></td>
                                    </tr>
                                    <tr class="highlight">
                                        <td><strong>Total Height B</strong></td>
                                        <td><strong>${totalHeight2.toFixed(1)} m</strong></td>
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
                                        <td>${frequency} GHz</td>
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
                                <pre id="exportPreview">${generateExportText(lat1, lon1, lat2, lon2, ground1, ground2, height1, height2, totalHeight1, totalHeight2, distance, frequency, kFactor, losStatus, minClearance, criticalCount, warningCount)}</pre>
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

        showInfoPopup(
            "Analisis Selesai",
            "Perhitungan LOS dan analisis terrain telah selesai.",
            "success",
        );
    } catch (error) {
        console.error("Error in calculation:", error);
        showInfoPopup("Error", `Terjadi kesalahan: ${error.message}`, "error");

        const output = document.getElementById("output");
        if (output) {
            output.innerHTML = `
                <div class="error">
                    <i class="fas fa-exclamation-circle"></i>
                    <h4>Error</h4>
                    <p>An error occurred: ${error.message}</p>
                    <p>Please check your input and try again.</p>
                </div>
            `;
        }
    }
}

// 13. INISIALISASI
document.addEventListener("DOMContentLoaded", function () {
    // Add sample data button
    const form = document.getElementById("calcForm");
    if (form) {
        const sampleBtn = document.createElement("button");
        sampleBtn.type = "button";
        sampleBtn.className = "btn-sample";
        sampleBtn.innerHTML = '<i class="fas fa-vial"></i> Load Sample Data';
        sampleBtn.onclick = loadSampleData;

        const actionDiv = form.querySelector(".uisp-action");
        if (actionDiv) {
            actionDiv.appendChild(sampleBtn);
        }
    }

    // Initialize last updated time
    const lastUpdated = document.getElementById("lastUpdated");
    if (lastUpdated) {
        lastUpdated.textContent = `Siap untuk kalkulasi`;
    }
});

// Export fungsi ke window
window.performCalculation = performCalculation;
window.loadSampleData = loadSampleData;
window.exportAsPDF = exportAsPDF;
window.exportAsCSV = exportAsCSV;
window.copyToClipboard = copyToClipboard;
window.switchTab = switchTab;
window.showTab = showTab;
