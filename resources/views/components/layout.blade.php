<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $pageLabel = request()->segment(1) ? \Illuminate\Support\Str::headline(request()->segment(1)) : null;
    @endphp
    <title>{{ $pageLabel ? $pageLabel.' · Aleph∞One' : 'Aleph∞One' }}</title>

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
        /* When a dropdown opens on a section header, give just that header top breathing
           room — keeps menus balanced without over-padding item-first menus (e.g. the user menu). */
        [role="menu"] > div:first-child { padding-top: 0.5rem !important; }
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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- Sweet Alert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Swiper.js CSS -->
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />

    <style>
        /* Ensure column filter inputs in list/table headers are wide enough to
           show the entered text in full across every list and modal table. */
        table thead input[type="text"],
        table thead input[type="search"],
        table thead input:not([type]) {
            min-width: 6rem;
        }

        .index-data-table {
            font-size: 0.75rem;
            line-height: 1.1rem;
            width: 100%;
            border-collapse: collapse;
        }

        .index-data-table thead th,
        .index-data-table tbody td {
            padding: 0.25rem 0.5rem !important;
            font-size: 0.75rem !important;
            line-height: 1.1rem !important;
            white-space: nowrap;
            vertical-align: middle;
            border-right: 1px solid rgb(229 231 235);
        }

        .index-data-table thead th:last-child,
        .index-data-table tbody td:last-child {
            border-right: none;
        }

        .index-data-table .index-sticky-cell {
            position: sticky;
            background-color: #fff;
        }

        .index-data-table thead .index-sticky-cell {
            background-color: rgb(249 250 251);
        }

        .index-data-table tbody tr:hover .index-sticky-cell {
            background-color: rgb(249 250 251);
        }

        .index-data-table .index-sticky-cell-last {
            box-shadow: 4px 0 8px -2px rgba(15, 23, 42, 0.12);
        }

        .index-data-table td.index-people-cell,
        .index-data-table th.index-people-cell,
        .index-data-table td:has(> .flex a[href*="/profile/"]),
        .index-data-table td:has(> div.flex a[href*="/profile/"]) {
            min-width: 9rem;
            max-width: 12rem;
            width: 12rem;
            overflow: hidden;
        }

        .index-data-table td.index-people-cell > .flex,
        .index-data-table td.index-people-cell > div,
        .index-data-table td:has(> .flex a[href*="/profile/"]) > .flex,
        .index-data-table td:has(> div.flex a[href*="/profile/"]) > div {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            min-width: 0;
            max-width: 100%;
            overflow: hidden;
        }

        .index-data-table td.index-people-cell a[href*="/profile/"],
        .index-data-table td:has(> .flex a[href*="/profile/"]) a[href*="/profile/"],
        .index-data-table td:has(> div.flex a[href*="/profile/"]) a[href*="/profile/"] {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            min-width: 0;
            flex: 1 1 0%;
        }

        .index-data-table td.index-people-cell img,
        .index-data-table td.index-people-cell .rounded-full,
        .index-data-table td:has(> .flex a[href*="/profile/"]) img {
            flex-shrink: 0;
        }

        .index-data-table > thead:first-of-type th,
        .index-data-table thead tr:first-child th {
            font-size: 0.625rem !important;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase !important;
        }

        .index-data-table > thead:first-of-type th button,
        .index-data-table > thead:first-of-type th button span,
        .index-data-table > thead:first-of-type th a,
        .index-data-table thead tr:first-child th button,
        .index-data-table thead tr:first-child th button span,
        .index-data-table thead tr:first-child th a {
            text-transform: uppercase !important;
        }

        .index-data-table tbody tr:hover {
            background-color: rgb(249 250 251);
        }

        .index-data-table input[type="text"],
        .index-data-table input[type="date"],
        .index-data-table input[type="number"],
        .index-data-table input[type="search"],
        .index-data-table select {
            width: 100%;
            min-width: 5rem;
            padding: 0.15rem 0.35rem !important;
            font-size: 0.6875rem !important;
            line-height: 1rem !important;
        }
    </style>

    @stack('styles') <!-- Allows additional styles for specific pages -->


    @livewireStyles
</head>

