<nav class="navbar navbar-expand-lg navbar-dark px-4 d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center">
        <button class="btn d-lg-none me-2" id="toggleSidebar">
            <i class="fas fa-bars"></i>
        </button>
        <h5 id="pageTitle" class="mb-0">{{$title}}</h5>
    </div>

    <div class="d-flex align-items-center">
        <input type="text" class="form-control form-control-sm me-3" placeholder="Cari tower...">
        <i class="fas fa-bell text-light me-3"></i>
        <img src="https://via.placeholder.com/35" alt="User" class="rounded-circle" width="35" height="35">
    </div>
</nav>

{{-- JS: sinkron judul navbar dengan link sidebar --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        function setTitleFromActive() {
            var active = document.querySelector('.sidebar .nav-link.active');
            var titleEl = document.getElementById('pageTitle');
            if (!titleEl) return;
            if (active) {
                titleEl.textContent = active.textContent.trim();
            } else {
                titleEl.textContent = document.title ? document.title : '{{$title}}';
            }
        }

        // Set awal saat load (berguna saat server-side render memberi kelas .active)
        setTitleFromActive();

        // Jika user klik link sidebar tanpa reload (atau sebelum navigasi), update judul segera
        document.querySelectorAll('.sidebar .nav-link').forEach(function(link) {
            link.addEventListener('click', function(e) {
                // update kelas active visual (opsional, jika ingin langsung terlihat)
                document.querySelectorAll('.sidebar .nav-link').forEach(function(l) {
                    l.classList.remove('active');
                });
                this.classList.add('active');
                var titleEl = document.getElementById('pageTitle');
                if (titleEl) titleEl.textContent = this.textContent.trim();
            });
        });

        // Jika situs memakai pushState/SPA, developer bisa panggil setTitleFromActive() setelah route change.
    });
</script>