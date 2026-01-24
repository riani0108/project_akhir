
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
    const R = 6371; // Radius bumi dalam km
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
        return data.results.map((r) => r.elevation ?? 0);
    } catch (e) {
        console.error("Error fetching terrain profile:", e);
        return Array(points.length).fill(0);
    }
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

        // Radius Fresnel zone pertama (m)
        const r = 17.32 * Math.sqrt((d1 * d2) / (frequencyGHz * distanceKm));

        const clearanceNeeded = 0.6 * r; // 60% clearance
        const actualClearance = losHeight - terrain[i];

        if (actualClearance < clearanceNeeded) {
            blocked = true;
            break;
        }
    }

    return { status: blocked ? "BLOCKED" : "CLEAR" };
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
    const terrainOnly = []; // Tambahan: elevasi tanah tanpa earth bulge

    for (let i = 0; i <= samples; i++) {
        const distFromA = distanceKm * (i / samples);
        labels.push(distFromA.toFixed(2));

        // Garis LOS (termasuk tinggi tower)
        const losHeight = heightA + (heightB - heightA) * (i / samples);
        losData.push(losHeight);

        const d1 = distFromA;
        const d2 = distanceKm - distFromA;

        // Earth curvature bulge
        const bulge = (d1 * d2) / (12.75 * kFactor);

        // Simpan terrain only (untuk debug)
        terrainOnly.push(terrain[i]);

        // Elevasi tanah + earth bulge
        earthBulge.push(terrain[i] + bulge);

        // Fresnel zone radius
        const r = 17.32 * Math.sqrt((d1 * d2) / (frequencyGHz * distanceKm));
        fresnelUpper.push(losHeight + r);
        fresnelLower.push(losHeight - r);
    }

    const ctx = document.getElementById("terrainChart")?.getContext("2d");
    if (!ctx) {
        console.error("Canvas terrainChart tidak ditemukan!");
        return;
    }

    if (terrainChartInstance) {
        terrainChartInstance.destroy();
    }

    const yScale = getMinMax(earthBulge, losData, fresnelUpper, fresnelLower);

    terrainChartInstance = new Chart(ctx, {
        type: "line",
        data: {
            labels,
            datasets: [
                {
                    label: "Elevasi Tanah Saja",
                    data: terrainOnly,
                    borderColor: "#A0522D",
                    backgroundColor: "rgba(160, 82, 45, 0.1)",
                    borderWidth: 1,
                    borderDash: [2, 2],
                    fill: false,
                    pointRadius: 0,
                },
                {
                    label: "Elevasi Tanah + Earth Bulge",
                    data: earthBulge,
                    borderColor: "#8B4513",
                    backgroundColor: "rgba(139, 69, 19, 0.15)",
                    fill: true,
                    pointRadius: 0,
                },
                {
                    label: "Garis LOS (Termasuk Tower)",
                    data: losData,
                    borderColor: "#FF0000",
                    borderWidth: 4,
                    borderDash: [6, 4],
                    pointRadius: 0,
                },
                {
                    label: "Fresnel Zone (Upper)",
                    data: fresnelUpper,
                    borderColor: "#00FF00",
                    backgroundColor: "rgba(0, 255, 0, 0.1)",
                    fill: "+1",
                    pointRadius: 0,
                },
                {
                    label: "Fresnel Zone (Lower)",
                    data: fresnelLower,
                    borderColor: "#00FF00",
                    fill: "-1",
                    pointRadius: 0,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: `Profil LOS - Tower A: ${heightA.toFixed(0)}m, Tower B: ${heightB.toFixed(0)}m`,
                    font: { size: 16 },
                },
                legend: {
                    position: "top",
                    labels: {
                        filter: function (item, chart) {
                            // Sembunyikan Fresnel Lower dari legend
                            return !item.text.includes("Fresnel Zone (Lower)");
                        },
                    },
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const datasetLabel = context.dataset.label || "";
                            const value = context.parsed.y;

                            // Custom tooltip untuk setiap dataset
                            if (datasetLabel.includes("Tanah Saja")) {
                                return `Tanah: ${value.toFixed(1)} m`;
                            }
                            if (datasetLabel.includes("Earth Bulge")) {
                                return `Tanah + Bulge: ${value.toFixed(1)} m`;
                            }
                            if (datasetLabel.includes("Garis LOS")) {
                                return `Garis LOS: ${value.toFixed(1)} m`;
                            }
                            if (datasetLabel.includes("Upper")) {
                                return `Fresnel Upper: ${value.toFixed(1)} m`;
                            }
                            if (datasetLabel.includes("Lower")) {
                                return `Fresnel Lower: ${value.toFixed(1)} m`;
                            }
                            return `${datasetLabel}: ${value.toFixed(1)} m`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: "Jarak dari Tower A (km)",
                    },
                },
                y: {
                    min: yScale.min,
                    max: yScale.max,
                    title: {
                        display: true,
                        text: "Ketinggian (m dpl)",
                    },
                    ticks: {
                        callback: (v) => Math.round(v),
                    },
                },
            },
        },
    });

    // Tampilkan info tinggi tower di console untuk debugging
    console.log("Tower Height A:", heightA, "m");
    console.log("Tower Height B:", heightB, "m");
    console.log("Terrain profile length:", terrain.length);
    console.log("Sample LOS values:", losData.slice(0, 3));
}

