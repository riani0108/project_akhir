<!-- INFORMATION SECTION -->
<section id="information-section" class="py-5 p2p-bg" data-aos="fade-up">
    <div class="container">

        <!-- SEARCH & FILTER -->
        <div class="mb-5 text-center">
            <h1 class="fw-bold text-white mb-3">Belajar Konsep P2P & BTS</h1>
            <p class="text-light mb-4">Cari topik yang ingin kamu pahami, lalu jelajahi kartu informasi interaktif di bawah.</p>
            <input type="text"
                id="searchInput"
                class="form-control form-control-lg shadow-sm rounded-pill w-75 mx-auto"
                placeholder="Cari topik (LOS, Fresnel, BTS, Haversine, Tower, Antena...)"
                onkeyup="searchInfo()">
        </div>

        <!-- HERO -->
        <div class="row align-items-center mb-5 g-5">
            <div class="col-lg-6" data-aos="fade-right">
                <span class="badge bg-light text-dark mb-2 px-3 py-2 rounded-pill">Dasar Jaringan Seluler</span>
                <h2 class="fw-bold text-white mb-4">Apa itu Base Transceiver Station (BTS)?</h2>
                <p class="fs-5 lh-lg text-light">
                    <strong>BTS</strong> adalah perangkat utama dalam jaringan telekomunikasi yang menghubungkan perangkat pengguna ke jaringan operator.
                </p>
                <p class="fs-5 lh-lg text-light">
                    Dalam sistem <strong>Point-to-Point</strong>, tower BTS sering digunakan sebagai lokasi radio link backbone.
                </p>
            </div>
            <div class="col-lg-6 text-center" data-aos="fade-left">
                <div class="tower-hero-card shadow-lg rounded-4 overflow-hidden">
                    <img src="front-end/img/tower1.jpg"
                        class="img-fluid w-100 h-100 object-fit-cover"
                        alt="Tower BTS">
                </div>
            </div>
        </div>

        <!-- TITLE CAROUSEL -->
        <div class="text-center mb-4">
            <h3 class="fw-bold text-white mb-2">Konsep Penting dalam P2P & BTS</h3>
            <p class="text-light mb-0">Swipe / klik panah untuk jelajahi kartu penjelasan.</p>
        </div>

        <!-- CAROUSEL -->
        <div id="infoCarousel" class="carousel slide mb-5" data-bs-ride="carousel">
            <div class="carousel-inner" id="infoList"></div>

            <button class="carousel-control-prev" type="button" data-bs-target="#infoCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#infoCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>

            <!-- indikator -->
            <div class="carousel-indicators position-static mt-4">
                <!-- akan diisi dinamis -->
            </div>
        </div>
    </div>
</section>

<!-- MODAL DETAIL -->
<div class="modal fade" id="infoModal" tabindex="-1" aria-labelledby="infoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="modalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <img id="modalImage" src="" alt="" class="img-fluid rounded-3 mb-3 d-none">
                <p id="modalText" class="fs-5 lh-lg"></p>
            </div>

        </div>
    </div>
</div>

