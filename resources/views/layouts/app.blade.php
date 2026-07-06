<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.googleapis.com/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Tippy.js for tooltips -->
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/dist/tippy.css" />
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>

    <!-- Styles -->
    @livewireStyles
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        @include('layouts.navigation')

        <!-- Page Heading -->
        @if (isset($header))
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>

    @stack('modals')

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

    <!-- Initialize Tippy.js tooltips -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips for all elements with data-tooltip attribute
            tippy('[data-tooltip]', {
                content: (reference) => {
                    const tooltip = reference.getAttribute('data-tooltip');
                    return tooltip.charAt(0).toUpperCase() + tooltip.slice(1).replace('_', ' ');
                },
                placement: (reference) => {
                    return reference.getAttribute('data-tooltip-position') || 'top';
                },
                theme: 'light-border',
                animation: 'shift-away',
                delay: [100, 200],
            });
        });
    </script>

    @stack('scripts')
</body>
</html>