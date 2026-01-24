<section class="hero">
    <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="2000">
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img src="../front-end/img/tower1.jpg" class="d-block w-100" alt="tower">
            </div>
            <div class="carousel-item">
                <img src="../img/tower2.jpg" class="d-block w-100" alt="tower2">
            </div>
            <div class="carousel-item">
                <img src="../img/tower3.jpg" class="d-block w-100" alt="tower3">
            </div>
        </div>
    </div>

    <div class="hero-content">
        <h1>Welcome To P2PWeb</h1>
        <p>Join us to learn</p>
        <a href="#about-section" class="btn nav-link {{ request()->routeIs('about.index') ? 'active' : '' }}">Get Started</a>
    </div>
</section>