<body class="h-full flex flex-col bg-gray-100">
    @php
        $selectedProjectId = (int) session('selected_project_id', 0);
        $currentSelectedProject = null;

        if ($selectedProjectId > 0) {
            $currentSelectedProject = auth()->user()?->people?->projects()
                ->select('projects.id', 'projects.code', 'projects.title')
                ->where('projects.id', $selectedProjectId)
                ->first();

            if (! $currentSelectedProject) {
                $currentSelectedProject = \App\Models\Projects::query()
                    ->select('id', 'code', 'title')
                    ->find($selectedProjectId);
            }
        }

        $currentProjectChipUrl = $currentSelectedProject
            ? route('projects.profile', $currentSelectedProject->code)
            : route('profile.projects');
    @endphp
    <nav class="bg-black" ..>
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="/" class="flex items-center gap-2 leading-none">
                            <img class="h-9 w-auto rounded-md bg-white p-1" src="/images/aleph-one-logo.png" alt="">
                            <span class="text-lg font-bold tracking-tight text-white whitespace-nowrap">Aleph<span style="color:#008E9A">∞</span>One</span>
                        </a>
                    </div>
                    <div class="hidden md:block">
                        <div class="ml-10 flex items-baseline space-x-4">
                            <!-- Current: "bg-gray-900 text-white", Default: "text-gray-300 hover:bg-gray-700 hover:text-white" -->
                            <x-nav-link href="/" :active="request()->is('/')">Home</x-nav-link>
                            <!-- Samples Dropdown -->
                            <div class="relative group">
                                <x-nav-link href="/samples" :active="request()->is('samples*')">Samples</x-nav-link>
                                <div
                                    class="absolute left-0 mt-1 w-64 rounded-xl shadow-2xl bg-gradient-to-br from-white to-gray-50 ring-1 ring-gray-200 hidden group-hover:block z-50 border border-gray-100">
                                    <div class="py-2" role="menu">
                                        @if (session()->has('selected_project_id'))
                                            <!-- Samples Home -->
                                            <a href="/samples"
                                                class="flex items-center px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 transition-all duration-200 border-l-4 border-transparent hover:border-blue-400"
                                                role="menuitem">
                                                <i class="fas fa-home text-blue-500 w-5"></i>
                                                <span class="ml-3">Samples Home</span>
                                            </a>

                                            <!-- Line Separator -->
                                            <div
                                                class="mx-4 my-2 h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent">
                                            </div>
                                        @endif

                                        <!-- Field Samples Section -->
                                        @php
                                            $user = Auth::user();
                                            $project = null;
                                            $selectedProjectId = (int) session('selected_project_id');
                                            $moduleAccess = [];
                                            $canViewHumanSamples = true;
                                            $canViewAnimalSamples = true;
                                            $canViewEnvironmentSamples = true;
                                            $canViewParasiteSamples = true;
                                            $canViewNucleicAcids = true;
                                            $canViewMicroplastics = true;
                                            $canViewCultures = true;
                                            $canViewPools = true;
                                            $canViewExperiments = true;
                                            $canViewTubes = true;
                                            $canViewTubePositions = true;
                                            $canViewBoxPositions = true;
                                            $canViewLiterature = true;
                                            $canRegisterHumanSamples = false;
                                            $canRegisterAnimalSamples = false;
                                            $canRegisterAnimalData = false;
                                            $canRegisterEnvironmentSamples = false;
                                            $canRegisterParasiteSamples = false;
                                            $canRegisterNucleicAcids = false;
                                            $canRegisterMicroplastics = false;
                                            $canRegisterCultures = false;
                                            $canRegisterPools = false;
                                            $canRegisterExperiments = false;
                                            $canRegisterTubes = false;
                                            $canRegisterTubePositions = false;
                                            $canRegisterBoxPositions = false;
                                            $canRegisterLiterature = false;

                                            if ($user && $user->people && $selectedProjectId > 0) {
                                                $project = $user->people
                                                    ->projects()
                                                    ->where('projects.id', $selectedProjectId)
                                                    ->withPivot('role', 'date_joined', 'permission')
                                                    ->first();

                                                if ($project && $project->pivot) {
                                                    $moduleAccess = \App\Support\ProjectPermission::moduleAccessFlags($user, $selectedProjectId);
                                                    $canViewHumanSamples = ($moduleAccess['human_samples']['view'] ?? false) || ($moduleAccess['human_samples']['edit'] ?? false);
                                                    $canViewAnimalSamples = ($moduleAccess['animal_samples']['view'] ?? false) || ($moduleAccess['animal_samples']['edit'] ?? false);
                                                    $canViewEnvironmentSamples = ($moduleAccess['environment_samples']['view'] ?? false) || ($moduleAccess['environment_samples']['edit'] ?? false);
                                                    $canViewParasiteSamples = ($moduleAccess['parasite_samples']['view'] ?? false) || ($moduleAccess['parasite_samples']['edit'] ?? false);
                                                    $canViewNucleicAcids = ($moduleAccess['nucleic_acids']['view'] ?? false) || ($moduleAccess['nucleic_acids']['edit'] ?? false);
                                                    $canViewMicroplastics = ($moduleAccess['microplastics']['view'] ?? false) || ($moduleAccess['microplastics']['edit'] ?? false);
                                                    $canViewCultures = ($moduleAccess['cultures']['view'] ?? false) || ($moduleAccess['cultures']['edit'] ?? false);
                                                    $canViewPools = ($moduleAccess['pools']['view'] ?? false) || ($moduleAccess['pools']['edit'] ?? false);
                                                    $canViewExperiments = ($moduleAccess['experiments']['view'] ?? false) || ($moduleAccess['experiments']['edit'] ?? false);
                                                    $canViewTubes = ($moduleAccess['tubes']['view'] ?? false) || ($moduleAccess['tubes']['edit'] ?? false);
                                                    $canViewTubePositions = ($moduleAccess['tube_positions']['view'] ?? false) || ($moduleAccess['tube_positions']['edit'] ?? false);
                                                    $canViewBoxPositions = ($moduleAccess['box_positions']['view'] ?? false) || ($moduleAccess['box_positions']['edit'] ?? false);
                                                    $canViewLiterature = ($moduleAccess['literature']['view'] ?? false) || ($moduleAccess['literature']['edit'] ?? false);
                                                    $canRegisterHumanSamples = \App\Support\ProjectPermission::canWrite($user, $selectedProjectId, 'human_samples');
                                                    $canRegisterAnimalSamples = \App\Support\ProjectPermission::canWrite($user, $selectedProjectId, 'animal_samples');
                                                    $canRegisterAnimalData = $canRegisterAnimalSamples;
                                                    $canRegisterEnvironmentSamples = \App\Support\ProjectPermission::canWrite($user, $selectedProjectId, 'environment_samples');
                                                    $canRegisterParasiteSamples = \App\Support\ProjectPermission::canWrite($user, $selectedProjectId, 'parasite_samples');
                                                    $canRegisterNucleicAcids = \App\Support\ProjectPermission::canWrite($user, $selectedProjectId, 'nucleic_acids');
                                                    $canRegisterMicroplastics = \App\Support\ProjectPermission::canWrite($user, $selectedProjectId, 'microplastics');
                                                    $canRegisterCultures = \App\Support\ProjectPermission::canWrite($user, $selectedProjectId, 'cultures');
                                                    $canRegisterPools = \App\Support\ProjectPermission::canWrite($user, $selectedProjectId, 'pools');
                                                    $canRegisterExperiments = \App\Support\ProjectPermission::canWrite($user, $selectedProjectId, 'experiments');
                                                    $canRegisterTubes = \App\Support\ProjectPermission::canWrite($user, $selectedProjectId, 'tubes');
                                                    $canRegisterTubePositions = \App\Support\ProjectPermission::canWrite($user, $selectedProjectId, 'tube_positions');
                                                    $canRegisterBoxPositions = \App\Support\ProjectPermission::canWrite($user, $selectedProjectId, 'box_positions');
                                                    $canRegisterLiterature = \App\Support\ProjectPermission::canWrite($user, $selectedProjectId, 'literature');
                                                }
                                            }
                                        @endphp
                                        <div class="px-4 py-1">
                                            <h2
                                                class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1 flex items-center">
                                                Field Samples
                                            </h2>
                                        </div>

                                        <!-- Human Samples Submenu -->
                                        @if ($canViewHumanSamples)
                                        <div class="relative group/sub">
                                            <div
                                                class="flex items-center justify-between px-4 py-3 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-pink-50 hover:to-rose-50 transition-all duration-200 border-l-4 border-transparent hover:border-pink-400">
                                                <div class="flex items-center">
                                                    <i class="fas fa-person text-pink-500 w-5"></i>
                                                    <span class="ml-3 font-medium">Human Samples</span>
                                                </div>
                                                <svg class="w-4 h-4 ml-2 text-gray-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </div>
                                            <div
                                                class="absolute left-full top-0 w-56 rounded-xl shadow-2xl bg-gradient-to-br from-white to-gray-50 ring-1 ring-gray-200 hidden group-hover/sub:block border border-gray-100">
                                                <div class="py-2">
                                                    @if (session()->has('selected_project_id'))
                                                        <a href="/samples/humans"
                                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-pink-50 hover:to-rose-50 transition-all duration-200">
                                                            <i class="fas fa-home text-pink-400 w-4"></i>
                                                            <span class="ml-3">HS Home</span>
                                                        </a>
                                                        @if ($canRegisterHumanSamples)
                                                            <a href="/samples/humans/create"
                                                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-pink-50 hover:to-rose-50 transition-all duration-200">
                                                                <i class="fas fa-plus text-pink-400 w-4"></i>
                                                                <span class="ml-3">Registration</span>
                                                            </a>
                                                        @endif
                                                    @endif
                                                    <a href="/samples/humans/list"
                                                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-pink-50 hover:to-rose-50 transition-all duration-200">
                                                        <i class="fas fa-list text-pink-400 w-4"></i>
                                                        <span class="ml-3">List</span>
                                                    </a>
                                                    <a href="/samples/humans/dashboard"
                                                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-pink-50 hover:to-rose-50 transition-all duration-200">
                                                        <i class="fas fa-chart-bar text-pink-400 w-4"></i>
                                                        <span class="ml-3">Dashboard</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        <!-- Animal Samples Submenu -->
                                        @if ($canViewAnimalSamples)
                                        <div class="relative group/sub">
                                            <div
                                                class="flex items-center justify-between px-4 py-3 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-yellow-50 hover:to-amber-50 transition-all duration-200 border-l-4 border-transparent hover:border-yellow-400">
                                                <div class="flex items-center">
                                                    <i class="fas fa-paw text-yellow-500 w-5"></i>
                                                    <span class="ml-3 font-medium">Animal Samples</span>
                                                </div>
                                                <svg class="w-4 h-4 ml-2 text-gray-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </div>
                                            <div
                                                class="absolute left-full top-0 w-56 rounded-xl shadow-2xl bg-gradient-to-br from-white to-gray-50 ring-1 ring-gray-200 hidden group-hover/sub:block border border-gray-100">
                                                <div class="py-2">
                                                    @if (session()->has('selected_project_id'))
                                                        <a href="/samples/animals"
                                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-yellow-50 hover:to-amber-50 transition-all duration-200">
                                                            <i class="fas fa-home text-yellow-400 w-4"></i>
                                                            <span class="ml-3">AS Home</span>
                                                        </a>
                                                        @if ($canRegisterAnimalSamples)
                                                            <a href="/samples/animals/create"
                                                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-yellow-50 hover:to-amber-50 transition-all duration-200">
                                                                <i class="fas fa-plus text-yellow-400 w-4"></i>
                                                                <span class="ml-3">Registration</span>
                                                            </a>
                                                        @endif
                                                    @endif
                                                    <a href="/samples/animals/list"
                                                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-yellow-50 hover:to-amber-50 transition-all duration-200">
                                                        <i class="fas fa-list text-yellow-400 w-4"></i>
                                                        <span class="ml-3">List</span>
                                                    </a>
                                                    <a href="/samples/animals/dashboard"
                                                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-yellow-50 hover:to-amber-50 transition-all duration-200">
                                                        <i class="fas fa-chart-bar text-yellow-400 w-4"></i>
                                                        <span class="ml-3">Dashboard</span>
                                                    </a>

                                                    @if (session()->has('selected_project_id'))

                                                        <!-- Line Separator -->
                                                        <div
                                                            class="mx-4 my-2 h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent">
                                                        </div>

                                                        <!-- Health Data Submenu -->
                                                        <div class="relative group/sub2">
                                                            <div
                                                                class="flex items-center justify-between px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-red-50 hover:to-pink-50 transition-all duration-200">
                                                                <div class="flex items-center">
                                                                    <i class="fas fa-heartbeat text-red-400 w-4"></i>
                                                                    <span class="ml-3">Health Data</span>
                                                                </div>
                                                                <svg class="w-4 h-4 ml-2 text-gray-400" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M9 5l7 7-7 7" />
                                                                </svg>
                                                            </div>
                                                            <div
                                                                class="absolute left-full top-0 w-56 rounded-xl shadow-2xl bg-gradient-to-br from-white to-gray-50 ring-1 ring-gray-200 hidden group-hover/sub2:block border border-gray-100">
                                                                <div class="py-2">
                                                                    @if (session()->has('selected_project_id'))
                                                                        @if ($canRegisterAnimalData)
                                                                            <a href="/samples/animals/health/create"
                                                                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-red-50 hover:to-pink-50 transition-all duration-200">
                                                                                <i
                                                                                    class="fas fa-plus text-red-400 w-4"></i>
                                                                                <span
                                                                                    class="ml-3">Registration</span>
                                                                            </a>
                                                                        @endif
                                                                    @endif
                                                                    <a href="/samples/animals/health/list"
                                                                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-red-50 hover:to-pink-50 transition-all duration-200">
                                                                        <i class="fas fa-list text-red-400 w-4"></i>
                                                                        <span class="ml-3">List</span>
                                                                    </a>
                                                                    <a href="/samples/animals/health/dashboard"
                                                                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-red-50 hover:to-pink-50 transition-all duration-200">
                                                                        <i
                                                                            class="fas fa-chart-bar text-red-400 w-4"></i>
                                                                        <span class="ml-3">Dashboard</span>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Medication Data Submenu -->
                                                        <div class="relative group/sub2">
                                                            <div
                                                                class="flex items-center justify-between px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-purple-50 hover:to-violet-50 transition-all duration-200">
                                                                <div class="flex items-center">
                                                                    <i
                                                                        class="fas fa-prescription-bottle text-purple-400 w-4"></i>
                                                                    <span class="ml-3">Medication</span>
                                                                </div>
                                                                <svg class="w-4 h-4 ml-2 text-gray-400" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M9 5l7 7-7 7" />
                                                                </svg>
                                                            </div>
                                                            <div
                                                                class="absolute left-full top-0 w-56 rounded-xl shadow-2xl bg-gradient-to-br from-white to-gray-50 ring-1 ring-gray-200 hidden group-hover/sub2:block border border-gray-100">
                                                                <div class="py-2">
                                                                    @if (session()->has('selected_project_id'))
                                                                        @if ($canRegisterAnimalData)
                                                                            <a href="/samples/animals/medication/create"
                                                                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-purple-50 hover:to-violet-50 transition-all duration-200">
                                                                                <i
                                                                                    class="fas fa-plus text-purple-400 w-4"></i>
                                                                                <span
                                                                                    class="ml-3">Registration</span>
                                                                            </a>
                                                                        @endif
                                                                    @endif
                                                                    <a href="/samples/animals/medication/list"
                                                                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-purple-50 hover:to-violet-50 transition-all duration-200">
                                                                        <i class="fas fa-list text-purple-400 w-4"></i>
                                                                        <span class="ml-3">List</span>
                                                                    </a>
                                                                    <a href="/samples/animals/medication/dashboard"
                                                                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-purple-50 hover:to-violet-50 transition-all duration-200">
                                                                        <i
                                                                            class="fas fa-chart-bar text-purple-400 w-4"></i>
                                                                        <span class="ml-3">Dashboard</span>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Vaccination Data Submenu -->
                                                        <div class="relative group/sub2">
                                                            <div
                                                                class="flex items-center justify-between px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-green-50 hover:to-emerald-50 transition-all duration-200">
                                                                <div class="flex items-center">
                                                                    <i class="fas fa-syringe text-green-400 w-4"></i>
                                                                    <span class="ml-3">Vaccination</span>
                                                                </div>
                                                                <svg class="w-4 h-4 ml-2 text-gray-400" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" stroke-width="2"
                                                                        d="M9 5l7 7-7 7" />
                                                                </svg>
                                                            </div>
                                                            <div
                                                                class="absolute left-full top-0 w-56 rounded-xl shadow-2xl bg-gradient-to-br from-white to-gray-50 ring-1 ring-gray-200 hidden group-hover/sub2:block border border-gray-100">
                                                                <div class="py-2">
                                                                    @if (session()->has('selected_project_id'))
                                                                        @if ($canRegisterAnimalData)
                                                                            <a href="/samples/animals/vaccination/create"
                                                                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-green-50 hover:to-emerald-50 transition-all duration-200">
                                                                                <i
                                                                                    class="fas fa-plus text-green-400 w-4"></i>
                                                                                <span
                                                                                    class="ml-3">Registration</span>
                                                                            </a>
                                                                        @endif
                                                                    @endif
                                                                    <a href="/samples/animals/vaccination/list"
                                                                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-green-50 hover:to-emerald-50 transition-all duration-200">
                                                                        <i class="fas fa-list text-green-400 w-4"></i>
                                                                        <span class="ml-3">List</span>
                                                                    </a>
                                                                    <a href="/samples/animals/vaccination/dashboard"
                                                                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-green-50 hover:to-emerald-50 transition-all duration-200">
                                                                        <i
                                                                            class="fas fa-chart-bar text-green-400 w-4"></i>
                                                                        <span class="ml-3">Dashboard</span>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        <!-- Environmental Samples Submenu -->
                                        @if ($canViewEnvironmentSamples)
                                        <div class="relative group/sub">
                                            <div
                                                class="flex items-center justify-between px-4 py-3 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-green-50 hover:to-emerald-50 transition-all duration-200 border-l-4 border-transparent hover:border-green-400">
                                                <div class="flex items-center">
                                                    <i class="fas fa-seedling text-green-500 w-5"></i>
                                                    <span class="ml-3 font-medium">Environmental Samples</span>
                                                </div>
                                                <svg class="w-4 h-4 ml-2 text-gray-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </div>
                                            <div
                                                class="absolute left-full top-0 w-56 rounded-xl shadow-2xl bg-gradient-to-br from-white to-gray-50 ring-1 ring-gray-200 hidden group-hover/sub:block border border-gray-100">
                                                <div class="py-2">
                                                    @if (session()->has('selected_project_id'))
                                                        <a href="/samples/environment"
                                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-green-50 hover:to-emerald-50 transition-all duration-200">
                                                            <i class="fas fa-home text-green-400 w-4"></i>
                                                            <span class="ml-3">ES Home</span>
                                                        </a>
                                                        @if ($canRegisterEnvironmentSamples)
                                                            <a href="/samples/environment/create"
                                                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-green-50 hover:to-emerald-50 transition-all duration-200">
                                                                <i class="fas fa-plus text-green-400 w-4"></i>
                                                                <span class="ml-3">Registration</span>
                                                            </a>
                                                        @endif
                                                    @endif
                                                    <a href="/samples/environment/list"
                                                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-green-50 hover:to-emerald-50 transition-all duration-200">
                                                        <i class="fas fa-list text-green-400 w-4"></i>
                                                        <span class="ml-3">List</span>
                                                    </a>
                                                    <a href="/samples/environment/dashboard"
                                                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-green-50 hover:to-emerald-50 transition-all duration-200">
                                                        <i class="fas fa-chart-bar text-green-400 w-4"></i>
                                                        <span class="ml-3">Dashboard</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        <!-- Line Separator -->
                                        <div
                                            class="mx-4 my-2 h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent">
                                        </div>

                                        <!-- Process Samples Section -->
                                        <div class="px-4 py-1">
                                            <h2
                                                class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1 flex items-center">
                                                Process Samples
                                            </h2>
                                        </div>

                                        <!-- Parasite Samples Submenu -->
                                        @if ($canViewTubes)
                                        <div class="relative group/sub">
                                            <div
                                                class="flex items-center justify-between px-4 py-3 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-emerald-100 hover:to-teal-200 transition-all duration-200 border-l-4 border-transparent hover:border-emerald-600">
                                                <div class="flex items-center">
                                                    <i class="fas fa-vial text-emerald-500 w-5"></i>
                                                    <span class="ml-3 font-medium">Tubes</span>
                                                </div>
                                                <svg class="w-4 h-4 ml-2 text-gray-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </div>
                                            <div
                                                class="absolute left-full top-0 w-56 rounded-xl shadow-2xl bg-gradient-to-br from-white to-gray-50 ring-1 ring-gray-200 hidden group-hover/sub:block border border-gray-100">
                                                <div class="py-2">
                                                    @if (session()->has('selected_project_id'))
                                                        @if ($canRegisterTubes)
                                                            <a href="/samples/process"
                                                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-emerald-100 hover:to-teal-200 transition-all duration-200">
                                                                <i class="fas fa-cogs text-emerald-400 w-4"></i>
                                                                <span class="ml-3">Process</span>
                                                            </a>
                                                        @endif
                                                    @endif
                                                    <a href="/samples/process/list"
                                                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-emerald-100 hover:to-teal-200 transition-all duration-200">
                                                        <i class="fas fa-list text-emerald-400 w-4"></i>
                                                        <span class="ml-3">List</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        <!-- Line Separator -->
                                        <div
                                            class="mx-4 my-2 h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent">
                                        </div>

                                        <!-- Lab-derived Samples Section -->
                                        <div class="px-4 py-1">
                                            <h2
                                                class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1 flex items-center">
                                                Lab-Derived Samples
                                            </h2>
                                        </div>

                                        <!-- Parasite Samples Submenu -->
                                        @if ($canViewParasiteSamples)
                                        <div class="relative group/sub">
                                            <div
                                                class="flex items-center justify-between px-4 py-3 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-purple-50 hover:to-pink-50 transition-all duration-200 border-l-4 border-transparent hover:border-purple-400">
                                                <div class="flex items-center">
                                                    <i class="fas fa-spider text-purple-500 w-5"></i>
                                                    <span class="ml-3 font-medium">Parasite Samples</span>
                                                </div>
                                                <svg class="w-4 h-4 ml-2 text-gray-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </div>
                                            <div
                                                class="absolute left-full top-0 w-56 rounded-xl shadow-2xl bg-gradient-to-br from-white to-gray-50 ring-1 ring-gray-200 hidden group-hover/sub:block border border-gray-100">
                                                <div class="py-2">
                                                    @if (session()->has('selected_project_id'))
                                                        <a href="/samples/parasites"
                                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-purple-50 hover:to-pink-50 transition-all duration-200">
                                                            <i class="fas fa-home text-purple-400 w-4"></i>
                                                            <span class="ml-3">PS Home</span>
                                                        </a>
                                                        @if ($canRegisterParasiteSamples)
                                                            <a href="/samples/parasites/create"
                                                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-purple-50 hover:to-pink-50 transition-all duration-200">
                                                                <i class="fas fa-search text-purple-400 w-4"></i>
                                                                <span class="ml-3">Identification</span>
                                                            </a>
                                                        @endif
                                                    @endif
                                                    <a href="/samples/parasites/list"
                                                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-purple-50 hover:to-pink-50 transition-all duration-200">
                                                        <i class="fas fa-list text-purple-400 w-4"></i>
                                                        <span class="ml-3">List</span>
                                                    </a>
                                                    <a href="/samples/parasites/dashboard"
                                                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-purple-50 hover:to-pink-50 transition-all duration-200">
                                                        <i class="fas fa-chart-bar text-purple-400 w-4"></i>
                                                        <span class="ml-3">Dashboard</span>
                                                    </a>
                                                    @if ($canRegisterParasiteSamples)
                                                        <!-- Line Separator -->
                                                        <div
                                                            class="mx-4 my-2 h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent">
                                                        </div>

                                                        <!-- Dissection Submenu -->
                                                        <div class="relative group/sub2">
                                                            <a href="/samples/parasites/dissection/create"
                                                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-purple-50 hover:to-pink-50 transition-all duration-200">
                                                                <i class="fas fa-cut text-purple-400 w-4"></i>
                                                                <span class="ml-3">Dissection</span>
                                                            </a>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        <!-- Nucleic Acids Submenu -->
                                        @if ($canViewNucleicAcids)
                                        <div class="relative group/sub">
                                            <div
                                                class="flex items-center justify-between px-4 py-3 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-blue-50 hover:to-cyan-50 transition-all duration-200 border-l-4 border-transparent hover:border-blue-400">
                                                <div class="flex items-center">
                                                    <i class="fas fa-dna text-blue-500 w-5"></i>
                                                    <span class="ml-3 font-medium">Nucleic Acids</span>
                                                </div>
                                                <svg class="w-4 h-4 ml-2 text-gray-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </div>

                                            <div
                                                class="absolute left-full top-0 w-56 rounded-xl shadow-2xl bg-gradient-to-br from-white to-gray-50 ring-1 ring-gray-200 hidden group-hover/sub:block border border-gray-100">
                                                <div class="py-2">
                                                    @if (session()->has('selected_project_id'))
                                                        <a href="/samples/nucleic"
                                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-blue-50 hover:to-cyan-50 transition-all duration-200">
                                                            <i class="fas fa-home text-blue-400 w-4"></i>
                                                            <span class="ml-3">NA Home</span>
                                                        </a>
                                                        @if ($canRegisterNucleicAcids)
                                                            <a href="/samples/nucleic/create"
                                                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-blue-50 hover:to-cyan-50 transition-all duration-200">
                                                                <i class="fas fa-vial text-blue-400 w-4"></i>
                                                                <span class="ml-3">Extraction</span>
                                                            </a>
                                                        @endif
                                                    @endif
                                                    <a href="/samples/nucleic/list"
                                                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-blue-50 hover:to-cyan-50 transition-all duration-200">
                                                        <i class="fas fa-list text-blue-400 w-4"></i>
                                                        <span class="ml-3">List</span>
                                                    </a>
                                                    <a href="/samples/nucleic/dashboard"
                                                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-blue-50 hover:to-cyan-50 transition-all duration-200">
                                                        <i class="fas fa-chart-bar text-blue-400 w-4"></i>
                                                        <span class="ml-3">Dashboard</span>
                                                    </a>

                                                    <!-- Line Separator -->
                                                    <div
                                                    class="mx-4 my-2 h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent">
                                                </div>

                                                    <!-- Sequences Submenu -->
                                                    <div class="relative group/sub2">
                                                        <div
                                                            class="flex items-center justify-between px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-purple-50 transition-all duration-200">
                                                            <div class="flex items-center">
                                                                <i class="fas fa-code text-indigo-400 w-4"></i>
                                                                <span class="ml-3">Sequences</span>
                                                            </div>
                                                            <svg class="w-4 h-4 ml-2 text-gray-400" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M9 5l7 7-7 7" />
                                                            </svg>
                                                        </div>
                                                        <div
                                                            class="absolute left-full top-0 w-56 max-h-[80vh] overflow-y-auto rounded-xl shadow-2xl bg-gradient-to-br from-white to-gray-50 ring-1 ring-gray-200 hidden group-hover/sub2:block border border-gray-100">
                                                            <div class="py-2">
                                                                @if (session()->has('selected_project_id'))
                                                                    <a href="/samples/nucleic/sequences"
                                                                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-purple-50 transition-all duration-200">
                                                                        <i class="fas fa-home text-indigo-400 w-4"></i>
                                                                        <span class="ml-3">SE Index</span>
                                                                    </a>
                                                                    @if ($canRegisterNucleicAcids)
                                                                        <a href="/samples/nucleic/sequences/create"
                                                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-purple-50 transition-all duration-200">
                                                                            <i
                                                                                class="fas fa-plus text-indigo-400 w-4"></i>
                                                                            <span class="ml-3">Register</span>
                                                                        </a>
                                                                    @endif
                                                                @endif
                                                                <a href="/samples/nucleic/sequences/list"
                                                                    class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-purple-50 transition-all duration-200">
                                                                    <i class="fas fa-list text-indigo-400 w-4"></i>
                                                                    <span class="ml-3">List</span>
                                                                </a>
                                                                <a href="/samples/nucleic/sequences/dashboard"
                                                                    class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-purple-50 transition-all duration-200">
                                                                    <i
                                                                        class="fas fa-chart-bar text-indigo-400 w-4"></i>
                                                                    <span class="ml-3">Dashboard</span>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- End Sequences Submenu -->
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        <!-- Microplastics Submenu -->
                                        @if ($canViewMicroplastics)
                                        <div class="relative group/sub">
                                            <div
                                                class="flex items-center justify-between px-4 py-3 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-sky-50 hover:to-cyan-50 transition-all duration-200 border-l-4 border-transparent hover:border-sky-400">
                                                <div class="flex items-center">
                                                    <i class="fas fa-recycle text-sky-500 w-5"></i>
                                                    <span class="ml-3 font-medium">Microplastics</span>
                                                </div>
                                                <svg class="w-4 h-4 ml-2 text-gray-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </div>
                                            <div
                                                class="absolute left-full top-0 w-56 rounded-xl shadow-2xl bg-gradient-to-br from-white to-gray-50 ring-1 ring-gray-200 hidden group-hover/sub:block border border-gray-100">
                                                <div class="py-2">
                                                    @if (session()->has('selected_project_id'))
                                                        <a href="/samples/microplastics"
                                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-sky-50 hover:to-cyan-50 transition-all duration-200">
                                                            <i class="fas fa-home text-sky-400 w-4"></i>
                                                            <span class="ml-3">MP Home</span>
                                                        </a>
                                                        @if ($canRegisterMicroplastics)
                                                            <a href="/samples/microplastics/create"
                                                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-sky-50 hover:to-cyan-50 transition-all duration-200">
                                                                <i class="fas fa-recycle text-sky-400 w-4"></i>
                                                                <span class="ml-3">Identification</span>
                                                            </a>
                                                        @endif
                                                    @endif
                                                    <a href="/samples/microplastics/list"
                                                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-sky-50 hover:to-cyan-50 transition-all duration-200">
                                                        <i class="fas fa-list text-sky-400 w-4"></i>
                                                        <span class="ml-3">List</span>
                                                    </a>
                                                    <a href="/samples/microplastics/dashboard"
                                                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-sky-50 hover:to-cyan-50 transition-all duration-200">
                                                        <i class="fas fa-chart-bar text-sky-400 w-4"></i>
                                                        <span class="ml-3">Dashboard</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        <!-- Cultures Submenu -->
                                        @if ($canViewCultures)
                                        <div class="relative group/sub">
                                            <div
                                                class="flex items-center justify-between px-4 py-3 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-orange-50 hover:to-orange-50 transition-all duration-200 border-l-4 border-transparent hover:border-orange-400">
                                                <div class="flex items-center">
                                                    <i class="fas fa-bacteria text-orange-500 w-5"></i>
                                                    <span class="ml-3 font-medium">Cultures</span>
                                                </div>
                                                <svg class="w-4 h-4 ml-2 text-gray-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </div>
                                            <div
                                                class="absolute left-full top-0 w-56 rounded-xl shadow-2xl bg-gradient-to-br from-white to-gray-50 ring-1 ring-gray-200 hidden group-hover/sub:block border border-gray-100">
                                                <div class="py-2">
                                                    @if (session()->has('selected_project_id'))
                                                        <a href="/samples/cultures"
                                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-orange-50 hover:to-orange-50 transition-all duration-200">
                                                            <i class="fas fa-home text-orange-400 w-4"></i>
                                                            <span class="ml-3">CU Home</span>
                                                        </a>
                                                        @if ($canRegisterCultures)
                                                            <a href="/samples/cultures/create"
                                                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-orange-50 hover:to-orange-50 transition-all duration-200">
                                                                <i class="fas fa-plus text-orange-400 w-4"></i>
                                                                <span class="ml-3">Registration</span>
                                                            </a>
                                                        @endif
                                                    @endif
                                                    <a href="/samples/cultures/list"
                                                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-orange-50 hover:to-orange-50 transition-all duration-200">
                                                        <i class="fas fa-list text-orange-400 w-4"></i>
                                                        <span class="ml-3">List</span>
                                                    </a>
                                                    <a href="/samples/cultures/dashboard"
                                                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-orange-50 hover:to-orange-50 transition-all duration-200">
                                                        <i class="fas fa-chart-bar text-orange-400 w-4"></i>
                                                        <span class="ml-3">Dashboard</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        <!-- Pools Submenu -->
                                        @if ($canViewPools)
                                        <div class="relative group/sub">
                                            <div
                                                class="flex items-center justify-between px-4 py-3 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-cyan-50 hover:to-cyan-50 transition-all duration-200 border-l-4 border-transparent hover:border-cyan-400">
                                                <div class="flex items-center">
                                                    <i class="fas fa-layer-group text-cyan-500 w-5"></i>
                                                    <span class="ml-3 font-medium">Pools</span>
                                                </div>
                                                <svg class="w-4 h-4 ml-2 text-gray-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </div>
                                            <div
                                                class="absolute left-full top-0 w-56 rounded-xl shadow-2xl bg-gradient-to-br from-white to-gray-50 ring-1 ring-gray-200 hidden group-hover/sub:block border border-gray-100">
                                                <div class="py-2">
                                                    @if (session()->has('selected_project_id'))
                                                        <a href="/samples/pools"
                                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-cyan-50 hover:to-cyan-50 transition-all duration-200">
                                                            <i class="fas fa-home text-cyan-400 w-4"></i>
                                                            <span class="ml-3">PO Home</span>
                                                        </a>
                                                        @if ($canRegisterPools)
                                                            <a href="/samples/pools/create"
                                                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-cyan-50 hover:to-cyan-50 transition-all duration-200">
                                                                <i class="fas fa-plus text-cyan-400 w-4"></i>
                                                                <span class="ml-3">Registration</span>
                                                            </a>
                                                        @endif
                                                    @endif
                                                    <a href="/samples/pools/list"
                                                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-cyan-50 hover:to-cyan-50 transition-all duration-200">
                                                        <i class="fas fa-list text-cyan-400 w-4"></i>
                                                        <span class="ml-3">List</span>
                                                    </a>
                                                    <a href="/samples/pools/dashboard"
                                                        class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-cyan-50 hover:to-cyan-50 transition-all duration-200">
                                                        <i class="fas fa-chart-bar text-cyan-400 w-4"></i>
                                                        <span class="ml-3">Dashboard</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <!-- Experiments Dropdown -->
                            @if ($canViewExperiments)
                            <div class="relative group">
                                <x-nav-link href="/experiments" :active="request()->is('experiments*')">Experiments</x-nav-link>
                                <div
                                    class="absolute left-0 mt-1 w-56 rounded-xl shadow-2xl bg-gradient-to-br from-white to-gray-50 ring-1 ring-gray-200 hidden group-hover:block z-50 border border-gray-100">
                                    <div class="py-2" role="menu">
                                        @if (session()->has('selected_project_id'))
                                            <a href="/experiments"
                                                class="flex items-center px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gradient-to-r hover:from-purple-50 hover:to-violet-50 transition-all duration-200 border-l-4 border-transparent hover:border-purple-400"
                                                role="menuitem">
                                                <i class="fas fa-home text-purple-500 w-5"></i>
                                                <span class="ml-3">EX Home</span>
                                            </a>
                                            <!-- Line Separator -->
                                            <div
                                                class="mx-4 my-2 h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent">
                                            </div>
                                        @endif
                                        <!-- Core Tasks Section -->
                                        <div class="px-4 py-1">
                                            <h2
                                                class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1 flex items-center">
                                                Core Tasks
                                            </h2>
                                        </div>

                                        @if (session()->has('selected_project_id'))
                                            @if ($canRegisterExperiments)
                                                <a href="/experiments/create"
                                                    class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-purple-50 hover:to-violet-50 transition-all duration-200 border-l-4 border-transparent hover:border-purple-400"
                                                    role="menuitem">
                                                    <i class="fas fa-plus text-purple-500 w-5"></i>
                                                    <span class="ml-3">Registration</span>
                                                </a>
                                            @endif
                                        @endif
                                        <a href="/experiments/list"
                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-purple-50 hover:to-violet-50 transition-all duration-200 border-l-4 border-transparent hover:border-purple-400"
                                            role="menuitem">
                                            <i class="fas fa-list text-purple-500 w-5"></i>
                                            <span class="ml-3">List</span>
                                        </a>
                                        <a href="/experiments/dashboard"
                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-purple-50 hover:to-violet-50 transition-all duration-200 border-l-4 border-transparent hover:border-purple-400"
                                            role="menuitem">
                                            <i class="fas fa-chart-line text-purple-500 w-5"></i>
                                            <span class="ml-3">Dashboard</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @if (session()->has('selected_project_id') && ($canViewTubePositions || $canViewBoxPositions))
                                <!-- Storage Dropdown -->
                                <div class="relative group">
                                    <x-nav-link href="/bank" :active="request()->is('bank*')">Storage</x-nav-link>
                                    <div
                                        class="absolute left-0 mt-1 w-56 rounded-xl shadow-2xl bg-gradient-to-br from-white to-gray-50 ring-1 ring-gray-200 hidden group-hover:block z-50 border border-gray-100">
                                        <div class="py-2" role="menu">
                                            <a href="/bank"
                                                class="flex items-center px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-blue-50 transition-all duration-200 border-l-4 border-transparent hover:border-indigo-400"
                                                role="menuitem">
                                                <i class="fas fa-home text-indigo-500 w-5"></i>
                                                <span class="ml-3">Storage Home</span>
                                            </a>

                                            <!-- Line Separator -->
                                            <div
                                                class="mx-4 my-2 h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent">
                                            </div>

                                            <!-- Processed Samples Section -->
                                            <div class="px-4 py-1">
                                                <h2
                                                    class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1 flex items-center">
                                                    Storage Units
                                                </h2>
                                            </div>

                                            <!-- Tubes Submenu -->
                                            @if ($canViewTubePositions)
                                            <div class="relative group/sub">
                                                <div
                                                    class="flex items-center justify-between px-4 py-3 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-blue-50 transition-all duration-200 border-l-4 border-transparent hover:border-indigo-400">
                                                    <div class="flex items-center">
                                                        <i class="fas fa-vials text-indigo-500 w-5"></i>
                                                        <span class="ml-3 font-medium">Tube Positions</span>
                                                    </div>
                                                    <svg class="w-4 h-4 ml-2 text-gray-400" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </div>
                                                <div
                                                    class="absolute left-full top-0 w-56 rounded-xl shadow-2xl bg-gradient-to-br from-white to-gray-50 ring-1 ring-gray-200 hidden group-hover/sub:block border border-gray-100">
                                                    <div class="py-2">
                                                        <a href="/bank/tubes"
                                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-blue-50 transition-all duration-200">
                                                            <i class="fas fa-home text-indigo-400 w-4"></i>
                                                            <span class="ml-3">Tubes Home</span>
                                                        </a>
                                                        @if ($canRegisterTubePositions)
                                                            <a href="/bank/tubes/create"
                                                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-blue-50 transition-all duration-200">
                                                                <i class="fas fa-plus text-indigo-400 w-4"></i>
                                                                <span class="ml-3">Registration</span>
                                                            </a>
                                                        @endif
                                                        <a href="/bank/tubes/list"
                                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-blue-50 transition-all duration-200">
                                                            <i class="fas fa-list text-indigo-400 w-4"></i>
                                                            <span class="ml-3">List</span>
                                                        </a>
                                                        <a href="/bank/tubes/dashboard"
                                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-blue-50 transition-all duration-200">
                                                            <i class="fas fa-chart-bar text-indigo-400 w-4"></i>
                                                            <span class="ml-3">Dashboard</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                            @endif

                                            <!-- Boxes Submenu -->
                                            @if ($canViewBoxPositions)
                                            <div class="relative group/sub">
                                                <div
                                                    class="flex items-center justify-between px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-blue-50 transition-all duration-200 border-l-4 border-transparent hover:border-indigo-400">
                                                    <div class="flex items-center">
                                                        <i class="fas fa-boxes-stacked text-indigo-500 w-5"></i>
                                                        <span class="ml-3 font-medium">Box Positions</span>
                                                    </div>
                                                    <svg class="w-4 h-4 ml-2 text-gray-400" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </div>
                                                <div
                                                    class="absolute left-full top-0 w-56 rounded-xl shadow-2xl bg-gradient-to-br from-white to-gray-50 ring-1 ring-gray-200 hidden group-hover/sub:block border border-gray-100">
                                                    <div class="py-2">
                                                        <a href="/bank/boxes"
                                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-blue-50 transition-all duration-200">
                                                            <i class="fas fa-home text-indigo-400 w-4"></i>
                                                            <span class="ml-3">BO Home</span>
                                                        </a>
                                                        @if ($canRegisterBoxPositions)
                                                            <a href="/bank/boxes/create"
                                                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-blue-50 transition-all duration-200">
                                                                <i class="fas fa-plus text-indigo-400 w-4"></i>
                                                                <span class="ml-3">Registration</span>
                                                            </a>
                                                        @endif
                                                        <a href="/bank/boxes/list"
                                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-blue-50 transition-all duration-200">
                                                            <i class="fas fa-list text-indigo-400 w-4"></i>
                                                            <span class="ml-3">List</span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                            @endif

                                        </div>
                                    </div>
                                </div>
                            @endif
                            <!-- Literature Dropdown -->
                            @if ($canViewLiterature)
                            <div class="relative group">
                                <x-nav-link href="/meta" :active="request()->is('meta*')">Literature</x-nav-link>
                                <div
                                    class="absolute left-0 mt-1 w-56 rounded-xl shadow-2xl bg-gradient-to-br from-white to-gray-50 ring-1 ring-gray-200 hidden group-hover:block z-50 border border-gray-100">
                                    <div class="py-2" role="menu">
                                        @if (session()->has('selected_project_id'))
                                            <a href="/meta"
                                                class="flex items-center px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gradient-to-r hover:from-emerald-50 hover:to-teal-50 transition-all duration-200 border-l-4 border-transparent hover:border-emerald-400"
                                                role="menuitem">
                                                <i class="fas fa-home text-emerald-500 w-5"></i>
                                                <span class="ml-3">Literature Home</span>
                                            </a>
                                            <!-- Line Separator -->
                                            <div
                                                class="mx-4 my-2 h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent">
                                            </div>
                                        @endif
                                        <!-- Core Tasks Section -->
                                        <div class="px-4 py-1">
                                            <h2
                                                class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1 flex items-center">
                                                Core Tasks
                                            </h2>
                                        </div>
                                        @if (session()->has('selected_project_id'))
                                            @if ($canRegisterLiterature)
                                                <a href="/meta/create"
                                                    class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-emerald-50 hover:to-teal-50 transition-all duration-200 border-l-4 border-transparent hover:border-emerald-400"
                                                    role="menuitem">
                                                    <i class="fas fa-plus text-emerald-500 w-5"></i>
                                                    <span class="ml-3">Registration</span>
                                                </a>
                                            @endif
                                        @endif
                                        <a href="/meta/list/animal"
                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-emerald-50 hover:to-teal-50 transition-all duration-200 border-l-4 border-transparent hover:border-emerald-400"
                                            role="menuitem">
                                            <i class="fas fa-list text-emerald-500 w-5"></i>
                                            <span class="ml-3">List</span>
                                        </a>
                                        <a href="/meta/dashboard"
                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-emerald-50 hover:to-teal-50 transition-all duration-200 border-l-4 border-transparent hover:border-emerald-400"
                                            role="menuitem">
                                            <i class="fas fa-chart-bar text-emerald-500 w-5"></i>
                                            <span class="ml-3">Dashboard</span>
                                        </a>

                                    </div>
                                </div>
                            </div>
                            @endif

                            @if (session()->has('selected_project_id'))
                                <!-- Team Dropdown -->
                                <x-nav-link href="/team" :active="request()->is('team')">Team</x-nav-link>

                                <!-- Documents Link -->
                                <x-nav-link href="/documents" :active="request()->is('documents')">Documents</x-nav-link>
                            @endif

                        </div>
                    </div>
                    <!-- Mobile menu button (visible on small screens) -->
                    <div class="ml-2 flex md:hidden">
                        <button type="button" id="mobile-menu-button"
                            class="relative inline-flex items-center justify-center rounded-md bg-gray-800 p-2 text-gray-100 hover:bg-gray-700 hover:text-white focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-gray-800"
                            aria-controls="mobile-menu" aria-expanded="false">
                            <span class="absolute -inset-0.5"></span>
                            <span class="sr-only">Open main menu</span>
                            <!-- Menu open: "hidden", Menu closed: "block" -->
                            <svg class="block h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                            <!-- Menu open: "block", Menu closed: "hidden" -->
                            <svg class="hidden h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="hidden md:block">
                    <div class="ml-4 flex items-center md:ml-6">
                        <a href="{{ $currentProjectChipUrl }}"
                            class="mr-3 inline-flex max-w-[20rem] items-center gap-2 rounded-full border border-white/10 bg-gray-900/80 px-3 py-1.5 text-xs font-medium text-gray-200 transition hover:border-blue-400/50 hover:bg-gray-800 hover:text-white"
                            title="{{ $currentSelectedProject ? trim(($currentSelectedProject->code ?? '').' '.($currentSelectedProject->title ? '· '.$currentSelectedProject->title : '')) : 'No project selected' }}">
                            <i class="fas fa-diagram-project text-[11px] {{ $currentSelectedProject ? 'text-blue-400' : 'text-gray-500' }}"></i>
                            @if ($currentSelectedProject)
                                <span class="text-gray-400">Project</span>
                                <span class="truncate font-semibold text-white">{{ $currentSelectedProject->code }}</span>
                            @else
                                <span class="text-gray-400">No project selected</span>
                            @endif
                        </a>

                        <button type="button" id="announcementButton"
                            class="relative rounded-full bg-gray-800 p-1 text-gray-400 hover:text-white focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-gray-800 mr-2">
                            <span class="absolute -inset-1.5"></span>
                            <span class="sr-only">View announcements</span>
                            <span class="h-6 w-6 flex items-center justify-center" aria-hidden="true">
                                <i class="fa-solid fa-bullhorn text-lg"></i>
                            </span>
                            <span id="announcementCounter"
                                class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden">0</span>
                        </button>

                        <!-- Announcements Dropdown -->
                        <div id="announcementDropdown"
                            class="hidden fixed right-4 mt-2 w-96 overflow-hidden rounded-2xl shadow-2xl bg-white ring-1 ring-black/10 z-50"
                            style="top: 80px;">
                            <div class="py-0">
                                <div
                                    class="px-4 py-3 border-b border-gray-200 bg-gradient-to-r from-indigo-900 to-indigo-800 text-white">
                                    <div class="flex justify-between items-center">
                                        <h3 class="text-sm font-semibold">Announcements</h3>
                                        <button id="markAllAnnouncementsRead"
                                            class="text-xs text-white/80 hover:text-white">Mark all as read</button>
                                    </div>
                                </div>
                                <div id="announcementList" class="overflow-y-auto bg-white" style="max-height: 440px;">
                                    <!-- Announcements will be loaded here -->
                                </div>
                            </div>
                        </div>

                        <button type="button" id="notificationButton"
                            class="relative rounded-full bg-gray-800 p-1 text-gray-400 hover:text-white focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-gray-800">
                            <span class="absolute -inset-1.5"></span>
                            <span class="sr-only">View notifications</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                            <span id="notificationCounter"
                                class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden">0</span>
                        </button>


                        <!-- Notification Dropdown -->
                        <div id="notificationDropdown"
                            class="hidden fixed right-4 mt-2 w-96 overflow-hidden rounded-2xl shadow-2xl bg-white ring-1 ring-black/10 z-50"
                            style="top: 80px;">
                            <div class="py-0">
                                <div
                                    class="px-4 py-3 border-b border-gray-200 bg-gradient-to-r from-slate-900 to-slate-800 text-white">
                                    <div class="flex justify-between items-center">
                                        <h3 class="text-sm font-semibold">Notifications</h3>
                                        <button id="markAllRead"
                                            class="text-xs text-white/80 hover:text-white">Mark all as read</button>
                                    </div>
                                </div>
                                <div id="notificationList" class="overflow-y-auto bg-white" style="max-height: 440px;">
                                    <!-- Notifications will be loaded here -->
                                </div>
                            </div>
                        </div>

                        <!-- Profile dropdown -->
                        @auth
                            <div class="relative ml-3">
                                <div>
                                    <button type="button"
                                        class="relative flex max-w-xs items-center rounded-full bg-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-gray-800"
                                        id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                                        <span class="absolute -inset-1.5"></span>
                                        <span class="sr-only">Open user menu</span>
                                        @auth
                                            <x-people-logo :person="auth()->user()->people" width="35" />
                                        @endauth
                                        @guest
                                            <x-people-logo width="35" />
                                        @endguest
                                    </button>
                                </div>

                                <!-- Dropdown menu -->
                                <div id="user-menu-dropdown"
                                    class="hidden absolute right-0 z-10 mt-2 w-56 rounded-xl shadow-2xl bg-gradient-to-br from-white to-gray-50 ring-1 ring-gray-200 border border-gray-100 focus:outline-none"
                                    role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button"
                                    tabindex="-1">
                                    <div class="py-2" role="menu">
                                        <a href="/profile"
                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 transition-all duration-200 border-l-4 border-transparent hover:border-blue-400"
                                            role="menuitem" tabindex="-1">
                                            <i class="fas fa-user text-blue-500 w-4"></i>
                                            <span class="ml-3">Profile</span>
                                        </a>
                                        <a href="/my-projects"
                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-green-50 hover:to-emerald-50 transition-all duration-200 border-l-4 border-transparent hover:border-green-400"
                                            role="menuitem" tabindex="-1">
                                            <i class="fas fa-project-diagram text-green-500 w-4"></i>
                                            <span class="ml-3">My Projects</span>
                                        </a>
                                        <a href="{{ route('profile.settings') }}"
                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-slate-50 hover:to-gray-100 transition-all duration-200 border-l-4 border-transparent hover:border-gray-400"
                                            role="menuitem" tabindex="-1">
                                            <i class="fas fa-cog text-gray-500 w-4"></i>
                                            <span class="ml-3">Settings</span>
                                        </a>
                                        <a href="/publish"
                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-purple-50 hover:to-violet-50 transition-all duration-200 border-l-4 border-transparent hover:border-purple-400"
                                            role="menuitem" tabindex="-1">
                                            <i class="fas fa-globe text-purple-500 w-4"></i>
                                            <span class="ml-3">Publish data</span>
                                        </a>
                                        <a href="/tube-requests"
                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-orange-50 hover:to-amber-50 transition-all duration-200 border-l-4 border-transparent hover:border-orange-400"
                                            role="menuitem" tabindex="-1">
                                            <i class="fas fa-vial text-orange-500 w-4"></i>
                                            <span class="ml-3">Sample requests</span>
                                        </a>

                                        @php
                                            $canAdmin = \App\Support\AdminAccess::canAccessAdminArea(auth()->user(), session('selected_project_id') ? (int) session('selected_project_id') : null);
                                        @endphp
                                        @if ($canAdmin)
                                            <!-- Line Separator -->
                                            <div
                                                class="mx-4 my-2 h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent">
                                            </div>

                                            <p class="px-4 pt-1 pb-0.5 text-xs font-semibold uppercase tracking-wider text-gray-400">Admin</p>

                                            <a href="{{ route('admin.lookups.index') }}"
                                                class="flex items-center pl-6 pr-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-purple-50 transition-all duration-200 border-l-4 border-transparent hover:border-indigo-400"
                                                role="menuitem" tabindex="-1">
                                                <i class="fa-solid fa-table-cells-large text-indigo-600 w-4"></i>
                                                <span class="ml-3">Lookup tables</span>
                                            </a>

                                            <a href="{{ route('admin.announcements.index') }}"
                                                class="flex items-center pl-6 pr-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-purple-50 transition-all duration-200 border-l-4 border-transparent hover:border-indigo-400"
                                                role="menuitem" tabindex="-1">
                                                <i class="fa-solid fa-bullhorn text-indigo-600 w-4"></i>
                                                <span class="ml-3">Announcements</span>
                                            </a>

                                            <a href="{{ route('admin.publication-reviews.index') }}"
                                                class="flex items-center pl-6 pr-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-purple-50 transition-all duration-200 border-l-4 border-transparent hover:border-indigo-400"
                                                role="menuitem" tabindex="-1">
                                                <i class="fa-solid fa-clipboard-check text-indigo-600 w-4"></i>
                                                <span class="ml-3">Check data for publication</span>
                                            </a>
                                        @endif

                                        <!-- Line Separator -->
                                        <div
                                            class="mx-4 my-2 h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent">
                                        </div>

                                        <form method="POST" action="/logout" class="block">
                                            @csrf
                                            <button type="submit"
                                                class="w-full flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gradient-to-r hover:from-red-50 hover:to-pink-50 transition-all duration-200 border-l-4 border-transparent hover:border-red-400"
                                                role="menuitem" tabindex="-1">
                                                <i class="fas fa-sign-out-alt text-red-500 w-4"></i>
                                                <span class="ml-3">Sign out</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endauth

                        @guest
                            <div class="ml-3">
                                <x-nav-link href="/register" :active="request()->is('register')">Register</x-nav-link>
                                <x-nav-link href="/login" :active="request()->is('login')">Log In</x-nav-link>
                            </div>
                        @endguest
                    </div>


                </div>
            </div>

            <!-- Mobile menu, show/hide based on menu state. -->
            <div class="md:hidden hidden" id="mobile-menu">
                @php
                    $mobileUser = Auth::user();
                    $mobileProjectId = (int) session('selected_project_id');
                    $mobileCanViewExperiments = true;
                    $mobileCanViewLiterature = true;
                    $mobileCanViewStorage = true;
                    if ($mobileUser && $mobileUser->people && $mobileProjectId > 0) {
                        $mobileCanViewExperiments = \App\Support\ProjectPermission::canView($mobileUser, $mobileProjectId, 'experiments');
                        $mobileCanViewLiterature = \App\Support\ProjectPermission::canView($mobileUser, $mobileProjectId, 'literature');
                        $mobileCanViewStorage = \App\Support\ProjectPermission::canView($mobileUser, $mobileProjectId, 'tube_positions')
                            || \App\Support\ProjectPermission::canView($mobileUser, $mobileProjectId, 'box_positions');
                    }
                @endphp
                <div class="space-y-1 px-2 pb-3 pt-2 sm:px-3">
                    <!-- Current: "bg-gray-900 text-white", Default: "text-gray-300 hover:bg-gray-700 hover:text-white" -->
                    <a href="/"
                        class="text-gray-300 hover:bg-gray-700 hover:text-white block rounded-md px-3 py-2 text-base font-medium">Home</a>
                    <a href="/samples"
                        class="text-gray-300 hover:bg-gray-700 hover:text-white block rounded-md px-3 py-2 text-base font-medium">Samples</a>
                    @if ($mobileCanViewExperiments)
                    <a href="/experiments"
                        class="text-gray-300 hover:bg-gray-700 hover:text-white block rounded-md px-3 py-2 text-base font-medium">Experiments</a>
                    @endif
                    @if (session()->has('selected_project_id') && $mobileCanViewStorage)
                        <a href="/bank"
                            class="text-gray-300 hover:bg-gray-700 hover:text-white block rounded-md px-3 py-2 text-base font-medium">Storage</a>
                    @endif
                    @if ($mobileCanViewLiterature)
                    <a href="/meta"
                        class="text-gray-300 hover:bg-gray-700 hover:text-white block rounded-md px-3 py-2 text-base font-medium">Literature</a>
                    @endif
                    @if (session()->has('selected_project_id'))
                        <a href="/team"
                            class="text-gray-300 hover:bg-gray-700 hover:text-white block rounded-md px-3 py-2 text-base font-medium">Team</a>
                        <a href="/documents"
                            class="text-gray-300 hover:bg-gray-700 hover:text-white block rounded-md px-3 py-2 text-base font-medium">Documents</a>
                    @endif
                </div>
                <div class="border-t border-gray-700 pb-3 pt-4">
                    <div class="flex items-center px-5">
                        <div class="flex-shrink-0">
                            @auth
                                <x-people-logo :person="auth()->user()->people" width="35" />
                            @endauth
                            @guest
                                <x-people-logo width="35" />
                            @endguest
                        </div>
                        <div class="ml-3">
                            @auth
                                <div class="text-base font-medium leading-none text-white">
                                    {{ auth()->user()->first_name . ' ' . auth()->user()->last_name }}</div>
                                <div class="text-sm font-medium leading-none text-gray-400">{{ auth()->user()->email }}
                                </div>
                            @endauth
                        </div>
                        <div class="ml-3 rounded-lg border border-white/10 bg-gray-800/80 px-3 py-2">
                            @if ($currentSelectedProject)
                                <div class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Current project</div>
                                <div class="text-sm font-semibold text-white">{{ $currentSelectedProject->code }}</div>
                                @if (!empty($currentSelectedProject->title))
                                    <div class="text-xs text-gray-400">{{ $currentSelectedProject->title }}</div>
                                @endif
                            @else
                                <div class="text-sm text-gray-400">No project selected</div>
                            @endif
                        </div>
                        <button type="button"
                            class="relative ml-auto flex-shrink-0 rounded-full bg-gray-800 p-1 text-gray-400 hover:text-white focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-gray-800">
                            <span class="absolute -inset-1.5"></span>
                            <span class="sr-only">View notifications</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                        </button>
                    </div>
                    <div class="mt-3 space-y-1 px-2">
                        <a href="/profile"
                            class="block rounded-md px-3 py-2 text-base font-medium text-gray-400 hover:bg-gray-700 hover:text-white">
                            Profile</a>
                        <a href="/my-projects"
                            class="block rounded-md px-3 py-2 text-base font-medium text-gray-400 hover:bg-gray-700 hover:text-white">My
                            Projects</a>
                        <a href="{{ route('profile.settings') }}"
                            class="block rounded-md px-3 py-2 text-base font-medium text-gray-400 hover:bg-gray-700 hover:text-white">Settings</a>
                        <a href="/publish"
                            class="block rounded-md px-3 py-2 text-base font-medium text-gray-400 hover:bg-gray-700 hover:text-white">Publish
                            data</a>
                        <form method="POST" action="/logout" class="block">
                            @csrf
                            <button type="submit"
                                class="block rounded-md px-3 py-2 text-base font-medium text-gray-400 hover:bg-gray-700 hover:text-white">Sign
                                out</button>
                        </form>
                    </div>
                </div>
            </div>
    </nav>

    <main class="flex-1">
        <div class="mx-auto max-w-7xl min-h-screen py-4 sm:px-6 lg:px-8">
            <!-- Guest Mode Navigation -->
            @if (!session('selected_project_id'))
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                <strong>Guest Mode:</strong> You are viewing public data.
                                <a href="{{ route('profile.projects') }}" class="font-medium underline hover:text-blue-600">
                                    Select a project
                                </a>
                                to access your private data.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            @include('partials.two-factor-banner')
            {{ $slot }}
        </div>
    </main>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/selectize@0.12.6/dist/js/standalone/selectize.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
    <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>

    @livewireScripts

    <script>
        (function () {
            if (window.__indexTableStickyInstalled) {
                return;
            }

            window.__indexTableStickyInstalled = true;

            function resolveStickyColumns(table) {
                if (table.dataset.stickyCols) {
                    return table.dataset.stickyCols
                        .split(',')
                        .map(function (value) { return parseInt(value.trim(), 10); })
                        .filter(function (value) { return Number.isFinite(value) && value > 0; });
                }

                var hasBulk = table.classList.contains('has-bulk-select');

                if (table.classList.contains('sticky-code-at-2')) {
                    return [2];
                }

                var tableId = table.id || '';

                if (/tube|culture|box_position|parasite_(samples|human|animal|environment)|experiments/i.test(tableId)) {
                    return hasBulk ? [2, 3] : [1, 2];
                }

                return hasBulk ? [2] : [1];
            }

            function clearStickyColumns(table) {
                table.querySelectorAll('.index-sticky-cell').forEach(function (cell) {
                    cell.classList.remove('index-sticky-cell', 'index-sticky-cell-last');
                    cell.style.removeProperty('left');
                    cell.style.removeProperty('min-width');
                    cell.style.removeProperty('width');
                    cell.style.removeProperty('z-index');
                });
            }

            function measureColumnWidth(table, columnIndex) {
                var cells = table.querySelectorAll('thead tr > th:nth-child(' + columnIndex + '), tbody tr > td:nth-child(' + columnIndex + ')');
                var maxWidth = 0;

                cells.forEach(function (cell) {
                    maxWidth = Math.max(maxWidth, cell.scrollWidth, cell.getBoundingClientRect().width);
                });

                return Math.ceil(maxWidth);
            }

            function applyIndexTableSticky() {
                document.querySelectorAll('.index-data-table').forEach(function (table) {
                    clearStickyColumns(table);

                    var columns = resolveStickyColumns(table);

                    if (!columns.length) {
                        return;
                    }

                    var left = 0;

                    columns.forEach(function (columnIndex, order) {
                        var cells = table.querySelectorAll('thead tr > th:nth-child(' + columnIndex + '), tbody tr > td:nth-child(' + columnIndex + ')');

                        if (!cells.length) {
                            return;
                        }

                        var width = measureColumnWidth(table, columnIndex);

                        if (width <= 0) {
                            return;
                        }

                        var zIndex = 30 - order;
                        var isLast = order === columns.length - 1;

                        cells.forEach(function (cell) {
                            cell.classList.add('index-sticky-cell');
                            cell.style.left = left + 'px';
                            cell.style.minWidth = width + 'px';
                            cell.style.zIndex = String(zIndex);

                            if (isLast) {
                                cell.classList.add('index-sticky-cell-last');
                            }
                        });

                        left += width;
                    });
                });
            }

            var stickyFrame = null;

            function scheduleIndexTableSticky() {
                if (stickyFrame !== null) {
                    cancelAnimationFrame(stickyFrame);
                }

                stickyFrame = requestAnimationFrame(function () {
                    stickyFrame = null;
                    applyIndexTableSticky();
                });
            }

            function runSticky() {
                applyIndexTableSticky();
                window.setTimeout(applyIndexTableSticky, 120);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', runSticky);
            } else {
                runSticky();
            }

            window.addEventListener('resize', scheduleIndexTableSticky);

            document.addEventListener('livewire:init', function () {
                runSticky();

                Livewire.hook('morph.updated', function () {
                    scheduleIndexTableSticky();
                });

                Livewire.hook('commit', function (_ref) {
                    var succeed = _ref.succeed;
                    succeed(function () {
                        scheduleIndexTableSticky();
                    });
                });
            });
        })();
    </script>

    <script>
        // Global SweetAlert listener for Livewire browser events.
        // (Placed in the base layout so it works even for dynamically swapped Livewire views.)
        if (!window.__swalListenerInstalled) {
            window.__swalListenerInstalled = true;
            document.addEventListener('swal', function(event) {
                if (typeof Swal === 'undefined') {
                    return;
                }
                const detail = event.detail || {};
                const payload = Array.isArray(detail) ? (detail[0] || {}) : detail;
                const hasHtml = payload.html !== null && payload.html !== undefined && String(payload.html).trim() !== '';
                Swal.fire({
                    icon: payload.icon || 'success',
                    title: payload.title || 'Success',
                    text: hasHtml ? undefined : (payload.text || ''),
                    html: hasHtml ? payload.html : undefined,
                    width: payload.width || undefined,
                    showCloseButton: payload.showCloseButton || false,
                    confirmButtonText: payload.confirmButtonText || 'OK',
                });
            });
        }

        @if (session()->has('swal'))
            document.addEventListener('DOMContentLoaded', function() {
                const payload = @json(session('swal'));
                if (typeof Swal === 'undefined' || !payload) {
                    return;
                }

                Swal.fire({
                    icon: payload.icon || 'success',
                    title: payload.title || 'Success',
                    text: payload.text || '',
                });
            });
        @endif

        if (!window.__tubeBadgeDisplayHelperInstalled) {
            window.__tubeBadgeDisplayHelperInstalled = true;

            window.alephGetTubeBadgeDisplayMode = function() {
                const checked = document.querySelector('input[name="tube_badge_display"]:checked, input[name="tube_code_display"]:checked');
                return checked && checked.value === 'alias' ? 'alias' : 'tube';
            };

            window.alephNormalizeTubeOption = function(option) {
                const normalized = option || {};
                const aliasCode = String(normalized.alias_code || normalized.alias || '').trim();
                const text = String(normalized.text || '').trim();
                let code = String(normalized.code || '').trim();

                if (code === '' && text !== '' && text !== aliasCode) {
                    code = text;
                }

                return {
                    ...normalized,
                    code,
                    text: text || code,
                    alias_code: aliasCode,
                    sample_type_label: String(normalized.sample_type_label || '').trim(),
                };
            };

            window.alephGetTubeBadgeLabel = function(option) {
                const normalized = window.alephNormalizeTubeOption(option);
                const mode = window.alephGetTubeBadgeDisplayMode();
                const aliasCode = normalized.alias_code !== '' && normalized.alias_code !== 'N/A'
                    ? normalized.alias_code
                    : '';

                if (mode === 'alias' && aliasCode !== '') {
                    return aliasCode;
                }

                return normalized.code;
            };

            window.alephResolveTubeOptionFromSelectize = function(selectElement, control, itemId) {
                const value = String(itemId);
                const nativeOption = Array.from(selectElement.options).find((option) => String(option.value) === value) || null;
                const storedOption = control.options[value] || {};

                return window.alephNormalizeTubeOption({
                    ...storedOption,
                    value,
                    code: nativeOption?.dataset.code || storedOption.code || '',
                    alias_code: nativeOption?.dataset.aliasCode || storedOption.alias_code || '',
                    sample_type_label: nativeOption?.dataset.sampleTypeLabel || storedOption.sample_type_label || '',
                });
            };

            window.alephApplyTubeBadgeItemHtml = function(itemElement, badgeHtml) {
                const removeButton = itemElement.querySelector('.remove');
                const itemMarkup = `<div>${badgeHtml}</div>`;

                if (removeButton) {
                    const detachedRemoveButton = removeButton.parentNode.removeChild(removeButton);
                    itemElement.innerHTML = itemMarkup;
                    itemElement.appendChild(detachedRemoveButton);
                    return;
                }

                itemElement.innerHTML = itemMarkup;
            };

            window.alephGetTubeDropdownLabel = function(option) {
                const normalized = window.alephNormalizeTubeOption(option);

                if (normalized.alias_code !== '') {
                    return `${normalized.code} (${normalized.alias_code})`;
                }

                return normalized.code;
            };

            window.alephEscapeHtml = function(value) {
                const div = document.createElement('div');
                div.textContent = String(value ?? '');
                return div.innerHTML;
            };

            window.alephBuildTubeBadgeItemHtml = function(option) {
                const normalized = window.alephNormalizeTubeOption(option);
                const displayLabel = window.alephGetTubeBadgeLabel(normalized);
                const primaryHtml = window.alephEscapeHtml(displayLabel);
                const secondaryHtml = normalized.sample_type_label
                    ? `<span class="text-white/90"> · ${window.alephEscapeHtml(normalized.sample_type_label)}</span>`
                    : '';

                return `${primaryHtml}${secondaryHtml}`;
            };

            window.alephRefreshTubeSelectPlaceholder = function(selectElementOrId) {
                const selectElement = typeof selectElementOrId === 'string'
                    ? document.getElementById(selectElementOrId)
                    : selectElementOrId;

                if (!selectElement || selectElement.dataset.tubeBadgeToggle !== '1' || !selectElement.selectize) {
                    return;
                }

                const control = selectElement.selectize;
                const placeholder = 'Enter tube code or alias';

                control.settings.placeholder = placeholder;

                if (control.$control_input) {
                    control.$control_input.attr('placeholder', placeholder);
                }

                if (typeof control.updatePlaceholder === 'function') {
                    control.updatePlaceholder();
                }
            };

            window.alephConfigureTubeBadgeSelectize = function(selectElementOrId) {
                const selectElement = typeof selectElementOrId === 'string'
                    ? document.getElementById(selectElementOrId)
                    : selectElementOrId;

                if (!selectElement || selectElement.dataset.tubeBadgeToggle !== '1' || !selectElement.selectize) {
                    return;
                }

                const control = selectElement.selectize;

                if (selectElement.dataset.tubeBadgeRefreshBound !== '1') {
                    control.on('item_add', function() {
                        setTimeout(function() {
                            window.alephConfigureTubeBadgeSelectize(selectElement);
                        }, 0);
                    });

                    selectElement.dataset.tubeBadgeRefreshBound = '1';
                }

                control.items.forEach(function(itemId) {
                    const value = String(itemId);
                    const $item = control.getItem(value);
                    if (!$item || !$item.length) {
                        return;
                    }

                    const option = window.alephResolveTubeOptionFromSelectize(selectElement, control, value);
                    const badgeHtml = window.alephBuildTubeBadgeItemHtml(option);
                    window.alephApplyTubeBadgeItemHtml($item[0], badgeHtml);
                });

                window.alephRefreshTubeSelectPlaceholder(selectElement);
            };

            window.alephRefreshTubeBadgeDisplay = function(selectElementOrId) {
                const selectElement = typeof selectElementOrId === 'string'
                    ? document.getElementById(selectElementOrId)
                    : selectElementOrId;

                if (!selectElement || selectElement.dataset.tubeBadgeToggle !== '1' || !selectElement.selectize) {
                    return;
                }

                const control = selectElement.selectize;
                if (control.renderCache && control.renderCache.item) {
                    control.renderCache.item = {};
                }

                window.alephConfigureTubeBadgeSelectize(selectElement);
                window.alephRefreshTubeSelectPlaceholder(selectElement);
            };

            window.alephUpdateTubeDisplayTogglePlacement = function(root) {
                const scope = root || document;
                const toggle = scope.querySelector('#tube-badge-display-toggle') || document.getElementById('tube-badge-display-toggle');
                if (!toggle) {
                    return;
                }

                const visibleAnchor = Array.from(scope.querySelectorAll('[data-tube-display-anchor="1"]')).find(function(anchor) {
                    return anchor.offsetParent !== null;
                });

                if (!visibleAnchor) {
                    toggle.classList.add('hidden');
                    return;
                }

                toggle.classList.remove('hidden');
                visibleAnchor.insertAdjacentElement('afterend', toggle);
                window.alephBindTubeBadgeDisplayToggleListeners?.();
            };

            window.alephHandleTubeBadgeDisplayChange = function() {
                window.__boxPreviewCellSizeManual = false;

                if (typeof window.alephRefreshAllTubeBadgeDisplays === 'function') {
                    window.alephRefreshAllTubeBadgeDisplays();
                }

                if (typeof window.alephUpdateBoxVisualization === 'function') {
                    window.alephUpdateBoxVisualization();
                    return;
                }

                window.alephScheduleBoxVisualizationUpdate?.(0);
            };

            window.alephOnTubeBadgeDisplayModeChange = window.alephHandleTubeBadgeDisplayChange;

            window.alephRefreshAllTubeBadgeDisplays = function(root) {
                const scope = root || document;
                scope.querySelectorAll('select[data-tube-badge-toggle="1"]').forEach(function(selectElement) {
                    if (!selectElement.selectize) {
                        return;
                    }

                    const control = selectElement.selectize;
                    if (control.renderCache && control.renderCache.item) {
                        control.renderCache.item = {};
                    }

                    window.alephConfigureTubeBadgeSelectize(selectElement);
                });
                window.alephUpdateTubeDisplayTogglePlacement(scope);
            };

            document.addEventListener('change', function(event) {
                const target = event.target;
                if (target && (target.matches('input[name="tube_badge_display"]') || target.matches('input[name="tube_code_display"]'))) {
                    window.alephHandleTubeBadgeDisplayChange?.();
                }

                if (target && (target.id === 'model' || target.name === 'model' || target.name === 'culture_step')) {
                    setTimeout(function() {
                        window.alephRefreshAllTubeBadgeDisplays();
                    }, 0);
                }
            });

            document.addEventListener('DOMContentLoaded', function() {
                window.alephRefreshAllTubeBadgeDisplays();
            });
        }
    </script>

    {{-- Emergency: global registrar-lock script disabled due runtime freeze.
         Registrar lock remains server-enforced in controllers; UI lock will be reintroduced per-form safely. --}}

    @stack('scripts') <!-- Allows additional scripts for specific pages -->

    @if (session()->has('selected_project_id'))
        <x-chat-window />
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');
            if (mobileMenuButton && mobileMenu) {
                const iconOpen = mobileMenuButton.querySelector('svg.block');
                const iconClose = mobileMenuButton.querySelector('svg.hidden');

                function setExpanded(isExpanded) {
                    mobileMenuButton.setAttribute('aria-expanded', String(isExpanded));
                    if (isExpanded) {
                        mobileMenu.classList.remove('hidden');
                        if (iconOpen) iconOpen.classList.add('hidden');
                        if (iconClose) iconClose.classList.remove('hidden');
                    } else {
                        mobileMenu.classList.add('hidden');
                        if (iconOpen) iconOpen.classList.remove('hidden');
                        if (iconClose) iconClose.classList.add('hidden');
                    }
                }

                // Start collapsed
                setExpanded(false);

                mobileMenuButton.addEventListener('click', function() {
                    const isExpanded = mobileMenuButton.getAttribute('aria-expanded') === 'true';
                    setExpanded(!isExpanded);
                });

                // Close menu when clicking any link inside it
                mobileMenu.addEventListener('click', function(event) {
                    const target = event.target;
                    if (target && (target.tagName === 'A' || target.closest('a'))) {
                        setExpanded(false);
                    }
                });
            }

            const notificationButton = document.getElementById('notificationButton');
            const notificationDropdown = document.getElementById('notificationDropdown');
            const notificationList = document.getElementById('notificationList');
            const notificationCounter = document.getElementById('notificationCounter');
            const markAllRead = document.getElementById('markAllRead');

            const announcementButton = document.getElementById('announcementButton');
            const announcementDropdown = document.getElementById('announcementDropdown');
            const announcementList = document.getElementById('announcementList');
            const announcementCounter = document.getElementById('announcementCounter');
            const markAllAnnouncementsRead = document.getElementById('markAllAnnouncementsRead');
            const isAuthenticated = @json(auth()->check());

            function getGuestAnnouncementsReadAt() {
                const raw = localStorage.getItem('announcementsReadAt');
                if (!raw) return null;
                const d = new Date(raw);
                return isNaN(d.getTime()) ? null : d;
            }

            function setGuestAnnouncementsReadAt(date) {
                localStorage.setItem('announcementsReadAt', date.toISOString());
            }

            // Toggle announcements dropdown
            announcementButton.addEventListener('click', function() {
                // Close notifications if open
                if (!notificationDropdown.classList.contains('hidden')) {
                    notificationDropdown.classList.add('hidden');
                }

                announcementDropdown.classList.toggle('hidden');
                loadAnnouncements();
            });

            // Close dropdowns when clicking outside
            document.addEventListener('click', function(event) {
                if (!announcementButton.contains(event.target) && !announcementDropdown.contains(event.target)) {
                    announcementDropdown.classList.add('hidden');
                }
            });

            function loadAnnouncements() {
                fetch('/announcements')
                    .then(response => response.json())
                    .then(announcements => {
                        announcementList.innerHTML = '';
                        let unreadCount = 0;
                        const guestReadAt = isAuthenticated ? null : getGuestAnnouncementsReadAt();

                        announcements.forEach(announcement => {
                            const announcementElement = createAnnouncementElement(announcement, guestReadAt);
                            announcementList.appendChild(announcementElement);

                            const isRead = isAuthenticated
                                ? Boolean(announcement.read)
                                : (guestReadAt ? (new Date(announcement.created_at) <= guestReadAt) : false);
                            if (!isRead) unreadCount++;
                        });

                        updateAnnouncementCounter(unreadCount);
                    });
            }

            function createAnnouncementElement(announcement, guestReadAt) {
                function announcementIconHtml(type) {
                    const t = String(type || '');
                    const map = {
                        update: { icon: 'fa-arrows-rotate', color: 'text-blue-600' },
                        meeting: { icon: 'fa-calendar-days', color: 'text-indigo-600' },
                        meeting_summary: { icon: 'fa-calendar-days', color: 'text-indigo-600' },
                        fix: { icon: 'fa-screwdriver-wrench', color: 'text-emerald-700' },
                        malfunction: { icon: 'fa-triangle-exclamation', color: 'text-red-600' },
                        info: { icon: 'fa-circle-info', color: 'text-slate-600' },
                    };

                    const def = map[t] || { icon: 'fa-bullhorn', color: 'text-gray-600' };
                    return `<i class="fa-solid ${def.icon} ${def.color}"></i>`;
                }

                const isRead = isAuthenticated
                    ? Boolean(announcement.read)
                    : (guestReadAt ? (new Date(announcement.created_at) <= guestReadAt) : false);

                const message = announcement.git_commit_message
                    ? `${announcement.message}\n\n${announcement.git_commit_message}`
                    : announcement.message;

                const div = document.createElement('div');
                div.className = `px-4 py-3 hover:bg-gray-50 transition-colors ${isRead ? 'bg-white' : 'bg-indigo-50'}`;

                const icon = announcementIconHtml(announcement.type);
                div.innerHTML = `
                <div class="flex items-start">
                    <div class="shrink-0 mr-3 mt-0.5 w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                        ${icon}
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">${announcement.title}</p>
                        <p class="text-sm text-gray-500 whitespace-pre-line">${message || ''}</p>
                        <p class="text-xs text-gray-400">${new Date(announcement.created_at).toLocaleString()}</p>
                    </div>
                    ${isRead ? '' : '<span class="ml-3 mt-2 h-2 w-2 rounded-full bg-indigo-600"></span>'}
                </div>
            `;

                return div;
            }

            function updateAnnouncementCounter(count) {
                if (count > 0) {
                    announcementCounter.textContent = count;
                    announcementCounter.classList.remove('hidden');
                } else {
                    announcementCounter.classList.add('hidden');
                }
            }

            markAllAnnouncementsRead.addEventListener('click', function() {
                if (!isAuthenticated) {
                    setGuestAnnouncementsReadAt(new Date());
                    loadAnnouncements();
                    return;
                }

                fetch('/announcements/mark-all-read', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            loadAnnouncements();
                        }
                    });
            });

            // Poll announcements every 60 seconds
            setInterval(loadAnnouncements, 60000);
            loadAnnouncements();

            // Toggle dropdown
            notificationButton.addEventListener('click', function() {
                // Close announcements if open
                if (!announcementDropdown.classList.contains('hidden')) {
                    announcementDropdown.classList.add('hidden');
                }
                notificationDropdown.classList.toggle('hidden');
                loadNotifications();
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                if (!notificationButton.contains(event.target) && !notificationDropdown.contains(event
                        .target)) {
                    notificationDropdown.classList.add('hidden');
                }
            });

            // Load notifications
            function loadNotifications() {
                fetch('/notifications')
                    .then(response => response.json())
                    .then(notifications => {
                        notificationList.innerHTML = '';
                        let unreadCount = 0;

                        notifications.forEach(notification => {
                            const notificationElement = createNotificationElement(notification);
                            notificationList.appendChild(notificationElement);
                            if (!notification.read) unreadCount++;
                        });

                        updateNotificationCounter(unreadCount);
                    });
            }

            // Create notification element
            function createNotificationElement(notification) {
                function notificationIconHtml(type) {
                    const t = String(type || '');

                    const map = {
                        project_invitation: { icon: 'fa-user-plus', color: 'text-indigo-600' },
                        experiment_created: { icon: 'fa-microscope', color: 'text-blue-600' },
                        protocols_created: { icon: 'fa-file-lines', color: 'text-slate-700' },
                        study_created: { icon: 'fa-book-open', color: 'text-indigo-600' },
                        literature_created: { icon: 'fa-book', color: 'text-purple-600' },

                        human_sample_created: { icon: 'fa-person', color: 'text-pink-600' },
                        animal_sample_created: { icon: 'fa-paw', color: 'text-yellow-600' },
                        environment_sample_created: { icon: 'fa-leaf', color: 'text-green-600' },
                        parasite_sample_created: { icon: 'fa-spider', color: 'text-purple-600' },
                        parasite_dissection_created: { icon: 'fa-scissors', color: 'text-purple-600' },
                        nucleic_acids_created: { icon: 'fa-dna', color: 'text-blue-600' },
                        microplastics_created: { icon: 'fa-recycle', color: 'text-sky-600' },
                        sequences_created: { icon: 'fa-code', color: 'text-blue-600' },
                        culture_created: { icon: 'fa-bacteria', color: 'text-orange-600' },
                        pool_created: { icon: 'fa-layer-group', color: 'text-cyan-600' },

                        tube_moved: { icon: 'fa-vials', color: 'text-indigo-500' },
                        field_sample_processed: { icon: 'fa-vial', color: 'text-orange-500' },
                        animal_sample_processed: { icon: 'fa-vial', color: 'text-orange-600' },
                        box_created: { icon: 'fa-box-archive', color: 'text-indigo-500' },
                        box_moved: { icon: 'fa-boxes-stacked', color: 'text-indigo-500' },

                        animal_created: { icon: 'fa-paw', color: 'text-amber-600' },
                        human_created: { icon: 'fa-person', color: 'text-rose-600' },
                        animal_health_created: { icon: 'fa-heart-pulse', color: 'text-red-600' },
                        animal_vaccination_created: { icon: 'fa-syringe', color: 'text-teal-600' },
                        animal_medication_created: { icon: 'fa-pills', color: 'text-violet-600' },
                        publication_review_submitted: { icon: 'fa-clipboard-check', color: 'text-indigo-600' },
                        publication_review_approved: { icon: 'fa-circle-check', color: 'text-green-600' },
                        publication_review_changes_requested: { icon: 'fa-pen-to-square', color: 'text-blue-600' },
                        publication_review_rejected: { icon: 'fa-circle-xmark', color: 'text-red-600' },
                    };

                    const def = map[t] || { icon: 'fa-bell', color: 'text-gray-500' };
                    return `<i class="fa-solid ${def.icon} ${def.color}"></i>`;
                }

                function notificationFallbackLink(type) {
                    const t = String(type || '');
                    const map = {
                        project_invitation: '/my-projects',
                        experiment_created: '/experiments/list',
                        human_sample_created: '/samples/humans/list',
                        animal_sample_created: '/samples/animals/list',
                        environment_sample_created: '/samples/environment/list',
                        parasite_sample_created: '/samples/parasites/list',
                        parasite_dissection_created: '/samples/parasites/list',
                        nucleic_acids_created: '/samples/nucleic/list',
                        microplastics_created: '/samples/microplastics/list',
                        sequences_created: '/samples/nucleic/sequences/list',
                        culture_created: '/samples/cultures/list',
                        pool_created: '/samples/pools/list',
                        tube_moved: '/bank/tubes/list',
                        field_sample_processed: '/bank/tubes/list',
                        animal_sample_processed: '/bank/tubes/list',
                        box_created: '/bank/boxes/list',
                        box_moved: '/bank/boxes/list',
                        animal_created: '/animals/list',
                        human_created: '/samples/humans/list',
                        publication_review_submitted: '/admin/publication-reviews',
                        publication_review_approved: '/publish',
                        publication_review_changes_requested: '/publish',
                        publication_review_rejected: '/publish',
                        animal_health_created: '/samples/animals/health/list',
                        animal_vaccination_created: '/samples/animals/vaccination/list',
                        animal_medication_created: '/samples/animals/medication/list',
                        literature_created: '/meta/list/animal',
                    };

                    return map[t] || null;
                }

                function notificationLink(notification) {
                    const mapped = notificationFallbackLink(notification.type);
                    return mapped || notification.link || null;
                }

                const div = document.createElement('div');
                div.className = `px-4 py-3 hover:bg-gray-50 transition-colors ${notification.read ? 'bg-white' : 'bg-blue-50'}`;

                const icon = notificationIconHtml(notification.type);
                const content = `
                <div class="flex items-start">
                    <div class="shrink-0 mr-3 mt-0.5 w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                        ${icon}
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">${notification.title}</p>
                        <p class="text-sm text-gray-500">${notification.message}</p>
                        <p class="text-xs text-gray-400">${new Date(notification.created_at).toLocaleString()}</p>
                    </div>
                    ${notification.read ? '' : '<span class="ml-3 mt-2 h-2 w-2 rounded-full bg-blue-600"></span>'}
                </div>
            `;

                div.innerHTML = content;

                const link = notificationLink(notification);
                if (link) {
                    div.addEventListener('click', () => {
                        // Mark as read before navigating
                        if (!notification.read) {
                            fetch(`/notifications/${notification.id}/mark-read`, {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector(
                                            'meta[name="csrf-token"]').content,
                                        'Content-Type': 'application/json'
                                    }
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        // Update the notification counter
                                        loadNotifications();
                                    }
                                });
                        }
                        window.location.href = link;
                    });
                    div.style.cursor = 'pointer';
                }

                return div;
            }

            // Update notification counter
            function updateNotificationCounter(count) {
                if (count > 0) {
                    notificationCounter.textContent = count;
                    notificationCounter.classList.remove('hidden');
                } else {
                    notificationCounter.classList.add('hidden');
                }
            }

            // Mark all as read
            markAllRead.addEventListener('click', function() {
                fetch('/notifications/mark-all-read', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            loadNotifications();
                        }
                    });
            });

            // Poll for new notifications every 30 seconds
            setInterval(loadNotifications, 30000);

            // Initial load
            loadNotifications();

            // Refresh notifications immediately after successful bulk imports.
            window.addEventListener('notification-created', function() {
                loadNotifications();
            });

            // Profile dropdown functionality
            const userMenuButton = document.getElementById('user-menu-button');
            const userMenuDropdown = document.getElementById('user-menu-dropdown');

            userMenuButton.addEventListener('click', function() {
                userMenuDropdown.classList.toggle('hidden');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                if (!userMenuButton.contains(event.target) && !userMenuDropdown.contains(event.target)) {
                    userMenuDropdown.classList.add('hidden');
                }
            });
        });
    </script>

    <script src="/js/dashboard-filter-autocomplete.js"></script>
    @stack('scripts')
    <script src="/js/dashboard-modal-tables.js?v={{ filemtime(public_path('js/dashboard-modal-tables.js')) }}"></script>
    <script src="/js/profile-tables.js?v={{ filemtime(public_path('js/profile-tables.js')) }}"></script>
</body>

</html>
