<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{$title}}</title>

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Fugaz+One&family=Poppins:wght@400;500;700&family=Roboto:wght@400;500;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="{{asset('back-end/css/style.css')}}">
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/favicon.png') }}" type="image/png">

</head>

<body>
    @yield('sidebar')

    <!-- content start -->
    <div class="content">
        @yield('navbar')
        @yield('content')
        <!-- content end -->

        <!-- Bootstrap Bundle with Popper -->
        <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>

        <!-- Font Awesome JS -->
        <script src="{{ asset('js/all.min.js') }}"></script>

        <!-- Custom JS -->
        <script src="{{ asset('js/custom.js') }}"></script>
        <script>
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('toggleSidebar');

            if (toggleBtn) {
                toggleBtn.addEventListener('click', () => {
                    sidebar.classList.toggle('active');
                });
            }
        </script>


    </div>
</body>

</html>