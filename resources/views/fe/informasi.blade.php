<div id="information-section" class="py-5 p2p-bg">
    <div class="container">

        <div class="mb-4">
            <input
                type="text"
                id="searchInput"
                class="form-control form-control-lg p2p-search"
                placeholder="🔍 Cari topik (LOS, Fresnel, Haversine...)"
                onkeyup="searchInfo()" />
        </div>

        <!-- ===== BTS SECTION ===== -->
        <div class="container-fluid bts-section py-5">
            <div class="container">

                <div class="row align-items-center">

                    <!-- ===== KIRI : TEXT ===== -->
                    <div class="col-lg-6 text-white wow fadeInLeft" data-wow-delay="0.2s">
                        <h2 class="fw-bold mb-4 bts-title">
                            Apa itu Base Transceiver Station (BTS)?
                        </h2>

                        <p>
                            <strong>Base Transceiver Station (BTS)</strong> adalah perangkat utama dalam jaringan
                            telekomunikasi yang berfungsi sebagai penghubung antara perangkat pengguna (UE)
                            dan jaringan inti operator.  BTS memancarkan dan menerima sinyal radio melalui antena yang terpasang pada tower.
                        </p><br>
                        <p>
                            Dalam sistem <strong>Point-to-Point (P2P)</strong>, tower BTS sering digunakan
                            sebagai lokasi pemasangan radio link untuk backbone, uplink,
                            atau interkoneksi antar site.
                        </p>
                    </div>

                    <!-- ===== KANAN : IMAGE ===== -->
                    <div class="col-lg-6 text-center wow fadeInRight" data-wow-delay="0.3s">
                        <img
                            src="front-end/img/tower1.jpg"
                            class="img-fluid bts-image"
                            alt="Tower BTS">
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== CONTENT ===== -->
        <div id="infoList" class="row g-4">

            <div class="col-md-6 info-card">
                <div class="p2p-card">
                    <h4>📍 Apa itu Point-to-Point?</h4>
                    <p>
                        Teknologi wireless untuk menghubungkan dua titik menggunakan antena terarah,
                        umum digunakan pada backbone ISP dan interkoneksi tower.
                    </p>
                </div>
            </div>

            <div class="col-md-6 info-card">
                <div class="p2p-card">
                    <h4>📐 Line of Sight (LOS)</h4>
                    <p>
                        Jalur pandang bebas antara dua tower.
                        LOS wajib terpenuhi sebelum memperhitungkan Fresnel Zone.
                    </p>
                </div>
            </div>

            <div class="col-md-6 info-card">
                <div class="p2p-card">
                    <h4>🟢 Fresnel Zone</h4>
                    <p>
                        Area elips di sekitar jalur sinyal.
                        Minimal 60% harus bersih untuk performa optimal.
                    </p>
                </div>
            </div>

            <div class="col-md-6 info-card">
                <div class="p2p-card">
                    <h4>🌍 Earth Curvature</h4>
                    <p>
                        Kelengkungan bumi dapat menjadi penghalang pada link jarak jauh,
                        terutama di atas 10 km.
                    </p>
                </div>
            </div>

            <div class="col-md-6 info-card">
                <div class="p2p-card">
                    <h4>📏 Haversine Distance</h4>
                    <p>
                        Metode akurat untuk menghitung jarak antar koordinat
                        dengan mempertimbangkan bentuk bumi.
                    </p>
                </div>
            </div>

            <div class="col-md-6 info-card">
                <div class="p2p-card">
                    <h4>🎯 Elevation Angle</h4>
                    <p>
                        Sudut kemiringan antena agar pointing tepat
                        ke tower tujuan.
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    function searchInfo() {
        const keyword = document.getElementById("searchInput").value.toLowerCase();
        const cards = document.querySelectorAll(".info-card");

        cards.forEach(card => {
            const text = card.innerText.toLowerCase();
            card.style.display = text.includes(keyword) ? "block" : "none";
        });
    }
</script>