<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>{{$title}}</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <script src="https://cdn.tailwindcss.com"></script>
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Fugaz+One&family=Poppins:wght@400;500;700&family=Roboto:wght@400;500;700&display=swap"
        rel="stylesheet">
    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="{{asset('front-end/lib/animate/animate.min.css')}}" rel="stylesheet">
    <link href="{{asset('front-end/lib/owlcarousel/assets/owl.carousel.min.css')}}" rel="stylesheet">


    <!-- Customized Bootstrap Stylesheet -->
    <link href="{{asset('front-end/css/bootstrap.min.css')}}" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="{{asset('front-end/css/style.css')}}" rel="stylesheet">

    <!-- Link ke Font Awesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">


</head>

<body>
    <!-- Spinner Start -->
    <!-- <div id="spinner"
        class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div> -->
    <!-- Spinner End -->
    <!-- Navbar Start -->
    <nav class="navbar navbar-expand-lg px-5 py-3 fixed-navbar">


        <a href="{{route('home.index')}}" class="navbar-brand d-flex align-items-center">
            <h1 class="display-5 mb-0 logo-text">P2PWeb</h1>
        </a>

        <!-- Toggler -->
        <button class="navbar-toggler custom-toggler" type="button" onclick="toggleNav()">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Menu -->
        <div class="navbar-collapse" id="navbarMenu">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item">
                    <a href="{{route('home')}}" class="nav-link {{ request()->routeIs('home.index') ? 'active' : '' }}">Home</a>
                </li>
                <li class="nav-item">
                    <a href="{{route('informasi')}}" class="nav-link {{ request()->routeIs('informasi.index') ? 'active' : '' }}">Information</a>
                </li>
                <li class="nav-item">
                    <a href="{{route('hitung')}}" class="nav-link {{ request()->routeIs('hitung.index') ? 'active' : '' }}">Map & Calculator</a>
                </li>
            </ul>

            <form class="form-inline my-2 my-lg-0">
                <div class="d-flex align-items-center ml-3 mt-lg-0">
                    @if(Auth::guard('pelanggan')->check())
                    <select id="pelangganActions" class="nav-select">
                        <option value="">Hi, {{ Auth::guard('pelanggan')->user()->nama_pelanggan }} ▾</option>
                        <option value="new">New Project</option>
                        <option value="save">Save</option>
                        <option value="logout">Logout</option>
                    </select>
                    @else
                    <a class="btn-login" href="{{ route('user-pelanggan.login') }}">Login</a>
                    <a class="btn-register ms-2" href="{{ route('user-pelanggan.register') }}">Register</a>
                    @endif

                </div>
            </form>
        </div>
    </nav>



    <!-- Main Content Wrapper -->
    <div id="main-content">
        <!-- konten halaman home -->
        @if($title ==='Home')
        @yield('banner')
        @yield('about')
        @endif

        <!-- konten halaman lain -->
        @if($title === 'Information')
        @yield('informasi')
        @elseif($title === 'Hitung')
        @yield('hitung')
        @elseif($title === 'Dashboard')
        @yield('dashboard')
        @elseif($title === 'Map Khusus Pelanggan')
        @yield('map-pelanggan')
        @elseif($title === 'Input Lokasi')
        @yield('input-lokasi')
        @endif
    </div>

    <form id="logout-form" action="{{ route('user-pelanggan.logout') }}" method="POST" style="display:none;">
        @csrf
    </form>


    <!-- Template Javascript -->
    <script src="{{asset('front-end/js/main.js')}}"></script>
    <!-- JavaScript Libraries -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{asset('front-end/lib/wow/wow.min.js')}}"></script>
    <script src="{{asset('front-end/lib/owlcarousel/owl.carousel.min.js')}}"></script>


    <script>
        function toggleNav() {
            var navbarMenu = document.getElementById("navbarMenu");
            navbarMenu.classList.toggle("show");
        }
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const navLinks = document.querySelectorAll(".navbar-expand-lg .nav-link");

            // Ambil active link dari localStorage
            const activeLink = localStorage.getItem("activeNavLink");

            // Jika ada active tersimpan → tandai
            if (activeLink) {
                navLinks.forEach(link => {
                    if (link.href === activeLink) {
                        link.classList.add("active");
                    }
                });
            }

            // Tambahkan event klik
            navLinks.forEach(link => {
                link.addEventListener("click", function() {
                    navLinks.forEach(l => l.classList.remove("active"));
                    this.classList.add("active");
                    localStorage.setItem("activeNavLink", this.href);
                });
            });

            // Handle select actions for pelanggan (New / Save / Logout)
            var sel = document.getElementById('pelangganActions');
            if (sel) {
                sel.addEventListener('change', function() {
                    var val = this.value;
                    if (!val) return;
                    if (val === 'new') {
                        document.dispatchEvent(new CustomEvent('pelanggan:new-project'));
                    } else if (val === 'save') {
                        document.dispatchEvent(new CustomEvent('pelanggan:save-project'));
                    } else if (val === 'logout') {
                        document.getElementById('logout-form').submit();
                    }

                    this.value = '';
                });
            }
        });
    </script>


</body>

</html>