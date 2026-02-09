<div class="sidebar p-3" id="sidebar" style="width: 250px; transition: all 0.3s;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="m-0">
            <a href="#" class="text-decoration-none" style="color: #104c91;">
            <i class="fas fa-broadcast-tower me-2"></i>P2P
        </h3>
        <button class="btn d-lg-none" id="sidebarClose" style="color: #104c91;">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <ul class="nav flex-column">

        <li>
            <a href="{{ route('data-tower.index') }}"
                class="nav-link {{ request()->routeIs('data-tower.index') ? 'active bg-#f0ae88' : '' }}">
                <i class="fas fa-database me-2"></i>Data Tower
            </a>
        </li>

        <li>
            <a href="{{ route('data-antenna.index') }}"
                class="nav-link {{ request()->routeIs('data-antenna.index') ? 'active bg-#f0ae88' : '' }}">
                <i class="fas fa-satellite-dish me-2"></i>Data Antenna
            </a>
        </li>

        <!-- <li>
            <a href="{{ route('peta-sebaran.index') }}"
                class="nav-link {{ request()->routeIs('peta-sebaran.index') ? 'active bg-#f0ae88' : '' }}">
                <i class="fas fa-map me-2"></i>Peta Sebaran
            </a>
        </li> -->
    </ul>
</div>


<script>
    document.getElementById('sidebarClose').addEventListener('click', function() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.remove('active');
    });
</script>