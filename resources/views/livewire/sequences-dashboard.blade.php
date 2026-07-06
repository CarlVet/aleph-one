<div>
    <!-- Guest Mode Banner -->
    @if ($isGuestMode)
        <div class="bg-gradient-to-r from-purple-100 to-blue-100 border border-purple-300 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-center space-x-3">
                <i class="fas fa-eye text-2xl text-purple-600"></i>
                <div>
                    <h3 class="text-lg font-semibold text-purple-800">Guest Mode Active</h3>
                    <p class="text-sm text-purple-600">You are viewing public sequences from all projects</p>
                </div>
                <a href="/my-projects"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-purple-700 bg-white border border-purple-300 rounded-lg hover:bg-purple-50 transition-colors duration-200">
                    <i class="fas fa-user-lock mr-2"></i>
                    Switch to Project Mode
                </a>
            </div>
        </div>
    @endif

    <!-- Create, List (Centered) -->
    <div class="relative flex justify-center items-center space-x-4 mt-2 mb-4">
        @if (! $isGuestMode)
            <!-- Left Arrow Home Link -->
            <a href="/samples/nucleic/sequences"
                class="absolute left-0 flex items-center text-gray-600 hover:text-blue-600 transition-colors duration-200 pl-2">
                <i class="fas fa-arrow-left text-2xl mr-2"></i>
                <span class="text-sm font-medium">Back to Sequences Home</span>
            </a>

            <a href="/samples/nucleic/sequences/create"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-green-600">
                <i class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                Create
            </a>

            <a href="/samples/nucleic/sequences/list"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                List
            </a>
        @else
            <div class="flex items-center space-x-4">
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Create (Project Mode)
                </div>
                <a href="/samples/nucleic/sequences/list"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                    <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                    List
                </a>
            </div>
        @endif
    </div>

    <!-- Header Section -->
    <div class="text-center mb-4">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Sequences Dashboard</h1>
        <p class="text-gray-600">Overview of all sequencing events and their origins</p>
    </div>

    <!-- Filters Section -->
    <div data-dashboard-filter-root class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 mb-8" x-data="{ open: true }">
        <div class="flex items-center justify-between mb-4">
            <button type="button"
                class="inline-flex items-center gap-2 text-lg font-semibold text-gray-800 hover:text-gray-900"
                x-on:click="open = !open"
                x-bind:aria-expanded="open.toString()">
                <i class="fas fa-chevron-down text-sm transition-transform duration-200" x-bind:class="open ? 'rotate-180' : ''"></i>
                <span>Filters</span>
            </button>
            <button type="button"
                wire:click="resetFilters"
                class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700 hover:text-slate-900 px-3 py-2 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 transition-colors"
                x-show="open">
                <i class="fas fa-rotate-left text-xs"></i>
                Reset
            </button>
        </div>
        <div x-show="open" x-collapse>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Source Type Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Origin Type</label>
                <select wire:model.live.debounce.300ms="sourceTypeFilter"
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                    <option value="all">All Origins</option>
                    <option value="human">Human</option>
                    <option value="animal">Animal</option>
                    <option value="environment">Environment</option>
                    <option value="parasite">Parasite</option>
                    <option value="culture">Culture</option>
                    <option value="pool">Pool</option>
                </select>
            </div>

            <!-- Method Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Method</label>
                <select wire:model.live.debounce.300ms="methodFilter"
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                    <option value="">All Methods</option>
                    @foreach ($allMethods as $method)
                        <option value="{{ $method }}">{{ $method }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Instrument Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Instrument</label>
                <select wire:model.live.debounce.300ms="instrumentFilter"
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                    <option value="">All Instruments</option>
                    @foreach ($allInstruments as $instrument)
                        <option value="{{ $instrument }}">{{ $instrument }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Laboratory Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Laboratory</label>
                <select wire:model.live.debounce.300ms="laboratoryFilter"
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                    <option value="">All Laboratories</option>
                    @foreach ($allLaboratories as $laboratory)
                        <option value="{{ $laboratory }}">{{ $laboratory }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sub-project</label>
                <select wire:model.live.debounce.300ms="subProjectFilter"
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                    <option value="">All sub-projects</option>
                    @foreach ($allSubProjects as $subProjectCode)
                        <option value="{{ $subProjectCode }}">{{ $subProjectCode }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Sequenced By Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sequenced By</label>
                <select wire:model.live.debounce.300ms="sequencedByFilter"
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                    <option value="">All People</option>
                    @foreach ($allPeople as $person)
                        <option value="{{ $person }}">{{ $person }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Sequencing Date Range Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sequencing Date Range</label>
                <div class="flex space-x-2">
                    <input type="date" wire:model.live.debounce.300ms="startDate"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                        placeholder="Start Date">
                    <input type="date" wire:model.live.debounce.300ms="endDate"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                        placeholder="End Date">
                </div>
            </div>

            <!-- Timeline Granularity -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Timeline</label>
                <div class="flex flex-wrap gap-4 rounded-lg border border-gray-200 px-3 py-2">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="radio" class="text-blue-600 focus:ring-blue-500" wire:model.live="timelineGranularity" value="monthly">
                        <span>Monthly</span>
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="radio" class="text-blue-600 focus:ring-blue-500" wire:model.live="timelineGranularity" value="yearly">
                        <span>Yearly</span>
                    </label>
                </div>
            </div>

            <!-- Length Range Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Length Range (nt)</label>
                <div class="flex space-x-2">
                    <input type="number" wire:model.live.debounce.300ms="startLength"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                        placeholder="Min">
                    <input type="number" wire:model.live.debounce.300ms="endLength"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                        placeholder="Max">
                </div>
            </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-6 mb-8">
        <!-- Total -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('sequencesModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $descriptive_stats['total_samples'] }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <i class="fas fa-dna text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>

        <!-- Human -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('humanSamplesModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Human</p>
                    <p class="text-3xl font-bold text-red-600">{{ $descriptive_stats['human_samples'] }}</p>
                </div>
                <div class="p-3 bg-red-100 rounded-full">
                    <i class="fas fa-user text-2xl text-red-600"></i>
                </div>
            </div>
        </div>

        <!-- Animal -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('animalSamplesModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Animal</p>
                    <p class="text-3xl font-bold text-green-600">{{ $descriptive_stats['animal_samples'] }}</p>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <i class="fas fa-paw text-2xl text-green-600"></i>
                </div>
            </div>
        </div>

        <!-- Environment -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('environmentSamplesModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Environment</p>
                    <p class="text-3xl font-bold text-purple-600">{{ $descriptive_stats['environment_samples'] }}</p>
                </div>
                <div class="p-3 bg-purple-100 rounded-full">
                    <i class="fas fa-leaf text-2xl text-purple-600"></i>
                </div>
            </div>
        </div>

        <!-- Culture -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('cultureSamplesModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Cultures</p>
                    <p class="text-3xl font-bold text-orange-600">{{ $descriptive_stats['culture_samples'] }}</p>
                </div>
                <div class="p-3 bg-orange-100 rounded-full">
                    <i class="fas fa-flask text-2xl text-orange-600"></i>
                </div>
            </div>
        </div>

        <!-- Pool -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('poolSamplesModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Pools</p>
                    <p class="text-3xl font-bold text-indigo-600">{{ $descriptive_stats['pool_samples'] }}</p>
                </div>
                <div class="p-3 bg-indigo-100 rounded-full">
                    <i class="fas fa-layer-group text-2xl text-indigo-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Sequences by Origin</h3>
            <div class="h-64">
                <canvas id="sourceChart"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Top Experiment Methods</h3>
            <div class="h-64">
                <canvas id="methodsChart"></canvas>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Top Instruments</h3>
            <div class="h-64">
                <canvas id="instrumentsChart"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Top Laboratories</h3>
            <div class="h-64">
                <canvas id="laboratoriesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Map Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 mb-8">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Sequences Distribution (by origin)</h3>
        <div class="h-96">
            <div id="map" class="w-full h-full rounded-lg"></div>
        </div>
        <div class="mt-4 flex items-center justify-center space-x-4">
            <div class="flex items-center">
                <div class="w-4 h-4 bg-red-800 rounded-full mr-2"></div>
                <span class="text-sm text-gray-600">Human</span>
            </div>
            <div class="flex items-center">
                <div class="w-4 h-4 bg-green-600 rounded-full mr-2"></div>
                <span class="text-sm text-gray-600">Animal</span>
            </div>
            <div class="flex items-center">
                <div class="w-4 h-4 bg-purple-600 rounded-full mr-2"></div>
                <span class="text-sm text-gray-600">Environment</span>
            </div>
            <div class="flex items-center">
                <div class="w-4 h-4 bg-orange-600 rounded-full mr-2"></div>
                <span class="text-sm text-gray-600">Parasite</span>
            </div>
            <div class="flex items-center">
                <div class="w-4 h-4 bg-yellow-600 rounded-full mr-2"></div>
                <span class="text-sm text-gray-600">Culture</span>
            </div>
            <div class="flex items-center">
                <div class="w-4 h-4 bg-indigo-600 rounded-full mr-2"></div>
                <span class="text-sm text-gray-600">Pool</span>
            </div>
            <div class="flex items-center ml-4">
                <svg width="25" height="25" viewBox="0 0 50 50" class="mr-2">
                    <circle cx="25" cy="25" r="20" fill="none" stroke="#666" stroke-width="2" />
                    <text x="25" y="30" text-anchor="middle" font-size="10" font-weight="bold" fill="#666">5</text>
                </svg>
                <span class="text-sm text-gray-600">Pie Chart = Multiple sequences at same location</span>
            </div>
        </div>
    </div>

    <!-- Sequencing Timeline -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 mb-4">
        <div>
            <h4 class="text-md font-semibold text-gray-700 mb-4">Sequencing Timeline (Last 12 Months)</h4>
            <div class="timeline-chart-container">
                <canvas id="timelineChart" class="w-full h-48"></canvas>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div id="sequencesModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-6xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">All Sequences in Filtered Dataset</h3>
                <button onclick="closeModal('sequencesModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="sequencesModalContent" data-modal-content class="text-sm text-gray-500">Loading…</div>
        </div>
    </div>

    <div id="humanSamplesModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-6xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Human-origin Sequences</h3>
                <button onclick="closeModal('humanSamplesModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="humanSamplesModalContent" data-modal-content class="text-sm text-gray-500">Loading…</div>
        </div>
    </div>

    <div id="animalSamplesModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-6xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Animal-origin Sequences</h3>
                <button onclick="closeModal('animalSamplesModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="animalSamplesModalContent" data-modal-content class="text-sm text-gray-500">Loading…</div>
        </div>
    </div>

    <div id="environmentSamplesModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-6xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Environment-origin Sequences</h3>
                <button onclick="closeModal('environmentSamplesModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="environmentSamplesModalContent" data-modal-content class="text-sm text-gray-500">Loading…</div>
        </div>
    </div>

    <div id="cultureSamplesModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-6xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Culture-origin Sequences</h3>
                <button onclick="closeModal('cultureSamplesModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="cultureSamplesModalContent" data-modal-content class="text-sm text-gray-500">Loading…</div>
        </div>
    </div>

    <div id="poolSamplesModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-6xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Pool-origin Sequences</h3>
                <button onclick="closeModal('poolSamplesModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="poolSamplesModalContent" data-modal-content class="text-sm text-gray-500">Loading…</div>
        </div>
    </div>

    <script>
        const timelineData = @json($descriptive_stats['sequencing_timeline']);
        const sequencesBySource = @json($sequencesBySource);
        const sequencesByMethod = @json($sequencesByMethod);
        const sequencesByInstrument = @json($sequencesByInstrument);
        const sequencesByLaboratory = @json($sequencesByLaboratory);

        window.timelineData = timelineData;
        window.sequencesBySource = sequencesBySource;
        window.sequencesByMethod = sequencesByMethod;
        window.sequencesByInstrument = sequencesByInstrument;
        window.sequencesByLaboratory = sequencesByLaboratory;
        window.mapPointsUrl = @json($mapPointsUrl);
        window.activeFilters = @json($activeFilters);
        window.modalTableUrls = @json($modalTableUrls);

        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
            document.getElementById(modalId).classList.add('flex');
            window.loadSequencesDashboardModal?.(modalId);
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('flex');
            document.getElementById(modalId).classList.add('hidden');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const modals = ['sequencesModal', 'humanSamplesModal', 'animalSamplesModal', 'environmentSamplesModal', 'cultureSamplesModal', 'poolSamplesModal'];

            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.addEventListener('click', function(e) {
                        if (e.target === modal) {
                            closeModal(modalId);
                        }
                    });
                }
            });
        });
    </script>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />
        <script src="/js/show-sequences.js?v={{ filemtime(public_path('js/show-sequences.js')) }}"></script>
    @endpush

    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

    <style>
        .pie-chart-cluster-icon {
            background: transparent !important;
            border: none !important;
        }

        .pie-chart-cluster-icon svg {
            filter: drop-shadow(2px 2px 4px rgba(0, 0, 0, 0.3));
        }

        .invisible-marker {
            background: transparent !important;
            border: none !important;
        }

        .leaflet-tooltip {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #ccc;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
    </style>
</div>

