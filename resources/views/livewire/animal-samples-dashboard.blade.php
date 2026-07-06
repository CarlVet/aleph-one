<div>
    <!-- Guest Mode Banner -->
    @if ($isGuestMode)
        <div class="bg-gradient-to-r from-purple-100 to-blue-100 border border-purple-300 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-center space-x-3">
                <i class="fas fa-eye text-2xl text-purple-600"></i>
                <div>
                    <h3 class="text-lg font-semibold text-purple-800">Guest Mode Active</h3>
                    <p class="text-sm text-purple-600">You are viewing public animal samples from all projects</p>
                    <p class="text-xs text-purple-500 mt-1">Note: only processed samples that have a tube are shown (raw, unprocessed samples are not included).</p>
                </div>
                <a href="/my-projects" 
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-purple-700 bg-white border border-purple-300 rounded-lg hover:bg-purple-50 transition-colors duration-200">
                    <i class="fas fa-user-lock mr-2"></i>
                    Switch to Project Mode
                </a>
            </div>
        </div>
    @endif

    <!-- Create, Edit, Dashboard (Centered) -->
    <div class="relative flex justify-center items-center space-x-4 mt-2 mb-2">
        @if (!$isGuestMode)
        <!-- Left Arrow Home Link -->
        <a href="/samples/animals"
            class="absolute left-0 flex items-center text-gray-600 hover:text-blue-600 transition-colors duration-200 pl-2">
            <i class="fas fa-arrow-left text-2xl mr-2"></i>
            <span class="text-sm font-medium">Back to AS Home</span>
        </a>
        @if(!$canEdit)
        <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
            <i class="fas fa-lock mr-2"></i>
            Create (Viewer)
        </div>
        @else
            <a href="/samples/animals/create"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-green-600">
                <i class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                Create
            </a>
            @endif

            <a href="/samples/animals/list"
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
                <a href="/samples/animals/list"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                    <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                    List
                </a>
            </div>
        @endif
    </div>

    <!-- Header Section -->
    <div class="text-center mb-4 mt-4">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Animal Samples Dashboard</h1>
        <p class="text-gray-600">Overview of all animal samples and their distribution</p>
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
                Reset all filters
            </button>
        </div>
        <div x-show="open" x-collapse>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Date Range Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                <div class="flex space-x-2">
                    <input type="date" wire:model.live.debounce.300ms="startDate"
                        class="w-full max-w-[140px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                        placeholder="Start Date">
                    <input type="date" wire:model.live.debounce.300ms="endDate"
                        class="w-full max-w-[140px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                        placeholder="End Date">
                </div>
            </div>

            <!-- Visualization Mode -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Visualize counts by</label>
                <div class="flex flex-wrap gap-4 rounded-lg border border-gray-200 px-3 py-2">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="radio" class="text-blue-600 focus:ring-blue-500" wire:model.live="visualize_by" value="samples">
                        <span>Animal samples</span>
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="radio" class="text-blue-600 focus:ring-blue-500" wire:model.live="visualize_by" value="animals">
                        <span>Animals</span>
                    </label>
                </div>
            </div>

            @if (! $isGuestMode)
            <!-- Sample visibility -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Samples to visualize</label>
                <div class="flex flex-wrap gap-4 rounded-lg border border-gray-200 px-3 py-2">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="radio" class="text-blue-600 focus:ring-blue-500" wire:model.live="sampleVisibility" value="all">
                        <span>All registered</span>
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="radio" class="text-blue-600 focus:ring-blue-500" wire:model.live="sampleVisibility" value="processed_with_tubes">
                        <span>Processed + has tube</span>
                    </label>
                </div>
            </div>
            @endif

            <!-- Animal Species Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Animal Species</label>
                <select wire:model.live.debounce.300ms="animal_species_filter" data-dashboard-placeholder-current="true"
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                    <option value="All">All Species</option>
                    @foreach ($allAnimalSpecies as $species)
                        <option value="{{ $species }}">{{ $species }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Sample Type Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sample Type</label>
                <select wire:model.live.debounce.300ms="sample_type_filter" data-dashboard-placeholder-current="true"
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                    <option value="All">All Types</option>
                    @foreach ($allSampleTypes as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Sampling Site Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sampling Site</label>
                <select wire:model.live.debounce.300ms="sampling_site_filter" data-dashboard-placeholder-current="true"
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                    <option value="All">All Sites</option>
                    @foreach ($allSamplingSites as $site)
                        <option value="{{ $site }}">{{ $site }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sub-project</label>
                <select wire:model.live.debounce.300ms="subProjectFilter" data-dashboard-placeholder-current="true"
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                    <option value="All">All sub-projects</option>
                    @foreach ($allSubProjects as $subProjectCode)
                        <option value="{{ $subProjectCode }}">{{ $subProjectCode }}</option>
                    @endforeach
                </select>
            </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
        <!-- Total Samples -->
        <div
            class="stat-card bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('samplesModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Samples</p>
                    <p class="text-3xl font-bold text-purple-600">{{ $descriptive_stats['total_samples'] }}</p>
                </div>
                <div class="p-3 bg-purple-100 rounded-full">
                    <i class="fas fa-flask text-2xl text-purple-600"></i>
                </div>
            </div>
        </div>

        <!-- Total Animals -->
        <div
            class="stat-card bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('animalsModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Animals</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $descriptive_stats['unique_animals'] }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <i class="fas fa-paw text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>

        <!-- Animal Species -->
        <div
            class="stat-card bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('speciesModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Animal Species</p>
                    <p class="text-3xl font-bold text-green-600">{{ $descriptive_stats['unique_species'] }}</p>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <i class="fas fa-hippo text-2xl text-green-600"></i>
                </div>
            </div>
        </div>

        <!-- Sampling Sites -->
        <div
            class="stat-card bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('sitesModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Sampling Sites</p>
                    <p class="text-3xl font-bold text-purple-600">{{ $descriptive_stats['unique_sampling_sites'] }}</p>
                </div>
                <div class="p-3 bg-purple-100 rounded-full">
                    <i class="fas fa-map-marker-alt text-2xl text-purple-600"></i>
                </div>
            </div>
        </div>

        <!-- Sample Types -->
        <div
            class="stat-card bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('typesModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Sample Types</p>
                    <p class="text-3xl font-bold text-indigo-600">{{ $descriptive_stats['unique_sample_types'] }}</p>
                </div>
                <div class="p-3 bg-indigo-100 rounded-full">
                    <i class="fas fa-lungs text-2xl text-indigo-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabbed Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8" wire:ignore>
        <div id="pieBox" class="dashboard-box relative bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <div id="pieContent" data-expand-content-root data-expand-title="Animal distribution">
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach (($pieChartTabs ?? []) as $tab)
                        <button type="button"
                            data-pie-tab="{{ $tab['key'] }}"
                            class="chart-tab-btn px-3 py-1.5 text-xs rounded-full border border-gray-200 text-gray-600 hover:text-indigo-700 hover:border-indigo-200">
                            {{ $tab['label'] }}
                        </button>
                    @endforeach
                </div>
                <div class="relative h-72">
                    <div class="dashboard-box-tools">
                        <button type="button" data-expand-target="pieContent"
                            class="icon-btn p-2 text-xs rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50"
                            title="Expand">
                            <i class="fas fa-expand"></i>
                        </button>
                        <button type="button" data-format-target="pie" data-format="png"
                            class="icon-btn format-btn p-2 text-xs rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50"
                            title="Format: png (click to change)">
                            <i class="fas fa-file-export"></i>
                        </button>
                        <button type="button" data-download-target="pie"
                            class="icon-btn p-2 text-xs rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50"
                            title="Download">
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                    <canvas id="pieTabbedChart"></canvas>
                </div>
                <div id="pieLegendScroller" class="mt-3"></div>
            </div>
        </div>

        <div id="barBox" class="dashboard-box relative bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <div id="barContent" data-expand-content-root data-expand-title="Animal counts">
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach (($barChartTabs ?? []) as $tab)
                        <button type="button"
                            data-bar-tab="{{ $tab['key'] }}"
                            class="chart-tab-btn px-3 py-1.5 text-xs rounded-full border border-gray-200 text-gray-600 hover:text-cyan-700 hover:border-cyan-200">
                            {{ $tab['label'] }}
                        </button>
                    @endforeach
                </div>
                <div class="relative h-72">
                    <div class="dashboard-box-tools">
                        <button type="button" data-expand-target="barContent"
                            class="icon-btn p-2 text-xs rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50"
                            title="Expand">
                            <i class="fas fa-expand"></i>
                        </button>
                        <button type="button" data-format-target="bar" data-format="png"
                            class="icon-btn format-btn p-2 text-xs rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50"
                            title="Format: png (click to change)">
                            <i class="fas fa-file-export"></i>
                        </button>
                        <button type="button" data-download-target="bar"
                            class="icon-btn p-2 text-xs rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50"
                            title="Download">
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                    <canvas id="barTabbedChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Map Section -->
    <div id="mapBox" class="dashboard-box bg-white rounded-xl shadow-lg p-6 border border-gray-100 mb-8">
        <div id="mapContent" data-expand-content-root data-expand-title="Animal distribution map">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                <h3 class="map-inline-title text-lg font-semibold text-gray-800">
                    {{ ($activeFilters['visualize_by'] ?? 'samples') === 'animals' ? 'Animals' : 'Animal samples' }} Location Map
                </h3>
                <div class="flex flex-wrap gap-2">
                    @foreach (($mapColorVariableOptions ?? []) as $option)
                        <button type="button"
                            data-map-tab="{{ $option['key'] }}"
                            class="map-tab-btn px-3 py-1.5 text-xs rounded-full border border-gray-200 text-gray-600 hover:text-indigo-700 hover:border-indigo-200">
                            {{ $option['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="relative h-96">
                <div class="dashboard-box-tools">
                    <button type="button" data-expand-target="mapContent"
                        class="icon-btn p-2 text-xs rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50"
                        title="Expand">
                        <i class="fas fa-expand"></i>
                    </button>
                    <button type="button" data-format-target="map" data-format="png"
                        class="icon-btn format-btn p-2 text-xs rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50"
                        title="Format: png (click to change)">
                        <i class="fas fa-file-export"></i>
                    </button>
                    <button type="button" data-download-target="map"
                        class="icon-btn p-2 text-xs rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50"
                        title="Download">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
                <div id="map" class="w-full h-full rounded-lg" wire:ignore></div>
                <div id="mapLegend"
                    class="absolute inset-x-3 bottom-3 z-[450] ml-auto max-w-md rounded-xl border border-gray-200 bg-white/95 px-3 py-2 shadow-lg backdrop-blur-sm">
                </div>
            </div>
        </div>
    </div>

    <div id="dashboardExpandModal" class="fixed inset-0 z-[70] hidden">
        <div class="absolute inset-0 bg-slate-950/70" data-expand-close></div>
        <div class="relative mx-auto flex h-full w-full max-w-7xl items-center justify-center p-4 sm:p-6">
            <div class="dashboard-expand-surface flex h-full w-full flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h3 id="dashboardExpandTitle" class="text-lg font-semibold text-slate-900">Expanded chart</h3>
                    <button type="button" data-expand-close
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="dashboardExpandBody" class="dashboard-expand-body min-h-0 flex-1 overflow-auto p-5"></div>
            </div>
        </div>
    </div>

    

    <!-- Processing Status and Timeline Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Sample Processing Overview</h3>
        
        <!-- Processing Status -->
        <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
                <h4 class="text-md font-semibold text-gray-700">Processing Progress</h4>
                <span class="text-sm text-gray-500">{{ $descriptive_stats['processed_samples'] }} of {{ $descriptive_stats['total_samples'] }} samples processed</span>
            </div>
            
            <div class="relative">
                <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                    <div class="processing-progress h-4 rounded-full transition-all duration-1000 ease-out flex items-center justify-center"
                         style="width: {{ $descriptive_stats['processing_rate'] }}%">
                        <span class="text-white text-xs font-medium">{{ $descriptive_stats['processing_rate'] }}%</span>
                    </div>
                </div>
                
                <!-- Processing indicators -->
                <div class="flex justify-between mt-2 text-xs text-gray-500">
                    <span>Pending: {{ $descriptive_stats['pending_samples'] }}</span>
                    <span>Processed: {{ $descriptive_stats['processed_samples'] }}</span>
                </div>
            </div>
        </div>

        <!-- Collection Timeline Chart -->
        <div>
            
            <h4 class="text-md font-semibold text-gray-700 mb-2">Sample Collection Timeline</h4>
            <div class="flex flex-wrap gap-4 px-3 py-2">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="radio" class="text-blue-600 focus:ring-blue-500" wire:model.live="timelineGranularity" value="monthly">
                    <span>Monthly</span>
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="radio" class="text-blue-600 focus:ring-blue-500" wire:model.live="timelineGranularity" value="yearly">
                    <span>Yearly</span>
                </label>
            </div>
            <div class="timeline-chart-container">
                <canvas id="timelineChart" class="w-full h-48" wire:ignore></canvas>
            </div>
        </div>
    </div>

    <!-- Modal for Animals -->
    <div id="animalsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Animals in Filtered Dataset</h3>
                <button onclick="closeModal('animalsModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="animalsModalContent" data-modal-content class="text-sm text-gray-500">Loading…</div>
        </div>
    </div>

    <!-- Modal for Species -->
    <div id="speciesModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Species in Filtered Dataset</h3>
                <button onclick="closeModal('speciesModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="speciesModalContent" data-modal-content class="text-sm text-gray-500">Loading…</div>
        </div>
    </div>

    <!-- Modal for Samples -->
    <div id="samplesModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-6xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Samples in Filtered Dataset</h3>
                <button onclick="closeModal('samplesModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="samplesModalContent" data-modal-content class="text-sm text-gray-500">Loading…</div>
        </div>
    </div>

    <!-- Modal for Sampling Sites -->
    <div id="sitesModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Sampling Sites in Filtered Dataset</h3>
                <button onclick="closeModal('sitesModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="sitesModalContent" data-modal-content class="text-sm text-gray-500">Loading…</div>
        </div>
    </div>

    <!-- Modal for Sample Types -->
    <div id="typesModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Sample Types in Filtered Dataset</h3>
                <button onclick="closeModal('typesModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="typesModalContent" data-modal-content class="text-sm text-gray-500">Loading…</div>
        </div>
    </div>

    <script>
        const timelineData = @json($descriptive_stats['collection_timeline']);
        const pieChartTabs = @json($pieChartTabs ?? []);
        const barChartTabs = @json($barChartTabs ?? []);
        const mapColorVariableOptions = @json($mapColorVariableOptions ?? []);

        window.timelineData = timelineData;
        window.pieChartTabs = pieChartTabs;
        window.barChartTabs = barChartTabs;
        window.mapColorVariableOptions = mapColorVariableOptions;
        window.mapPointsUrl = @json($mapPointsUrl);
        window.activeFilters = @json($activeFilters);
        window.modalTableUrls = @json($modalTableUrls);

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
            document.getElementById(modalId).classList.add('flex');
            window.loadAnimalDashboardModal?.(modalId);
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('flex');
            document.getElementById(modalId).classList.add('hidden');
        }

        // Close modal when clicking outside
        document.addEventListener('DOMContentLoaded', function() {
            const modals = ['animalsModal', 'speciesModal', 'samplesModal', 'processingModal', 'sitesModal', 'typesModal'];
            
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
        <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
        <script src="/js/show-animal-samples.js?v={{ filemtime(public_path('js/show-animal-samples.js')) }}"></script>
    @endpush

    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

    <style>
        .chart-tab-btn.is-active {
            border-color: #6366f1;
            color: #4338ca;
            background: rgba(99, 102, 241, 0.12);
            box-shadow: 0 8px 20px -10px rgba(79, 70, 229, 0.65);
        }

        .map-tab-btn.is-active {
            border-color: #334155;
            color: #0f172a;
            background: rgba(15, 23, 42, 0.08);
            box-shadow: 0 8px 20px -12px rgba(15, 23, 42, 0.75);
        }

        .dashboard-box-tools {
            position: absolute;
            top: 0.6rem;
            right: 0.6rem;
            display: inline-flex;
            gap: 0.4rem;
            z-index: 5;
        }

        body.dashboard-box-open {
            overflow: hidden;
        }

        .dashboard-expand-surface [data-expand-content-root] {
            min-height: 100%;
        }

        .dashboard-expand-body .map-inline-title {
            display: none;
        }

        .dashboard-expand-body .dashboard-box-tools [data-expand-target] {
            display: none;
        }

        .dashboard-expand-surface .h-72 {
            height: min(70vh, 42rem);
        }

        .dashboard-expand-surface .h-96 {
            height: min(72vh, 46rem);
        }

        #mapLegend {
            max-height: 8rem;
            overflow: auto;
        }

        .invisible-marker {
            background: transparent !important;
            border: none !important;
        }

        .pie-chart-cluster-icon {
            background: transparent !important;
            border: none !important;
        }

        .pie-chart-cluster-icon svg {
            filter: drop-shadow(2px 2px 4px rgba(0, 0, 0, 0.3));
        }
    </style>

</div>