// === FUNGSI UTAMA YANG DIPANGGIL DARI TOMBOL ===
async function performCalculation() {
    // Reset output
    document.getElementById("output").innerHTML = "Menghitung...";

    // Ambil nilai dari form
    const coord1Input = document.getElementById("coord1")?.value.trim() || "";
    const coord2Input = document.getElementById("coord2")?.value.trim() || "";

    if (!coord1Input || !coord2Input) {
        alert("Masukkan koordinat Tower 1 dan Tower 2!");
        return;
    }

    // Parse koordinat
    const coord1 = coord1Input.split(",").map((s) => s.trim());
    const coord2 = coord2Input.split(",").map((s) => s.trim());

    const lat1 = parseFloat(coord1[0]);
    const lon1 = parseFloat(coord1[1]);
    const lat2 = parseFloat(coord2[0]);
    const lon2 = parseFloat(coord2[1]);

    if (![lat1, lon1, lat2, lon2].every(isFinite)) {
        alert(
            "Format koordinat salah! Gunakan: latitude, longitude (contoh: -6.2088, 106.8456)",
        );
        return;
    }

    // AMBIL TINGGI TOWER DENGAN BENAR!
    // Cek semua kemungkinan ID yang mungkin digunakan di form
    const height1Input =
        document.getElementById("height1") ||
        document.getElementById("tower1_height") ||
        document.getElementById("tower_height1");

    const height2Input =
        document.getElementById("height2") ||
        document.getElementById("tower2_height") ||
        document.getElementById("tower_height2");

    const height1 = height1Input ? parseFloat(height1Input.value) || 30 : 30;
    const height2 = height2Input ? parseFloat(height2Input.value) || 30 : 30;

    const frequencyInput =
        document.getElementById("frequency") || document.getElementById("freq");
    const kFactorInput =
        document.getElementById("kfactor") ||
        document.getElementById("k_factor");

    const frequency = frequencyInput
        ? parseFloat(frequencyInput.value) || 6
        : 6;
    const kFactor = kFactorInput
        ? parseFloat(kFactorInput.value) || 1.33
        : 1.33;

    console.log("Input values:", {
        lat1,
        lon1,
        lat2,
        lon2,
        height1,
        height2,
        frequency,
        kFactor,
    });

    // Ambil elevasi tanah
    document.getElementById("output").innerHTML = "Mengambil data elevasi...";
    const ground1 = await getElevation(lat1, lon1);
    const ground2 = await getElevation(lat2, lon2);

    // TOTAL HEIGHT = ELEVASI TANAH + TINGGI TOWER
    const totalHeight1 = ground1 + height1;
    const totalHeight2 = ground2 + height2;

    const distance = haversineDistance(lat1, lon1, lat2, lon2);
    const angle = calculateElevationAngle(totalHeight1, totalHeight2, distance);

    document.getElementById("output").innerHTML =
        "Menganalisis terrain profile...";
    const terrain = await getTerrainProfile(lat1, lon1, lat2, lon2, 60);

    const losResult = detectLOSFromTerrain(
        terrain,
        totalHeight1, // Gunakan total height (ground + tower)
        totalHeight2,
        distance,
        frequency,
    );

    // Gambar chart dengan parameter yang benar
    drawTerrainChart(
        terrain,
        totalHeight1, // Parameter heightA untuk chart
        totalHeight2, // Parameter heightB untuk chart
        distance,
        frequency,
        kFactor,
    );

    // Tampilkan hasil dengan informasi lengkap
    const statusClass =
        losResult.status === "CLEAR"
            ? "uisp-status-clear"
            : "uisp-status-blocked";

    const clearancePercent = losResult.status === "CLEAR" ? ">60%" : "<60%";

    document.getElementById("output").innerHTML = `
    <div class="result-container">
        <h4>📡 Hasil Analisis Link</h4>

        <div class="result-grid">
            <div class="result-column">
                <h5>📊 Data Input</h5>
                <p><b>Jarak:</b> ${distance.toFixed(2)} km</p>
                <p><b>Tower 1:</b><br>
                   - Lokasi: ${lat1.toFixed(4)}, ${lon1.toFixed(4)}<br>
                   - Elevasi tanah: ${ground1.toFixed(0)} m<br>
                   - Tinggi tower: ${height1} m<br>
                   - <b>Total tinggi:</b> ${totalHeight1.toFixed(0)} m
                </p>
                <p><b>Tower 2:</b><br>
                   - Lokasi: ${lat2.toFixed(4)}, ${lon2.toFixed(4)}<br>
                   - Elevasi tanah: ${ground2.toFixed(0)} m<br>
                   - Tinggi tower: ${height2} m<br>
                   - <b>Total tinggi:</b> ${totalHeight2.toFixed(0)} m
                </p>
            </div>

            <div class="result-column">
                <h5>⚙️ Parameter Teknis</h5>
                <p><b>Frekuensi:</b> ${frequency} GHz</p>
                <p><b>K-Factor:</b> ${kFactor}</p>
                <p><b>Sudut elevasi:</b> ${angle.toFixed(2)}°</p>
                <p><b>Clearance Fresnel:</b> ${clearancePercent}</p>

                <h5>📈 Status LOS</h5>
                <div class="status-indicator ${statusClass}">
                    <b>${losResult.status === "CLEAR" ? "✅ CLEAR" : "❌ BLOCKED"}</b>
                </div>

                <p class="recommendation">
                    ${
                        losResult.status === "CLEAR"
                            ? "Link feasible! Sinyal dapat terhubung dengan baik."
                            : "Link terhalang! Perlu menaikkan tinggi tower atau pindah lokasi."
                    }
                </p>
            </div>
        </div>

        <div class="note">
            <small>
                <b>Catatan:</b> Garis merah pada grafik menunjukkan garis pandang (LOS)
                yang sudah termasuk tinggi tower. Fresnel zone (area hijau) membutuhkan
                minimal 60% clearance dari ground + earth bulge.
            </small>
        </div>
    </div>
    `;
}

// Export fungsi ke global scope
window.performCalculation = performCalculation;