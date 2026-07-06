<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($heading) ? trim((string) $heading).' · Aleph∞One' : 'Aleph∞One' }}</title>

    @vite(['resources/js/app.js'])

    <link rel="icon" type="image/png" href="/images/aleph-one-favicon.png">
    <link rel="apple-touch-icon" href="/images/aleph-one-favicon.png">
    <meta name="theme-color" content="#0d2b45">

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>

    <!-- Inter (brand font) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Brand font everywhere + drop the default link/button underline (explicit Tailwind `underline` still wins) -->
    <style>
        body, button, input, select, textarea, .btn {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
        }
        a, a:hover, a:focus { text-decoration: none; }
    </style>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />

    <script src="https://cdnjs.cloudflare.com/ajax/libs/shapefile/0.6.10/shapefile.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.shapefile/1.1.0/leaflet.shpfile.min.js"></script>

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Selectize CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/selectize@0.12.6/dist/css/selectize.default.css">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.7/css/jquery.dataTables.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- Sweet Alert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Swiper.js CSS -->
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />

    @stack('styles') <!-- Allows additional styles for specific pages -->


    @livewireStyles
</head>

<body class="h-full flex flex-col bg-gray-100">
    <main class="flex items-center justify-center min-h-screen">
        <div class="w-full max-w-md mx-auto py-4 px-4 sm:px-6 lg:px-8">
            {{ $slot }}
        </div>
    </main>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/selectize@0.12.6/dist/js/standalone/selectize.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
    <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>

    @livewireScripts

    <script>
        // Global SweetAlert listener for Livewire browser events.
        // (Placed in the base layout so it works even for dynamically swapped Livewire views.)
        if (!window.__swalListenerInstalled) {
            window.__swalListenerInstalled = true;
            document.addEventListener('swal', function (event) {
                if (typeof Swal === 'undefined') {
                    return;
                }
                const detail = event.detail || {};
                const payload = Array.isArray(detail) ? (detail[0] || {}) : detail;
                Swal.fire({
                    icon: payload.icon || 'success',
                    title: payload.title || 'Success',
                    text: payload.text || '',
                });
            });
        }
    </script>

    @stack('scripts') <!-- Allows additional scripts for specific pages -->

    @stack('scripts')
</body>

</html>