<script>
    const infoItems = [{
            icon: "fa-map-marker-alt",
            title: "Point-to-Point (P2P)",
            text: "Koneksi Point to Point (P2P) adalah salah satu metode komunikasi yang digunakan dalam jaringan komputer untuk menghubungkan dua titik secara langsung. Dalam konteks ini, “titik” dapat berupa perangkat seperti komputer, router, atau server. Koneksi ini memungkinkan transfer data yang lebih cepat dan efisien, serta sering digunakan dalam berbagai aplikasi, mulai dari telekomunikasi hingga jaringan lokal.",
            category: "Teknologi",
            image: "front-end/img/ptp.jpg"
        },
        {
            icon: "fa-map-marker-alt",
            title: "Point-to-Multi-Point (PtMP)",
            text: "Koneksi Point to Multi (PtMP) adalah teknologi yang menghubungkan satu akses titik dengan banyak klien.",
            category: "Teknologi",
            image: "front-end/img/ptmp.jpg"
        },
        {
            icon: "fa-eye",
            title: "Line of Sight (LOS)",
            text: "LOS adalah jalur pandang langsung antara dua antena tanpa halangan seperti gedung atau pohon.",
            category: "Konsep",
            image: "front-end/img/los.jpg"
        },
        {
            icon: "fa-broadcast-tower",
            title: "Fresnel Zone",
            text: "Zona Fresnel adalah area elips di sekitar jalur sinyal yang harus bebas hambatan minimal 60% agar link stabil.",
            category: "Konsep",
            image: "front-end/img/fresnel-zone.png"
        },
        {
            icon: "fa-globe",
            title: "Earth Curvature",
            text: "Earth curvature adalah efek kelengkungan permukaan bumi yang membuat permukaan tanah “naik” terhadap garis lurus antara dua antena. Akibatnya,meskipun Line of Sight(LOS) kelihatan bebas di peta datar, di dunia nyata sinyal bisa terhalang bumi sendiri.",
            category: "Konsep"
        },
        {
            icon: "fa-ruler-combined",
            title: "Haversine Distance",
            text: "Rumus Haversine digunakan untuk menghitung jarak antar koordinat di permukaan bumi.",
            category: "Perhitungan"
        },
        {
            icon: "fa-angle-up",
            title: "Elevation Angle",
            text: "Sudut elevasi antena untuk menentukan arah pancaran sinyal agar tepat menuju antena penerima.",
            category: "Perhitungan"
        },
        {
            icon: "fa-tower-observation",
            title: "Tower Monopole",
            text: "Tower monopole adalah struktur tunggal yang sederhana dan estetis, sering digunakan di area perkotaan yang ruang menara BTS nya terbatas.",
            category: "Tower",
            image: "front-end/img/monopole.jpg"
        },
        {
            icon: "fa-tower-observation",
            title: "Tower Guyed",
            text: "Tower guyed didukung oleh kabel penyangga untuk stabilitas, cocok untuk ketinggian tinggi dengan biaya yang lebih rendah dibandingkan self-supporting.",
            category: "Tower",
            image: "front-end/img/tower-guyed.jpg"
        },
        {
            icon: "fa-satellite-dish",
            title: "Antena Omnidirectional",
            text: "Antena omnidirectional memancarkan sinyal ke semua arah secara merata, cocok untuk cakupan luas seperti hotspot WiFi atau BTS kecil.",
            category: "Antena",
            image: "front-end/img/omni.jpg"
        },
        {
            icon: "fa-satellite-dish",
            title: "Antena Directional",
            text: "Antena directional fokus pada arah tertentu, seperti Grid atau Yagi, digunakan untuk link point-to-point dengan jangkauan jauh dan efisiensi tinggi.",
            category: "Antena",
            image: "front-end/img/directional.jpg"
        },
        {
            icon: "fa-satellite-dish",
            title: "Antena Sector",
            text: "Antena sector menutupi sektor tertentu (misalnya 120 derajat), umum digunakan di BTS untuk membagi area menjadi sel-sel kecil dalam jaringan seluler.",
            category: "Antena",
            image: "front-end/img/sector.jpg"
        }
    ];

    function initCarousel(filteredItems = infoItems) {
        const container = document.getElementById("infoList");
        const indicators = document.querySelector("#infoCarousel .carousel-indicators");
        container.innerHTML = "";
        indicators.innerHTML = "";

        const screen = window.innerWidth;
        const chunkSize = screen >= 992 ? 3 : screen >= 768 ? 2 : 1;
        const totalSlides = Math.ceil(filteredItems.length / chunkSize);

        for (let i = 0; i < filteredItems.length; i += chunkSize) {
            const group = filteredItems.slice(i, i + chunkSize);

            const slideHtml = `
      <div class="carousel-item ${i === 0 ? "active" : ""}">
        <div class="row g-4 justify-content-center">
          ${group
            .map(item => {
              const title   = (item.title || "").replace(/'/g, "\\'");
              const text    = (item.text || "").replace(/'/g, "\\'");
              const image   = (item.image || "").replace(/'/g, "\\'");
              const excerpt = item.text && item.text.length > 110
                ? item.text.substring(0, 110) + "..."
                : item.text || "";

              return `
                <div class="col-lg-4 col-md-6">
                  <div class="p2p-card"
                       onclick="openDetail('${title}','${text}','${image}')">
                    <span class="badge">${item.category || ""}</span>
                    <div class="card-icon"><i class="fas ${item.icon || ""}"></i></div>
                    <h4>${item.title || ""}</h4>
                    <p>${excerpt}</p>
                    <small class="fw-semibold">Klik untuk selengkapnya →</small>
                  </div>
                </div>
              `;
            })
            .join("")}
        </div>
      </div>
    `;

            container.innerHTML += slideHtml;
        }

        for (let s = 0; s < totalSlides; s++) {
            indicators.innerHTML += `
      <button type="button"
              data-bs-target="#infoCarousel"
              data-bs-slide-to="${s}"
              ${s === 0 ? 'class="active" aria-current="true"' : ""}
              aria-label="Slide ${s + 1}">
      </button>`;
        }
    }


    function openDetail(title, text, image) {
        document.getElementById("modalTitle").innerText = title;
        document.getElementById("modalText").innerText = text;

        const imgEl = document.getElementById("modalImage");

        if (image) {
            imgEl.src = image;
            imgEl.alt = title;
            imgEl.classList.remove("d-none");
        } else {
            imgEl.classList.add("d-none");
            imgEl.removeAttribute("src");
        }

        new bootstrap.Modal(document.getElementById("infoModal")).show();
    }


    function searchInfo() {
        const keyword = document.getElementById("searchInput").value.toLowerCase();
        const filtered = infoItems.filter(item =>
            item.title.toLowerCase().includes(keyword) ||
            item.text.toLowerCase().includes(keyword) ||
            item.category.toLowerCase().includes(keyword)
        );
        initCarousel(filtered);
    }

    window.addEventListener("resize", () => initCarousel());
    document.addEventListener("DOMContentLoaded", () => {
        initCarousel();
        AOS.init(); // pastikan AOS JS & CSS sudah disertakan
    });
</script>